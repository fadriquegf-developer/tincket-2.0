@php
  $btnClass = $entry->liquidation ? 'bg-success' : 'bg-danger';
  $confirmText = $entry->liquidation
    ? __('backend.session.confirm_unliquidate')
    : __('backend.session.confirm_liquidate');
  $url = url($crud->route . '/' . $entry->getKey() . '/liquidation');
@endphp

<a href="{{ $url }}" class="dropdown-item {{ $btnClass }} text-white" data-button-type="liquidate"
  data-confirm="{{ $confirmText }}">
  <i class="la la-euro me-2 text-white"></i> {{ __('backend.session.liquidation') }}
</a>

<script>
  // evita múltiples inicializaciones si la vista se repite
  if (!window.__bpLiquidateInitV1) {
    window.__bpLiquidateInitV1 = true;

    document.addEventListener('click', function (e) {
      const el = e.target.closest('a[data-button-type="liquidate"]');
      if (!el) return;

      e.preventDefault(); // NO navegar
      const href = el.getAttribute('href');
      const text = el.getAttribute('data-confirm') || '¿Confirmas?';

      // SweetAlert v1 (sweetalert.js.org)
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
              text: "Confirmar",
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

      // Fallback nativo si por lo que sea no está cargado sweetalert v1
      if (confirm(text)) window.location.href = href;
    });
  }
</script>