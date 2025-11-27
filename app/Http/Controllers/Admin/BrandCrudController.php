<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\Capability;
use App\Http\Requests\BrandRequest;
use Backpack\CRUD\app\Library\Widget;
use App\Services\BrandCreationService;
use App\Traits\AllowUsersTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\BrandCreationException;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Prologue\Alerts\Facades\Alert;

class BrandCrudController extends CrudController
{
    use AllowUsersTrait;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    protected BrandCreationService $brandCreator;

    public function __construct()
    {
        parent::__construct();
        $this->brandCreator = app(BrandCreationService::class);
    }

    public function setup()
    {
        /* Acceso exclusivo para superusuarios */
        $this->isSuperuser();

        CRUD::setModel(Brand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/brand');
        CRUD::setEntityNameStrings(__('menu.brand'), __('menu.brands'));

        // Solo permitir eliminar brands sin relaciones
        CRUD::operation('delete', function () {
            CRUD::addClause('doesntHave', 'applications');
            CRUD::addClause('doesntHave', 'users');
            CRUD::addClause('doesntHave', 'events');
        });
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.brand.name'),
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'code_name',
            'label' => __('backend.brand.code_name'),
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'allowed_host',
            'label' => __('backend.brand.allowed_host'),
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'capability',
            'label' => __('backend.brand.capability'),
            'type' => 'relationship',
            'attribute' => 'name',
        ]);

