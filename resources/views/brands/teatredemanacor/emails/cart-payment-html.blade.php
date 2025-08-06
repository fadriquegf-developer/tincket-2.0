@extends('core.emails.layout')
@php($brand = $cart->brand)

@section('content')
<p>
    Benvolgut usuari/a ,
</p>
<p>
    Des de la plataforma de ticketing del Teatre Auditori de Manacor, us informem d'una incidència que ha tingut lloc entre el 12/09 i el 14/09 i per la que la vostra comanda {{$cart->confirmation_code}} no ha estat pagada correctament tot i que el sistema us va enviar les entrades.
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
    En cas de no poder realitzar el pagament online , podeu venir presencialment al Teatre per tal de fer l'abonament corresponent.
</p>
<p>
    Per qualsevol dubte estem a la vostra disposició
</p>
<p>
    Disculpeu les molèsties.
</p>
@endsection
