<?php

namespace App\Http\Controllers\Admin;

use App\Models\Session;
use App\Models\SessionAccessLog;
use App\Models\Inscription;
use Illuminate\Http\Request;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;



class ValidationController extends CrudController
{

    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(Session::class);
        CRUD::setRoute(backpack_url('validation'));
        CRUD::setEntityNameStrings('validación', 'validaciones');
        $this->setAccessUsingPermissions();
    }

    public function setupListOperation()
    {
        CRUD::enableExportButtons();
        CRUD::removeAllButtonsFromStack('line');
        CRUD::addButton('line', 'validar', 'view', 'crud::buttons.validar', 'beginning');
        CRUD::addButtonFromView('top','offline_logs', 'offline_logs','end');


        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.validation.sessionname'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'event',
            'label' => __('backend.validation.event'),
            'type' => 'relationship',
            'attribute' => 'name',
        ]);

        CRUD::addColumn([
            'name' => 'count_free_positions',
            'label' => __('backend.validation.free_positions'),
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->count_free_positions . '/' . $entry->max_places;
            },
        ]);

        CRUD::addColumn([
            'name' => 'validated_in',
            'label' => __('backend.validation.validated_in'),
            'type' => 'closure',
            'function' => function ($entry) {
                $validatedIn = $entry->count_validated - $entry->count_validated_out;
                return "{$validatedIn}/{$entry->count_validated}";
            },
            'wrapper' => ['class' => 'hidden-xs hidden-sm'],
        ]);

        CRUD::addColumn([
            'name' => 'validated_out',
            'label' => __('backend.validation.validated_out'),
            'type' => 'closure',
            'function' => function ($entry) {
                return "{$entry->count_validated_out}/{$entry->count_validated}";
            },
            'wrapper' => ['class' => 'hidden-xs hidden-sm'],
        ]);

        CRUD::addColumn([
            'name' => 'space',
            'label' => __('backend.validation.space'),
            'type' => 'relationship',
            'attribute' => 'name',
        ]);

        CRUD::addColumn([
            'name' => 'starts_on',
            'label' => __('backend.validation.starts_on'),
            'type' => 'datetime',
        ]);

        CRUD::addClause('where', 'ends_on', '>', \Carbon\Carbon::now());
        CRUD::orderBy('starts_on', 'asc');

    }


    public function show($id)
{
    $session = Session::ownedByBrand()->findOrFail($id);
    return view('core.validation.show', ['session' => $session]);
}

    /**
     * Al escanear un código de barras, este método comprueba la validez de la inscripción 
     * asociada y devuelve un JSON con información sobre si se le permite el acceso 
     * (success), qué inscripción se ha validado (inscription) y cuántas inscripciones 
     * hay validadas en la sesión (n_validated). Si no es válida, indica el motivo en 
     * el campo details.
     *
     * - Primero determina si el evento tiene activada la validación global (validate_all_event).
     *   En ese caso, cualquier inscripción del mismo evento es aceptada, aunque pertenezca a otra 
     *   sesión. De lo contrario, solo se admite la inscripción de la sesión concreta recibida.
     *
     * - Si la inscripción es válida y la sesión también tiene activada la validación múltiple
     *   (validate_all_session), se marcan como validadas todas las inscripciones del mismo carrito 
     *   en esa sesión. Si no, se valida únicamente la inscripción escaneada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        // 1. Obtenemos la sesión solicitada
        $session = Session::ownedByBrand()->findOrFail($request->get('session_id'));

        // 2. Verificamos si el EVENTO tiene o no validate_all_event
        $validateAllEvent = $session->event->validate_all_event;
        $barcode = $request->get('barcode');

        // 3. Buscamos la inscripción
        if ($validateAllEvent) {
            // Cualquier sesión del mismo evento
            $inscription = Inscription::where('barcode', $barcode)
                ->whereHas('session', function ($q) use ($session) {
                    $q->where('event_id', $session->event_id);
                })
                ->first();
        } else {
            // Sólo esta sesión
            $inscription = Inscription::where('barcode', $barcode)
                ->where('session_id', $session->id)
                ->first();
        }

        // 4. Verificar que la inscripción exista y que esté confirmada
        $inscription = ($inscription && $inscription->cart && $inscription->cart->is_confirmed) ? $inscription : null;

        // 5. Definimos el éxito si no está ya validada
        $success = isset($inscription) &&
            (!$inscription->checked_at || ($inscription->checked_at && $inscription->out_event));

        if ($success) {
            // 6. Ver si la sesión en la que está la inscripción tiene 'validate_all_session'
            $validateAllSession = $inscription->session->validate_all_session;

            if ($validateAllSession) {
                // Validar todas las inscripciones de este carrito en la misma sesión
                Inscription::where('cart_id', $inscription->cart->id)
                    ->where('session_id', $inscription->session_id)
                    ->update(['checked_at' => \Carbon\Carbon::now(), 'out_event' => 0]);
            } else {
                // Validar solo esa inscripción
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
        }

        // 7. Cuántas inscripciones hay validadas en la sesión real de la inscripción
        $nValidated = $inscription ? $inscription->session->count_validated : 0;

        // 8. Preparamos la respuesta
        $data = [
            'success' => $success,
            'inscription' => $inscription,
            'n_validated' => $nValidated,
        ];

        // Render de la vista parcial con información de validación
        $data['details'] = view('core.validation.partials.check', ['data' => $data])->render();
        
        return response()->json(compact('data'));
    }

    public function outEvent(Request $request)
    {
        // 1. Obtenemos la sesión solicitada
        $session = Session::ownedByBrand()->findOrFail($request->get('session_id'));

        // 2. Verificamos si el EVENTO tiene o no validate_all_event
        $validateAllEvent = $session->event->validate_all_event;
        $barcode = $request->get('barcode');

        // 3. Buscamos la inscripción
        if ($validateAllEvent) {
            // Cualquier sesión del mismo evento
            $inscription = Inscription::where('barcode', $barcode)
                ->whereHas('session', function ($q) use ($session) {
                    $q->where('event_id', $session->event_id);
                })
                ->first();
        } else {
            // Sólo esta sesión
            $inscription = Inscription::where('barcode', $barcode)
                ->where('session_id', $session->id)
                ->first();
        }

        // 4. Verificar que la inscripción exista y que esté confirmada
        $inscription = $inscription
            && ($inscription->session_id == $session->id || $validateAllEvent)
            && $inscription->cart
            && $inscription->cart->is_confirmed ? $inscription : null;

        $data = [
            'success' => isset($inscription)
                && !$inscription->out_event
                && ($inscription->session_id == $session->id || $validateAllEvent),
            'inscription' => $inscription
        ];

        // this is HTML data that will be shown in information modal panel
        $data['details'] = view('core.validation.partials.out', compact('data'))->render();

        // Si la inscripcion no esta fuera, marcar como fuera del recinto
        if ($data['success'] && !$inscription->out_event) {
            $validateAllSession = $inscription->session->validate_all_session;

            if ($validateAllSession) {
                // Validar todas las inscripciones de este carrito en la misma sesión
                Inscription::where('cart_id', $inscription->cart->id)
                    ->where('session_id', $inscription->session_id)
                    ->update(['out_event' => 1]);
            } else {
                // Validar solo esa inscripción
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

        return response()->json(compact('data'));
    }
}
