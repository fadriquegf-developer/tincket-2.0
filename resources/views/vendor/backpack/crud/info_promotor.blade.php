@extends(backpack_view('layouts.horizontal'))

@section('header')
	<section class="header-operation animated fadeIn d-flex mb-4 align-items-baseline d-print-none ms-4" bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">{!! $crud->getSubheading() ?? mb_ucfirst(trans('backpack::crud.preview')).' '.$crud->entity_name !!}</p>
            @if ($crud->hasAccess('list'))
                <p class="ms-2 ml-2 mb-0" bp-section="page-subheading-back-button">
                    <small><a href="{{ url($crud->route) }}" class="font-sm"><i class="la la-angle-double-left"></i> {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
                </p>
            @endif
        </section>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">

        @include('crud::inc.grouped_errors')

        <form method="post" action="/code/info-promotor">
            {!! csrf_field() !!}
            <input type="hidden" name="promotor_id" value="{{ $promotor->id }}">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ __('backend.code.info_promotor') }}</h3>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text" id="nom">
                                    <i class="la la-user-circle-o"></i>
                                </span>
                                <input type="text" class="form-control" value="{{ $promotor->name }}" aria-describedby="nom" disabled>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text" id="fecha_alta">
                                    <i class="la la-calendar"></i>
                                </span>
                                <input type="text" class="form-control" value="{{ $promotor->created_at }}" aria-describedby="fecha_alta" disabled>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text" id="email">
                                    <i class="la la-envelope"></i>
                                </span>
                                <input type="text" class="form-control" value="{{ $promotor->email }}" aria-describedby="email" disabled>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text" id="phone">
                                    <i class="la la-phone"></i>
                                </span>
                                <input type="text" class="form-control" value="{{ $promotor->phone }}" aria-describedby="phone" disabled>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="comment">Comentari</label>
                                <textarea
                                    name="comment"
                                    id="comment"
                                    class="form-control"
                                    rows="10"
                                    placeholder="Comentaris varis sobre el promotor…"
                                >{!! $promotor->comment !!}</textarea>
                            </div>
                        </div>
                    </div>
                </div><!-- /.card-body -->

                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> Guardar
                    </button>
                </div><!-- /.card-footer -->
            </div><!-- /.card -->
        </form>
    </div>
</div>
@endsection
