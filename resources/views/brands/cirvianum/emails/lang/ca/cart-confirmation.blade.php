@extends('brands.cirvianum.emails.layout')
@section('content')
<p>
    Benvolgut/da {{ $cart->client->name ?? '{name}' }},<br>
    <br>
    En aquest email trobaràs adjuntes les entrades. Recorda
    imprimir-les o portar-les descarregades al mòbil.
</p>
<p>
    El teu codi de compra és: <span style="font-weight: bold;">{{ $cart->confirmation_code ?? '{code}' }}</span>
</p>
<p>
    Esperem que gaudeixis de l'espectacle!
</p>
<p>
    Salutacions,
</p>
<p>
    Facebook | <a href="https://www.facebook.com/Teatrecirvianum/" target="_blank">Teatre Cirviànum</a> <br>
    Twiter | <a href="https://twitter.com/teatrecirvianum" target="_blank">Teatre Cirviànum</a>
</p>
<p style="font-weight: bold;">
    Teatre Cirviànum de Torelló <br>
    Plaça Nova, 10<br>
    08570 Torelló<br>
    T 938 50 40 95  
</p>
@endsection
