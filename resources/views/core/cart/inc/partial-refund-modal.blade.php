@if (isset($entry) && $entry->payment && $entry->payment->paid_at && !$entry->trashed())
    @php
        $hasRefundableInscriptions = $entry->inscriptions()->whereNull('deleted_at')->count() > 1;
    @endphp

    @if ($hasRefundableInscriptions && $canManageRefunds)
        {{-- ══════════════════════════════════════════════════════════════════
             BOTÓN: Devolución Parcial
             ══════════════════════════════════════════════════════════════════ --}}
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#partialRefundModal"
            onclick="loadPartialRefundData({{ $entry->getKey() }})">
            <i class="la la-cut me-1"></i>
            {{ __('refund.partial_refund_button') }}
        </button>

        {{-- ══════════════════════════════════════════════════════════════════
             MODAL: Devolución Parcial
             ══════════════════════════════════════════════════════════════════ --}}
        @push('after_scripts')
            <div class="modal fade" id="partialRefundModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">
                                <i class="la la-cut me-2"></i>
                                {{ __('refund.partial_refund_title') }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            {{-- Loading spinner --}}
                            <div id="partialRefundLoading" class="text-center py-5">
                                <div class="spinner-border text-info" role="status">
                                    <span class="visually-hidden">{{ __('refund.loading') }}</span>
                                </div>
                                <p class="mt-2 text-muted">{{ __('refund.loading_inscriptions') }}</p>
                            </div>

                            {{-- Contenido principal (oculto hasta cargar) --}}
                            <div id="partialRefundContent" style="display: none;">
                                {{-- Resumen del carrito --}}
                                <div class="card bg-light mb-3">
                                    <div class="card-body py-2">
                                        <div class="row small">
                                            <div class="col-md-4">
                                                <strong>{{ __('refund.code') }}:</strong>
                                                <span id="prCartCode">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>{{ __('refund.original_amount') }}:</strong>
                                                <span id="prOriginalAmount">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>{{ __('refund.already_refunded') }}:</strong>
                                                <span id="prTotalRefunded" class="text-danger">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Alerta informativa --}}
                                <div class="alert alert-info mb-3">
                                    <i class="la la-info-circle me-1"></i>
                                    <strong>{{ __('refund.instructions_title') }}:</strong>
                                    {{ __('refund.instructions_text') }}
                                </div>

                                {{-- Formulario --}}
                                <form id="partialRefundForm">
                                    @csrf

                                    {{-- Tabla de inscripciones --}}
                                    <div class="table-responsive mb-3">
                                        <table class="table table-hover table-sm" id="inscriptionsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40px;">
                                                        <input type="checkbox" id="selectAllInscriptions"
                                                            class="form-check-input" title="{{ __('refund.select_all') }}">
                                                    </th>
                                                    <th>{{ __('refund.event_session') }}</th>
                                                    <th>{{ __('refund.seat') }}</th>
                                                    <th>{{ __('refund.rate') }}</th>
                                                    <th class="text-end">{{ __('refund.price') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="inscriptionsBody">
                                                {{-- Se llena dinámicamente --}}
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="4" class="text-end">
                                                        {{ __('refund.total_to_refund') }}:
                                                    </th>
                                                    <th class="text-end">
                                                        <strong id="prTotalToRefund">0,00 €</strong>
                                                    </th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    {{-- Motivo y notas --}}
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                {{ __('refund.select_reason') }} *
                                            </label>
                                            <select name="refund_reason" id="prRefundReason" class="form-select" required>
                                                <option value="">{{ __('refund.select_option') }}</option>
                                                <option value="customer_request">
                                                    {{ __('refund.reasons.customer_request') }}
                                                </option>
                                                <option value="event_cancelled">
                                                    {{ __('refund.reasons.event_cancelled') }}
                                                </option>
                                                <option value="duplicate_payment">
                                                    {{ __('refund.reasons.duplicate_payment') }}
                                                </option>
                                                <option value="admin_manual">
                                                    {{ __('refund.reasons.admin_manual') }}
                                                </option>
                                                <option value="other">
                                                    {{ __('refund.reasons.other') }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                {{ __('refund.notes_label') }}
                                            </label>
                                            <textarea name="refund_notes" id="prRefundNotes" class="form-control" rows="2" maxlength="500"
                                                placeholder="{{ __('refund.notes_placeholder') }}"></textarea>
                                        </div>
                                    </div>
                                </form>

                                {{-- Historial de devoluciones parciales --}}
                                <div id="refundHistorySection" style="display: none;">
                                    <hr>
                                    <h6 class="mb-3">
                                        <i class="la la-history me-1"></i>
                                        {{ __('refund.refund_history_title') }}
                                    </h6>
                                    <div id="refundHistoryContent"></div>
                                </div>
                            </div>

                            {{-- Error --}}
                            <div id="partialRefundError" class="alert alert-danger" style="display: none;">
                                <i class="la la-exclamation-triangle me-1"></i>
                                <span id="partialRefundErrorMessage"></span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                {{ __('refund.cancel') }}
                            </button>
                            <button type="button" class="btn btn-info" id="submitPartialRefund"
                                onclick="submitPartialRefund()" disabled>
                                <i class="la la-cut me-1"></i>
                                {{ __('refund.partial_refund_submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 JAVASCRIPT
                 ══════════════════════════════════════════════════════════════ --}}
            <script>
                // Traducciones para JavaScript
                const refundTranslations = {
                    selectAtLeastOne: @json(__('refund.select_at_least_one')),
                    selectReasonRequired: @json(__('refund.select_reason_required')),
                    cannotSelectAll: @json(__('refund.cannot_select_all')),
                    confirmPartialRefund: @json(__('refund.confirm_partial_refund')),
                    inscriptionsCount: @json(__('refund.inscriptions_count')),
                    amount: @json(__('refund.amount')),
                    seatsWillBeReleased: @json(__('refund.seats_will_be_released')),
                    errorPrefix: @json(__('refund.error_prefix')),
                    errorLoadingData: @json(__('refund.error_loading_data')),
                    errorProcessingRefund: @json(__('refund.error_processing_refund')),
                    processing: @json(__('refund.processing')),
                    partialRefundSubmit: @json(__('refund.partial_refund_submit')),
                    viewInscriptions: @json(__('refund.view_inscriptions')),
                    reference: @json(__('refund.reference')),
                };

                let partialRefundData = null;
                let selectedInscriptions = new Set();

                /**
                 * Cargar datos del carrito para devolución parcial
                 */
                async function loadPartialRefundData(cartId) {
                    // Mostrar loading
                    document.getElementById('partialRefundLoading').style.display = 'block';
                    document.getElementById('partialRefundContent').style.display = 'none';
                    document.getElementById('partialRefundError').style.display = 'none';
                    document.getElementById('submitPartialRefund').disabled = true;
                    selectedInscriptions.clear();

                    try {
                        const response = await fetch(`{{ backpack_url('cart') }}/${cartId}/partial-refund-data`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || refundTranslations.errorLoadingData);
                        }

                        partialRefundData = data;
                        renderPartialRefundModal(data);

                    } catch (error) {
                        document.getElementById('partialRefundLoading').style.display = 'none';
                        document.getElementById('partialRefundError').style.display = 'block';
                        document.getElementById('partialRefundErrorMessage').textContent = error.message;
                    }
                }

                /**
                 * Renderizar el contenido del modal
                 */
                function renderPartialRefundModal(data) {
                    // Ocultar loading
                    document.getElementById('partialRefundLoading').style.display = 'none';
                    document.getElementById('partialRefundContent').style.display = 'block';

                    // Resumen
                    document.getElementById('prCartCode').textContent = data.confirmation_code;
                    document.getElementById('prOriginalAmount').textContent = data.original_amount_formatted;
                    document.getElementById('prTotalRefunded').textContent = data.total_refunded_formatted;

                    // Tabla de inscripciones
                    const tbody = document.getElementById('inscriptionsBody');
                    tbody.innerHTML = '';

                    data.inscriptions.forEach(inscription => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <input type="checkbox" 
                                       class="form-check-input inscription-checkbox" 
                                       value="${inscription.id}"
                                       data-price="${inscription.price_sold}"
                                       onchange="updateSelection()">
                            </td>
                            <td>
                                <strong>${inscription.event_name}</strong><br>
                                <small class="text-muted">${inscription.session_name} - ${inscription.session_date || ''}</small>
                            </td>
                            <td>${inscription.slot_name}</td>
                            <td><span class="badge bg-secondary">${inscription.rate_name}</span></td>
                            <td class="text-end">${inscription.price_sold_formatted}</td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Historial de devoluciones
                    if (data.refund_history && data.refund_history.length > 0) {
                        document.getElementById('refundHistorySection').style.display = 'block';
                        renderRefundHistory(data.refund_history);
                    } else {
                        document.getElementById('refundHistorySection').style.display = 'none';
                    }

                    // Reset select all
                    document.getElementById('selectAllInscriptions').checked = false;
                    updateSelection();
                }

                /**
                 * Renderizar historial de devoluciones
                 */
                function renderRefundHistory(history) {
                    const container = document.getElementById('refundHistoryContent');
                    container.innerHTML = '';

                    history.forEach(refund => {
                        const items = refund.items.map(i =>
                            `<li>${i.slot_name} (${i.rate_name}): ${i.price_sold}</li>`
                        ).join('');

                        const refText = refund.refund_reference ?
                            `| ${refundTranslations.reference}: ${refund.refund_reference}` :
                            '';

                        const card = document.createElement('div');
                        card.className = 'card mb-2';
                        card.innerHTML = `
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>#${refund.id}</strong> - ${refund.amount_formatted}
                                        ${refund.status_badge}
                                        <br>
                                        <small class="text-muted">
                                            ${refund.created_at} | ${refund.reason}
                                            ${refText}
                                        </small>
                                    </div>
                                </div>
                                <details class="mt-1">
                                    <summary class="small text-muted" style="cursor: pointer;">
                                        ${refundTranslations.viewInscriptions} (${refund.inscription_count})
                                    </summary>
                                    <ul class="small mb-0 mt-1">${items}</ul>
                                </details>
                            </div>
                        `;
                        container.appendChild(card);
                    });
                }

                /**
                 * Actualizar selección y total
                 */
                function updateSelection() {
                    selectedInscriptions.clear();
                    let total = 0;

                    document.querySelectorAll('.inscription-checkbox:checked').forEach(checkbox => {
                        selectedInscriptions.add(parseInt(checkbox.value));
                        total += parseFloat(checkbox.dataset.price);
                    });

                    // Actualizar total
                    document.getElementById('prTotalToRefund').textContent =
                        total.toLocaleString('{{ app()->getLocale() }}', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) + ' €';

                    // Habilitar/deshabilitar botón
                    const canSubmit = selectedInscriptions.size > 0 &&
                        selectedInscriptions.size < partialRefundData.inscriptions.length;
                    document.getElementById('submitPartialRefund').disabled = !canSubmit;

                    // Advertencia si selecciona todas
                    if (selectedInscriptions.size === partialRefundData.inscriptions.length) {
                        alert(refundTranslations.cannotSelectAll);
                    }
                }

                /**
                 * Seleccionar/deseleccionar todas
                 */
                document.getElementById('selectAllInscriptions').addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.inscription-checkbox');
                    // Si hay más de una inscripción, seleccionar todas menos una
                    const maxToSelect = checkboxes.length - 1;
                    let selected = 0;

                    checkboxes.forEach(checkbox => {
                        if (this.checked && selected < maxToSelect) {
                            checkbox.checked = true;
                            selected++;
                        } else if (!this.checked) {
                            checkbox.checked = false;
                        }
                    });

                    updateSelection();
                });

                /**
                 * Enviar solicitud de devolución parcial
                 */
                async function submitPartialRefund() {
                    if (selectedInscriptions.size === 0) {
                        alert(refundTranslations.selectAtLeastOne);
                        return;
                    }

                    const reason = document.getElementById('prRefundReason').value;
                    if (!reason) {
                        alert(refundTranslations.selectReasonRequired);
                        return;
                    }

                    const notes = document.getElementById('prRefundNotes').value;

                    // Confirmar
                    const count = selectedInscriptions.size;
                    const total = document.getElementById('prTotalToRefund').textContent;
                    const confirmMessage =
                        `${refundTranslations.confirmPartialRefund}\n\n${refundTranslations.inscriptionsCount}: ${count}\n${refundTranslations.amount}: ${total}\n\n${refundTranslations.seatsWillBeReleased}`;

                    if (!confirm(confirmMessage)) {
                        return;
                    }

                    // Deshabilitar botón
                    const submitBtn = document.getElementById('submitPartialRefund');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        `<span class="spinner-border spinner-border-sm me-1"></span> ${refundTranslations.processing}`;

                    try {
                        const response = await fetch(
                            `{{ backpack_url('cart') }}/${partialRefundData.cart_id}/request-partial-refund`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    inscription_ids: Array.from(selectedInscriptions),
                                    refund_reason: reason,
                                    refund_notes: notes,
                                }),
                            });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || refundTranslations.errorProcessingRefund);
                        }

                        // Éxito - cerrar modal y recargar página
                        alert(data.message);
                        window.location.reload();

                    } catch (error) {
                        alert(`${refundTranslations.errorPrefix}: ${error.message}`);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = `<i class="la la-cut me-1"></i> ${refundTranslations.partialRefundSubmit}`;
                    }
                }
            </script>
        @endpush
    @endif
@endif
