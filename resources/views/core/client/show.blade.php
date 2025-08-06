@extends(backpack_view('layouts.horizontal'))

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3 ms-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline d-print-none"
            bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">{{ __('backend.menu.clients') }}</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">
                {!! mb_ucfirst(trans('backpack::crud.preview')) . ' ' . __('backend.menu.clients')  !!}
            </p>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading-back-button">
                <small><a href="{{ url('/client') }}" class="font-sm"><i class="la la-angle-double-left"></i>
                        {{ trans('backpack::crud.back_to_all') }}
                        <span>{{ __('backend.menu.clients') }}</span></a></small>
            </p>
        </section>
        <a href="javascript: window.print();" class="btn float-right"><i class="la la-print"></i></a>
    </div>
@endsection

@section('content')
    <div class="container-fluid animated fadeIn">
        <div class="row" bp-section="crud-operation-show">
            <div class="col-md-12">
                <div class="card no-padding no-border mb-4">
                    <table class="table table-striped m-0 p-0">
                        <tbody>
                            <tr>
                                <td class="border-top-0"><strong>{{  __('backend.menu.client') . ' id'}}:</strong></td>
                                <td class="border-top-0"><span>{{ $crud->entry->id }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.surname')}}:</strong></td>
                                <td><span>{{ $crud->entry->surname }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.name')}}:</strong></td>
                                <td><span>{{ $crud->entry->name }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.email')}}:</strong></td>
                                <td><span>{{ $crud->entry->email }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.num_session')}}:</strong></td>
                                <td><span>{{ $crud->entry->num_session }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.newsletter')}}:</strong></td>
                                <td><span>{{ $crud->entry->newsletter ? 'Sí' : 'No' }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.created_at')}}:</strong></td>
                                <td><span>{{ optional($crud->entry->created_at)->format('d/m/Y H:i') }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.address')}}:</strong></td>
                                <td><span>{{ $crud->entry->address }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Brand:</strong></td>
                                <td><span>{{ $crud->entry->brand_id }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.city')}}:</strong></td>
                                <td><span>{{ $crud->entry->city }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.date_birth')}}:</strong></td>
                                <td><span>{{ $crud->entry->date_birth }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>DNI:</strong></td>
                                <td><span>{{ $crud->entry->dni }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.locale')}}:</strong></td>
                                <td><span>{{ $crud->entry->locale }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.mobile_phone')}}:</strong></td>
                                <td><span>{{ $crud->entry->mobile_phone }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.phone')}}:</strong></td>
                                <td><span>{{ $crud->entry->phone }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.postal_code')}}:</strong></td>
                                <td><span>{{ $crud->entry->postal_code }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{__('backend.client.province')}}:</strong></td>
                                <td><span>{{ $crud->entry->province }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Accions:</strong></td>
                                <td>
                                    <a href="{{ backpack_url('client/' . $crud->entry->id . '/edit') }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="la la-edit"></i> Editar
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Carts Table --}}
                <div class="card mb-4">
                    <div class="card-header"><strong>Carts</strong></div>
                    <div class="card-body table-responsive p-0">
                        @if ($crud->entry->carts && $crud->entry->carts->count())
                            <table class="table table-striped m-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><strong>{{__('backend.cart.confirmationcode')}}</strong></th>
                                        <th><strong>{{__('backend.create_at')}}</strong></th>
                                        <th><strong>{{__('backend.updated_at')}}</strong></th>
                                        <th><strong>{{__('backend.deleted_at')}}</strong></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($crud->entry->carts as $i => $cart)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $cart->confirmation_code }}</td>
                                            <td>{{ optional($cart->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>{{ optional($cart->updated_at)->format('d/m/Y H:i') }}</td>
                                            <td>{{ optional($cart->deleted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                            <td>
                                                <a href="{{ backpack_url('cart/' . $cart->id . '/show') }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="la la-eye"></i> Preview
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="mb-0">Aquest client no té cistelles.</p>
                        @endif
                    </div>
                </div>

                {{-- Sales Table --}}
                <div class="card mb-4">
                    <div class="card-header"><strong>Sales</strong></div>
                    <div class="card-body table-responsive p-0">
                        @if ($crud->entry->sales && $crud->entry->sales->count())
                            <table class="table table-striped m-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><strong>{{__('backend.cart.confirmationcode')}}</strong></th>
                                        <th><strong>{{__('backend.events.event')}}</strong></th>
                                        <th><strong>{{__('backend.session.session')}}</strong></th>
                                        <th><strong>{{__('backend.create_at')}}</strong></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($crud->entry->sales as $i => $sale)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $sale->confirmation_code }}</td>
                                            <td>{{ $sale->event_name }}</td>
                                            <td>{{ $sale->session->name ?? '-' }}</td>
                                            <td>{{ optional($sale->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if ($sale->cart_id)
                                                    <a href="{{ backpack_url('cart/' . $sale->cart_id . '/show') }}"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="la la-eye"></i> Preview
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="mb-0">Aquest client no té vendes associades.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection