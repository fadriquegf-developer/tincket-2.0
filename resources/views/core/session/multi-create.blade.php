{{-- resources/views/admin/session/multi-create.blade.php --}}
@extends(backpack_view('blank'))

@section('header')
     <section class="container-fluid">
           <h2>{{ __('backend.multi_session.create_multiple') }}</h2>
     </section>
     @php
           $backUrl = backpack_url('session');
           $backToAll = trans('backpack::crud.back_to_all');
           $entityPlural = trans('backend.menu.sessions');
     @endphp

     {{-- enlace “← Tornar a tots Sessions” --}}
     <a href="{{ $backUrl }}" class=" small d-inline-flex align-items-center mb-3">
           <i class="la la-angle-left la-lg me-1"></i>
           {{ $backToAll }} {{ $entityPlural }}
     </a>
@endsection

@section('content')
     <div id="multiSessionApp" data-store-url="{{ route('session.multi-store') }}"
           data-index-url="{{ backpack_url('session') }}" data-events='@json($events)' data-spaces='@json($spaces)'
           data-tpvs='@json($tpvs)' data-rates='@json($rates)' data-trans='@json(__('backend.multi_session'))'></div>

@endsection

@push('after_scripts')
     <script src="https://unpkg.com/vue@3"></script>
     <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
     <script src="{{ asset('js/vue/multi-session.js') }}"></script>
@endpush