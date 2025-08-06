<?php

namespace App\Http\Controllers\Admin;

use DB;
use App\Models\Censu;
use App\Imports\CodeImport;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use Maatwebsite\Excel\Facades\Excel;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class CensuCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(Censu::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/censu');
        CRUD::setEntityNameStrings(__('backend.menu.censu'), __('backend.menu.census'));

        CRUD::orderBy('name');
        CRUD::enableReorder('name', 1);
        $this->setAccessUsingPermissions();

    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.rate.name'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
            }
        ]);

        CRUD::addColumn([
            'name' => 'code',
            'label' => __('backend.rate.code'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere(DB::raw('lower(code)'), 'like', '%' . strtolower($searchTerm) . '%');
            }
        ]);

        $this->crud->addButtonFromView('top', 'import_codes', 'import_census_codes', 'end');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(\App\Http\Requests\CensuCrudRequest::class);
        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.censu.name'),
            'wrapperAttributes' => [
                'class' => 'col-lg-6 form-group'
            ],
        ]);

        CRUD::addField([
            'name' => 'code',
            'label' => __('backend.censu.code'),
            'wrapperAttributes' => [
                'class' => 'col-lg-6 form-group'
            ],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Importamos una lista de codigos enlazados a la session
     */
    public function importCodes()
    {

        $file    = request()->file('csv');
        $brand   = get_current_brand();

        if (!$file || !$brand) {
            Alert::error('Falta el archivo CSV o la marca.')->flash();
            return back();
        }

        $brandId = $brand->id;
        $import  = new CodeImport($brandId);

        DB::transaction(function () use ($file, $brandId, $import) {

            // 1) Vacía los códigos de la marca
            Censu::flushEventListeners();
            Censu::where('brand_id', $brandId)->delete();

            // 2) Importa (las filas sin code quedan “skippeadas”)
            Excel::import($import, $file);      // CSV, delimitador ';' por config
        });

        /* 3) Pop-up según resultado */
        $skipped = $import->failures()->count();

        if ($skipped) {
            Alert::warning(
                "Importación completada: se ignoraron {$skipped} fila(s) porque «code» estaba vacío."
            )->flash();
        } else {
            Alert::success('¡Todos los códigos se importaron correctamente!')->flash();
        }

        return back();
    }
}
