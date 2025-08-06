<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Event;
use App\Models\SyncValidation;
use Illuminate\Support\Facades\DB;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SyncValidationCrudController extends CrudController
{

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(SyncValidation::class);
        CRUD::setRoute(backpack_url('log-sync'));
        CRUD::setEntityNameStrings('Offline log', 'Offline logs');
        CRUD::denyAllAccess();
        if (auth()->check() && auth()->user()->hasPermissionTo('carts.index')) {
            $this->crud->allowAccess('list');
        }

        $this->crud->query->whereHas('event', function ($query) {
            return $query->ownedByBrand();
        });

        $this->crud->orderBy('updated_at', 'desc');

        $this->crud->removeButton('update');
        $this->crud->denyAccess(['create', 'delete']);

        $this->crud->enableExportButtons();
        $this->crud->enableDetailsRow();
        $this->crud->allowAccess('details_row');
        $this->crud->setDetailsRowView('core.validation.sync-logs-details');
    }

    protected function setupListOperation()
    {
        $this->crud->addColumns([
            [
                'label' => __('backend.session.event'),
                'type' => 'select',
                'name' => 'event_id',
                'entity' => 'event',
                'attribute' => 'name',
                'model' => Event::class,
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('event', function ($q) use ($column, $searchTerm) {
                        $q->where(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
                    });
                }
            ],
            [
                'label' => __('backend.events.createdby'),
                'type' => 'select',
                'name' => 'user_id',
                'entity' => 'user',
                'attribute' => 'email',
                'model' => User::class,
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('user', function ($q) use ($column, $searchTerm) {
                        $q->where(DB::raw('lower(email)'), 'like', '%' . strtolower($searchTerm) . '%');
                    });
                }
            ],
            [
                'name' => 'created_at',
                'label' => __('backend.created_at'),
                'type' => 'date.str'
            ]
        ]);
    }

}
