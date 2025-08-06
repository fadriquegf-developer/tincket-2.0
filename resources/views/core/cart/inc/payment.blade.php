<div id="payment" class="card mb-4 border h-100">
    <div class="card-header">
        <h5 class="mb-0">{{ __('backend.cart.inc.payment') }}</h5>
    </div>
    <div class="card-body">
        @if($entry->payment)
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.payment') }} {{ __('backend.cart.inc.code') }}</label>
                <div class="col-sm-9">{{ $entry->payment->order_code }}</div>
            </div>
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.paidat') }}</label>
                <div class="col-sm-9">{{ $entry->payment->paid_at }}</div>
            </div>
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.payment') }} {{ __('backend.cart.amount') }}</label>
                <div class="col-sm-9">{{ sprintf('%s€', number_format($entry->priceSold, 2)) }}</div>
            </div>
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.payment') }} {{ __('backend.cart.platform') }}</label>
                <div class="col-sm-9">
                    @php 
                        $paymentType = $entry->payment->getTicketOfficePaymentType();
                    @endphp
                    {{ $entry->payment->gateway }}
                    @if($entry->payment->tpv_name)
                        ({{ $entry->payment->tpv_name }})
                    @elseif($paymentType)
                        ({{ __('backend.ticket.payment_type.'.$paymentType) }})
                    @endif
                </div>
            </div>
        @endif

        <div class="row">
            <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.soldby') }}</label>
            <div class="col-sm-9">
                @php
                    if($entry->seller instanceof App\Models\Application) {
                        echo sprintf("External application (%s)", $entry->seller->code_name);                        
                    } elseif($entry->seller instanceof App\Models\User) {
                        echo sprintf("%s (%s)", $entry->seller->name, $entry->seller->email);
                    }
                @endphp
            </div>
        </div>
    </div>
</div>
