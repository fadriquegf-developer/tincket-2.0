@php
    use App\Models\Slot;
    use App\Models\Rate;
    $rates = Rate::query()->get();
@endphp

@if($entry->inscriptions->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('backend.cart.inc.inscriptionsset') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="w-5">{{ __('backend.cart.inc.sessionid') }}</th>
                        <th class="w-25">{{ __('backend.cart.inc.event') }}</th>
                        <th class="w-15">{{ __('backend.cart.inc.session') }}</th>
                        <th class="w-15">{{ __('backend.ticket.rate') }}</th>
                        <th class="w-10">{{ __('backend.ticket.price') }}</th>
                        <th class="w-25"># {{ __('backend.cart.inc.inscriptions') }}</th>
                        <th class="text-end w-5"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entry->inscriptions->groupBy('session_id') as $sessionId => $set)
                        @php
                            $first = $set->first();
                            $session = $first->session;
                            // Formatear la fecha de inicio con Carbon, o mostrar guión si es null
                            $sessionDate = $session && $session->starts_on
                                ? $session->starts_on->format('d/m/Y H:i')
                                : '—';
                            // Nombre de la sesión, si existe
                            $sessionName = $session && $session->name
                                ? $session->name
                                : null;
                        @endphp

                        {{-- Fila “padre”: muestra datos de sesión y número de inscripciones --}}
                        <tr data-bs-toggle="collapse" data-bs-target="#inscriptions_{{ $sessionId }}" aria-expanded="false"
                            style="cursor: pointer; border-bottom: 1px solid #dee2e6;">
                            <td>{{ $session?->id ?? '—' }}</td>
                            <td>{{ $session?->event->name ?? '—' }}</td>
                            <td>
                                @if($sessionName)
                                    {{ $sessionName }}<br>
                                @endif
                                <small class="text-muted">{{ $sessionDate }}</small>
                            </td>
                            <td></td>
                            <td></td>
                            <td id="ins-count-{{ $sessionId }}">{{ $set->count() }}</td>
                            <td class="text-end">
                                <i class="la la-plus"></i>
                            </td>
                        </tr>

                        {{-- Filas “hijo”: cada inscripción individual --}}
                        @foreach ($set as $inscription)
                            @php
                                // Obtener nombre de slot (si existe), usando el modelo Slot
                                $slotName = null;
                                if ($inscription->slot_id) {
                                    $slot = Slot::find($inscription->slot_id);
                                    $slotName = $slot?->name;
                                }
                            @endphp

                            <tr class="collapse" id="inscriptions_{{ $sessionId }}">
                                <td>
                                    <i class="la la-long-arrow-right"></i>
                                    @if ($inscription->isGift())
                                        <i class="la la-gift text-warning" title="{{ __('backend.cart.gift_card') }}"></i>
                                    @endif
                                </td>
                                <td>
                                    {{ $slotName ?? '—' }}
                                </td>
                                <td>
                                    {{ $inscription->barcode }}
                                </td>
                                <td>
                                    {{ $inscription->rate->name ?? '-' }}
                                </td>
                                <td>
                                    {{ number_format($inscription->price, 2) }}€
                                </td>
                                <td colspan="1" class="d-flex align-items-center gap-2">
                                    {{-- Botón Modificar --}}
                                    @can('carts.edit')
                                        <button type="button" class="btn btn-sm btn-outline-primary ms-2 pe-2" data-bs-toggle="modal"
                                            data-bs-target="#editInscriptionModal" data-inscription-id="{{ $inscription->id }}"
                                            data-rate-id="{{ $inscription->rate_id }}" data-price="{{ $inscription->price }}"><i
                                                class="la la-edit me-1"></i>
                                            Modificar
                                        </button>
                                    @endcan

                                    {{-- Dropdown acciones --}}
                                    @if($entry->confirmation_code)
                                        <div class="dropdown">
                                            @can('carts.index')
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle pe-2" type="button"
                                                    id="dropdownActions{{ $inscription->id }}" data-bs-toggle="dropdown"
                                                    data-bs-auto-close="outside" aria-expanded="false"><i class="la la-download me-1"></i>
                                                    {{ __('backend.cart.download') }}
                                                </button>
                                            @endcan

                                            <ul class="dropdown-menu" aria-labelledby="dropdownActions{{ $inscription->id }}">
                                                @if(Route::has('inscription.generate'))
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('inscription.generate', ['inscription' => $inscription->id, 'web' => 1]) }}"
                                                            target="_blank">
                                                            <i class="la la-file-pdf-o me-1"></i> {{ __('backend.cart.inc.download') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('inscription.generate', ['inscription' => $inscription->id, 'ticket-office' => 1]) }}"
                                                            target="_blank">
                                                            <i class="la la-file-pdf-o me-1"></i>
                                                            {{ __('backend.cart.inc.download_ticket_office') }}
                                                        </a>
                                                    </li>
                                                @endif

                                                @if(auth()->check() && auth()->user()->isSuperuser())
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('open.inscription.pdf', ['inscription' => $inscription->id]) }}?token={{ $entry->token }}"
                                                            target="_blank">
                                                            <i class="la la-file-pdf-o me-1"></i> {{ __('backend.cart.inc.preview') }}
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    @endif

                                    {{-- Botón Eliminar --}}
                                    @can('carts.delete')
                                    <a href="{{ route('cart.inscription.destroy', [$entry->id, $inscription->id]) }}"
                                        class="btn btn-sm btn-outline-danger pe-2"
                                        onclick="
                                            event.preventDefault();
                                            if (!confirm('{{ trans('backpack::crud.delete_confirm') }}')) return;

                                            fetch(this.href, {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                },
                                                body: new URLSearchParams({ _method: 'DELETE' })
                                            })
                                            .then(response => {
                                                if (!response.ok) throw new Error('Error ' + response.status);
                                                window.location.href = '{{ route('cart.show', $entry->id) }}';   // ← redirección
                                            })
                                            .catch(err => {
                                                alert('No se pudo eliminar: ' + err.message);
                                            });">
                                            <i class="la la-trash me-1"></i> {{ __('backend.cart.inc.delete') }}
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif


