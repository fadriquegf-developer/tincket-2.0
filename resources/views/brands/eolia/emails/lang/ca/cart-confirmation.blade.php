@extends('brands.eolia.emails.layout')
@section('content')
<p>
    Benvolgut {{ $cart->client->name ?? '{name}' }},<br>
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
<!--<p style="font-weight: bold;">
    Casal Riudomenc - Teatre Auditori<br/>
    C/ Sant Jaume, 2<br/>
    43330 Riudoms<br/>
    T. 977 76 82 58
</p>-->
@endsection
