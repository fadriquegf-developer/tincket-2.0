<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class FailedJobCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class FailedJobCrudController extends CrudController
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
        CRUD::setModel(\App\Models\FailedJob::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/failed-job');
        CRUD::setEntityNameStrings(__('menu.failed_job'), __('menu.failed_jobs'));
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
            'name' => 'failed_at',
            'label' => __('backend.failed_job.failed_at'),
            'type' => 'datetime'
        ]);

        CRUD::addColumn([
            'name' => 'function',
            'label' => __('backend.failed_job.function'),
            'type' => 'closure',
            'function' => function ($entry) {
                $json = json_decode($entry->payload);

                return $json->displayName;
            }
        ]);

        CRUD::addColumn([
            'name' => 'id_aux',
            'label' => __('backend.failed_job.id'),
            'type' => 'closure',
            'function' => function ($entry) {
                $json = json_decode($entry->payload);
                $success = preg_match('/;i\:([0-9]*);s\:10\:/', $json->data->command, $match);

                if ($success) {
                    return $match[1];
                }

                return '-';
            }

        ]);

        CRUD::addButtonFromModelFunction('line', 'retry_failed_job', 'retryFailedJobButton','beginning');
    }

    protected function setupShowOperation()
    {
        CRUD::addColumn([
            'name' => 'connection',
            'label' => __('backend.failed_job.connection'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'queue',
            'label' => __('backend.failed_job.queue'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'payload',
            'label' => __('backend.failed_job.payload'),
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
            'name' => 'exception',
            'label' => __('backend.failed_job.exception'),
            'type' => 'custom_html',
            'value' => function ($entry) {
                return '<pre style="white-space: pre-wrap; word-break: break-word; color: #e57373;">'
                    . e($entry->exception)
                    . '</pre>';
            },
        ]);

        CRUD::addColumn([
            'name' => 'failed_at',
            'label' => __('backend.failed_job.failed_at'),
            'type' => 'datetime',
        ]);
    }


    public function retry($id)
    {
        $failedJob = \DB::table('failed_jobs')->find($id);


        \DB::table('jobs')->insert([
            'queue' => $failedJob->queue,
            'payload' => $failedJob->payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        \DB::table('failed_jobs')->where('id', $id)->delete();

        return back();
    }


}
