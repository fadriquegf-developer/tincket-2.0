<?php

namespace App\Http\Controllers\ApiValidation;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiValidation\EventResource;
use App\Http\Resources\ApiValidation\InscriptionResource;
use App\Http\Resources\ApiValidation\SessionResource;
use App\Models\SessionAccessLog;
use App\Models\SyncValidation;
use App\Models\Inscription;
use App\Models\Event;
use App\Models\Session;

class ValidationController extends Controller
{
    public function getEvents(Request $request)
    {
        $events = $this->allBuilder()->get();

        return response()->json(EventResource::collection($events));
    }

    /**
     * Download all info event for offline validation
     */
    public function downloadEvent(Request $request, $id)
    {
        $event = $this->allBuilder()->where('id', $id)->with('sessions_no_finished.inscriptions')->firstOrFail();
        $sessions = $event->sessions_no_finished()->with('space.location')->get();

        $inscriptions = Inscription::paid()
            ->whereIn('session_id', $sessions->pluck('id'))
            ->with(['rate', 'group_pack.pack'])
            ->get();


        // group by session
        $inscriptions = $inscriptions->groupBy('session_id');
        // filter inscription attributes
        $inscriptions = $inscriptions->map(function ($sessions) {
            return $sessions->map(function ($inscription) {
                return new InscriptionResource($inscription);
            });
        });

        $data = [
            'event' => new EventResource($event),
            'inscriptions' => $inscriptions,
            'time' => Carbon::now()->toISOString()
        ];

        return response()->json($data);
    }

    /**
     * Sync with inscriptions vlaidated offline
     */
    public function syncEvent(Request $request, $id)
    {
        // Buscar el evento y sesiones activas
        $event = Event::authorizedBrands()->where('id', $id)->with('sessions')->firstOrFail();
        $sessions = $event->sessions->pluck('id');
        $timezone = config('app.timezone');

        $syncInscriptions = [];

        // Crear un log del proceso de sincronización
        $log = SyncValidation::create([
            'user_id' => auth()->user()->id,
            'event_id' => $id,
            'request' => json_encode($request->all()),
        ]);

        // Iterar todas las inscripciones enviadas en la solicitud
        foreach ($request->all() as $sessionId => $inscriptions) {
            $syncInscriptions[$sessionId] = [];
            foreach ($inscriptions as $remoteInscription) {
                $updated = false;

                if ($sessions->contains($sessionId)) {
                    // Buscar la inscripción en la base de datos
                    $inscription = Inscription::where('barcode', $remoteInscription['barcode'])
                        ->where('id', $remoteInscription['id'])
                        ->where('session_id', $sessionId)
                        ->first();

                    if ($inscription) {
                        // Obtener la fecha de validación si existe
                        $checked_at = isset($remoteInscription['checked_at']) ? Carbon::parse($remoteInscription['checked_at'])->setTimezone($timezone) : Carbon::now();

                        // Verificar si validate_all_session está activado
                        $session = Session::find($sessionId);
                        if ($session && $session->validate_all_session) {
                            // Validar todas las inscripciones del carrito para la misma sesión
                            $cartId = $inscription->cart_id;
                            $inscriptionsToValidate = Inscription::where('cart_id', $cartId)
                                ->where('session_id', $sessionId)
                                ->get();

                            foreach ($inscriptionsToValidate as $insc) {
                                $insc->checked_at = $checked_at;
                                $insc->save();
                            }

                            $updated = true;
                            $remoteInscription['group_validated'] = true;
                        } else {
                            // Validación individual si validate_all_session está desactivado
                            if ($checked_at) {
                                $inscription->checked_at = $checked_at;
                                $inscription->out_event = $remoteInscription['out_event'];
                                $updated = true;
                            }

                            if ($updated) {
                                $inscription->save();
                            } else {
                                // Marcar errores si no hubo actualización
                                if ($inscription->out_event === 1) {
                                    $remoteInscription['error'] = 'already_out';
                                } elseif ($inscription->checked_at !== null) {
                                    $remoteInscription['error'] = 'already_scanned';
                                }
                            }
                        }
                    } else {
                        // Inscripción no encontrada
                        $remoteInscription['error'] = 'not_found';
                    }
                } else {
                    $remoteInscription['error'] = 'not_found';
                }

                $remoteInscription['sync'] = $updated;
                $syncInscriptions[$sessionId][] = $remoteInscription;
            }
        }

        // Guardar el log de la respuesta
        $data = [$syncInscriptions];
        $log->response = json_encode($data);
        $log->save();

        return response()->json($data);
    }

