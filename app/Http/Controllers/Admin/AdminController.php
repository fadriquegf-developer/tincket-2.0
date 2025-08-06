<?php

namespace App\Http\Controllers\Admin;

use App\Models\UpdateNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Backpack\CRUD\app\Http\Controllers\AdminController as BackpackAdminController;

class AdminController extends BackpackAdminController
{

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(): View
    {
        $this->data['title'] = trans('backpack::base.dashboard');

        $this->data['history'] = Auth::user()->updateNotifications()->orderBy('id', 'desc')->get();

        return view(backpack_view('dashboard'), $this->data);
    }


    public function develop()
    {
        $this->data['title'] = 'Develop'; //trans('backpack::base.dashboard'); // set the page title

        return view('core.develop.index', $this->data);
    }

    public function configPromotor()
    {
        return view('promoter.config');
    }

    public function storeConfigPromotor() {}
}
