@php
    $entry = $crud->getCurrentEntry();
@endphp

@include('crud::fields.inc.wrapper_start')
    
    <div class="form-inline mailing-test-form mt-4">
        <label for="testEmail">{!! $field['label'] !!}</label>
        <div class="form-group col-md-5 col-xs-12" style="padding: 0 !important;">
            <input type="email" class="form-control mb-2" id="testEmail" style="width: 100%;" placeholder="correo@ejemplo.com"/>
        </div>
        <button type="button" class="btn btn-default">{{ __('backend.mail.test_it') }}</button>
    </div>
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
    jQuery(document).ready(function ($) {
        $(".mailing-test-form .btn").click(function () {
            var btn = $(this);
            var email = $(this).parent().find("#testEmail").val();
            
            if (!email) {
                new Noty({
                    type: "error",
                    text: "Por favor introduce un email"
                }).show();
                return;
            }
            
            var url = "{{ route('apibackend.mailing.test', $entry->id ?? 0) }}?email=" + email;
            
            btn.prop('disabled', true).text('Enviando...');

            $.get(url).done(function () {
                new Noty({
                    text: "Test enviado correctamente a " + email,
                    type: "success"
                }).show();
                btn.prop('disabled', false).text("{{ __('backend.mail.test_it') }}");
            }).fail(function() {
                new Noty({
                    title: "Error",
                    text: "No se pudo enviar el test",
                    type: "error"
                }).show();
                btn.prop('disabled', false).text("{{ __('backend.mail.test_it') }}");
            });
        });
    });
</script>
@endpush