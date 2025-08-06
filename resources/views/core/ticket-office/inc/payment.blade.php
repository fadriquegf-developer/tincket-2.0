<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">
            {{ trans('tincket/backend.ticket.payment') }}
        </h3>
    </div>
    <div class="box-body">
        <div class="box-body">
            @if($entry->payment)
            <div class="row">
                <label class="col-xs-3">{{ trans('tincket/backend.ticket.payment_code') }}</label>
                <p class="col-xs-9">{{ $entry->payment->order_code }}</p>
            </div>
            <div class="row">
                <label class="col-xs-3">{{ trans('tincket/backend.ticket.paid_at') }}</label>
                <p class="col-xs-9">{{ $entry->payment->paid_at }}</p>
            </div>
            <div class="row">
                <label class="col-xs-3">{{ trans('tincket/backend.ticket.payment_platform') }}</label>
                <p class="col-xs-9">{{ $entry->payment->gateway }}</p>
            </div>
            @endif
            <div>
                <label class="radio-inline" for="payment_type_1">
                    <input type="radio" id="payment_type_1" name="payment_type" value="{{ App\Services\Payment\Impl\PaymentTicketOfficeService::CASH }}" checked> {{ trans('tincket/backend.ticket.payment_type.cash') }}
                </label>
                <label class="radio-inline" for="payment_type_2">
                    <input type="radio" id="payment_type_2" name="payment_type" value="{{ App\Services\Payment\Impl\PaymentTicketOfficeService::CARD }}"> {{ trans('tincket/backend.ticket.payment_type.card') }}
                </label>
            </div>
        </div>
    </div>
</div>