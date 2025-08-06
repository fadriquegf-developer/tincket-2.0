@extends(backpack_view('layouts.horizontal'))

@php
    $t = Lang::get('statistics');

    $propsFilters = [
        'filters' => [
            'from' => null,
            'to' => null,
            'breakdown' => 'U',
        ],
        't' => $t,
    ];

    $propsResults = ['t' => $t];
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-4 mt-4 align-items-baseline d-print-none "
        bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">{{ __('statistics.title') }}</h1>
        <p class="ms-2 ml-2 mb-0" id="datatable_info_stack" bp-section="page-subheading">{{ __('statistics.subtitle') }}</p>
    </section>
@endsection

@section('content')
    <div class="row g-3">
        {{-- ▸ FILTROS--}}
        <div id="balance-filters" data-props='@json($propsFilters, JSON_HEX_APOS)'></div>

        {{-- ▸ RESULTADOS --}}
        <div id="balance-results" data-props='@json($propsResults, JSON_HEX_APOS)'></div>
    </div>
@endsection

@push('after_scripts')
    <script src="https://unpkg.com/vue@3.4.21/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.2/dist/axios.min.js"></script>

    <script src="{{ asset('js/vue/statistics/balance.js') }}?v={{ filemtime(public_path('js/vue/statistics/balance.js')) }}"></script>
@endpush