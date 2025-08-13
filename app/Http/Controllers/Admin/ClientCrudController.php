<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Imports\ClientImport;
use App\Traits\AllowUsersTrait;
use Prologue\Alerts\Facades\Alert;
use App\Exports\ClientsExtraExport;
use App\Traits\CrudPermissionTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ClientRequest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\Pro\Http\Controllers\Operations\BulkTrashOperation;
use Backpack\Pro\Http\Controllers\Operations\CustomViewOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

class ClientCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;
    use CustomViewOperation;
    use BulkTrashOperation;
    use CrudPermissionTrait;
    use AllowUsersTrait;

    public function setup()
    {
        CRUD::setModel(Client::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client');
        CRUD::setEntityNameStrings(__('backend.menu.client'), __('backend.menu.clients'));

        // Activar sistema de permisos
        if (!$this->isSuperuser()) {
            $this->setAccessUsingPermissions();
        }
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('core.client.show');
    }

    protected function setupListOperation()
    {
        //CRUD::denyAccess('bulkTrash'); // No permitir eliminar clientes
        CRUD::denyAccess('bulkDestroy');
        CRUD::enableExportButtons();
        CRUD::addButtonFromView('top', 'import_csv', 'client_import_button', 'end');
        CRUD::addButtonFromModelFunction('top', 'export', 'exportButton', position: 'end');
        CRUD::addButtonFromModelFunction('top', 'newsletter', 'newsletterButton', 'end');

        CRUD::addFilter([
            'name' => 'session',
            'type' => 'select2',
            'label' => trans('backend.client.session')
        ], function () {
            return Session::where('brand_id', get_current_brand()->id)->orderBy('starts_on', 'DESC')->get()->pluck('name_filter', 'id')->toArray();
        }, function ($value) { // if the filter is active
            $this->crud->query = $this->crud->query->whereHas('carts', function ($query) use ($value) {
                $query->whereHas('inscriptions', function ($query) use ($value) {
                    $query->whereHas('session', function ($query) use ($value) {
                        $query->where('id', $value);
                    });
                });
            });
        });

        CRUD::addFilter(
            [
                'name' => 'from_to',
                'label' => __('backend.client.from_to'),
                'type' => 'date_range',
            ],
            false,
            function ($value) {
                $dates = json_decode($value);
                $this->crud->addClause('where', 'created_at', '>=', $dates->from);
                $this->crud->addClause('where', 'created_at', '<=', $dates->to . ' 23:59:59');
            }
        );

        CRUD::addColumn([
            'name' => 'id',
            'label' => __('backend.menu.client') . ' id',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.client.name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'surname',
            'label' => __('backend.client.surname'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => __('backend.client.email'),
            'type' => 'email',
        ]);

        CRUD::addColumn([
            'name' => "num_session",
            'label' => __('backend.client.num_session'),
            'type' => "numeric"
        ]);

        CRUD::addColumn([
            'name' => 'newsletter',
            'label' => __('backend.client.newsletter'),
            'type' => 'boolean',
            'options' => [0 => 'No', 1 => 'Si']
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => __('backend.client.created_at'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'phone',
            'label' => __('backend.client.phone'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'mobile_phone',
            'label' => __('backend.client.mobile_phone'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'locale',
            'label' => __('backend.client.locale'),
            'type' => 'select_from_array',
            'options' => ['ca' => __('backend.client.ca'), 'es' => __('backend.client.es'), 'gl' => __('backend.client.gl')],
        ]);

        if (get_brand_capability() === 'engine') {
            CRUD::addColumn([
                'name' => 'brand_id',
                'label' => __('backend.client.brand_id'),
                'type' => 'select',
                'entity' => 'brand',
                'attribute' => 'name',
                'model' => \App\Models\Brand::class,
            ]);
        }
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ClientRequest::class);

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.client.name'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6 required']
        ]);

        CRUD::addField([
            'name' => 'surname',
            'label' => __('backend.client.surname'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6 required']
        ]);

        CRUD::addField([
            'name' => 'email',
            'label' => __('backend.client.email'),
            'type' => 'email',
            'wrapper' => ['class' => 'form-group col-md-6 required']
        ]);

        CRUD::addField([
            'name' => 'locale',
            'label' => __('backend.client.locale'),
            'type' => 'select_from_array',
            'options' => ['es' => 'Español', 'ca' => 'Català', 'gl' => 'Galego'],
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-md-6 required']
        ]);

        CRUD::addField([
            'name' => 'phone',
            'label' => __('backend.client.phone'),
            'type' => 'phone',
            'config' => [
                'initialCountry' => 'es',
            ],
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'mobile_phone',
            'label' => __('backend.client.mobile_phone'),
            'type' => 'phone',
            'config' => [
                'initialCountry' => 'ES',
            ],
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'address',
            'label' => __('backend.client.address'),
            'type' => 'address',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'postal_code',
            'label' => __('backend.client.postal_code'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'province',
            'label' => __('backend.client.province'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'city',
            'label' => __('backend.client.city'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'dni',
            'label' => 'DNI',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'date_birth',
            'label' => __('backend.client.date_birth'),
            'type' => 'date',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'password',
            'label' => __('backend.user.password'),
            'type' => 'password',
            'wrapper' => [
                'class' => 'form-group col-md-6' .
                    ($this->crud->getCurrentOperation() === 'create' ? ' required' : '')
            ],
        ]);

        CRUD::addField([
            'name' => 'password_confirmation',
            'label' => __('backend.user.password_confirmation'),
            'type' => 'password',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'info_password',
            'label' => __('backend.user.info_password'),
            'type' => 'custom_html',
            'value' => '<div class="alert alert-info">' . __('backend.user.info_password') . '</div>'
        ]);

        CRUD::addField([
            'name' => 'newsletter',
            'label' => 'Newsletter',
            'type' => 'switch',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function import()
    {
        // 1) Validación básica del archivo
        $validator = Validator::make(request()->all(), [
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            Alert::error(__('backend.alert.csv'))->flash();
            return back();
        }

        $file = request()->file('csv');

        // 2) Validación rápida de cabeceras (opcional ↴ ajusta el array si cambias campos)
        $expected = [
            'name',
            'surname',
            'email',
            'phone',
            'mobile_phone',
            'locale',
            'date_birth',
            'dni',
            'province',
            'city',
            'address',
            'postal_code',
            'newsletter'
        ];

        $csv = new \SplFileObject($file->getRealPath());
        $csv->setFlags(\SplFileObject::READ_CSV);
        $header = array_map('mb_strtolower', $csv->fgetcsv());

        if ($missing = array_diff($expected, $header)) {
            Alert::error(
                __('backend.alert.import_client.missing_columns') . ': ' . implode(', ', $missing)
            )->flash();
            return back();
        }

        // 3) Importación inmediata
        try {
            Excel::import(
                new ClientImport(get_current_brand()->id),
                $file,
                null,
                \Maatwebsite\Excel\Excel::CSV
            );

            Alert::success(__('backend.alert.import_client.success'))->flash();
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // filas con errores de validación
            Alert::error(__('backend.alert.import_client.error_validation', [
                'rows' => count($e->failures())
            ]))->flash();
        } catch (\Throwable $e) {
            \Log::error('Error al importar clientes', ['exception' => $e]);
            Alert::error(__('backend.alert.import_client.error'))->flash();
        }

        return back();
    }

    public function export()
    {
        // No cambies memory_limit; si necesitas más filas usa cola (ShouldQueue)
        return Excel::download(new ClientsExtraExport, 'clients_extra.xlsx');
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
