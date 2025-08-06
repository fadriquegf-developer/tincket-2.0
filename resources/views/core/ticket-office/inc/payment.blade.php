<div class="box">
    <div class="box-header">
        <h3 class="box-title">
            {{ trans('ticket-office.payment') }}
        </h3>
    </div>
    <div class="box-body">
        @if($entry->payment)
        <div class="row mb-3">
            <label class="col-sm-4 col-form-label">{{ trans('ticket-office.payment_code') }}</label>
            <div class="col-sm-8">
                <p class="form-control-plaintext">{{ $entry->payment->order_code }}</p>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-4 col-form-label">{{ trans('ticket-office.paid_at') }}</label>
            <div class="col-sm-8">
                <p class="form-control-plaintext">{{ $entry->payment->paid_at }}</p>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-4 col-form-label">{{ trans('ticket-office.payment_platform') }}</label>
            <div class="col-sm-8">
                <p class="form-control-plaintext">{{ $entry->payment->gateway }}</p>
            </div>
        </div>
        @endif
        
        <div class="row">
            <div class="col-sm-8 offset-sm-4">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="payment_type_1" name="payment_type" 
                           value="{{ App\Services\Payment\Impl\PaymentTicketOfficeService::CASH }}" checked>
                    <label class="form-check-label" for="payment_type_1">
                        {{ trans('ticket-office.payment_type.cash') }}
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="payment_type_2" name="payment_type" 
                           value="{{ App\Services\Payment\Impl\PaymentTicketOfficeService::CARD }}">
                    <label class="form-check-label" for="payment_type_2">
                        {{ trans('ticket-office.payment_type.card') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>