@extends('core.emails.layout')
@php($brand = $cart->brand)

@section('content')
<p>
    Benvolgut/da,
</p>
<p>
    Des de la plataforma de ticketing del Teatre Cirvianum, us informem d'una incidència que va tenir lloc entre els dies 12 i 14 de setembre, motiu per la qual la vostra comanda {{$cart->confirmation_code}} no ha estat pagada correctament, tot i que el sistema us va enviar les entrades.
</p>
<p>
    Us preguem doncs, que cliqueu al següent enllaç per tal de formalitzar la compra i poder validar les vostres entrades correctament: <a style="font-weight: bold;" href="{{config('clients.frontend.url')}}/reserva/pagament/carrito/{{ $cart->token }}">Link de pagament</a>
</p>
<p>
    En cas que no us funcioni l'enllaç anterior, copieu i enganxeu la següent adreça en el vostre navegador:<br>
    {{config('clients.frontend.url')}}/reserva/pagament/carrito/{{ $cart->token }}
</p>
<p>
    En cas de no poder realitzar el pagament en línia, podeu venir presencialment al Departament de Cultura de l'Ajuntament de Torelló (plaça Nova, 11, 1r pis), o bé a la taquilla del Teatre Cirvianum, qualsevol dia hi hagi funció, per tal de fer efectiu el pagament corresponent.
</p>
<p>
    Per qualsevol dubte estem a la vostra disposició. 
</p>
<p>
    Disculpeu les molèsties.
</p>
@endsection
