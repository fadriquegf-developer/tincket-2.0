@if ($entry->groupPacks->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ __('backend.cart.inc.packs') }}</h5>
        </div>

        {{-- ---------- CADA PAQUETE ---------- --}}
        <div class="card-body">
            @foreach ($entry->groupPacks as $group_pack)
                <div class="table-responsive mb-4">

                    {{-- tabla “cabecera” del pack ------------------------------------------------ --}}
                    <table class="table table-borderless table-sm align-middle mb-2 gp-table">
                        <thead>
                            <tr class="text-uppercase small text-muted">
                                <th style="width:40%">{{ __('backend.cart.cart') }} {{ __('backend.cart.inc.pack') }} ID
                                </th>
                                <th>{{ __('backend.cart.inc.packs') }}</th>
                                <th># {{ __('backend.cart.inc.session') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="fw-semibold">
                                <td>{{ $group_pack->id }}</td>
                                <td>{{ $group_pack->pack->name }}</td>
                                <td>{{ $group_pack->inscriptions->count() }}</td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- tabla con las inscripciones ------------------------------------------------ --}}
                    <table class="table table-sm table-hover table-borderless mb-0 gp-table">
                        <thead>
                            <tr class="text-uppercase small text-muted">
                                <th style="width:40%">{{ __('backend.cart.inc.event') }}</th>
                                <th>{{ __('backend.cart.inc.place') ?? 'Lloc' }}</th>
                                <th>Barcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group_pack->inscriptions as $inscription)
                                <tr>
                                    <td>
                                        {{ $inscription->session->event->name }}
                                        — {{ $inscription->session->starts_on }}
                                    </td>
                                    <td>
                                        @if ($inscription->slot_id)
                                            @php
                                                $key = 'slot-' . $inscription->slot_id . '-name';
                                                $slot = Cache::store('array')->remember(
                                                    $key,
                                                    30,
                                                    fn() => \App\Models\Slot::find($inscription->slot_id)->name,
                                                );
                                            @endphp
                                            {{ $slot }}
                                        @endif
                                    </td>
                                    <td>{{ $inscription->barcode }}</td>
                                    <td class="">

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
@endif

@push('after_styles')
    <style>
        /* --------------- Zebra + hover (igual que en los listados Backpack) --------------- */
        html[data-bs-theme="dark"] .gp-table tbody tr:nth-child(even) td {
            background: rgba(255, 255, 255, .01);
        }

        html[data-bs-theme="light"] .gp-table tbody tr:nth-child(even) td {
            background: rgba(0, 0, 0, .02);
        }

        .gp-table tbody tr:hover td {
            background: rgba(255, 255, 255, .04);
            /* sirve para ambos temas */
        }

        /* bordes finos en la parte superior de las tablas */
        .gp-table thead th {
            border-bottom: 1px solid rgba(255, 255, 255, .08) !important;
        }
    </style>
@endpush

@push('after_scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            $(document).on('click', '.js-del-insc', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                if (!confirm('{{ trans('backpack::crud.delete_confirm') }}')) return;

                fetch($(this).data('url'), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => {
                        if (r.status >= 200 && r.status < 400) {
                            location.reload();
                        } else {
                            alert('Error eliminant la inscripció');
                        }
                    })
                    .catch(() => alert('Error eliminant la inscripció'));
            });

        });
    </script>
@endpush
