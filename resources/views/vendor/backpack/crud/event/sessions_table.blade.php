<div class="card mt-4">
    <div class="card-header">
        <strong>{{ __('backend.events.sessions') }}</strong>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('backend.events.start_on') }}</th>
                <th>{{ __('backend.session.maximumplaces') }}</th>
                <th>{{ __('backend.session.occupiedplaces') }}</th>
                <th>{{ __('backend.events.updated_at') }}</th>
                <th>{{ __('backend.events.deleted_at') }}</th>

            </tr>
        </thead>
        <tbody>
            @foreach ($entry->sessions_order as $session)
                <tr>
                    <td>{{ $session->id }}</td>
                    <td>{{ $session->starts_on ? \Carbon\Carbon::parse($session->session_start)->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td>{{ $session->max_places }}</td>
                    <td>{{ $session->inscriptions()->whereNotNull('barcode')->count() }}</td>
                    <td>{{ $session->updated_at }}</td>
                    <td>{{ $session->deleted_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>