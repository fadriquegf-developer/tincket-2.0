@extends('core.emails.layout')
@section('content')
<p>
    Dear {{ $client->name or '{name}' }},<br>
    <br>
    We're sending this email to you because you've rencently asked to reset your password.<br/>
    <br>
    If this request isn't coming from you, please ignore this email. Otherwise
    click this <a href="https://demo.yesweticket.com/recuperar-contrasenya/{{ $client->reset_token }}">link to access password recovery</a>.
</p>
<p>
    Best regards,
</p>
<hr>
<p style="font-weight: bold;">
    Demo de YesWeTicket <br>
    Plaça Javajan, 1<br>
    08570 Javaland<br>
    T 93 000 00 00
</p>
@endsection