        CRUD::addColumn([
            'name' => 'parent',
            'label' => __('backend.brand.parent_id_list'),
            'type' => 'relationship',
            'attribute' => 'name',
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'label' => __('backend.brand.status'),
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->deleted_at) {
                    return '<span class="badge badge-danger">Inactivo</span>';
                }
                return '<span class="badge badge-success">Activo</span>';
            },
            'escaped' => false
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(BrandRequest::class);
        CRUD::setOperationSetting('showTranslationNotice', false);

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.brand.name'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'code_name',
            'label' => __('backend.brand.code_name'),
            'type' => 'text',
            'hint' => __('backend.brand.code_name_hint'),
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'allowed_host',
            'label' => __('backend.brand.allowed_host'),
            'type' => 'text',
            'hint' => 'Dominio sin https:// (ej: marca.yesweticket.com)',
            'wrapper' => [
                'class' => 'form-group col-md-12'
            ]
        ]);

        CRUD::addField([
            'name' => 'capability_id',
            'label' => __('menu.capability'),
            'type' => 'select',
            'entity' => 'capability',
            'model' => Capability::class,
            'attribute' => 'name',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'parent_id',
            'label' => __('backend.brand.parent_id'),
            'type' => 'select',
            'entity' => 'parent',
            'model' => Brand::class,
            'attribute' => 'name',
            'options' => function ($query) {
                return $query->whereHas('capability', function ($q) {
                    $q->where('code_name', 'basic');
                })->get();
            },
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'enable_tpv',
            'label' => __('backend.brand.enable_tpv'),
            'type' => 'checkbox',
            'default' => true,
            'fake' => true,
            'wrapper' => [
                'class' => 'form-group col-md-12'
            ]
        ]);

        // Cargar JS personalizado solo en el formulario de Brand
        Widget::add()->type('script')->content('/assets/js/admin/forms/brand.js');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        $brand = $this->crud->getCurrentEntry();

        if ($brand) {
            // Hacer el campo capability_id de solo lectura y no requerido
            CRUD::modifyField('capability_id', [
                'attributes' => ['disabled' => 'disabled'],
                'hint' => 'No se puede cambiar la capability de una marca existente',
                'value' => $brand->capability_id, // Asegurar que muestre el valor actual
            ]);

            // Si tiene relaciones, añadir más restricciones visuales si es necesario
            if ($brand->hasRelations()) {
                CRUD::modifyField('code_name', [
                    'attributes' => ['readonly' => 'readonly'],
                    'hint' => 'No se puede cambiar el código de una marca con datos asociados'
                ]);
            }
        }
    }

    /**
     * Store a newly created resource in the database.
     * 
     * @param BrandRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BrandRequest $request)
    {
        try {
            // Validar datos adicionales
            $validated = $this->validateStoreRequest($request);

            // Crear brand usando transacción
            $brand = DB::transaction(function () use ($validated) {
                // Preparar el servicio según configuración
                if ($validated['enable_tpv'] ?? false) {
                    $this->brandCreator->withJavajanTpv();
                } else {
                    $this->brandCreator->withoutJavajanTpv();
                }

                // Crear la marca
                $brand = $this->brandCreator->create($validated);

                if (!$brand) {
                    throw new BrandCreationException('No se pudo crear la marca');
                }

                

                return $brand;
            });

            // Crear subdominio si es necesario
            if (!empty($brand->allowed_host)) {
                $this->attemptSubdomainCreation($brand);
            }

            Alert::success('Marca creada exitosamente')->flash();

            return redirect($this->crud->route);
        } catch (BrandCreationException $e) {
            Log::error('Brand creation failed', [
                'error' => $e->getMessage(),
                'data' => $e->getErrorData(),
                'user_id' => auth()->id()
            ]);

            Alert::error('Error al crear la marca: ' . $e->getMessage())->flash();

            return redirect()->back()->withInput();
        } catch (\Exception $e) {
            Log::critical('Unexpected error creating brand', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            Alert::error('Error inesperado. Por favor, contacte al administrador.')->flash();

            return redirect()->back()->withInput();
        }
    }

    /**
     * Update the specified resource in the database.
     * 
     * @param BrandRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(BrandRequest $request)
    {
        try {
            $brand = $this->crud->getCurrentEntry();

            // Validar que no se cambien campos críticos si tiene relaciones
            if ($brand->hasRelations()) {
                $this->validateUpdateRestrictions($request, $brand);
            }

            // Usar el trait update pero con manejo de errores
            $response = $this->traitUpdate($request);

            return $response;
        } catch (\Exception $e) {
            Log::error('Brand update failed', [
                'brand_id' => $brand->id ?? null,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            Alert::error('Error al actualizar la marca: ' . $e->getMessage())->flash();

            return redirect()->back()->withInput();
        }
    }

    /**
     * Valida datos adicionales para store
     */
    protected function validateStoreRequest(BrandRequest $request): array
    {
        $validated = $request->validated();

        // Agregar campos fake/adicionales
        $validated['enable_tpv'] = $request->input('enable_tpv', false);

        // Si es promotor, verificar marca padre
        if ($validated['capability_id'] == 3 && empty($validated['parent_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => ['Las marcas promotor requieren una marca padre']
            ]);
        }

        return $validated;
    }

    /**
     * Valida restricciones de actualización
     */
    protected function validateUpdateRestrictions($request, Brand $brand): void
    {
        // No permitir cambiar code_name si tiene datos
        if ($request->has('code_name') && $request->code_name !== $brand->code_name) {
            if ($brand->events()->exists() || $brand->applications()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'code_name' => ['No se puede cambiar el código de una marca con datos asociados']
                ]);
            }
        }

        // Solo validar capability si viene en el request (no debería venir al estar disabled)
        if ($request->has('capability_id') && $request->capability_id != $brand->capability_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'capability_id' => ['No se puede cambiar la capability de una marca existente']
            ]);
        }
    }

    /**
     * Intenta crear subdominio para la marca
     */
    protected function attemptSubdomainCreation(Brand $brand): void
    {
        try {
            // Extraer subdomain del allowed_host
            $parts = explode('.', $brand->allowed_host);
            if (count($parts) > 2) {
                $subdomain = $parts[0];

                // Llamar al servicio de creación de subdominios
                $controller = app(\App\Http\Controllers\Api\v1\PartnerApiController::class);
                $result = $controller->createSubdomain($subdomain);

                if (!$result) {
                    Log::warning('Could not create subdomain for brand', [
                        'brand_id' => $brand->id,
                        'subdomain' => $subdomain
                    ]);

                    Alert::warning('La marca se creó pero el subdominio debe configurarse manualmente')->flash();
                }
            }
        } catch (\Exception $e) {
            Log::error('Subdomain creation failed', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage()
            ]);

            Alert::warning('La marca se creó pero el subdominio debe configurarse manualmente')->flash();
        }
    }
}
