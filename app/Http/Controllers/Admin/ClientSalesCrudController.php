<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use Illuminate\Http\Request;
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
        CRUD::setEntityNameStrings(__('backend.menu.client'), __('backend.menu.clients'));
        $this->setAccessUsingPermissions();

        CRUD::enableExportButtons();

        $this->crud->query->select([
            'clients.id',
            'clients.name',
            'clients.surname',
            'clients.email',
            'clients.postal_code',
            'clients.city',
            \DB::raw('COUNT(CASE WHEN group_pack_id IS NULL THEN 1 END) AS n_inscriptions'),
            \DB::raw('COUNT(CASE WHEN group_pack_id IS NOT NULL THEN 1 END) AS n_inscriptions_packs'),
            \DB::raw('COUNT(DISTINCT group_pack_id) AS n_packs'),
            \DB::raw('COUNT(*) AS total'),
        ])->join('carts', 'clients.id', '=', 'carts.client_id')
            ->join('inscriptions', 'inscriptions.cart_id', '=', 'carts.id')
            ->whereNotNull('carts.confirmation_code')
            ->groupBy(
                'clients.id',
                'clients.name',
                'clients.surname',
                'clients.email',
                'clients.postal_code',
                'clients.city'
            );
    }

    public function search(Request $request)
    {
        CRUD::hasAccessOrFail('list');

        if ($request->input('search') && $request->input('search')['value']) {
            CRUD::applySearchTerm($request->input('search')['value']);
        }

        $countQuery = clone $this->crud->query;
        $cAux = $countQuery->select(\DB::raw('COUNT(*) OVER () as n'))->first();
        $totalRows = $filteredRows = $cAux->n ?? 0;

        if ($request->input('start')) {
            CRUD::skip($request->input('start'));
        }

        if ($request->input('length')) {
            CRUD::take($request->input('length'));
        }

        if ($request->input('order')) {
            $column_number = $request->input('order')[0]['column'];
            if ($this->crud->details_row) {
                $column_number = $column_number - 1;
            }
            $column_direction = $request->input('order')[0]['dir'];
            $column = CRUD::findColumnById($column_number);

            if ($column['tableColumn']) {
                $this->crud->query->getQuery()->orders = null;
                CRUD::orderBy($column['name'], $column_direction);
            }
        } else {
            $this->crud->query->orderBy('total', 'DESC');
        }

        $entries = CRUD::getEntries();

        return CRUD::getEntriesAsJsonForDatatables($entries, $totalRows, $filteredRows);
    }

    protected function setupListOperation()
    {
       CRUD::addFilter(
            [ // daterange filter
                'type' => 'date_range',
                'name' => 'from_to',
                'label' => __('backend.statistics.client-sales.filter_date')
            ],
            false,
            function ($value) { // if the filter is active, apply these constraints
                $dates = json_decode($value);
               CRUD::addClause('where', 'inscriptions.created_at', '>=', $dates->from);
               CRUD::addClause('where', 'inscriptions.created_at', '<=', $dates->to . ' 23:59:59');
            }
        );

        CRUD::addColumn([
            'name' => 'id',
            'label' =>__('backend.menu.client') . ' Id',
            'type' => 'view',
            'view' => 'core.statistics.client-sales.link',
        ]);
       CRUD::addColumn(['name' => 'email', 'label' => __('backend.client.email')]);
       CRUD::addColumn(['name' => 'surname', 'label' => __('backend.client.surname')]);
       CRUD::addColumn(['name' => 'name', 'label' => __('backend.client.name')]);
       CRUD::addColumn(['name' => 'postal_code', 'label' => __('backend.client.postal_code')]);
       CRUD::addColumn(['name' => 'city', 'label' => __('backend.client.city')]);
       CRUD::addColumn(['name' => 'n_inscriptions', 'label' => __('backend.statistics.client-sales.n_inscriptions')]);
       CRUD::addColumn(['name' => 'n_inscriptions_packs', 'label' => __('backend.statistics.client-sales.n_inscriptions_packs')]);
       CRUD::addColumn(['name' => 'n_packs', 'label' => __('backend.statistics.client-sales.n_packs')]);
       CRUD::addColumn(['name' => 'total', 'label' => __('backend.statistics.client-sales.total')]);
    }

    /**
     * Script to migrate form_field to client attributes
     * IMPORTANT: is using IDs from \App\FormField if you want no used for other brands is mandatory to changes it
     */
    protected function migrateFormFields()
    {
        dd('disabled');
        $clients = Client::ownedByBrand()->with('answers')->get();
        foreach ($clients as $client) {
            $answers = $client->answers;
            foreach ($answers as $a) {
                if ($client->city === null && $a->field_id === 14 && $a->answer !== null) {
                    $client->city = $a->answer;
                }

                if ($client->postal_code === null && $a->field_id === 15 && $a->answer !== null) {
                    $client->postal_code = $a->answer;
                }

                if ($client->address === null && $a->field_id === 16 && $a->answer !== null) {
                    $client->address = $a->answer;
                }
            }

            $client->save();
        }

        dd('Tot ok');
    }
}
