{{-- resources/views/core/ticket-office/inc/client.blade.php --}}
<div class="box">
    <div class="box-header">
        <h4 class="box-title">{{ __('ticket-office.client') }}</h4>
    </div>
    <div class="box-body">
        <div class="mb-3">
            <label for="client_email" class="form-label">{{ __('ticket-office.email') }}</label>
            <input id="client_email" type="email" name="client[email]" class="form-control" value="{{ old('client.email', $old_data['client']['email'] ?? '') }}">
        </div>
        <div class="mb-3">
            <label for="client_firstname" class="form-label">{{ __('ticket-office.firstname') }}</label>
            <input id="client_firstname" type="text" name="client[firstname]" class="form-control" value="{{ old('client.firstname', $old_data['client']['firstname'] ?? '') }}">
        </div>
        <div class="mb-3">
            <label for="client_lastname" class="form-label">{{ __('ticket-office.lastname') }}</label>
            <input id="client_lastname" type="text" name="client[lastname]" class="form-control" value="{{ old('client.lastname', $old_data['client']['lastname'] ?? '') }}">
        </div>
    </div>
</div>

@push('after_scripts')
<script>
    // Configuración de AJAX y autocompletado
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(function () {
        $('#client_email').autocomplete({
            source: function (request, response) {
                $.post("{{ url('client/autocomplete') }}", {
                    email: request.term,
                    autocomplete: 1
                })
                .done(function (data) {
                    response($.map(data.data, function (item) {
                        return {
                            label: item.email + ' - ' + item.name + ' ' + item.surname,
                            value: item.email,
                            name: item.name,
                            surname: item.surname
                        };
                    }));
                })
                .fail(function () {
                    response([]);
                });
            },
            minLength: 3,
            select: function (event, ui) {
                $('#client_email').val(ui.item.value);
                $('#client_firstname').val(ui.item.name);
                $('#client_lastname').val(ui.item.surname);

                // Si deseas actualizar datos en Vue, puedes emitir un evento personalizado:
                document.dispatchEvent(new CustomEvent('client-selected', {
                    detail: {
                        email: ui.item.value,
                        firstname: ui.item.name,
                        lastname: ui.item.surname
                    }
                }));
            }
        });
    });
</script>
@endpush
