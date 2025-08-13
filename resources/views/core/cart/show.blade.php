{{-- resources/views/vendor/backpack/crud/custom_cart_show.blade.php --}}
@extends('crud::show')

@section('header')
  <section class="content-header">
    <h1 class="mx-3">
      {{ trans('backpack::crud.preview') }}
      <span>{{ $crud->entity_name }} {{ $entry->confirmation_code }}</span>
    </h1>
  </section>
@endsection

@section('content')
  {{-- “Volver al listado” --}}
  @if ($crud->hasAccess('list'))
    <a href="{{ url($crud->route) }}" class="mb-2">
      <i class="la la-angle-double-left mb-4"></i>
      {{ trans('backpack::crud.back_to_all') }}
      <span>{{ $crud->entity_name_plural }}</span>
    </a>
  @endif

  {{-- Acciones de Tickets / Regenerar / Enviar email / Cambiar Gateway / Pago Taquilla --}}
  @if($entry->confirmation_code !== null)
    <div class="mb-3">
      <a href="{{ url($crud->route.'/'.$entry->getKey().'/download') }}"
         class="btn btn-sm btn-outline-primary me-2">
        <i class="la la-download me-1"></i>
        {{ __('backend.cart.download_tickets') }}
      </a>

      <a href="{{ url($crud->route.'/'.$entry->getKey().'/regenerate') }}"
         class="btn btn-sm btn-outline-primary me-2">
        <i class="la la-file-pdf-o me-1"></i>
        {{ __('backend.cart.regenerate_tickets') }}
      </a>

      <a href="{{ url($crud->route.'/'.$entry->getKey().'/regenerate?send=true') }}"
         class="btn btn-sm btn-outline-primary me-2">
        <i class="la la-at me-1"></i>
        {{ __('backend.cart.regenerate_send_tickets') }}
      </a>
      @if(auth()->check() && auth()->user()->isSuperuser() && $entry->client_id !== null)
        {{-- Botón: Enviar email de pago --}}
        <button type="button"
                class="btn btn-sm btn-outline-primary me-2"
                data-bs-toggle="modal"
                data-bs-target="#mailPaymentModal">
          <i class="la la-credit-card-alt me-1"></i>
          {{ __('backend.cart.send_mail_payment') }}
        </button>
      @endif 
      @push('after_scripts')
        {{-- Modal: Enviar email de pago --}}
        <div class="modal fade" id="mailPaymentModal" tabindex="-1" aria-labelledby="mailPaymentModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="mailPaymentModalLabel">
                  Alerta sobre enviament del email de Pagament
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tancar"></button>
              </div>
              <div class="modal-body">
                Tenir en compte que enviar l’email de pagament implicarà que s’eliminarà la línia de pagament del carrito,
                i el codi de confirmació es convertirà en XXXXXXXXX-{{ $entry->getKey() }}.
                Utilitzar només en casos on el TPV hagi tingut un error en el pagament.
                També es guardarà el codi de confirmació actual dins del camp comentaris, per poder verificar que al TPV va donar error.
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tancar</button>
                <a href="{{ url($crud->route.'/'.$entry->getKey().'/send-mail-payment') }}"
                   class="btn btn-primary">
                  <i class="la la-credit-card-alt me-1"></i>
                  {{ __('backend.cart.send_mail_payment') }}
                </a>
              </div>
            </div>
          </div>
        </div>
      @endpush

      @if(auth()->check() && auth()->user()->isSuperuser())
        {{-- Botón: Cambiar Plataforma de Pagament --}}
        <button type="button"
                class="btn btn-sm btn-outline-primary me-2"
                data-bs-toggle="modal"
                data-bs-target="#changePaymentGateway">
          <i class="la la-money me-1"></i>
          {{ __('backend.cart.changeGateway') }}
        </button>
        @php
            $currentGateway = $entry->payment->gateway ?? null;
        @endphp
        @push('after_scripts')
        {{-- Modal: Cambiar Plataforma de Pagament --}}
        <div class="modal fade" id="changePaymentGateway" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <form action="{{ route('crud.cart.change-gateway', $entry->getKey()) }}" method="POST">
                @csrf
                <input type="hidden" name="cart_id" value="{{ $entry->getKey() }}">
                <div class="modal-header">
                  <h5 class="modal-title">{{__('backend.cart.select_gateway')}}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tancar"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label for="gateway" class="form-label">Gateway</label>
                    <select id="gateway" name="gateway" class="form-select">
                      <option value="TicketOffice"
                          {{ $currentGateway === 'TicketOffice' ? 'selected' : '' }}>
                          {{__('backend.cart.cash')}}
                      </option>
                      <option value="Redsys Redirect"
                          {{ $currentGateway === 'Redsys Redirect' ? 'selected' : '' }}>
                          {{__('backend.cart.card')}}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('backend.cart.close')}}</button>
                  <button type="submit" class="btn btn-primary">{{__('backend.cart.change')}}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        @endpush
      @endif
    </div>
  @endif

  @if(
      ($entry->confirmation_code === null && !isset($entry->payment))
      || (str_contains($entry->confirmation_code, 'XXXXXXXXX'))
  )
  
    <div class="mb-3">
      {{-- Botón: Pago a Taquilla --}}
      <button type="button"
              class="btn btn-sm btn-outline-primary me-2"
              data-bs-toggle="modal"
              data-bs-target="#ticketOfficePaymentModal">
        <i class="la la-credit-card-alt me-1"></i>
        {{ __('backend.cart.payment_ticket_office') }}
      </button>
    @push('after_scripts')
      {{-- Modal: Pago a Taquilla --}}
      <div class="modal fade" id="ticketOfficePaymentModal" tabindex="-1" aria-labelledby="ticketOfficePaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form action="{{ route('crud.cart.payment-office') }}" method="POST">
              @csrf
              <input type="hidden" name="cart_id" value="{{ $entry->getKey() }}">
              <div class="modal-header">
                <h5 class="modal-title" id="ticketOfficePaymentModalLabel">
                  Realitzar pagament a taquilla
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tancar"></button>
              </div>
              <div class="modal-body">
                <div class="form-check">
                  <input class="form-check-input"
                         type="radio"
                         name="payment_type"
                         id="payment_type_1"
                         value="{{ App\Services\Payment\Impl\PaymentTicketOfficeService::CASH }}"
                         checked>
                  <label class="form-check-label" for="payment_type_1">
                    {{ __('backend.ticket.payment_type.cash') }}
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input"
                         type="radio"
                         name="payment_type"
                         id="payment_type_2"
                         value="{{ App\Services\Payment\Impl\PaymentTicketOfficeService::CARD }}">
                  <label class="form-check-label" for="payment_type_2">
                    {{ __('backend.ticket.payment_type.card') }}
                  </label>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tancar</button>
                <button type="submit" class="btn btn-success">Realitzar Pagament</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endpush

      {{-- Botón: Descargar Tickets --}}

     @if(auth()->check() && auth()->user()->isSuperuser() && $entry->client_id !== null)
        {{-- Botón: Ver URL de Pagament --}}
        <button type="button"
                class="btn btn-sm btn-outline-primary me-2"
                data-bs-toggle="modal"
                data-bs-target="#urlPaymentModal">
          <i class="la la-external-link me-1"></i>
          {{ __('backend.cart.payment_url') }}
        </button>

        @push('after_scripts')
        {{-- Modal: URL de Pagament --}}
          <div class="modal fade" id="urlPaymentModal" tabindex="-1" aria-labelledby="urlPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="urlPaymentModalLabel">URL de Pagament</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tancar"></button>
                </div>
                <div class="modal-body">
                  {{ brand_setting('clients.frontend.url') }}reserva/pagament/carrito/{{ $entry->token }}
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tancar</button>
                </div>
              </div>
            </div>
          </div>
        @endpush
      @endif
    </div>
  @endif
  
  <div class="w-100 mb-3">
      @include('core.cart.inc.inscriptions')
    </div>

    <div class="w-100 mb-3">
      @include('core.cart.inc.packs')
    </div>

    <div class="w-100 mb-3">
      @include('core.cart.inc.gift_cards')
    </div>

    <div class="w-100 mb-3">
      @include('core.cart.inc.client')
    </div>

  <form action="{{ route('cart.update', $entry->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row mb-3">
      <div class="col-md-8">
        @include('core.cart.inc.payment')
      </div>

      <div class="col-md-4">
        @include('core.cart.inc.comment')
      </div>
    </div>

    {{-- BOTONES DE GUARDADO --}}
    <div class="row mt-4">
      <div class="col-12">
        <div class="card bg-light">
          <div class="card-body">
            <input type="hidden" name="save_action" value="save_and_back">
            <button type="submit" class="btn btn-success">
              <i class="la la-save me-1"></i>
              {{ trans('backpack::crud.save') }}
            </button>
            <a href="{{ route('cart.index') }}" class="btn btn-secondary">
              <i class="la la-ban me-1"></i>
              {{ trans('backpack::crud.cancel') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>
@endsection

@section('after_styles')
  @parent
  {{-- jQuery UI CSS (si realmente lo necesitas) --}}
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css"
    rel="stylesheet"
    {{-- integrity="sha512-uto9mlQIQb7U8cTMsIM+HbYFKWlzl81lCpD+GsaU4iA4UMlRpkWzoG2vI5C1PtPs/ggGz5ZT0G63eUb9vKcZAQ==" --}}
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />
@endsection

@section('after_scripts')
  {{-- Dependencias JS: Underscore y jQuery UI (si tu lógica las requiere) --}}
  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"
    {{-- integrity="sha512-Zi4vLVSs2WL7JP+N8KmWCm+hW6u3iASvFXuAkYP8Ymjbn5n51ydg1hohGlBIIsVwMXCm4EddU1E0P3fP67nDJg==" --}}
    crossorigin="anonymous"
    referrerpolicy="no-referrer">
  </script>
  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"
    {{-- integrity="sha512-uto9mlQzrs59VwIL59U0vO4pqjbxDWu7dgfaCjEqLGsrx2G1deYWho3NG3g34tUbp9p1OmL2LlfWPBs4hw9bpg==" --}}
    crossorigin="anonymous"
    referrerpolicy="no-referrer">
  </script>

  <script type="text/javascript">
    jQuery(document).ready(function ($) {
      // Eliminar confirmación con AJAX
      $("[data-button-type=delete]").off('click').on('click', function (e) {
        e.preventDefault();
        var delete_button = $(this);
        var delete_url    = $(this).attr('href');

        if (confirm("{{ trans('backpack::crud.delete_confirm') }}")) {
          $.ajax({
            url: delete_url,
            type: 'DELETE',
            success: function (result) {
              new PNotify({
                title: "{{ trans('backpack::crud.delete_confirmation_title') }}",
                text: "{{ trans('backpack::crud.delete_confirmation_message') }}",
                type: "success"
              });
              delete_button.closest('tr').remove();
              var total_inscriptions = parseInt($('#ins-count').text() || '0');
              $('#ins-count').text(total_inscriptions - 1);
            },
            error: function (result) {
              new PNotify({
                title: "{{ trans('backpack::crud.delete_confirmation_not_title') }}",
                text: "{{ trans('backpack::crud.delete_confirmation_not_message') }}",
                type: "warning"
              });
            }
          });
        } else {
          new PNotify({
            title: "{{ trans('backpack::crud.delete_confirmation_not_deleted_title') }}",
            text: "{{ trans('backpack::crud.delete_confirmation_not_deleted_message') }}",
            type: "info"
          });
        }
      });

      @if(session('download_all_inscriptions', false))
        // Lógica de descarga exponencial
        let attempts = 0;
        function downloadAttempt() {
          $.get("{{ route('crud.cart.download', ['cart' => $entry->getKey()]) }}")
            .done(function () {
              window.location.href = "{{ route('crud.cart.download', ['cart' => $entry->getKey()]) }}";
            })
            .fail(function () {
              attempts++;
              if (attempts > 16) {
                new PNotify({
                  title: "PDF cannot be downloaded",
                  text : "Files cannot be retrieved. Try manually or contact staff.",
                  type : "error"
                });
              } else {
                setTimeout(downloadAttempt, 500 * attempts);
              }
            });
        }
        downloadAttempt();
      @endif
    });
  </script>

  @stack('after_scripts')
@endsection
