@extends('backpack::layout')

@section('header')
<section class="content-header">
    <h1>
        {{ trans('tincket/backend.inscription.preview') }}
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url(config('backpack.base.route_prefix'), 'dashboard') }}">{{ trans('backpack::crud.admin') }}</a></li>
        <li><a href="{{ url('/inscription') }}" class="text-capitalize">{{ trans('tincket/backend.inscription.inscriptions') }}</a></li>
        <li class="active">{{ trans('backpack::crud.preview') }}</li>
    </ol>
</section>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <form method="post" action="{{ $url }}">
            <div class="box">

                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('backpack::crud.add_a_new') }}</h3>
                </div>
                <div class="box-body row display-flex-wrap" style="display: flex; flex-wrap: wrap;">
                    <input type="hidden" name="url" value="{{ $pdf_url }}">
                    @foreach($params as $param => $value)
                    <input type="hidden" name="{{ $param }}" value="{{ $value }}">
                    @endforeach
                </div><!-- /.box-body -->
                <div class="box-footer">

                    <button type="submit" class="btn btn-primary">Submit</button>

                </div><!-- /.box-footer-->

            </div><!-- /.box -->
        </form>
    </div>
</div>
@endsection