@extends('crud::show')

@section('header')
    <section class="content-header">
        <h1 class="mx-3">
            <span class="badge bg-danger me-2">ELIMINADO</span>
            {{ trans('backpack::crud.preview') }}
            <span>{{ $crud->entity_name }} {{ $entry->confirmation_code }}</span>
        </h1>
    </section>
@endsection

@section('content')
    {{-- Volver al listado de eliminados --}}
    <a href="{{ url($crud->route . '/view/trash-carts') }}" class="mb-2">
        <i class="la la-angle-double-left mb-4"></i>
        Volver a carritos eliminados
    </a>

    {{-- Solo información, sin acciones --}}
    <div class="alert alert-info mb-3">
        <i class="la la-info-circle"></i>
        Este carrito fue eliminado el {{ $entry->deleted_at->format('d/m/Y H:i') }}
        @if ($entry->deleted_user_id)
            por {{ $entry->deletedUser->name ?? 'Usuario desconocido' }}
        @endif
    </div>

    {{-- Inscripciones (solo lectura) --}}
    <div class="w-100 mb-3">
        @include('core.cart.inc.inscriptions-readonly')
    </div>

    {{-- Packs (solo lectura) --}}
    <div class="w-100 mb-3">
        @include('core.cart.inc.packs-readonly')
    </div>

    {{-- Gift cards --}}
    <div class="w-100 mb-3">
        @include('core.cart.inc.gift_cards')
    </div>

    {{-- Cliente --}}
    <div class="w-100 mb-3">
        @include('core.cart.inc.client-readonly')
    </div>

    <div class="row">
        {{-- Pago --}}
        <div class="col-md-8">
            @include('core.cart.inc.payment-readonly', ['payment' => $payment])
        </div>

        {{-- Comentario (solo lectura) --}}
        <div class="col-md-4">
            @include('core.cart.inc.comment-readonly')
        </div>
    </div>
@endsection
