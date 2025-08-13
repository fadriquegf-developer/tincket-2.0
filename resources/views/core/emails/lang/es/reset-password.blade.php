@extends('core.emails.layout')
@section('content')
<p>
    Hola {{ $client->name ?? '{name}' }},<br>
    <br>
    Le enviamos este email porque nos ha solicitado un cambio de contraseña<br/>
    <br>
    Si no ha sido usted ignore el correo. En caso contrario
    haga clic en el siguiente <a href="https://demo.yesweticket.com/recuperar-contrasenya/{{ $client->reset_token }}">enlace para realizar el cambio</a>.
</p>
<p>
    Saludos cordiales,
</p>
<hr>
<p style="font-weight: bold;">
    Demo de YesWeTicket <br>
    Plaça Javajan, 1<br>
    08570 Javaland<br>
    T 93 000 00 00
</p>
@endsection
