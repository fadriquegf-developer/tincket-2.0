<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StatisticsController extends Controller
{
    /* public function __construct()
    {
        $this->middleware('permission:manage_statistics');
    } */

    public function indexSales(Request $request)
    {
        $brand = get_current_brand();

        return view('core.statistics.sales.index', ['brand' => $brand]);
    }

    public function indexBalance(Request $request)
    {
        return view('core.statistics.balance.index');
    }

}
