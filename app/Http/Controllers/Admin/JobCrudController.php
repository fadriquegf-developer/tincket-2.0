<?php

namespace App\Http\Controllers\Admin;

use App\Models\Job;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class JobCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class JobCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Job::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/job');
        CRUD::setEntityNameStrings(__('menu.job'), __('menu.jobs'));
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'queue',
            'label' => __('backend.job.queue'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'payload',
            'label' => __('backend.job.payload'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'attempts',
            'label' => __('backend.job.attempts'),
            'type' => 'number',
        ]);

        CRUD::addColumn([
            'name' => 'available_at',
            'label' => __('backend.job.available_at'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => __('backend.job.created_at'),
            'type' => 'datetime',
        ]);
    }

    protected function setupShowOperation()
    {
        CRUD::addColumn([
            'name' => 'queue',
            'label' => __('backend.job.queue'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'payload',
            'label' => __('backend.job.payload'),
            'type' => 'custom_html',
            'value' => function ($entry) {
                $data = json_decode($entry->payload, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return '<code>Error al decodificar el JSON</code>';
                }

                return '<pre style="white-space: pre-wrap; word-break: break-word;">'
                    . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    . '</pre>';
            },
        ]);

        CRUD::addColumn([
            'name' => 'attempts',
            'label' => __('backend.job.attempts'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => __('backend.job.created_at'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'reserved_at',
            'label' => __('backend.job.reserved_at'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'available_at',
            'label' => __('backend.job.available_at'),
            'type' => 'datetime',
        ]);
    }


}