@push('after_scripts')
    <div class="modal fade" id="editInscriptionModal" tabindex="-1" aria-labelledby="editInscriptionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('inscription.update.price') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="inscription_id" id="modalInscriptionId" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editInscriptionModalLabel">{{__('backend.cart.edit')}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modalRateId" class="form-label">{{ __('backend.cart.rate') }}</label>
                            <select name="rate_id" id="modalRateId" class="form-select" required>
                                @foreach($rates as $rate)
                                    <option value="{{ $rate->id }}">{{ $rate->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="modalPrice" class="form-label">{{ __('backend.cart.price') }}</label>
                            <input type="number" step="0.01" name="price" id="modalPrice" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        var editModal = document.getElementById('editInscriptionModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var inscriptionId = button.getAttribute('data-inscription-id');
            var rateId = button.getAttribute('data-rate-id');
            var price = button.getAttribute('data-price');

            document.getElementById('modalInscriptionId').value = inscriptionId;
            document.getElementById('modalRateId').value = rateId;
            document.getElementById('modalPrice').value = price;
        });
    </script>
@endpush

@push('after_styles')
    <style>
        .table-responsive {
            overflow: visible !important;
            position: relative;
        }

        .table-responsive .dropdown-menu {
            z-index: 2000 !important;
        }

        .w-5 {
            width: 5% !important;
        }

        .w-10 {
            width: 10% !important;
        }

        .w-15 {
            width: 15% !important;
        }

        .w-20 {
            width: 20% !important;
        }

        .w-25 {
            width: 25% !important;
        }

        .table {
            table-layout: fixed;
        }
    </style>
@endpush