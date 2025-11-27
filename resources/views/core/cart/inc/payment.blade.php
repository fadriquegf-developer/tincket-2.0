<div id="payment" class="card mb-4 border h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('backend.cart.inc.payment') }}</h5>

        {{-- Badge de estado de reembolso --}}
        @if ($entry->payment)
            @if ($entry->payment->refunded_at)
                <span class="badge bg-warning text-dark">
                    <i class="la la-undo me-1"></i> {{ __('refund.refunded') }}
                </span>
            @elseif ($entry->payment->needs_refund)
                <span class="badge bg-danger">
                    <i class="la la-exclamation-triangle me-1"></i> {{ __('refund.pending') }}
                </span>
            @endif
        @endif
    </div>
    <div class="card-body">
        @if ($entry->payment)
            {{-- ══════════════════════════════════════════════════════════════
                 ALERTA DE REEMBOLSO PENDIENTE
                 ══════════════════════════════════════════════════════════════ --}}
            @if ($entry->payment->needs_refund && !$entry->payment->refunded_at)
                <div class="alert alert-danger mb-3">
                    <div class="d-flex align-items-start">
                        <i class="la la-exclamation-triangle fa-2x me-3 mt-1"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">
                                <strong>{{ __('refund.alert_title') }}</strong>
                            </h6>
                            <p class="mb-2">
                                {{ __('refund.alert_description') }}
                            </p>
                            <p class="mb-2 small">
                                <strong>{{ __('refund.reason') }}:</strong>
                                @switch($entry->payment->refund_reason)
                                    @case('duplicate_slots')
                                        {{ __('refund.reason_duplicate_slots') }}
                                    @break

                                    @default
                                        {{ $entry->payment->refund_reason ?? __('refund.reason_not_specified') }}
                                @endswitch
                            </p>
                            <hr class="my-2">
                            <p class="mb-0 small text-muted">
                                <i class="la la-info-circle me-1"></i>
                                {{ __('refund.steps') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ══════════════════════════════════════════════════════════════
                 INFORMACIÓN DE REEMBOLSO COMPLETADO
                 ══════════════════════════════════════════════════════════════ --}}
            @if ($entry->payment->refunded_at)
                <div class="alert alert-warning mb-3">
                    <div class="d-flex align-items-center">
                        <i class="la la-check-circle fa-2x me-3"></i>
                        <div>
                            <strong>{{ __('refund.completed_title') }}</strong>
                            <p class="mb-0 small">
                                {{ __('refund.refunded_on', ['date' => $entry->payment->refunded_at->format('d/m/Y H:i')]) }}
                                @if ($entry->payment->refund_reference)
                                    <br>{{ __('refund.reference') }}:
                                    <code>{{ $entry->payment->refund_reference }}</code>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ══════════════════════════════════════════════════════════════
                 DATOS DEL PAGO
                 ══════════════════════════════════════════════════════════════ --}}
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
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.soldby') }}</label>
                <div class="col-sm-9">
                    @php
                        if ($entry->seller instanceof App\Models\Application) {
                            echo __('refund.external_application', ['name' => $entry->seller->code_name]);
                        } elseif ($entry->seller instanceof App\Models\User) {
                            echo sprintf('%s (%s)', $entry->seller->name, $entry->seller->email);
                        }
                    @endphp
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 SECCIÓN DE REEMBOLSO (solo si aplica)
                 ══════════════════════════════════════════════════════════════ --}}
            @if ($entry->payment->needs_refund || $entry->payment->refunded_at)
                <hr class="my-3">
                <h6 class="text-muted mb-3">
                    <i class="la la-undo me-1"></i> {{ __('refund.info_title') }}
                </h6>

                <div class="row mb-2">
                    <label class="col-sm-3 fw-semibold">{{ __('refund.status') }}</label>
                    <div class="col-sm-9">
                        @if ($entry->payment->refunded_at)
                            <span class="badge bg-warning text-dark">
                                <i class="la la-check me-1"></i> {{ __('refund.refunded') }}
                            </span>
                        @elseif ($entry->payment->needs_refund)
                            <span class="badge bg-danger">
                                <i class="la la-clock me-1"></i> {{ __('refund.pending_full') }}
                            </span>
                        @endif
                    </div>
                </div>

                @if ($entry->payment->refund_reason)
                    <div class="row mb-2">
                        <label class="col-sm-3 fw-semibold">{{ __('refund.reason') }}</label>
                        <div class="col-sm-9">
                            @switch($entry->payment->refund_reason)
                                @case('duplicate_slots')
                                    <span class="text-danger">
                                        <i class="la la-exclamation-circle me-1"></i>
                                        {{ __('refund.reason_duplicate_slots_short') }}
                                    </span>
                                @break

                                @default
                                    {{ $entry->payment->refund_reason }}
                            @endswitch
                        </div>
                    </div>
                @endif

                @if ($entry->payment->refunded_at)
                    <div class="row mb-2">
                        <label class="col-sm-3 fw-semibold">{{ __('refund.refund_date') }}</label>
                        <div class="col-sm-9">{{ $entry->payment->refunded_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                @endif

                @if ($entry->payment->refund_reference)
                    <div class="row mb-2">
                        <label class="col-sm-3 fw-semibold">{{ __('refund.reference') }}</label>
                        <div class="col-sm-9"><code>{{ $entry->payment->refund_reference }}</code></div>
                    </div>
                @endif

                {{-- Botón para marcar como reembolsado (solo superusers y si está pendiente) --}}
                @if ($entry->payment->needs_refund && !$entry->payment->refunded_at && auth()->check() && auth()->user()->isSuperuser())
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                                data-bs-target="#markRefundedModal">
                                <i class="la la-check me-1"></i>
                                {{ __('refund.mark_as_refunded') }}
                            </button>
                            <small class="text-muted ms-2">
                                {{ __('refund.mark_as_refunded_note') }}
                            </small>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Gateway Response Colapsable --}}
            @if ($entry->payment->gateway_response)
                <hr class="my-3">
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

