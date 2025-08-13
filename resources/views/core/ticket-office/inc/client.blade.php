<div class="box">
    <div class="box-header">
        <h3 class="box-title">
            {{ __('ticket-office.client') }}
        </h3>
    </div>
    <div class="box-body">
        <div class="row mb-3">
            <label for="client_email" class="col-sm-3 col-form-label">
                {{ __('ticket-office.email') }}
            </label>
            <div class="col-sm-9">
                <input type="email" 
                       class="form-control" 
                       name="client[email]" 
                       id="client_email" 
                       ng-model="client.email"
                       autocomplete="email" />
            </div>
        </div>
        <div class="row mb-3">
            <label for="client_firstname" class="col-sm-3 col-form-label">
                {{ __('ticket-office.firstname') }}
            </label>
            <div class="col-sm-9">
                <input type="text" 
                       class="form-control" 
                       name="client[firstname]" 
                       id="client_firstname"
                       ng-model="client.firstname"
                       autocomplete="given-name" />
            </div>
        </div>
        <div class="row mb-3">
            <label for="client_lastname" class="col-sm-3 col-form-label">
                {{ __('ticket-office.lastname') }}
            </label>
            <div class="col-sm-9">
                <input type="text" 
                       class="form-control" 
                       name="client[lastname]" 
                       id="client_lastname"
                       ng-model="client.lastname"
                       autocomplete="family-name" />
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    // Setup CSRF for jQuery
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Autocomplete functionality
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
                .fail(function (xhr) {
                    console.error("Error searching:", xhr.responseText);
                    response([]);
                });
            },
            minLength: 3,
            select: function (event, ui) {
                $('#client_firstname').val(ui.item.name);
                $('#client_lastname').val(ui.item.surname);
                
                // Update Angular model
                var scope = angular.element($('#client_email')).scope();
                scope.$apply(function() {
                    scope.client.email = ui.item.value;
                    scope.client.firstname = ui.item.name;
                    scope.client.lastname = ui.item.surname;
                });
            }
        });
    });
</script>
@endpush
