{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i>
        {{ trans('backpack::base.dashboard') }}</a></li>

@switch($capability = get_brand_capability())
    @case('engine')
        @include('menu.engine')
    @break

    @case('basic')
        @include('menu.basic')
    @break

    @case('promoter')
        @include('menu.promotor')
    @break
@endswitch
