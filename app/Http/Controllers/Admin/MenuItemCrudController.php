<?php

namespace App\Http\Controllers\Admin;

use App\Models\MenuItem;
use App\Models\Page;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class MenuItemCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ReorderOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(MenuItem::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/menu-item');
        CRUD::setEntityNameStrings(__('backend.menu.menu_item'), __('backend.menu.menu_items'));

        $this->setAccessUsingPermissions();
        CRUD::enableReorder('name', 3);
        CRUD::allowAccess('reorder');

    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.menu_item.label'),
        ]);

        CRUD::addColumn([
            'label' => __('backend.menu_item.parent'),
            'type' => 'select',
            'name' => 'parent_id',
            'entity' => 'parent',
            'attribute' => 'name',
            'model' => MenuItem::class,
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('backend.menu_item.label'),
        ]);

        CRUD::addField([
            'label' => __('backend.menu_item.parent'),
            'type' => 'select_from_builder',
            'name' => 'parent_id',
            'entity' => 'parent',
            'attribute' => 'name',
            'model' => MenuItem::class,
            'builder' => fn () => MenuItem::whereNull('parent_id')->orderBy('lft'),
        ]);

        CRUD::addField([
            'name' => 'type,link,page_id',
            'label' => __('backend.menu_item.type'),
            'type' => 'page_or_link',
            'pages' => Page::get(),
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
