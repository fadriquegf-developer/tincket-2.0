@if ($entry->client)
    <div id="client" class="card mb-4 border">
        <div class="card-header">
            <h5 class="mb-0">{{ __('backend.cart.client') }}</h5>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.client.name') }}</label>
                <div class="col-sm-9">{{ $entry->client->name }}</div>
            </div>
            <div class="row mb-2">
                <label class="col-sm-3 fw-semibold">{{ __('backend.client.surname') }}</label>
                <div class="col-sm-9">{{ $entry->client->surname }}</div>
            </div>
            <div class="row">
                <label class="col-sm-3 fw-semibold">Email</label>
                <div class="col-sm-9">{{ $entry->client->email }}</div>
            </div>
        </div>
    </div>
@endif