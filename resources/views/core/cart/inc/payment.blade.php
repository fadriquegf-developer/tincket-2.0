<div id="payment" class="card mb-4 border h-100">
    <div class="card-header">
        <h5 class="mb-0">{{ __('backend.cart.inc.payment') }}</h5>
    </div>
    <div class="card-body">
        @if ($entry->payment)
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.payment') }}
                    {{ __('backend.cart.inc.code') }}</label>
                <div class="col-sm-9">{{ $entry->payment->order_code }}</div>
            </div>
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.paidat') }}</label>
                <div class="col-sm-9">{{ $entry->payment->paid_at }}</div>
            </div>
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.payment') }}
                    {{ __('backend.cart.amount') }}</label>
                <div class="col-sm-9">{{ sprintf('%s€', number_format($entry->priceSold, 2)) }}</div>
            </div>
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.payment') }}
                    {{ __('backend.cart.platform') }}</label>
                <div class="col-sm-9">
                    @php
                        $paymentType = $entry->payment->getTicketOfficePaymentType();
                    @endphp
                    {{ $entry->payment->gateway }}
                    @if ($entry->payment->tpv_name)
                        ({{ $entry->payment->tpv_name }})
                    @elseif($paymentType)
                        ({{ __('backend.ticket.payment_type.' . $paymentType) }})
                    @endif
                </div>
            </div>
            <div class="row">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.soldby') }}</label>
                <div class="col-sm-9">
                    @php
                        if ($entry->seller instanceof App\Models\Application) {
                            echo sprintf('External application (%s)', $entry->seller->code_name);
                        } elseif ($entry->seller instanceof App\Models\User) {
                            echo sprintf('%s (%s)', $entry->seller->name, $entry->seller->email);
                        }
                    @endphp
                </div>
            </div>

            {{-- Gateway Response Colapsable --}}
            @if ($entry->payment->gateway_response)
                <div class="row mb-2">
                    <label
                        class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.gateway_response') ?? 'Gateway Response' }}</label>
                    <div class="col-sm-9">
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#gatewayResponse{{ $entry->id }}" aria-expanded="false"
                            aria-controls="gatewayResponse{{ $entry->id }}">
                            <i class="la la-eye me-1"></i> {{ __('backend.cart.inc.show_detail') }}
                        </button>

                        <div class="collapse mt-2" id="gatewayResponse{{ $entry->id }}">
                            <div class="card card-body bg-light">
                                @php
                                    $gatewayData = json_decode($entry->payment->gateway_response, true);
                                @endphp

                                @if ($gatewayData)
                                    <table class="table table-sm table-striped mb-0">
                                        <tbody>
                                            @foreach ($gatewayData as $key => $value)
                                                <tr>
                                                    <td class="fw-semibold text-nowrap" style="width: 40%;">
                                                        {{ str_replace('_', ' ', ucfirst($key)) }}:
                                                    </td>
                                                    <td>
                                                        @if (is_array($value))
                                                            {{ json_encode($value) }}
                                                        @else
                                                            {{ urldecode($value) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <pre class="mb-0">{{ $entry->payment->gateway_response }}</pre>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif


    </div>
</div>