    /**
     * Sync with inscriptions vlaidated offline
     */
    public function syncEventLogs(Request $request, $id)
    {
        // Buscar el evento y sesiones activas
        $event = Event::authorizedBrands()->where('id', $id)->with('sessions')->firstOrFail();
        $sessions = $event->sessions->pluck('id');
        $timezone = config('app.timezone');

        // Iterar todas las inscripciones enviadas en la solicitud
        foreach ($request->all() as $sessionId => $inscriptions) {
            foreach ($inscriptions as $remoteLog) {
                if ($sessions->contains($sessionId)) {
                    SessionAccessLog::create([
                        'session_id' => $sessionId,
                        'inscription_id' => $remoteLog['inscription_id'] ?? null,
                        'origin' => 'offline',
                        'out_event' => $remoteLog['out_event'] ?? null,
                        'user_id' => auth()->user()->id,
                        'created_at' => Carbon::parse($remoteLog['created_at'])->setTimezone($timezone),
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function getSessions(Request $request, $id)
    {
        $event = $this->allBuilder()->where('id', $id)->firstOrFail();

        $sessions = $event->sessions_no_finished()
            ->with([
                'space.location',
                // carga sólo las inscripciones pagadas
                'inscriptions' => function ($q) {
                    $q->paid();
                },
            ])
            ->orderBy('starts_on', 'ASC')
            ->get();

        return response()->json(SessionResource::collection($sessions));
    }

    /**
     * Al escanear un código de barras, este método comprueba la validez de la inscripción 
     * asociada respetando la configuración de validación a dos niveles:
     *
     *  1) A nivel de evento (validate_all_event):
     *     - Si está activo, se admiten inscripciones de cualquier sesión que pertenezca 
     *       al mismo evento de la sesión recibida por parámetro ($id).
     *     - Si está desactivado, solo se aceptan inscripciones de la sesión concreta ($id).
     *
     *  2) A nivel de sesión (validate_all_session de cada sesión):
     *     - Si está activo para la sesión de la inscripción encontrada, se validan todas 
     *       las inscripciones de ese carrito para dicha sesión.
     *     - Si está inactivo, se valida únicamente la inscripción escaneada.
     *
     * El método retorna un JSON con:
     *  - success: Indica si la validación fue exitosa.
     *  - inscription: Los datos de la inscripción validada (en caso de existir).
     *  - message: Mensaje explicando el resultado de la validación (ej.: ya validada, 
     *             sesión incorrecta, etc.).
     *  - total / n_validated: Cantidad total de inscripciones en la sesión $id y cuántas 
     *                        están validadas actualmente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  El ID de la sesión solicitada
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request, $id)
    {
        // 1. Obtener la sesión solicitada y verificar que la marca esté autorizada
        $session = Session::whereHas('event', function ($q) {
            $q->authorizedBrands();
        })->findOrFail($id);

        // 2. Verificar si el EVENTO tiene o no 'validate_all_event'
        $event = $session->event;
        $validateAllEvent = $event->validate_all_event;  // <-- Campo a nivel de Event
        $barcode = $request->get('barcode');

        // 3. Buscar la inscripción según la configuración del evento
        if ($validateAllEvent) {
            // Si el evento permite validar inscripciones de cualquier sesión
            $inscription = Inscription::where('barcode', $barcode)
                ->with(['rate', 'group_pack.pack', 'cart'])
                ->whereHas('session', function ($q) use ($event) {
                    $q->where('event_id', $event->id);
                })
                ->first();
        } else {
            // De lo contrario, solo la buscamos en esta sesión concreta
            $inscription = Inscription::where('barcode', $barcode)
                ->with(['rate', 'group_pack.pack', 'cart'])
                ->where('session_id', $id)
                ->first();
        }

        // 4. Mensaje por defecto
        $message = trans('backend.validation.errors.not_found');

        // 5. Lógica de validación
        //    - Si no existe la inscripción, se mantiene el mensaje por defecto.
        //    - Si existe, comprobamos si ya está validada o no, si está en la sesión,
        //      etc. Ojo que, si $validateAllEvent es true, podríamos no forzar
        //      que coincida con la sesión pedida en el $id.
        $success = false;

        if ($inscription) {
            $success = (!$inscription->checked_at || ($inscription->checked_at && $inscription->out_event));

            if ($success) {
                // Ver si la sesión en la que está la inscripción tiene 'validate_all_session'
                $sessionOfInscription = $inscription->session;
                $validateAllSession   = $sessionOfInscription->validate_all_session;
                $message = trans('backend.validation.success.validated') . $inscription->apiTextRateName();

                if ($validateAllSession) {
                    // Validar todas las inscripciones del carrito en *esa* sesión
                    $inscriptions = Inscription::where('cart_id', $inscription->cart->id)
                        ->where('session_id', $sessionOfInscription->id)
                        ->update(['checked_at' => \Carbon\Carbon::now(), 'out_event' => 0]);

                    // Afegir N entrades validades
                    $message .= '<br><b>' . trans('backend.validation.success.validated_grup') . '</b>: ' . $inscriptions;
                } else {
                    // Validar sólo la inscripción actual
                    $inscription->checked_at = \Carbon\Carbon::now();
                    $inscription->out_event = 0;
                    $inscription->save();
                }

                // log check-in
                SessionAccessLog::create([
                    'session_id' => $inscription->session_id,
                    'inscription_id' => $inscription->id,
                    'origin' => 'online',
                    'out_event' => false,
                    'user_id' => auth()->user()->id,
                ]);
            } else {
                // Ya validada previamente
                $message = trans('backend.validation.errors.already_scanned', [
                    'date' => $inscription->checked_at->format('d/m/Y'),
                    'time' => $inscription->checked_at->format('H:i')
                ]) . $inscription->apiTextRateName();
            }
        }

        // 6. Construir la respuesta
        //    'total' y 'n_validated' siguen refiriéndose a la sesión que llegó por $id.
        $data = [
            'success'     => $success,
            'inscription' => $inscription ? new InscriptionResource($inscription) : null,
            'message'     => $message,
            'total'       => $session->inscriptions()->paid()->count(),
            'n_validated' => $session->inscriptions()->paid()->whereNotNull('checked_at')->count(),
        ];

        // 7. Devolver la respuesta en JSON
        return response()->json($data);
    }



    public function outEvent(Request $request, $id)
    {
        // 1. Obtener la sesión solicitada y verificar que la marca esté autorizada
        $session = Session::whereHas('event', function ($q) {
            $q->authorizedBrands();
        })->findOrFail($id);

        // 2. Verificar si el EVENTO tiene o no 'validate_all_event'
        $event = $session->event;
        $validateAllEvent = $event->validate_all_event;  // <-- Campo a nivel de Event
        $barcode = $request->get('barcode');

        // 3. Buscar la inscripción según la configuración del evento
        if ($validateAllEvent) {
            // Si el evento permite validar inscripciones de cualquier sesión
            $inscription = Inscription::where('barcode', $barcode)
                ->with(['rate', 'group_pack.pack'])
                ->whereHas('session', function ($q) use ($event) {
                    $q->where('event_id', $event->id);
                })
                ->first();
        } else {
            // De lo contrario, solo la buscamos en esta sesión concreta
            $inscription = Inscription::where('barcode', $barcode)
                ->with(['rate', 'group_pack.pack'])
                ->first();
        }

        // 4. Mensaje por defecto
        $message = trans('backend.validation.errors.not_found');

        // 5. Lógica de validación
        //    - Si no existe la inscripción, se mantiene el mensaje por defecto.
        //    - Si existe, comprobamos si ya está validada o no,
        $success = false;
        if ($inscription) {
            if ($inscription->out_event) {
                $message =  trans(
                    'backend.validation.errors.already_scanned',
                    [
                        'date' => $inscription->updated_at->format('d/m/Y'),
                        'time' => $inscription->updated_at->format('H:i')
                    ]
                );
            } else {
                // check-out
                $sessionOfInscription = $inscription->session;
                $validateAllSession   = $sessionOfInscription->validate_all_session;
                $message = trans('backend.validation.success.validated') . $inscription->apiTextRateName();
                $success = true;

                if ($validateAllSession) {
                    // Validar todas las inscripciones del carrito en *esa* sesión
                    $inscriptions = Inscription::where('cart_id', $inscription->cart->id)
                        ->where('session_id', $sessionOfInscription->id)
                        ->update(['out_event' => 1]);

                    // Afegir N entrades validades
                    $message .= '<br><b>' . trans('backend.validation.success.validated_grup') . '</b>: ' . $inscriptions;
                } else {
                    // Validar sólo la inscripción actual
                    $inscription->checked_at = \Carbon\Carbon::now();
                    $inscription->out_event = 1;
                    $inscription->save();
                }

                // log check-in
                SessionAccessLog::create([
                    'session_id' => $inscription->session_id,
                    'inscription_id' => $inscription->id,
                    'origin' => 'online',
                    'out_event' => true,
                    'user_id' => auth()->user()->id,
                ]);
            }
        }

        $data = [
            'success' => $success,
            'inscription' => isset($inscription) ? new InscriptionResource($inscription) : null,
            'message' => $message
        ];

        // finally we mark inscription as checked in
        if ($data['success'] && !$inscription->out_event) {
            $inscription->out_event = 1;
            $inscription->save();
        }

        $data['total']       = $session->inscriptions()->paid()->count();
        $data['n_validated'] = $session->inscriptions()->paid()->where('out_event', true)->count();

        return response()->json($data);
    }

    /**
     * The index and show method need to start from a common query. This method
     * provides this Query and every method applies afterward their own filters
     * @return \Illuminate\Database\Query\Builder
     */
    private function allBuilder()
    {
        return Event::published()->authorizedBrands()->whereHas('sessions_no_finished', function ($query) {
            return $query
                ->has('space.location');
        });
    }
}
