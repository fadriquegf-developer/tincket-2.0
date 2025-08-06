@if ($entry->client)
    <div id="client" class="card mb-4 border">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('backend.cart.client') }}</h5>
            <div class="btn-group btn-group-sm">
                @if(auth()->check() && auth()->user()->isSuperuser())
                    <button type="button" class="btn btn-outline-secondary fa-edit" data-bs-toggle="modal"
                        data-bs-target="#editClientModal">
                        <i class="la la-edit"></i>
                    </button>
                @endif
            </div>
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

@once
    @push('after_scripts')
        <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ url($crud->route . '/' . $entry->getKey() . '/change-client') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editClientModalLabel">{{__('backend.cart.changeClient')}}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <label for="client_id" class="form-label">{{__('backend.menu.client')}}</label>
                            <select class="form-control" id="client_id" name="client_id" style="width:100%">

                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">
                                <i class="la la-save me-1"></i>
                                {{__('backend.rate.save')}} {{ __('backend.menu.client') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $('#client_id').select2({
                dropdownParent: $('#editClientModal'),
                theme: 'bootstrap-5',
                placeholder: '{{  __('backend.cart.searchBy') }}',
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route("client.autocomplete") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function (client) {
                                return {
                                    id: client.id,
                                    text: client.name + " " + client.surname + " (" + client.email + ")"
                                }
                            })
                        };
                    },
                    cache: true
                }
            });
        </script>
    @endpush
@endonce