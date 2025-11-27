<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UpdateNotificationRequest;
use Illuminate\Http\Request;
use App\Traits\AllowUsersTrait;
use App\Models\UpdateNotification;
use Illuminate\Support\Facades\Auth;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class UpdateNotificationCrudController extends CrudController
{
    use AllowUsersTrait;

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;


    public function setup()
    {
        $this->isSuperuser();

        CRUD::setModel(UpdateNotification::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/update-notification');
        CRUD::setEntityNameStrings(__('menu.update_notification'), __('menu.update_notifications'));

        $this->crud->query->withoutGlobalScope('user');
        $this->crud->query->withoutGlobalScope('brand');
    }

    protected function setupListOperation(): void
    {
        CRUD::addColumn([
            'name' => 'version',
            'label' => __('backend.notification.version'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'subject',
            'label' => __('backend.notification.subject'),
            'type' => 'text',
            'limit' => 100,
        ]);
    }

    protected function setupCreateOperation(): void
    {
        $this->crud->setValidation(UpdateNotificationRequest::class);

        CRUD::addField([
            'name' => 'version',
            'label' => __('backend.notification.version'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'subject',
            'label' => __('backend.notification.subject'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'content',
            'label' => __('backend.notification.content'),
            'type' => 'wysiwyg',
            'wrapperAttributes' => [
                'class' => 'form-group',
            ],
        ]);

    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }


    public function updateToReadedNotification($id)
    {
        $user_id = Auth::user()->id;

        if (!UpdateNotification::find($id)->users()->find($user_id)) {
            UpdateNotification::find($id)->users()->attach($user_id, ['created_at' => \Carbon\Carbon::now()]);
        }

        return redirect('dashboard');
    }

    public function updateAllToReadedNotification()
    {
        $user_id = Auth::user()->id;

        foreach (UpdateNotification::all() as $notification) {
            if (!$notification->users()->find($user_id)) {
                $notification->users()->attach($user_id, ['created_at' => \Carbon\Carbon::now()]);
            }
        }

        return redirect('dashboard');

    }

}
