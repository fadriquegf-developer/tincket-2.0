<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">
            {{ __('backend.ticket.client') }}
        </h3>
    </div>
    <div class="box-body form-horizontal">
        <div class="form-group">
            <label for="client.email" class="col-sm-2 control-label">{{ __('backend.ticket.email') }}</label>
            <div class="input-group col-sm-9">
                <input type="email" class="form-control" name="client.email" id="email" ng-model="client.email" />

            </div>
        </div>
        <div class="form-group">
            <label for="client.firstname" class="col-sm-2 control-label">{{ __('backend.ticket.firstname') }}</label>
            <div class="input-group col-sm-9">
                <input type="text" class="form-control" name="client.firstname" id="firstname"
                    ng-model="client.firstname" />
            </div>
        </div>
        <div class="form-group">
            <label for="client.lastname" class="col-sm-2 control-label">{{ __('backend.ticket.lastname') }}</label>
            <div class="input-group col-sm-9">
                <input type="text" class="form-control" name="client.lastname" id="lastname"
                    ng-model="client.lastname" />
            </div>
        </div>
    </div>
</div>
@push('after_scripts')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <!-- FOOTER SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <script>
        // Setup CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Autocomplete
        $(function () {
            $('#email').autocomplete({
                source: function (request, response) {
                    $.post("{{ url('client/autocomplete?autocomplete=1') }}", { email: request.term })
                        .done(function (data) {
                            return response($.map(data.data, function (item) {
                                return {
                                    label: item.email + ' - ' + item.name + ' ' + item.surname,
                                    value: item.email,
                                    name: item.name,
                                    surname: item.surname
                                };
                            }));
                        })
                        .fail(function (xhr) {
                            console.error("❌ Error al buscar:", xhr.responseText);
                            response([]);
                        });
                },
                minLength: 3,
                select: function (event, ui) {
                    $('#firstname').val(ui.item.name);
                    $('#lastname').val(ui.item.surname);
                }
            });
        });
    </script>
@endpush