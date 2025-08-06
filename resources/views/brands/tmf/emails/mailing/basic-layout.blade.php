@extends('brands.tmf.emails.layout')
@php($brand = $mailing->brand)

@section('content')
{!! $mailing->content !!}
@endsection

@section('extra_content')
@include('core.emails.mailing.embedded_extra_content')
@endsection
