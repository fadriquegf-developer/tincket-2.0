<?php

namespace App\Http\Controllers\Admin;

use App\Models\GiftCard;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class GiftCardCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(GiftCard::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/gift-card');
        CRUD::setEntityNameStrings(__('backend.menu.gift_card'), __('backend.menu.gift_cards'));

        CRUD::orderBy('created_at', 'DESC');
        CRUD::with(['event', 'inscription']);

        $this->setAccessUsingPermissions();
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'         => 'event',
            'label'        => __('backend.session.event'),
            'type'         => 'relationship',
            'attribute'    => 'name',
            'searchLogic'  => function ($query, $column, $searchTerm) {
                $query->orWhereHas('event', function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($searchTerm).'%']);
                });
            },
        ]);

        CRUD::addColumn([
            'name' => 'code',
            'label' => __('backend.gift_card.code'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name'        => 'claimed',
            'label'       => __('backend.gift_card.claimed'),
            'type'        => 'boolean',
            'value'       => fn ($entry) => (bool) $entry->inscription,
            'options'     => [0 => 'No', 1 => 'Sí'],
            'orderable'   => false,
            'searchLogic' => false,
        ]);


    }

}
