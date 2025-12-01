@php
    // Obtener devoluciones parciales del carrito
    $partialRefunds = $entry->partialRefunds()
        ->with('items')
        ->orderBy('created_at', 'desc')
        ->get();
    
    $pendingPartialRefunds = $partialRefunds->where('status', 'pending');
    $completedPartialRefunds = $partialRefunds->where('status', 'completed');
    $hasPartialRefunds = $partialRefunds->isNotEmpty();
@endphp

@if ($hasPartialRefunds)
    <div class="card mb-3">
        <div class="card-header bg-info bg-opacity-10 text-white">
            <h6 class="mb-0">
                <i class="la la-cut me-2"></i>
                {{ __('refund.partial_history_title') ?? 'Devoluciones Parciales' }}
                <span class="badge bg-secondary ms-2">{{ $partialRefunds->count() }}</span>
            </h6>
        </div>
        <div class="card-body p-0">
            
            {{-- ══════════════════════════════════════════════════════════════
                 DEVOLUCIONES PENDIENTES
                 ══════════════════════════════════════════════════════════════ --}}
            @if ($pendingPartialRefunds->isNotEmpty())
                @foreach ($pendingPartialRefunds as $refund)
                    <div class="border-bottom p-3 bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge text-dark me-2">
                                    <i class="la la-clock me-1"></i>
                                    {{ __('refund.partial_status.pending') ?? 'Pendiente' }}
                                </span>
                                <strong>#{{ $refund->id }}</strong>
                                <span class="text-muted ms-2">
                                    {{ $refund->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <div class="text-end">
                                <strong class="text-danger fs-5">
                                    {{ number_format($refund->amount, 2) }} €
                                </strong>
                                <br>
                                <small class="text-muted">
                                    {{ $refund->items->count() }} {{ __('refund.confirm_inscriptions') ?? 'inscripciones' }}
                                </small>
                            </div>
                        </div>
                        
                        {{-- Motivo y notas --}}
                        <div class="small text-muted mb-2">
                            <strong>{{ __('refund.reason') ?? 'Motivo' }}:</strong> 
                            {{ $refund->reason_text }}
                            @if ($refund->notes)
                                <br><strong>{{ __('refund.notes_label') ?? 'Notas' }}:</strong> 
                                {{ $refund->notes }}
                            @endif
                        </div>
                        
                        {{-- Inscripciones devueltas (colapsable) --}}
                        <details class="mb-3">
                            <summary class="small text-muted" style="cursor: pointer;">
                                <i class="la la-list me-1"></i>
                                {{ __('refund.partial_view_inscriptions') ?? 'Ver inscripciones devueltas' }}
                            </summary>
                            <ul class="list-unstyled small mt-2 ms-3 mb-0">
                                @foreach ($refund->items as $item)
                                    <li class="mb-1">
                                        <i class="la la-ticket text-muted me-1"></i>
                                        {{ $item->event_name ?? 'Evento' }} - 
                                        {{ $item->slot_name ?? 'Sin butaca' }}
                                        <span class="badge bg-secondary ms-1">{{ $item->rate_name }}</span>
                                        <span class="text-danger ms-1">{{ number_format($item->price_sold, 2) }} €</span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                        
                        {{-- Botones de acción --}}
                        @if ($canManageRefunds)
                            <div class="d-flex flex-wrap gap-2">
                                {{-- Botón: Marcar como completada (manual) --}}
                                <button type="button" 
                                        class="btn btn-success btn-sm" 
                                        data-bs-toggle="modal"
                                        data-bs-target="#markPartialRefundModal{{ $refund->id }}">
                                    <i class="la la-check me-1"></i>
                                    {{ __('refund.mark_as_refunded') ?? 'Marcar como completada' }}
                                </button>
                                
                                {{-- Botón: Procesar con Redsys (solo si es superuser y gateway soportado) --}}
                                @php
                                    $canAutoRefundPartial = $entry->payment 
                                        && in_array($entry->payment->gateway, ['Sermepa', 'Redsys', 'Redsys Redirect', 'SermepaSoapService', 'RedsysSoapService'])
                                        && auth()->user()->isSuperuser();
                                @endphp
                                
                                @if ($canAutoRefundPartial)
                                    <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#processPartialRefundModal{{ $refund->id }}">
                                        <i class="la la-credit-card me-1"></i>
                                        {{ __('refund.process_auto_button') ?? 'Procesar con Redsys' }}
                                    </button>
                                @else
                                    <span class="text-muted small align-self-center">
                                        <i class="la la-info-circle me-1"></i>
                                        {{ __('refund.gateway_not_supported') ?? 'Procesar manualmente' }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                    
                    {{-- ══════════════════════════════════════════════════════
                         MODAL: Marcar devolución parcial como completada
                         ══════════════════════════════════════════════════════ --}}
                    @push('after_scripts')
                        <div class="modal fade" id="markPartialRefundModal{{ $refund->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ backpack_url('cart/partial-refund/' . $refund->id . '/mark-completed') }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">
                                                <i class="la la-check-circle me-2"></i>
                                                {{ __('refund.modal_title') ?? 'Marcar como completada' }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info mb-3">
                                                <i class="la la-info-circle me-1"></i>
                                                <strong>{{ __('refund.modal_important') ?? 'Importante:' }}</strong>
                                                {{ __('refund.modal_warning') ?? 'Solo marca como completada después de haber realizado la devolución desde el panel de Redsys.' }}
                                            </div>
                                            
                                            {{-- Resumen --}}
                                            <div class="card bg-light mb-3">
                                                <div class="card-body py-2">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <small class="text-muted">Devolución parcial</small><br>
                                                            <strong>#{{ $refund->id }}</strong>
                                                        </div>
                                                        <div class="col-6 text-end">
                                                            <small class="text-muted">{{ __('refund.amount') ?? 'Importe' }}</small><br>
                                                            <strong class="text-danger">{{ number_format($refund->amount, 2) }} €</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    {{ __('refund.refund_reference') ?? 'Referencia del reembolso' }} 
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       name="refund_reference" 
                                                       class="form-control" 
                                                       required
                                                       placeholder="{{ __('refund.refund_reference_placeholder') ?? 'Ej: 123456789012' }}">
                                                <div class="form-text">
                                                    {{ __('refund.refund_reference_help') ?? 'Código de operación de la devolución en Redsys.' }}
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    {{ __('refund.additional_notes') ?? 'Notas adicionales' }}
                                                </label>
                                                <textarea name="refund_notes" 
                                                          class="form-control" 
                                                          rows="2"
                                                          placeholder="{{ __('refund.additional_notes_placeholder') ?? 'Opcional' }}"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                {{ __('refund.cancel') ?? 'Cancelar' }}
                                            </button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="la la-check me-1"></i>
                                                {{ __('refund.confirm_refund') ?? 'Confirmar' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endpush
                    
                    {{-- ══════════════════════════════════════════════════════
                         MODAL: Procesar con Redsys
                         ══════════════════════════════════════════════════════ --}}
                    @if ($canAutoRefundPartial ?? false)
                        @push('after_scripts')
                            <div class="modal fade" id="processPartialRefundModal{{ $refund->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ backpack_url('cart/partial-refund/' . $refund->id . '/process') }}" method="POST">
                                            @csrf
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">
                                                    <i class="la la-credit-card me-2"></i>
                                                    {{ __('refund.process_auto_title') ?? 'Procesar con Redsys' }}
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning mb-3">
                                                    <i class="la la-exclamation-triangle me-1"></i>
                                                    <strong>{{ __('refund.modal_important') ?? 'Atención:' }}</strong>
                                                    {{ __('refund.process_auto_warning') ?? 'Esta acción enviará una solicitud de devolución a Redsys. El importe se devolverá a la tarjeta del cliente.' }}
                                                </div>
                                                
                                                {{-- Resumen --}}
                                                <div class="card bg-light mb-3">
                                                    <div class="card-body py-2">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <small class="text-muted">Devolución parcial</small><br>
                                                                <strong>#{{ $refund->id }}</strong>
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                <small class="text-muted">{{ __('refund.amount') ?? 'Importe a devolver' }}</small><br>
                                                                <strong class="text-danger fs-4">{{ number_format($refund->amount, 2) }} €</strong>
                                                            </div>
                                                        </div>
                                                        <hr class="my-2">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <small class="text-muted">{{ __('backend.cart.platform') ?? 'Gateway' }}</small><br>
                                                                <strong>{{ $entry->payment->gateway }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <p class="text-muted small mb-0">
                                                    <i class="la la-info-circle me-1"></i>
                                                    Se procesará la devolución de {{ $refund->items->count() }} inscripciones 
                                                    por un total de {{ number_format($refund->amount, 2) }} €.
                                                </p>
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
                @endforeach
            @endif
            
            {{-- ══════════════════════════════════════════════════════════════
                 DEVOLUCIONES COMPLETADAS (colapsadas por defecto)
                 ══════════════════════════════════════════════════════════════ --}}
            @if ($completedPartialRefunds->isNotEmpty())
                <div class="p-3">
                    <details>
                        <summary class="text-success" style="cursor: pointer;">
                            <i class="la la-check-circle me-1"></i>
                            <strong>{{ __('refund.partial_status.completed') ?? 'Completadas' }}</strong>
                            <span class="badge bg-success ms-1">{{ $completedPartialRefunds->count() }}</span>
                        </summary>
                        
                        <div class="mt-3">
                            @foreach ($completedPartialRefunds as $refund)
                                <div class="card mb-2 border-success">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-success me-2">
                                                    <i class="la la-check me-1"></i>
                                                    Completada
                                                </span>
                                                <strong>#{{ $refund->id }}</strong>
                                                <span class="text-muted ms-2 small">
                                                    {{ $refund->refunded_at?->format('d/m/Y H:i') }}
                                                </span>
                                                @if ($refund->refund_reference)
                                                    <br>
                                                    <small class="text-muted">
                                                        Ref: <code>{{ $refund->refund_reference }}</code>
                                                    </small>
                                                @endif
                                            </div>
                                            <div class="text-end">
                                                <strong class="text-success">
                                                    {{ number_format($refund->amount, 2) }} €
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $refund->items->count() }} inscripciones
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>
            @endif
            
        </div>
    </div>
@endif