{{-- ══════════════════════════════════════════════════════════════════════════════
     MODAL: Marcar como reembolsado
     ══════════════════════════════════════════════════════════════════════════════ --}}
@if ($entry->payment && $entry->payment->needs_refund && !$entry->payment->refunded_at)
    @push('after_scripts')
        <div class="modal fade" id="markRefundedModal" tabindex="-1" aria-labelledby="markRefundedModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('crud.cart.mark-refunded', $entry->getKey()) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="markRefundedModalLabel">
                                <i class="la la-undo me-2"></i>
                                {{ __('refund.modal_title') }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="{{ __('refund.modal_close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info mb-3">
                                <i class="la la-info-circle me-1"></i>
                                <strong>{{ __('refund.modal_important') }}</strong> {{ __('refund.modal_warning') }}
                            </div>

                            {{-- Resumen del pago --}}
                            <div class="card bg-light mb-3">
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">{{ __('refund.payment_code') }}</small><br>
                                            <strong>{{ $entry->payment->order_code }}</strong>
                                        </div>
                                        <div class="col-6 text-end">
                                            <small class="text-muted">{{ __('refund.amount') }}</small><br>
                                            <strong
                                                class="text-danger">{{ number_format($entry->payment->amount ?? $entry->priceSold, 2) }}€</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="refund_reference" class="form-label">
                                    {{ __('refund.refund_reference') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="refund_reference" name="refund_reference"
                                    placeholder="{{ __('refund.refund_reference_placeholder') }}" required autofocus>
                                <div class="form-text">
                                    {{ __('refund.refund_reference_help') }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="refund_notes" class="form-label">{{ __('refund.additional_notes') }}</label>
                                <textarea class="form-control" id="refund_notes" name="refund_notes" rows="2"
                                    placeholder="{{ __('refund.additional_notes_placeholder') }}"></textarea>
                                <div class="form-text">
                                    {{ __('refund.additional_notes_help') }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="la la-times me-1"></i> {{ __('refund.cancel') }}
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <i class="la la-check me-1"></i>
                                {{ __('refund.confirm_refund') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endpush
@endif
