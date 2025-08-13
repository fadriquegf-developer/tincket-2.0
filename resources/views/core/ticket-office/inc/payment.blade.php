{{-- resources/views/core/ticket-office/inc/payment.blade.php --}}
<div class="box">
    <div class="box-header">
        <h4 class="box-title">{{ trans('ticket-office.payment') }}</h4>
    </div>
    <div class="box-body">
        @if ($entry->payment)
            <p>
                <strong>{{ trans('ticket-office.payment_code') }}:</strong> {{ $entry->payment->order_code }}<br>
                <strong>{{ trans('ticket-office.paid_at') }}:</strong> {{ $entry->payment->paid_at }}<br>
                <strong>{{ trans('ticket-office.payment_platform') }}:</strong> {{ $entry->payment->gateway }}
            </p>
        @endif

        <div class="mb-3">
            <label class="form-label">{{ trans('ticket-office.payment_type.cash') }}</label>
            <input type="radio" name="payment[type]" value="cash">
        </div>
        <div class="mb-3">
            <label class="form-label">{{ trans('ticket-office.payment_type.card') }}</label>
            <input type="radio" name="payment[type]" value="card">
        </div>
    </div>
</div>
