@extends('core.emails.layout')
@php($brand = $cart->brand)

@section('content')
<p>
    Benvolgut usuari/a ,
</p>
<p>
    Des de la plataforma de ticketing del Festival Ulisses de Premià, us informem d'una incidència que ha tingut lloc entre el 12/09 i el 14/09 i per la que la vostra comanda {{$cart->confirmation_code}} no ha estat pagada correctament tot i que el sistema us va enviar les entrades.
</p>
<p>
    Us preguem doncs, que cliqueu al següent enllaç per tal de formalitzar la compra i poder validar les vostres entrades correctament.
</p>
<p>
    <a style="font-weight: bold;" href="{{config('clients.frontend.url')}}/reserva/pagament/carrito/{{ $cart->token }}">Link de pagament</a>
</p>
<p>
    En cas que no us funcioni l'enllaç anterior, copieu i enganxeu la següent adreça en el vostre navegador<br>
    {{config('clients.frontend.url')}}/reserva/pagament/carrito/{{ $cart->token }}
</p>
<p>
    El marge per efectuar el pagament és fins demà dia 16/09 a les 10:00h (matí). A partir d'aquesta hora , les comandes que no constin com a pagades, s'eliminaran i les entrades quedaran anul·lades automàticament.
</p>
<p>
    Per qualsevol dubte ens podeu contactar a <a href="mailto:info@ticketara.com">info@ticketara.com</a>
</p>
<p>
    Disculpeu les molèsties.
</p>
<p>
    Equip de Ticketara.
</p>
@endsection
