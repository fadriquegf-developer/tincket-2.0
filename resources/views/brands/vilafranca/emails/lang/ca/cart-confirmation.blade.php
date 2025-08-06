@extends('brands.vilafranca.emails.layout')
@section('content')
<p>
    Benvolgut/da {{ $cart->client->name or '{name}' }},<br>
    <br>
    En aquest email trobaràs adjuntes les entrades. Recorda
    imprimir-les o portar-les descarregades al mòbil.
</p>
<table border="1" width="100%" cellpadding="1" cellspacing="0" bgcolor="ffffff">
        @foreach($cart->inscriptions as $inscription)
        @if ($loop->first)
            <tr>
                <th colspan="4">
                    <b>Llistat d'entrades</b>
                </th>
            </tr>
            <tr>
                <th><b>Sessió</b></th>
                <th><b>Codi de barres</b></th>
                <th><b>Preu</b></th>
                <th></th>
            </tr>
        @endif
        <tr>
            <td><i class="fa fa-long-arrow-right mr-3" aria-hidden="true"></i> {{$inscription->session->name_filter}}</td>
            <td>{{$inscription->barcode}}</td>
            <td>{{$inscription->price}}€</td>
            <td style="text-align: right;">
                @if($inscription->price == 0)
                <a target="_blank" href="https://entrades.vilafranca.cat/perfil/cart/{{$cart->id}}/inscription/{{$inscription->id}}/delete/{{$cart->token}}?from_email=true" type="button">
                    Eliminar
                </a>
                @endif
            </td>
        </tr>
        
        @endforeach
</table>
<p>
    El teu codi de compra és: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
</p>
<p>
    Esperem que gaudeixis de l'espectacle!
</p>
<p>
    Salutacions,
</p>
@endsection
