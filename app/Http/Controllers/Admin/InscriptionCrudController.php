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
    

    // 1) Evita $with globales del modelo en ESTA query (si tu modelo Inscription los tiene)
    $this->crud->query->setEagerLoads([]);

    // 2) Eager loading mínimo y con columnas acotadas (anidado para evitar 2 cargas)
    $this->crud->with([
        'session:id,name,starts_on,event_id,brand_id',
        'session.event:id,name',
        'slot:id,name',
        'cart:id,client_id,confirmation_code,created_at',
        // confirmedPayment: trae SOLO lo que pintas
        'cart.confirmedPayment:id,cart_id,gateway,tpv_name,paid_at,deleted_at',
    ]);

    // 3) Select de inscriptions mínimo (solo lo que usas)
    $this->crud->query->select([
        'inscriptions.id',
        'inscriptions.session_id',
        'inscriptions.slot_id',
        'inscriptions.barcode',
        'inscriptions.code',
        'inscriptions.price_sold',
        'inscriptions.group_pack_id',
        'inscriptions.updated_at',
        'inscriptions.cart_id',
        'inscriptions.deleted_at', // quítalo si no usas el filtro "trashed" a la vez
    ]);

    // 4) Orden 100% indexable y estable
    $this->crud->orderBy('inscriptions.updated_at', 'desc')
               ->orderBy('inscriptions.id', 'desc');

    // 5) Export: desactívalo mientras mides (puede lanzar consultas extra)
    // CRUD::disableExportButtons();

    // ==== Columnas (usan las relaciones ya cargadas) ====

    CRUD::addColumn([
        'label' => __('backend.inscription.event'),
        'name'  => 'event_display',
        'type'  => 'closure',
        'function' => fn($ins) => optional(optional($ins->session)->event)->name ?? '-',
    ]);

    CRUD::addColumn([
        'name'  => 'session_display',
        'label' => __('backend.inscription.session'),
        'type'  => 'closure',
        'function' => function ($ins) {
            $s = $ins->session;
            if (!$s) return '-';
            // Evita Carbon::parse; asegúrate de tener casts en el modelo: 'starts_on' => 'datetime'
            $fecha = $s->starts_on ? $s->starts_on->format('d/m/Y H:i') : '-';
            return e($s->name).' '.$fecha;
        },
    ]);

    CRUD::addColumn([
        'label' => __('backend.inscription.slot'),
        'name'  => 'slot_display',
        'type'  => 'closure',
        'function' => fn($ins) => optional($ins->slot)->name ?? '-',
    ]);

    CRUD::addColumn(['name' => 'barcode', 'label' => __('backend.inscription.barcode')]);
    CRUD::addColumn(['name' => 'code',    'label' => __('backend.inscription.code')]);

    CRUD::addColumn([
        'name'  => 'price_sold_formatted',
        'label' => __('backend.inscription.pricesold'),
        'type'  => 'closure',
        'function' => fn($ins) => number_format($ins->price_sold, 2).' €',
    ]);

    CRUD::addColumn([
        'label'   => __('backend.inscription.paymentplatform'),
        'name'    => 'payment_platform',
        'type'    => 'closure',
        'escaped' => false,
        'function'=> function ($ins) {
            // Ya viene eager loaded: no hay N+1
            $p = optional(optional($ins->cart)->confirmedPayment);
            $gateway = $p->gateway ?? 'NA';
            $tpv     = $p->tpv_name ?? '';
            return e($gateway).'<br/><small>'.e($tpv).'</small>';
        },
    ]);

    CRUD::addColumn([
        'name'  => 'updated_at',
        'label' => __('backend.inscription.modifiedon'),
        'type'  => 'date.str'
    ]);

    CRUD::addColumn([
        'label'   => __('backend.inscription.client'),
        'name'    => 'client_link',
        'type'    => 'closure',
        'escaped' => false,
        'function'=> function ($ins) {
            $clientId = optional($ins->cart)->client_id;
            if ($clientId) {
                $url = backpack_url("client/{$clientId}/show");
                return '<a href="'.e($url).'" target="_blank">'.e($clientId).'</a>';
            }
            return 'Client eliminat';
        },
    ]);

    CRUD::addColumn([
        'label'    => __('backend.inscription.origin'),
        'name'     => 'origin_display',
        'type'     => 'closure',
        'function' => fn($ins) => $ins->group_pack_id === null ? 'Simple' : 'Pack',
    ]);

    CRUD::addColumn([
        'label'   => __('backend.inscription.cardconfirmationcode'),
        'name'    => 'cart_confirmation_link',
        'type'    => 'closure',
        'escaped' => false,
        'function'=> function ($ins) {
            $code = optional($ins->cart)->confirmation_code;
            if ($code) {
                $url = backpack_url("cart/{$ins->cart_id}/show");
                return '<a href="'.e($url).'" target="_blank">'.e($code).'</a>';
            }
            return 'Cistella eliminada';
        },
    ]);

    // ==== Filtros (usa whereHas; todos pegan a índices) ====

    // Últimos 3 meses (usa carts.created_at vía relación; más barato que subselect)
    $this->crud->addFilter(
        ['name'=>'last_3_months','type'=>'simple','label'=>__('backend.cart.last_3_months')],
        false,
        function () {
            $date = now()->subMonths(3)->startOfDay();
            $this->crud->query->whereHas('cart', fn($q) => $q->where('created_at', '>=', $date));
        }
    );

    // Trashed (si realmente usas deleted_at en inscriptions; si no, quita el deleted_at del select)
    CRUD::addFilter(
        ['type'=>'simple','name'=>'trashed','label'=>__('backend.inscription.showremovedinscriptions')],
        false,
        fn () => $this->crud->query->onlyTrashed()
    );

    // Evento (usa whereHas sobre session; hay índice sessions.event_id)
    CRUD::addFilter(
        ['type'=>'select2_ajax','name'=>'event_id','label'=>__('backend.inscription.event'),'placeholder'=>__('backpack::crud.select')],
        backpack_url('api/event'),
        fn ($eventId) => $this->crud->query->whereHas('session', fn($q) => $q->where('event_id', $eventId))
    );

    // Rango de pago (usa la relación eager: cart.confirmedPayment → EXISTS con el índice de payments)
    CRUD::addFilter(
        ['type'=>'date_range','name'=>'paid_range','label'=>__('backend.inscription.modifiedon')],
        false,
        function ($value) {
            $dates = json_decode($value);
            if (!empty($dates->from) && !empty($dates->to)) {
                $from = $dates->from.' 00:00:00';
                $to   = $dates->to.' 23:59:59';
                $this->crud->query->whereHas(
                    'cart.confirmedPayment',
                    fn($q) => $q->whereBetween('paid_at', [$from, $to])->whereNull('deleted_at')
                );
            }
        }
    );

    // Gateways (usa whereHas en vez de subquery)
    $gateways = cache()->remember('inscriptions_gateways_distinct', 600, function () {
        return \App\Models\Payment::whereNotNull('gateway')
            ->whereNull('deleted_at')
            ->distinct()->orderBy('gateway')
            ->pluck('gateway', 'gateway')->toArray();
    });

    CRUD::addFilter(
        ['type'=>'select2','name'=>'gateway','label'=>__('backend.inscription.paymentplatform')],
        $gateways,
        fn($value) => $this->crud->query->whereHas(
            'cart.confirmedPayment',
            fn($q) => $q->where('gateway', $value)->whereNull('deleted_at')
        )
    );

    // 5) Búsqueda global: solo columnas locales
    $this->crud->setOperationSetting('searchLogic', [
        'barcode' => fn($q,$c,$t) => $q->orWhere('inscriptions.barcode','like',"%{$t}%"),
        'code'    => fn($q,$c,$t) => $q->orWhere('inscriptions.code','like',"%{$t}%"),
    ]);
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
        $inscription->price_sold = $request->price;
        $inscription->save();

        return redirect()->back()->with('success', 'Inscripción actualizada correctamente.');
    }

}
