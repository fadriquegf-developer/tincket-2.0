<?php

namespace App\Http\Controllers\Admin;

use App\Models\Session;
use App\Models\Inscription;
use Illuminate\Http\Request;
use App\Traits\AllowUsersTrait;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class CapabilityCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InscriptionCrudController extends CrudController
{
    use CrudPermissionTrait;
    use AllowUsersTrait;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;


    public function setup()
    {
        CRUD::setModel(Inscription::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/inscription');
        CRUD::setEntityNameStrings(__('backend.menu.inscription'), __('backend.menu.inscriptions'));

        $brand = get_current_brand();
        CRUD::addClause('forBrand', $brand);

        CRUD::denyAllAccess();
        if (auth()->check() && auth()->user()->hasPermissionTo('carts.index')) {
            $this->crud->allowAccess(['list', 'delete']);
        }
    }


    protected function setupListOperation()
    {

        CRUD::enableExportButtons();
        $this->crud->query
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->select('inscriptions.*');

        CRUD::addColumn([
            'label' => __('backend.inscription.event'),
            'type' => 'relationship',
            'name' => 'session_id',          // <— el campo foráneo en la tabla “inscriptions”
            'entity' => 'session',             // <— el nombre exacto de la relación en Inscription::session()
            'attribute' => 'event_name',          // <— el accesor en Session: getEventNameAttribute()
            'model' => Session::class, // <— namespace completo de tu modelo Session
            'searchLogic' => function ($query, $column, $searchTerm) {
                // 1) Entrar en la relación “session” del modelo Inscription
                $query->whereHas('session', function ($q) use ($searchTerm) {
                    // 2) Dentro de “session”, entrar en “event”
                    //    y filtrar por su campo “name” (o el campo original donde guardas el nombre)
                    $q->whereHas('event', function ($q2) use ($searchTerm) {
                        $q2->where('name', 'like', '%' . strtolower($searchTerm) . '%');
                    });
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'session_display',            // Identificador interno (no importa si no existe en BD)
            'label' => __('backend.inscription.session'),
            'type' => 'closure',                    // Usaremos un closure para formatear “nombre + fecha”
            'function' => function ($inscription) {
                // 1) Obtenemos la sesión relacionada
                $session = $inscription->session;
                if (!$session) {
                    return '-';
                }
                // 2) Formateamos la fecha de inicio. Si no existe, mostramos “-”
                $fecha = $session->starts_on
                    ? $session->starts_on->format('d/m/Y H:i')
                    : '-';
                // 3) Escapamos el nombre y lo concatenamos con la fecha
                return e($session->name) . ' ' . $fecha;
            },
            // 4) Para que el buscador funcione, añadimos searchLogic:
            'searchLogic' => function ($query, $column, $searchTerm) {
                // Filtramos por “nombre de sesión” (sessions.name) y/o “fecha” (sessions.starts_on)
                $query->whereHas('session', function ($q) use ($searchTerm) {
                    // Convertimos el término en minúsculas:
                    $term = strtolower($searchTerm);
                    $q->whereRaw('LOWER(sessions.name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw("DATE_FORMAT(sessions.starts_on, '%d/%m/%Y %H:%i') LIKE ?", ["%{$searchTerm}%"]);
                    // — Si no necesitas buscar por fecha formateada, puedes omitir la línea de orWhereRaw.
                });
            },
        ]);

        CRUD::addColumn([
            'label' => __('backend.inscription.slot'),
            'type' => 'select',
            'name' => 'slot_id',
            'entity' => 'slot',
            'attribute' => 'name',
            'model' => \App\Models\Slot::class,
        ]);

        CRUD::addColumn([
            'name' => 'barcode',
            'label' => __('backend.inscription.barcode'),
        ]);

        CRUD::addColumn([
            'name' => 'code',
            'label' => __('backend.inscription.code'),
        ]);

        CRUD::addColumn([
            'name' => 'price_sold_formatted',       // nombre cualquiera (no existe en BD)
            'label' => __('backend.inscription.pricesold'),
            'type' => 'closure',                    // v5+: usa closure en lugar de model_callback
            'function' => function ($inscription) {
                // Concatena “€” y formatea a dos decimales:
                return number_format($inscription->price_sold, 2) . ' €';
            },
        ]);

        CRUD::addColumn([
            'label' => __('backend.inscription.paymentplatform'),
            'name' => 'payment_platform',
            'type' => 'closure',
            'escaped' => false,
            'function' => function ($inscription) {
                $payment = optional($inscription->cart->confirmedPayment);
                $gateway = $payment->gateway ?? 'NA';
                $tpv_name = $payment->tpv_name ?? '';
                return e($gateway) . '<br/><small>' . e($tpv_name) . '</small>';
            },
        ]);

        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => __('backend.inscription.modifiedon'),
            'type' => 'date.str'
        ]);

        CRUD::addColumn([
            'label' => __('backend.inscription.client'),
            'name' => 'client_link',
            'type' => 'closure',
            'escaped' => false,
            'function' => function ($inscription) {
                if (isset($inscription->cart->client_id)) {
                    $clientId = $inscription->cart->client_id;
                    $url = backpack_url("client/{$clientId}/show");
                    return sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        e($url),
                        e($clientId)
                    );
                }

                return 'Client eliminat';
            },
        ]);

        CRUD::addColumn([
            'label' => __('backend.inscription.origin'),
            'name' => 'origin_display',
            'type' => 'closure',
            'function' => function ($inscription) {
                return $inscription->group_pack_id === null
                    ? 'Simple'
                    : 'Pack';
            },
        ]);

        CRUD::addColumn([
            'label' => __('backend.inscription.cardconfirmationcode'),
            'name' => 'cart_confirmation_link',
            'type' => 'closure',
            'escaped' => false,
            'function' => function ($inscription) {
                if (isset($inscription->cart->confirmation_code)) {
                    $cartId = $inscription->cart_id;
                    $code = $inscription->cart->confirmation_code;
                    $url = backpack_url("cart/{$cartId}/show");
                    return sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        e($url),
                        e($code)
                    );
                }
                return 'Cistella eliminada';
            },
        ]);

        // Filtros

        $this->crud->addFilter(
            [
                'name' => 'last_3_months',
                'type' => 'simple',
                'label' => __('backend.cart.last_3_months'),
            ],
            false,
            function () {
                // hace 3 meses, a las 00:00
                $date = now()->subMonths(3)->startOfDay();
                $this->crud->addClause('where', 'carts.created_at', '>=', $date);
            }
        );

        CRUD::addFilter([
            'type' => 'simple',
            'name' => 'trashed',
            'label' => __('backend.inscription.showremovedinscriptions'),
        ], false, function () {
            $this->crud->query->onlyTrashed();
        });

        CRUD::addFilter([
            'type' => 'select2',
            'name' => 'event_id',
            'label' => __('backend.inscription.event'),
        ], function () {
            return \App\Models\Event::orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        }, function ($value) {
            CRUD::addClause('whereHas', 'session', function ($q) use ($value) {
                $q->where('event_id', $value);
            });
        });

        CRUD::addFilter([
            'type' => 'date_range',
            'name' => 'paid_range',
            'label' => __('backend.inscription.modifiedon'),
        ], false, function ($value) {
            $dates = json_decode($value);
            CRUD::addClause('whereHas', 'cart.confirmedPayment', function ($q) use ($dates) {
                $q->whereDate('paid_at', '>=', $dates->from)
                    ->whereDate('paid_at', '<=', $dates->to);
            });
        });

        CRUD::addFilter([
            'type' => 'select2',
            'name' => 'gateway',
            'label' => __('backend.inscription.paymentplatform'),
        ], function () {
            return \App\Models\Payment::whereNotNull('gateway')
                ->distinct()->pluck('gateway', 'gateway')->toArray();
        }, function ($value) {
            CRUD::addClause('whereHas', 'cart.confirmedPayment', function ($q) use ($value) {
                $q->where('gateway', $value);
            });
        });

    }

    public function generate($id)
    {
        $currentBrandId = get_current_brand()->id;
        $partnershipBrandIds = brand_setting('base.brand.partnershiped_ids', []);

        if (is_string($partnershipBrandIds)) {
            $partnershipBrandIds = explode(',', $partnershipBrandIds);
        }

        $allowedBrandIds = array_merge($partnershipBrandIds, [$currentBrandId]);

        $inscription = Inscription::paid()
            ->where('id', $id)
            ->whereHas('session', function ($q) use ($allowedBrandIds) {
                $q->whereIn('brand_id', $allowedBrandIds);
            })
            ->firstOrFail();

        $url = env('APP_ENV') == 'local' ? 'https://pdf.yesweticket.com/render' : 'https://pdf.yesweticket.com/render';

        $pdfParams = [
            'inscription' => $inscription,
            'token' => $inscription->cart->token,
        ];

        // load parameters
        if ($inscription->cart->seller_type === 'App\Models\User' && !request()->has('web') || request()->get('ticket-office') == 1) {
            $params = brand_setting('base.inscription.ticket-office-params');
            $pdfParams['ticket-office'] = 1;
        } else {
            $params = brand_setting('base.inscription.ticket-web-params');
            $pdfParams['web'] = 1;
        }

        $params['url'] = url(route(
            'open.inscription.pdf',
            $pdfParams
        ));

        $client = new \GuzzleHttp\Client();
        $response = $client->request(
            'POST',
            $url,
            ['form_params' => $params]
        );

        return response()->make($response->getBody()->getContents(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $inscription->barcode . '.pdf"'
        ]);
    }

    public function updatePrice(Request $request)
    {
        $request->validate([
            'inscription_id' => 'required|exists:inscriptions,id',
            'rate_id' => 'required|exists:rates,id',
            'price' => 'required|numeric|min:0',
        ]);

        $inscription = Inscription::findOrFail($request->inscription_id);
        $inscription->rate_id = $request->rate_id;
        $inscription->price = $request->price;
        $inscription->save();

        return redirect()->back()->with('success', 'Inscripción actualizada correctamente.');
    }

}
