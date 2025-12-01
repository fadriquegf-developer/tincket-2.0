@php
    $payment = $entry->payment ?? null;
    $hasPaid = $payment && $payment->paid_at;
    $isPending = $payment && $payment->isPendingRefund();
    $isRefunded = $payment && $payment->isRefunded();
    $canManageRefunds = auth()->check() && auth()->user()->isSuperuser();

    // Verificar si es gateway compatible con devolución automática
    $autoRefundGateways = ['Sermepa', 'Redsys', 'Redsys Redirect', 'SermepaSoapService', 'RedsysSoapService'];
    $canAutoRefund = $payment && in_array($payment->gateway, $autoRefundGateways);
@endphp

<div id="payment" class="card mb-4 border h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('backend.cart.inc.payment') }}</h5>

        {{-- Badge de estado de reembolso --}}
        @if ($payment)
            @if ($isRefunded)
                <span class="badge bg-success">
                    <i class="la la-check-circle me-1"></i> {{ __('refund.refunded') }}
                </span>
            @elseif ($isPending)
                <span class="badge bg-danger">
                    <i class="la la-exclamation-triangle me-1"></i> {{ __('refund.pending') }}
                </span>
            @endif
        @endif
    </div>
    <div class="card-body">
        @if ($payment)
            {{-- ══════════════════════════════════════════════════════════════
                 ALERTA DE REEMBOLSO PENDIENTE
                 ══════════════════════════════════════════════════════════════ --}}
            @if ($isPending)
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
                                {{ __('refund.reasons.' . ($payment->refund_reason ?? 'not_specified'), [], app()->getLocale()) !==
                                'refund.reasons.' . ($payment->refund_reason ?? 'not_specified')
                                    ? __('refund.reasons.' . ($payment->refund_reason ?? 'not_specified'))
                                    : $payment->refund_reason ?? __('refund.reason_not_specified') }}
                            </p>

                            {{-- Detalles técnicos colapsables --}}
                            @if ($payment->refund_details && isset($payment->refund_details['conflicts']))
                                <details class="mb-2">
                                    <summary class="text-muted small" style="cursor: pointer;">
                                        <i class="la la-code me-1"></i> Ver detalles técnicos
                                    </summary>
                                    <pre class="mt-2 p-2 bg-dark text-light rounded small" style="max-height: 150px; overflow-y: auto;">{{ json_encode($payment->refund_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @endif

                            <hr class="my-2">
                            <p class="mb-0 small text-muted">
                                <i class="la la-info-circle me-1"></i>
                                {{ __('refund.steps') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Botones de acción para reembolso pendiente --}}
                @if ($canManageRefunds)
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        {{-- Botón: Marcar como reembolsado (manual) --}}
                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                            data-bs-target="#markRefundedModal">
                            <i class="la la-check me-1"></i>
                            {{ __('refund.mark_as_refunded') }}
                        </button>

                        {{-- Botón: Procesar automático con Redsys --}}
                        @if ($canAutoRefund)
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#processRefundModal">
                                <i class="la la-credit-card me-1"></i>
                                {{ __('refund.process_auto_button') ?? 'Procesar con Redsys' }}
                            </button>
                        @else
                            <span class="text-muted small align-self-center">
                                <i class="la la-info-circle me-1"></i>
                                {{ __('refund.gateway_not_supported') ?? 'Este gateway no soporta devoluciones automáticas' }}
                            </span>
                        @endif
                    </div>
                @endif
            @endif

            {{-- ══════════════════════════════════════════════════════════════
                 INFORMACIÓN DE REEMBOLSO COMPLETADO
                 ══════════════════════════════════════════════════════════════ --}}
            @if ($isRefunded)
                <div class="alert alert-success mb-3">
                    <div class="d-flex align-items-center">
                        <i class="la la-check-circle fa-2x me-3"></i>
                        <div>
                            <strong>{{ __('refund.completed_title') }}</strong>
                            <p class="mb-0 small">
                                {{ __('refund.refunded_on', ['date' => $payment->refunded_at->format('d/m/Y H:i')]) }}
                                @if ($payment->refund_reference)
                                    <br>{{ __('refund.reference') }}:
                                    <code>{{ $payment->refund_reference }}</code>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @include('core.cart.inc.partial-refunds-pending')

            {{-- ══════════════════════════════════════════════════════════════
                 DATOS DEL PAGO
                 ══════════════════════════════════════════════════════════════ --}}
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.payment') }}
                    {{ __('backend.cart.inc.code') }}</label>
                <div class="col-sm-9">{{ $payment->order_code }}</div>
            </div>
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.cart.inc.paidat') }}</label>
                <div class="col-sm-9">{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i:s') : '-' }}</div>
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
                        $paymentType = $payment->getTicketOfficePaymentType();
                    @endphp
                    {{ $payment->gateway }}
                    @if ($payment->tpv_name)
                        ({{ $payment->tpv_name }})
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
                        } else {
                            echo '-';
                        }
                    @endphp
                </div>
            </div>


            {{-- BOTONES DE DEVOLUCIÓN (solo si NO está pendiente/reembolsado) --}}
            @if ($hasPaid && !$isPending && !$isRefunded && $canManageRefunds)
                <hr class="my-3">

                {{-- Verificar si hay más de 1 inscripción activa para mostrar devolución parcial --}}
                @php
                    $activeInscriptionsCount = $entry->inscriptions()->whereNull('deleted_at')->count();
                    $canPartialRefund = $activeInscriptionsCount > 1;
                @endphp

                <div class="d-flex align-items-center flex-wrap gap-2">
                    {{-- Botón: Solicitar devolución completa --}}
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                        data-bs-target="#requestRefundModal">
                        <i class="la la-undo me-1"></i>
                        {{ __('refund.request_button') ?? 'Solicitar devolución' }}
                    </button>

                    {{-- Botón: Devolución parcial (solo si hay más de 1 inscripción) --}}
                    @if ($canPartialRefund)
                        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal"
                            data-bs-target="#partialRefundModal"
                            onclick="loadPartialRefundData({{ $entry->getKey() }})">
                            <i class="la la-cut me-1"></i>
                            {{ __('refund.partial_refund_button') ?? 'Devolución parcial' }}
                        </button>
                    @endif

                    <small class="text-muted ms-2">
                        @if ($canPartialRefund)
                            {{ __('refund.request_description') ?? 'Devolución completa o parcial' }}
                            <span class="badge bg-secondary ms-1">{{ $activeInscriptionsCount }} inscripciones</span>
                        @else
                            {{ __('refund.request_description') ?? 'Solo tiene 1 inscripción' }}
                        @endif
                    </small>
                </div>
            @endif

            {{-- Gateway Response Colapsable --}}
            @if ($payment->gateway_response)
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
                                    $gatewayData = json_decode($payment->gateway_response, true);
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
                                    <pre class="mb-0">{{ $payment->gateway_response }}</pre>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <p class="text-muted mb-0">
                <i class="la la-info-circle me-1"></i>
                {{ __('backend.cart.no_payment') ?? 'No hay información de pago' }}
            </p>
        @endif
    </div>
</div>


{{-- MODAL: Solicitar Reembolso (marcar como pendiente) --}}
@if ($hasPaid && !$isPending && !$isRefunded && $canManageRefunds)
    @push('after_scripts')
        <div class="modal fade" id="requestRefundModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('crud.cart.request-refund', $entry->getKey()) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="la la-undo me-2"></i>
                                {{ __('refund.request_title') ?? 'Solicitar devolución' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted">
                                {{ __('refund.request_description') ?? 'Marcar este pago como pendiente de reembolso.' }}
                            </p>

                            {{-- Resumen del pago --}}
                            <div class="card bg-light mb-3">
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-6">
                                            <small
                                                class="text-muted">{{ __('refund.payment_code') ?? 'Código' }}</small><br>
                                            <strong>{{ $payment->order_code }}</strong>
                                        </div>
                                        <div class="col-6 text-end">
                                            <small class="text-muted">{{ __('refund.amount') ?? 'Importe' }}</small><br>
                                            <strong
                                                class="text-danger">{{ number_format($entry->priceSold, 2) }}€</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    {{ __('refund.select_reason') ?? 'Motivo' }} <span class="text-danger">*</span>
                                </label>
                                <select name="refund_reason" class="form-select" required>
                                    <option value="">-- {{ __('refund.select_reason') ?? 'Selecciona el motivo' }}
                                        --</option>
                                    <option value="customer_request">
                                        {{ __('refund.reasons.customer_request') ?? 'Solicitud del cliente' }}</option>
                                    <option value="event_cancelled">
                                        {{ __('refund.reasons.event_cancelled') ?? 'Evento cancelado' }}</option>
                                    <option value="duplicate_payment">
                                        {{ __('refund.reasons.duplicate_payment') ?? 'Pago duplicado' }}</option>
                                    <option value="admin_manual">
                                        {{ __('refund.reasons.admin_manual') ?? 'Devolución manual' }}</option>
                                    <option value="other">{{ __('refund.reasons.other') ?? 'Otro motivo' }}</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('refund.notes_label') ?? 'Notas adicionales' }}</label>
                                <textarea name="refund_notes" class="form-control" rows="3"
                                    placeholder="{{ __('refund.notes_placeholder') ?? 'Ej: El cliente llamó solicitando cancelación...' }}"></textarea>
                            </div>

                            <div class="alert alert-info mb-0 small">
                                <i class="la la-info-circle me-1"></i>
                                {{ __('refund.steps') ?? 'Después de marcar, deberás procesar el reembolso desde Redsys o usar el botón de proceso automático.' }}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                {{ __('refund.cancel') ?? 'Cancelar' }}
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="la la-undo me-1"></i>
                                {{ __('refund.request_button') ?? 'Solicitar devolución' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endpush
@endif

{{-- MODAL: Marcar como reembolsado (manual) --}}
@if ($isPending && $canManageRefunds)
    @push('after_scripts')
        <div class="modal fade" id="markRefundedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('crud.cart.mark-refunded', $entry->getKey()) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="la la-check-circle me-2"></i>
                                {{ __('refund.modal_title') }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                                            <strong>{{ $payment->order_code }}</strong>
                                        </div>
                                        <div class="col-6 text-end">
                                            <small class="text-muted">{{ __('refund.amount') }}</small><br>
                                            <strong
                                                class="text-danger">{{ number_format($entry->priceSold, 2) }}€</strong>
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
                            <button type="submit" class="btn btn-success">
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

{{-- MODAL: Procesar Reembolso Automático con Redsys --}}
@if ($isPending && $canManageRefunds && $canAutoRefund)
    @push('after_scripts')
        <div class="modal fade" id="processRefundModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('crud.cart.process-refund', $entry->getKey()) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="la la-credit-card me-2"></i>
                                {{ __('refund.process_auto_title') ?? 'Procesar devolución automática' }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info mb-3">
                                <i class="la la-exclamation-triangle me-1"></i>
                                {{ __('refund.process_auto_warning') ?? 'Esta acción enviará una solicitud de devolución a Redsys. El importe se devolverá a la tarjeta del cliente.' }}
                            </div>

                            {{-- Resumen del pago --}}
                            <div class="card bg-light mb-3">
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-6">
                                            <small
                                                class="text-muted">{{ __('refund.payment_code') ?? 'Código' }}</small><br>
                                            <strong>{{ $payment->order_code }}</strong>
                                        </div>
                                        <div class="col-6 text-end">
                                            <small
                                                class="text-muted">{{ __('refund.original_amount') ?? 'Importe original' }}</small><br>
                                            <strong
                                                class="text-danger">{{ number_format($entry->priceSold, 2) }}€</strong>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <small
                                                class="text-muted">{{ __('backend.cart.platform') ?? 'Gateway' }}</small><br>
                                            <strong>{{ $payment->gateway }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    class="form-label">{{ __('refund.partial_amount') ?? 'Importe a devolver (opcional)' }}</label>
                                <div class="input-group">
                                    <input type="number" name="refund_amount" class="form-control" step="0.01"
                                        min="0.01" max="{{ $entry->priceSold }}"
                                        placeholder="{{ number_format($entry->priceSold, 2) }}">
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="form-text">
                                    {{ __('refund.partial_amount_help') ?? 'Dejar vacío para devolución total. Introducir importe para devolución parcial.' }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                {{ __('refund.cancel') ?? 'Cancelar' }}
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="la la-credit-card me-1"></i>
                                {{ __('refund.process_auto_button') ?? 'Procesar con Redsys' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endpush
@endif

{{-- MODAL: Devolución Parcial --}}
@include('core.cart.inc.partial-refund-modal')
