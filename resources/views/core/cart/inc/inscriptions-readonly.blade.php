@if ($entry->inscriptions->isNotEmpty())
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entry->inscriptions->groupBy('session_id') as $sessionId => $set)
                        @php
                            $first = $set->first();
                            $session = $first->session;
                            $sessionDate =
                                $session && $session->starts_on ? $session->starts_on->format('d/m/Y H:i') : '—';
                            $sessionName = $session && $session->name ? $session->name : null;
                        @endphp

                        <tr data-bs-toggle="collapse" data-bs-target="#inscriptions_{{ $sessionId }}"
                            style="cursor: pointer; border-bottom: 1px solid #dee2e6;">
                            <td>{{ $session?->id ?? '—' }}</td>
                            <td>{{ $session?->event->name ?? '—' }}</td>
                            <td>
                                @if ($sessionName)
                                    {{ $sessionName }}<br>
                                @endif
                                <small class="text-muted">{{ $sessionDate }}</small>
                            </td>
                            <td></td>
                            <td></td>
                            <td>{{ $set->count() }}</td>
                        </tr>

                        @foreach ($set as $inscription)
                            @php
                                $slotName = $inscription->slot?->name;
                            @endphp

                            <tr class="collapse" id="inscriptions_{{ $sessionId }}">
                                <td><i class="la la-long-arrow-right"></i></td>
                                <td>{{ $slotName ?? '—' }}</td>
                                <td>{{ $inscription->barcode }}</td>
                                <td>{{ $inscription->rate->name ?? '-' }}</td>
                                <td>{{ number_format($inscription->price, 2) }}€</td>
                                <td><span class="badge bg-secondary">Eliminado</span></td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
