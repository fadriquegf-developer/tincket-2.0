<?php

namespace App\Http\Controllers\ApiBackend;

use Carbon\Carbon;
use App\Models\Event;
use App\Models\Client;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Resources\EventApiCollection;


class ClientApiBackendController extends \App\Http\Controllers\Controller
{

    public function subscribed(Request $request)
    {
        /*  $newsletter_field = \App\FormField::ownedByBrand()->where('config', 'like', '%newsletter%')->limit(1)->get()->first();

        if(!is_null($newsletter_field))
        {
            $list = \App\Client::ownedByBrand()->join('form_field_answers', 'form_field_answers.client_id', 'clients.id')
            ->whereNull('form_field_answers.deleted_at')
            ->where('form_field_answers.field_id', '=', $newsletter_field->id)
            ->where('form_field_answers.answer', 'like', '%newsletter%')
            ->select(['clients.email'])
            ->get();

            return $list;
        }  */

        return Client::ownedByBrand()->where('newsletter', 1)->select(['clients.email'])->get();

        // return response()->json(["example@example.com", "foobar@example.com"]);

        // this will load all clients if the fiels doesnt exists.
        /* return \App\Client::ownedByBrand()->select(['clients.email'])->get(); */
    }

    public function subscribedTo(Request $request)
    {
        // Obtener el campo de intereses configurado
        $brand_id = get_current_brand()->id;

        $interests_field = Setting::where('brand_id', $brand_id)
            ->where('key', 'ywt.mail_interests_field_id')
            ->first();

        // Si no hay campo configurado, devolver todos los suscritos
        if (is_null($interests_field)) {

            $clients = Client::ownedByBrand()
                ->where('newsletter', 1)
                ->select(['clients.email'])
                ->get();

            return $clients;
        }

        // Obtener intereses del request
        $interests = $request->input('interests', []);

        // Asegurar que es un array
        if (!is_array($interests)) {
            $interests = [$interests];
        }

        // Filtrar valores vacíos
        $interests = array_filter($interests, function ($val) {
            return !empty($val);
        });

        $query = Client::ownedByBrand()
            ->where('newsletter', 1)
            ->whereHas('answers', function ($query) use ($interests, $interests_field) {

                $query->where('form_field_answers.field_id', '=', $interests_field->value)
                    ->whereNull('form_field_answers.deleted_at');

                $query->where(function ($q) use ($interests) {
                    foreach ($interests as $key => $interest) {
                        if ($key == 0) {
                            $q->where(function ($q2) use ($interest) {
                                $q2->where('form_field_answers.answer', 'like', $interest . ',%')
                                    ->orWhere('form_field_answers.answer', 'like', '%,' . $interest . ',%')
                                    ->orWhere('form_field_answers.answer', 'like', '%,' . $interest)
                                    ->orWhere('form_field_answers.answer', '=', $interest);
                            });
                        } else {
                            $q->orWhere(function ($q2) use ($interest) {
                                $q2->where('form_field_answers.answer', 'like', $interest . ',%')
                                    ->orWhere('form_field_answers.answer', 'like', '%,' . $interest . ',%')
                                    ->orWhere('form_field_answers.answer', 'like', '%,' . $interest)
                                    ->orWhere('form_field_answers.answer', '=', $interest);
                            });
                        }
                    }
                });
            })
            ->select(['clients.id', 'clients.email']);

        // Log de la SQL generada
        try {
            $sql = str_replace(['?'], ['\'%s\''], $query->toSql());
            $sql = vsprintf($sql, $query->getBindings());
        } catch (\Exception $e) {
            \Log::warning('[MAILING DEBUG] No se pudo formatear SQL');
        }

        try {
            $clients = $query->get();

            // Si no hay resultados, mostrar ejemplos de lo que hay en BD
            if ($clients->count() == 0) {
                \Log::warning('[MAILING DEBUG] ⚠️  NO SE ENCONTRARON CLIENTES');

                // Verificar cuántas respuestas hay en total
                $total_answers = \DB::table('form_field_answers')
                    ->where('field_id', $interests_field->value)
                    ->whereNull('deleted_at')
                    ->count();

                if ($total_answers > 0) {
                    // Mostrar muestra de respuestas
                    \DB::table('form_field_answers')
                        ->where('field_id', $interests_field->value)
                        ->whereNull('deleted_at')
                        ->limit(5)
                        ->get(['client_id', 'answer']);

                    // Verificar si alguna respuesta contiene los intereses buscados
                    foreach ($interests as $interest) {
                        \DB::table('form_field_answers')
                            ->where('field_id', $interests_field->value)
                            ->whereNull('deleted_at')
                            ->where(function ($q) use ($interest) {
                                $q->where('answer', 'like', $interest . ',%')
                                    ->orWhere('answer', 'like', '%,' . $interest . ',%')
                                    ->orWhere('answer', 'like', '%,' . $interest)
                                    ->orWhere('answer', '=', $interest);
                            })
                            ->count();

                    }

                    // Verificar si el problema son clientes sin newsletter=1
                    foreach ($interests as $interest) {
                        \DB::table('form_field_answers')
                            ->join('clients', 'clients.id', '=', 'form_field_answers.client_id')
                            ->where('form_field_answers.field_id', $interests_field->value)
                            ->whereNull('form_field_answers.deleted_at')
                            ->where(function ($q) use ($interest) {
                                $q->where('form_field_answers.answer', 'like', $interest . ',%')
                                    ->orWhere('form_field_answers.answer', 'like', '%,' . $interest . ',%')
                                    ->orWhere('form_field_answers.answer', 'like', '%,' . $interest)
                                    ->orWhere('form_field_answers.answer', '=', $interest);
                            })
                            ->count();

                        \DB::table('form_field_answers')
                            ->join('clients', 'clients.id', '=', 'form_field_answers.client_id')
                            ->where('form_field_answers.field_id', $interests_field->value)
                            ->where('clients.newsletter', 1)
                            ->whereNull('form_field_answers.deleted_at')
                            ->where(function ($q) use ($interest) {
                                $q->where('form_field_answers.answer', 'like', $interest . ',%')
                                    ->orWhere('form_field_answers.answer', 'like', '%,' . $interest . ',%')
                                    ->orWhere('form_field_answers.answer', 'like', '%,' . $interest)
                                    ->orWhere('form_field_answers.answer', '=', $interest);
                            })
                            ->count();

                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('[MAILING DEBUG] ❌ ERROR AL EJECUTAR CONSULTA:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            // Devolver array vacío en caso de error
            return collect([]);
        }

        return $clients;
    }


    public function getAllClientsByDate($date_start = null, $date_end = null)
    {
        $clients = Client::ownedByBrand();
        if ($date_start != null) {
            $clients = $clients->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', $date_start));
        }
        if ($date_end != null) {
            $clients = $clients->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', $date_end));
        }

        return response()->json([
            'data' => [
                'num_clients' => $clients->get()->count(),
                'desde' => $date_start,
                'hasta' => $date_end
            ]
        ]);
    }

    public function getAllEventsInfoByDate($event = null, $date_start = null, $date_end = null)
    {
        $events = Event::ownedByBrand();

        if ($event != 'all') {
            $events = $events->where('id', $event);
        }

        if ($date_start != null) {
            $events = $events->whereHas('sessions.inscriptions', function ($q) use ($date_start) {
                $q->where('inscriptions.created_at', '>=', Carbon::createFromFormat('Y-m-d', $date_start));
            });
        }

        if ($date_end != null) {
            $events = $events->whereHas('sessions.inscriptions', function ($q) use ($date_end) {
                $q->where('inscriptions.created_at', '<=', Carbon::createFromFormat('Y-m-d', $date_end));
            });
        }

        return response()->json(new EventApiCollection($events->get()));
    }

    public function autocomplete(Request $request)
    {
        $q = $request->get('q');

        $clients = Client::where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                ->orWhere('surname', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
        })->limit(20)->get();

        return response()->json($clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'surname' => $client->surname,
                'email' => $client->email,
            ];
        }));
    }
}
