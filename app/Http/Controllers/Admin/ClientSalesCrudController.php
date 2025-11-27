<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ClientSalesCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        CRUD::setModel(Client::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/statistics/client-sales');
        CRUD::setEntityNameStrings(__('menu.client'), __('menu.clients'));
        $this->setAccessUsingPermissions();

        CRUD::enableExportButtons();

        // Validación de contexto de brand
        $currentBrand = get_current_brand();
        if (!$currentBrand) {
            abort(403, 'No brand context available');
        }

        // Validación de capability
        if (!in_array($currentBrand->capability->code_name, ['basic', 'promoter', 'engine'])) {
            abort(403, 'This feature is not available for your brand capability');
        }
    }

    protected function setupListOperation()
    {
        $currentBrand = get_current_brand();
        if (!$currentBrand) {
            abort(403, 'No brand context');
        }

        // Modificar query base con una aproximación más simple
        $this->crud->query = Client::query()
            ->select([
                'clients.id',
                'clients.name',
                'clients.surname',
                'clients.email',
                'clients.postal_code',
                'clients.city',
                DB::raw('(
                    SELECT COUNT(DISTINCT i.id) 
                    FROM inscriptions i
                    JOIN carts c ON i.cart_id = c.id
                    WHERE c.client_id = clients.id 
                    AND c.confirmation_code IS NOT NULL
                    AND c.deleted_at IS NULL
                    AND i.deleted_at IS NULL
                    AND i.group_pack_id IS NULL
                ) as n_inscriptions'),
                DB::raw('(
                    SELECT COUNT(DISTINCT i.id) 
                    FROM inscriptions i
                    JOIN carts c ON i.cart_id = c.id
                    WHERE c.client_id = clients.id 
                    AND c.confirmation_code IS NOT NULL
                    AND c.deleted_at IS NULL
                    AND i.deleted_at IS NULL
                    AND i.group_pack_id IS NOT NULL
                ) as n_inscriptions_packs'),
                DB::raw('(
                    SELECT COUNT(DISTINCT i.group_pack_id) 
                    FROM inscriptions i
                    JOIN carts c ON i.cart_id = c.id
                    WHERE c.client_id = clients.id 
                    AND c.confirmation_code IS NOT NULL
                    AND c.deleted_at IS NULL
                    AND i.deleted_at IS NULL
                    AND i.group_pack_id IS NOT NULL
                ) as n_packs'),
                DB::raw('(
                    SELECT COUNT(DISTINCT i.id) 
                    FROM inscriptions i
                    JOIN carts c ON i.cart_id = c.id
                    WHERE c.client_id = clients.id 
                    AND c.confirmation_code IS NOT NULL
                    AND c.deleted_at IS NULL
                    AND i.deleted_at IS NULL
                ) as total')
            ])
            ->where('clients.brand_id', $currentBrand->id)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('carts')
                    ->whereColumn('carts.client_id', 'clients.id')
                    ->whereNotNull('carts.confirmation_code')
                    ->whereNull('carts.deleted_at');
            });

        // Si es promotor, incluir brands hijos
        if ($currentBrand->capability->code_name === 'promoter') {
            $childBrandIds = $currentBrand->children->pluck('id')->toArray();
            if (!empty($childBrandIds)) {
                $this->crud->query->orWhereIn('clients.brand_id', $childBrandIds);
            }
        }

        // Filtro de fecha
        CRUD::addFilter(
            [
                'type'  => 'date_range',
                'name'  => 'from_to',
                'label' => __('backend.statistics.client-sales.filter_date'),
            ],
            false,
            function ($value) {
                try {
                    $d = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    return;
                }

                if (!is_array($d) || (!isset($d['from']) && !isset($d['to']))) {
                    return;
                }

                $from = !empty($d['from']) ? $d['from'] . ' 00:00:00' : null;
                $to = !empty($d['to']) ? $d['to'] . ' 23:59:59' : null;

                if ($from || $to) {
                    CRUD::addClause(function ($q) use ($from, $to) {
                        $q->whereExists(function ($qq) use ($from, $to) {
                            $qq->select(DB::raw(1))
                                ->from('carts')
                                ->join('payments', 'payments.cart_id', '=', 'carts.id')
                                ->whereColumn('carts.client_id', 'clients.id')
                                ->whereNotNull('payments.paid_at')
                                ->whereNull('payments.deleted_at');

                            if ($from) {
                                $qq->where('payments.paid_at', '>=', $from);
                            }
                            if ($to) {
                                $qq->where('payments.paid_at', '<=', $to);
                            }
                        });
                    });
                }
            }
        );

        // Columnas
        CRUD::addColumn([
            'name'  => 'id',
            'label' => __('menu.client') . ' Id',
            'type'  => 'view',
            'view'  => 'core.statistics.client-sales.link',
            'searchLogic' => false,
            'orderable' => true,
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => __('backend.client.email'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('clients.email', 'like', '%' . $searchTerm . '%');
            }
        ]);

        CRUD::addColumn([
            'name' => 'surname',
            'label' => __('backend.client.surname'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('clients.surname', 'like', '%' . $searchTerm . '%');
            }
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.client.name'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('clients.name', 'like', '%' . $searchTerm . '%');
            }
        ]);

        CRUD::addColumn([
            'name' => 'postal_code',
            'label' => __('backend.client.postal_code'),
            'searchLogic' => false
        ]);

        CRUD::addColumn([
            'name' => 'city',
            'label' => __('backend.client.city'),
            'searchLogic' => false
        ]);

        CRUD::addColumn([
            'name' => 'n_inscriptions',
            'label' => __('backend.statistics.client-sales.n_inscriptions'),
            'type' => 'number',
            'searchLogic' => false,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->orderBy('n_inscriptions', $columnDirection);
            }
        ]);

        CRUD::addColumn([
            'name' => 'n_inscriptions_packs',
            'label' => __('backend.statistics.client-sales.n_inscriptions_packs'),
            'type' => 'number',
            'searchLogic' => false,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->orderBy('n_inscriptions_packs', $columnDirection);
            }
        ]);

        CRUD::addColumn([
            'name' => 'n_packs',
            'label' => __('backend.statistics.client-sales.n_packs'),
            'type' => 'number',
            'searchLogic' => false,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->orderBy('n_packs', $columnDirection);
            }
        ]);

        CRUD::addColumn([
            'name' => 'total',
            'label' => __('backend.statistics.client-sales.total'),
            'type' => 'number',
            'searchLogic' => false,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->orderBy('total', $columnDirection);
            }
        ]);

        // Configuración de DataTables
        $this->crud->setDefaultPageLength(25);
        $this->crud->setPageLengthMenu([[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']]);

        // Ordenamiento por defecto
        $this->crud->orderBy('total', 'desc');
    }
}
