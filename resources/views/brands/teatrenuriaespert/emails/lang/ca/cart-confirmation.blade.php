@extends('brands.teatrenuriaespert.emails.layout')
@section('content')
<p>
    Benvolgut {{ $cart->client->name or '{name}' }},<br>
    <br>
    En aquest email trobaràs adjuntes les entrades. Pots portar-les descarregades al mòbil, no cal imprimir-les
</p>
<p>
    El teu codi de compra és: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
</p>
<p>
    Esperem que gaudeixis de l'espectacle!
</p>
<p>
    Salutacions,
</p>
<p>
    Teatre Núria Espert<br>
    Plaça Federico García Lorca<br>
    <a href="mailto:info@teatrenuriaespert.cat">info@teatrenuriaespert.cat</a>
</p>
@endsection
