@extends('core.emails.layout')

@section('content')
<p>
    L'alta del promotor <b>{{ $name }}</b> s'ha realitzat correctament.
</p>
<p>
    Les dades d'accés son:
</p>
<p>
    <b>Email:</b> {{ $email }}
    <b>Contrasenya:</b> {{ $password }}
    <b>URL Gestió:</b> <a href="https://{{ $code_name }}.yesweticket.com" target="_blank">{{ $code_name }}.yesweticket.com</a>
    <b>URL Promotor:</b> <a href="{{ $front_url }}/b/{{$code_name}}" target="_blank">{{ $front_url }}/b/{{$code_name}}</a>
</p>
<p>
En un marge de 24h/48h hores donarem d'alta el servei. T'enviarem un correu electrònic per confirmar que el servei ja està actiu. A partir d'aquest moment podràs accedir amb les credencials facilitades en aquest correu.
</p>
<p>
Atentament,<br>
{{ config('mail.contact.signature', 'Equip de Ticketara') }}
</p>
@endsection
