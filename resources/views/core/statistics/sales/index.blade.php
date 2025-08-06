@extends(backpack_view('layouts.horizontal'))

@php
    // ← Asegúrate de tener resources/lang/{locale}/statistics.php
    $t = Lang::get('statistics')['sales'] ?? [];
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-4 mt-4 align-items-baseline">
        <h1 class="text-capitalize mb-0">{{ $t['title'] ?? 'Estadísticas' }}</h1>
        <p class="ms-2 mb-0">{{ $t['subtitle'] ?? 'Ventas' }}</p>
    </section>
@endsection


@section('content')
    <div class="row g-3">
        {{-- ▸ FILTROS --}}
        <div id="sales-filters" data-props='@json(["t" => $t], JSON_HEX_APOS)'></div>

        {{-- ▸ RESULTADOS --}}
        <div id="sales-results" class="col-12" data-props='@json(["t" => $t], JSON_HEX_APOS)'></div>
    </div>
@endsection



@push('after_scripts') {{-- se carga CON defer --}}
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.2/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script>window.appLocale = "{{ app()->getLocale() }}"</script>
    <script src="{{ asset('js/vue/statistics/sales.js') }}?v={{ filemtime(public_path('js/vue/statistics/sales.js')) }}"></script>
@endpush