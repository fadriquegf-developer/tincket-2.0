@extends(backpack_view('layouts.horizontal'))

@section('content')
    @php $data = []; @endphp
    @foreach ($inscriptions as $inscription)
        @php
            $data[] = [
                'card_id'   => $inscription->cart->id,
                'name'      => $inscription->cart->client->name    ?? '',
                'surname'   => $inscription->cart->client->surname ?? '',
                'email'     => $inscription->cart->client->email   ?? '',
                'phone'     => $inscription->cart->client->phone   ?? '',
                'code'      => $inscription->cart->confirmation_code,
                'rate'      => $inscription->getRateName(),
                'slot'      => $inscription->slot->name ?? 'n/a',
                'bar_code'  => $inscription->barcode,
                'actions'   =>
                    '<a href="'.url('cart/'.$inscription->cart->id.'/regenerate').'" target="_blank" class="btn btn-default mb-1"><i class="fa fa-file-pdf-o"></i> '.
                        __('backend.cart.regenerate_tickets').
                    '</a>'.
                    '<a href="'.url('cart/'.$inscription->cart->id.'/regenerate?send=true').'" target="_blank" class="btn btn-default mb-1"><i class="fa fa-file-pdf-o"></i> '.
                        __('backend.cart.regenerate_send_tickets').
                    '</a>',
            ];
        @endphp
    @endforeach

    <section class="content">
      <div class="row">
        <div class="col-12">
          <div class="box">
            <div class="box-header with-border">
              <h2 class="box-title">{{ __('Listado de inscripciones sin PDF generado') }}</h2>
            </div>
            <div class="box-body">
              <table id="inscription-table" class="table table-striped table-bordered hover w-100">
                <thead>
                  <tr>
                    <th>{{ __('backend.client.name') }}</th>
                    <th>{{ __('backend.client.surname') }}</th>
                    <th>{{ __('backend.client.email') }}</th>
                    <th>{{ __('backend.client.phone') }}</th>
                    <th>{{ __('backend.cart.confirmationcode') }}</th>
                    <th>{{ __('backend.rate.rate') }}</th>
                    <th>{{ __('backend.ticket.slot') }}</th>
                    <th>{{ __('backend.inscription.barcode') }}</th>
                    <th>{{ __('Acciones') }}</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
@endsection

@section('after_styles')
  <link rel="stylesheet" href="{{ asset('vendor/backpack/crud/css/crud.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/backpack/crud/css/edit.css') }}">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
@endsection

@section('after_scripts')
  <script src="{{ asset('vendor/backpack/crud/js/crud.js') }}"></script>
  <script src="{{ asset('vendor/backpack/crud/js/edit.js') }}"></script>
  <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

  <script>
    var data = @json($data);
    $(function() {
      $('#inscription-table').DataTable({
        data: data,
        responsive: true,
        pageLength: 25,
        columns: [
          { data: 'name' },
          { data: 'surname' },
          { data: 'email' },
          { data: 'phone' },
          {
            data: 'code',
            className: "text-center",
            render: function(data, type, row) {
              return '<a href="/cart/'+row.card_id+'" target="_blank">'+data+'</a>';
            }
          },
          { data: 'rate' },
          { data: 'slot' },
          { data: 'bar_code' },
          { data: 'actions' }
        ],
        dom: 'Bfrtip',
        language: {
          url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/{{ app()->getLocale() }}.json'
        }
      });
    });
  </script>
@endsection
