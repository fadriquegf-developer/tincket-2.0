@extends('core.emails.layout')
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
@endsection
