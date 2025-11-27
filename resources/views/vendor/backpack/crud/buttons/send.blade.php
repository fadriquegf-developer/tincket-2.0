@if ($entry->canBeSent())
    @php
        $url = url($crud->route . '/' . $entry->getKey() . '/send');
        $confirmText = __('backend.mail.confirm_send');
    @endphp

    <a href="{{ $url }}" class="btn btn-sm btn-success" data-button-type="send" data-confirm="{{ $confirmText }}">
        <i class="la la-paper-plane"></i> {{ __('backend.mail.send') }}
    </a>
@endif

<script>
    if (!window.__bpSendInitV1) {
        window.__bpSendInitV1 = true;

        document.addEventListener('click', function (e) {
            const el = e.target.closest('a[data-button-type="send"]');
            if (!el) return;

            e.preventDefault();
            const href = el.getAttribute('href');
            const text = el.getAttribute('data-confirm') || '¿Confirmas el envío?';

            if (window.swal) {
                swal({
                    title: text,
                    icon: 'warning',
                    buttons: {
                        cancel: {
                            text: 'Cancelar',
                            visible: true,
                            className: "bg-secondary",
                        },
                        confirm: {
                            text: "Enviar",
                            value: true,
                            visible: true,
                            closeModal: true,
                            className: "bg-success",
                        }
                    },
                    dangerMode: false
                }).then(function (ok) {
                    if (ok) window.location.href = href;
                });
                return;
            }

            if (confirm(text)) window.location.href = href;
        });
    }
</script>