@php
    $sales = $entry->salesFromStats();
@endphp

@if ($sales->count())
    <div class="card mt-4">
        <div class="card-header">
            <strong>{{ __('backend.events.sales') }}</strong>
        </div>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('backend.menu.client') }}</th>
                    <th>{{ __('backend.client.phone') }}</th>
                    <th>{{ __('backend.client.mobile_phone') }}</th>
                    <th>{{ __('backend.events.cart') }}</th>
                    <th>{{ __('backend.events.start_on') }}</th>
                    <th>{{ __('backend.events.created_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entry->salesFromStats() as $sale)
                    <tr>
                        <td>{{ $sale->id }}</td>
                        <td>{{ $sale->email ?? '-' }}</td>
                        <td>{{ $sale->phone ?? '-' }}</td>
                        <td>{{ $sale->mobile_phone ?? '-' }}</td>
                        <td>{{ $sale->cart_confirmation_code ?? '-' }}</td>
                        <td>{{ $sale->session_start ? \Carbon\Carbon::parse($sale->session_start)->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td>{{ $sale->created_at ? \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@else
    <p class="text-muted mt-4">{{ __('No hi ha vendes per aquest esdeveniment.') }}</p>
@endif