@extends(backpack_view('layouts.horizontal'))


@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline d-print-none"
            bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">{{ __('backend.menu.validations') }}</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">
                {!! mb_ucfirst(trans('backpack::crud.preview')) . ' ' . __('backend.menu.validation')  !!}
            </p>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading-back-button">
                <small><a href="{{ url('/validation') }}" class="font-sm"><i class="la la-angle-double-left"></i>
                        {{ trans('backpack::crud.back_to_all') }}
                        <span>{{ __('backend.menu.validations') }}</span></a></small>
            </p>
        </section>
        <a href="javascript: window.print();" class="btn float-right"><i class="la la-print"></i></a>
    </div>
@endsection

@section('content')
    <div class="row mt-2">
        <div class="col-md-8 col-md-offset-2">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        {{ $session->event->name }}
                        @if($session->name)
                            - {{ $session->name }}
                        @endif
                        ({{ $session->starts_on->format('d-m-Y H:i') }})
                    </h3>
                </div>
                <div class="box-body row">
                    <div class="col-md-12">
                        <p>{{ __('backend.validation.you_are_validating') }}</p>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <label class="col-xs-4">{{ __('backend.validation.event') }}</label>
                            <p class="col-xs-6">{{ $session->event->name }}</p>

                            @if($session->name)
                                <label class="col-xs-4">{{ __('backend.validation.session') }}</label>
                                <p class="col-xs-6">{{ $session->name }}</p>
                            @endif

                            <label class="col-xs-4">{{ __('backend.validation.space') }}</label>
                            <p class="col-xs-6">{{ $session->space->name }}</p>

                            <label class="col-xs-4">{{ __('backend.validation.session_starts_on') }}</label>
                            <p class="col-xs-6">{{ $session->starts_on }}</p>

                            <label class="col-xs-4">{{ __('backend.validation.validated') }}</label>
                            <p class="col-xs-6" id="n_validated">{{ $session->count_validated }}</p>
                        </div>
                    </div>
                    <div class="col-md-4 visible-md visible-lg">
                        <img src="{{ asset($session->event->image) }}" alt="{{ $session->event->name }}" width="100%" />
                    </div>
                </div>
            </div>
            <div class="box">
                <div class="box-body row">
                    <div class="col-xs-12">
                        <form id="validator" action="{{ route('validation.check', ['session_id' => $session->id]) }}"
                            method="POST">
                            <div class="input-group">
                                <input name="barcode" type="text" class="form-control" placeholder="Scan barcode">
                                <span class="input-group-btn">
                                    <button class="btn btn-default ms-2"
                                        type="submit">{{ __('backend.validation.validate') }}!</button>
                                </span>
                            </div>
                            {{ csrf_field() }}
                        </form>
                    </div>
                    {{-- <div class="col-xs-12" style="margin-top:20px">
                        <form id="validator" action="{{ route('validation.out', ['session_id' => $session->id]) }}"
                            method="POST">
                            <div class="input-group">
                                <input name="barcode" type="text" class="form-control" placeholder="Scan barcode">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="submit">{{ __('backend.validation.out')
                                        }}!</button>
                                </span>
                            </div>
                            {{ csrf_field() }}
                        </form>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>

@endsection

@push('after_scripts')
    {{-- Modal HTML para validaciones --}}
    <div class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center result-icon">
                    </div>
                    <div class='details'>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script de validación --}}
    <script src="{{ asset('js/crud/inscriptions_validator.js') }}?v={{ time() }}"></script>
    <script>
        $(function () {
            $("form#validator").tincketInscriptionValidator({
                input: 'barcode',
                modalWrapper: 'div.modal'
            });
            $('input[name="barcode"]').focus();
        });
    </script>
@endpush