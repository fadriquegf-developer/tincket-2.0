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
        CRUD::setEntityNameStrings(__('backend.menu.client'), __('backend.menu.clients'));
        $this->setAccessUsingPermissions();

        CRUD::enableExportButtons();

        // ───────────────────────────────
        // Subconsulta: inscripciones agregadas por carrito (rápido de sumar por cliente)
        // ───────────────────────────────
        $insPerCart = DB::table('inscriptions')
            ->selectRaw('
                cart_id,
                SUM(CASE WHEN group_pack_id IS NULL  THEN 1 ELSE 0 END) AS n_ins,
                SUM(CASE WHEN group_pack_id IS NOT NULL THEN 1 ELSE 0 END) AS n_ins_packs,
                COUNT(DISTINCT group_pack_id) AS n_packs,
                COUNT(*) AS total
            ')
            ->whereNull('deleted_at')
            ->groupBy('cart_id');

        $q = $this->crud->query;

        // Query base: cliente → carritos confirmados → agregados por carrito
        $q->from('clients')
            ->join('carts', 'clients.id', '=', 'carts.client_id')
            ->join(DB::raw('(' . $insPerCart->toSql() . ') as ic'), 'ic.cart_id', '=', 'carts.id')
            ->mergeBindings($insPerCart)
            ->whereNotNull('carts.confirmation_code')
            ->whereNull('carts.deleted_at')
            // SELECT finales (todo sale de la agregación por carrito)
            ->select([
                'clients.id',
                'clients.name',
                'clients.surname',
                'clients.email',
                'clients.postal_code',
                'clients.city',
                DB::raw('SUM(ic.n_ins) AS n_inscriptions'),
                DB::raw('SUM(ic.n_ins_packs) AS n_inscriptions_packs'),
                DB::raw('SUM(ic.n_packs) AS n_packs'),
                DB::raw('SUM(ic.total) AS total'),
            ])
            ->groupBy(
                'clients.id',
                'clients.name',
                'clients.surname',
                'clients.email',
                'clients.postal_code',
                'clients.city'
            );
    }

    /**
     * DataTables server-side con optimizaciones:
     * - búsqueda limitada a columnas indexadas
     * - conteo barato por DISTINCT client_id
     * - límite de página
     */
    public function search(Request $request)
    {
        CRUD::hasAccessOrFail('list');

        // ─ Búsqueda: solo en columnas indexables
        if ($term = trim((string) $request->input('search.value'))) {
            $this->crud->query->where(function ($q) use ($term) {
                $q->where('clients.email', 'like', "%{$term}%")
                    ->orWhere('clients.surname', 'like', "%{$term}%")
                    ->orWhere('clients.name', 'like', "%{$term}%");
            });
        }

        // ─ Conteo barato: DISTINCT clients.id sin GROUP/ORDER
        $countQ = clone $this->crud->query;
        $countQ->getQuery()->orders = null;
        $countQ->getQuery()->groups = null;

        $totalRows = $filteredRows = (int) DB::query()
            ->fromSub(
                $countQ->select('clients.id')->distinct(),
                't'
            )
            ->selectRaw('COUNT(*) AS n')
            ->value('n');

        // ─ Paginación con tope
        if ($request->filled('start')) {
            CRUD::skip((int) $request->input('start'));
        }
        $length = (int) $request->input('length', 50);
        CRUD::take(min(max($length, 1), 1000));

        // ─ Orden
        if ($request->input('order')) {
            $colNo = (int) $request->input('order.0.column');
            $dir = $request->input('order.0.dir', 'asc');
            $col = CRUD::findColumnById($colNo);
            if (($col['tableColumn'] ?? false)) {
                $this->crud->query->getQuery()->orders = null;
                CRUD::orderBy($col['name'], $dir);
            }
        } else {
            $this->crud->query->orderBy('total', 'DESC');
        }

        $entries = CRUD::getEntries();

        return CRUD::getEntriesAsJsonForDatatables($entries, $totalRows, $filteredRows);
    }

    /**
     * Listado + filtro por rango de FECHA DE VENTA (paid_at).
     * Implementado con EXISTS sobre payments para aprovechar índice.
     */
    protected function setupListOperation()
    {
        CRUD::addFilter(
            [
                'type'  => 'date_range',
                'name'  => 'from_to',
                'label' => __('backend.statistics.client-sales.filter_date'),
            ],
            false,
            function ($value) {
                $d = json_decode($value);
                if (!$d) {
                    return;
                }

                $from = !empty($d->from) ? ($d->from . ' 00:00:00') : null;
                $to   = !empty($d->to)   ? ($d->to  . ' 23:59:59') : null;

                CRUD::addClause(function ($q) use ($from, $to) {
                    $q->whereExists(function ($qq) use ($from, $to) {
                        $qq->from('payments as p')
                            ->selectRaw('1')
                            ->whereColumn('p.cart_id', 'carts.id')
                            ->whereNull('p.deleted_at');

                        if ($from) {
                            $qq->where('p.paid_at', '>=', $from);
                        }
                        if ($to) {
                            $qq->where('p.paid_at', '<=', $to);
                        }
                    });
                });
            }
        );

        CRUD::addColumn([
            'name'  => 'id',
            'label' => __('backend.menu.client') . ' Id',
            'type'  => 'view',
            'view'  => 'core.statistics.client-sales.link',
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
}
