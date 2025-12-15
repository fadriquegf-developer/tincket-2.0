<?php

namespace App\Http\Controllers\Admin;


use App\Models\Pack;
use App\Models\User;
use App\Models\Session;
use App\Models\PackRule;
use App\Models\GroupPack;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Traits\CrudPermissionTrait;
use App\Uploaders\WebpImageUploader;
use App\Http\Requests\PackCrudRequest;
use App\Uploaders\PngImageUploader;
use Backpack\CRUD\app\Http\Requests\CrudRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

class PackCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation {
        store as traitStore;
    }
    use UpdateOperation {
        update as traitUpdate;
    }
    use DeleteOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(Pack::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/pack');
        CRUD::setEntityNameStrings('Pack', 'Packs');
        $this->setAccessUsingPermissions();
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('created_at', 'desc');
        CRUD::enableExportButtons();

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.pack.packname'),
            'wrapper' => [
                'element' => 'span',
                'title' => function ($crud, $column, $entry, $related_key) {
                    return $entry->name;
                },
            ]
        ]);

        CRUD::addColumn([
            'name' => 'starts_on',
            'label' => __('backend.pack.startson'),
            'type' => 'datetime',
            'format' => 'DD/MM/YYYY HH:mm'
        ]);

        CRUD::addColumn([
            'name' => 'ends_on',
            'label' => __('backend.pack.endson'),
            'type' => 'datetime',
            'format' => 'DD/MM/YYYY HH:mm'
        ]);

        CRUD::addColumn([
            'name' => 'total_sold_link',
            'label' => __('backend.pack.totalsold'),
            'type' => 'closure',
            'function' => function ($entry) {
                $n = GroupPack::join('carts', 'carts.id', '=', 'group_packs.cart_id')
                    ->where('group_packs.pack_id', $entry->id)
                    ->whereNull('carts.deleted_at')
                    ->whereNotNull('carts.confirmation_code')
                    ->count();

                return '<a href="' . backpack_url("pack/{$entry->id}/sales") . '">' . $n . '</a>';
            },
            'escaped' => false,
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(PackCrudRequest::class);

        $this->setBasicTab();
        $this->setConfigurationTab();
        $this->setExtraTab();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }


    private function setBasicTab()
    {
        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.pack.packname'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic'
        ]);

        CRUD::addField([
            'name' => 'slug',
            'label' => __('backend.pack.slug'),
            'type' => 'slug',
            'target' => 'name',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic'
        ]);

        CRUD::addField([
            'name' => 'starts_on',
            'type' => 'datetime',
            'label' => __('backend.pack.startson'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic'
        ]);

        CRUD::addField([
            'name' => 'ends_on',
            'type' => 'datetime',
            'label' => __('backend.pack.endson'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => 'Basic'
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => __('backend.pack.packdescription'),
            'type' => 'ckeditor',
            //'extraPlugins' => ['oembed'],
            'tab' => 'Basic'
        ]);
    }

    private function setConfigurationTab()
    {

        CRUD::addField([
            'name' => 'min_per_cart',
            'label' => __('backend.pack.minpercart'),
            'type' => 'number',
            'attributes' => [
                'min' => 1,
                'step' => 1,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.pack.configuration'),
        ]);

        CRUD::addField([
            'name' => 'max_per_cart',
            'label' => __('backend.pack.maxpercart'),
            'type' => 'number',
            'attributes' => [
                'min' => 1,
                'step' => 1,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'tab' => __('backend.pack.configuration')
        ]);

        CRUD::addField([
            'name' => 'separator_1',
            'type' => 'custom_html',
            'value' => '<hr>',
            'tab' => __('backend.pack.configuration')
        ]);

        CRUD::addField([
            'name' => 'cart_rounded',
            'label' => __('backend.pack.config.cart_rounded'),
            'type' => 'switch',
            'wrapperAttributes' => [
                'class' => 'form-group col-sm-7',
            ],
            'tab' => __('backend.pack.configuration'),
        ]);

        CRUD::addField([
            'name' => 'separator_4',
            'type' => 'custom_html',
            'value' => __('backend.pack.config.cart_rounded-alert'),
            'wrapperAttributes' => [
                'class' => 'form-group col-sm-5',
            ],
            'tab' => __('backend.pack.configuration')
        ]);

        CRUD::addField([
            'name' => 'round_to_nearest',
            'label' => __('backend.pack.config.round-nearest'),
            'type' => 'switch',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-7',
            ],
            'tab' => __('backend.pack.configuration'),
        ]);

        CRUD::addField([
            'name' => 'separator_2',
            'type' => 'custom_html',
            'value' => __('backend.pack.config.round-alert'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-5',
            ],
            'tab' => __('backend.pack.configuration')
        ]);

        CRUD::addField([
            'name' => 'one_session_x_event',
            'label' => __('backend.pack.config.one-session-x-event'),
            'type' => 'switch',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-7',
            ],
            'tab' => __('backend.pack.configuration'),
        ]);

        CRUD::addField([
            'name' => 'separator_6',
            'type' => 'custom_html',
            'value' => '<hr>',
            'tab' => __('backend.pack.configuration')
        ]);

        CRUD::addField([
            'name' => 'rules',
            'type' => 'rules_packs',
            'tab' => __('backend.pack.configuration'),
        ]);

        CRUD::addField([
            'name' => 'separator_5',
            'type' => 'custom_html',
            'value' => '<hr>',
            'tab' => __('backend.pack.configuration')
        ]);

        CRUD::addField([
            'name' => 'sessions',
            'type' => 'view',
            'view' => 'core.pack.fields.sessions',
            'sessions' => Session::where('ends_on', '>', \Carbon\Carbon::now())->orderBy('starts_on', 'ASC')->get(),
            'tab' => __('backend.pack.configuration'),
        ]);
    }

    private function setExtraTab()
    {
        CRUD::addField([
            'name' => 'custom_logo',
            'label' => __('backend.session.custom_logo'),
            'type' => 'image',
            'crop' => true,
            'upload' => true,
            'withFiles' => [
                'disk' => 'public',
                'uploader' => PngImageUploader::class,
                'path' => 'uploads/' . get_current_brand()->code_name . '/pack',
                'resize' => [
                    'max' => 200,
                ],
            ],
            'tab' => 'Ticket',
        ]);

        CRUD::addField([
            'name' => 'color',
            'type' => 'color',
            'label' => __('backend.session.session_color'),
            'wrapperAttributes' => [
                'class' => 'form-group col-lg-3',
            ],
            'tab' => 'Ticket',
        ]);

        CRUD::addField([
            'name' => 'bg_color',
            'type' => 'color',
            'label' => __('backend.session.session_bg_color'),
            'wrapperAttributes' => [
                'class' => 'form-group col-lg-3',
            ],
            'tab' => 'Ticket',
        ]);

        CRUD::addField([
            'name' => 'banner',
            'label' => __('backend.session.banner'),
            'type' => 'image',
            'crop' => true,
            'upload' => true,
            'withFiles' => [
                'disk' => 'public',
                'uploader' => PngImageUploader::class,
                'path' => 'uploads/' . get_current_brand()->code_name . '/pack',
                'resize' => [
                    'max' => 1200,
                ],
            ],
            'tab' => 'Ticket'
        ]);
    }

    public function store(PackCrudRequest $request)
    {
        // Quitamos reglas y sesiones del payload que Backpack va a guardar
        $cleanInput = Arr::except($request->all(), ['rules', 'sessions']);

        // Creamos el pack manualmente; guardamos entry para el resto del flujo
        $entry = $this->crud->create($cleanInput);
        $this->crud->setSaveAction();
        $this->crud->entry = $entry;

        // Ahora procesamos relaciones
        $this->updatePackRules($request);
        $this->updatePackSessions($request);

        return $this->crud->performSaveAction($entry->getKey());
    }

    public function update(PackCrudRequest $request)
    {
        // 1. Campos "limpios" (sin rules ni sessions) para la tabla packs
        $cleanInput = Arr::except($request->all(), ['rules', 'sessions']);

        // 2. ID del modelo que se está editando (Backpack siempre pasa "id")
        $id = $request->route('id');          // ← aquí ya no llamamos a getRouteName

        // 3. Backpack actualiza y devuelve la instancia
        $entry = $this->crud->update($id, $cleanInput);
        $this->crud->entry = $entry;          // por si lo usan métodos auxiliares

        // 4. Sincronizar relaciones
        $this->updatePackRules($request);
        $this->updatePackSessions($request);

        // 5. Redirección estándar de Backpack
        return $this->crud->performSaveAction($entry->getKey());
    }

    /**
     * It stores in database the pack rules comming in Request.
     *
     * It adds, modify of delete PackRules in order to sync them
     *
     * @param CrudRequest $request
     */
    /**
     * Sincroniza las reglas (discount tiers) de un Pack.
     */
    private function updatePackRules(CrudRequest $request): void
    {
        /** @var \App\Models\Pack $entry */
        $entry = $this->crud->entry;

        /* -----------------------------------------------------------------
         | 1. Normalizar la entrada
         |------------------------------------------------------------------
         | Siempre tendremos una colección de ARRAYS con las keys:
         | id, number_sessions, percent_pack, price_pack, all_sessions
         */
        $rules = collect($request->input('rules', []))
            ->map(fn($r) => (array) $r)   // stdClass → array
            ->values();

        /* -----------------------------------------------------------------
         | 2. Borrar reglas que ya no existen en el formulario
         |------------------------------------------------------------------ */
        $currentIds = $entry->rules->pluck('id')->toArray();
        $incomingIds = $rules->pluck('id')->filter()->all();

        $idsToDelete = array_diff($currentIds, $incomingIds);
        if ($idsToDelete) {
            $entry->rules()->whereIn('id', $idsToDelete)->delete();
        }

        /* -----------------------------------------------------------------
         | 3. Añadir o actualizar cada regla recibida
         |------------------------------------------------------------------ */
        foreach ($rules as $rule) {

            // Sanitizar valores: si son negativos los tratamos como NULL
            $rule['price_pack'] = isset($rule['price_pack']) && $rule['price_pack'] >= 0 ? floatval($rule['price_pack']) : null;
            $rule['percent_pack'] = isset($rule['percent_pack']) && $rule['percent_pack'] >= 0 ? floatval($rule['percent_pack']) : null;

            /* ----------- A) Regla existente (tiene ID) ----------- */
            if (!empty($rule['id'])) {

                // Si no hay ni percent ni price ⇒ eliminarla
                if (is_null($rule['percent_pack']) && is_null($rule['price_pack'])) {
                    $entry->rules()->where('id', $rule['id'])->delete();
                    continue;
                }

                // Asegurarse de que realmente pertenece a este Pack
                if ($entry->rules()->where('id', $rule['id'])->exists()) {
                    $entry->rules()->updateOrCreate(['id' => $rule['id']], $rule);
                }

                /* ----------- B) Regla nueva (sin ID) ------------------ */
            } elseif (!is_null($rule['percent_pack']) || !is_null($rule['price_pack'])) {
                // Sólo guardamos si al menos uno de los dos campos tiene valor
                $entry->rules()->create($rule);        // ó ->save(new PackRule($rule));
            }
        }
    }


    /**
     * Updates which Sessions belongs to a the Pack
     *
     * @param CrudRequest $request
     */
    private function updatePackSessions(CrudRequest $request): void
    {
        /** @var \App\Models\Pack $entry */
        $entry = $this->crud->entry;

        // 1. Extrae los IDs únicos (y no vacíos) que llegan desde el formulario
        $sessionIds = collect($request->input('sessions', []))
            ->pluck('id')          // [ {id: 1}, {id: 2} ] → [1, 2]
            ->filter()             // elimina null / '' / 0
            ->unique()             // evita duplicados
            ->values()             // rehace índices
            ->all();               // convierte a array plano

        // 2. Consulta sólo las sesiones válidas de la marca (si aplica el scope)
        $sessions = Session::whereIn('id', $sessionIds)
            ->pluck('id')
            ->all();

        // 3. Sincroniza la relación many-to-many
        $entry->sessions()->sync($sessions);
    }

    public function sales($id)
    {
        $pack = $this->crud->query->findOrFail($id);

        $sales = GroupPack::select('group_packs.*')          // evita ambigüedades si haces select *
            ->join('carts', 'carts.id', '=', 'group_packs.cart_id')
            ->where('group_packs.pack_id', $pack->id)
            ->whereNull('carts.deleted_at')
            ->whereNotNull('carts.confirmation_code')
            ->with('cart.client')                            // eager-load para no “N+1”
            ->orderBy('carts.created_at', 'desc')            // columna existente
            ->get();

        /* genera el array que pide la DataTable */
        $data = $sales->map(function ($sale) {
            $client = $sale->cart->client;

            return [
                'name' => optional($client)->name,
                'surname' => optional($client)->surname,
                'email' => optional($client)->email,
                'code' => $sale->cart->confirmation_code ?? '',
                'seller_type' => $sale->seller_type === User::class
                    ? __('backend.inscription.sold_ticket_office')
                    : __('backend.inscription.sold_web'),
                'created_at' => $sale->cart->created_at->format('Y-m-d H:i'),
            ];
        });

        return view('core.pack.list-inscriptions', compact('pack', 'data'));
    }
}
