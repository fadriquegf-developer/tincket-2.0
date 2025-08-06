@extends('core.emails.layout')
@section('content')
<p>
    Ola {{ $client->name or '{name}' }},<br>
    <br>
    Solicitaches un cambio de contrasinal?<br/>
    <br>
    Se non fuches ti, por favor, ignora este correo. En caso contrario clica na seguinte Si no ha sido usted ignore el correo. En caso contrario
    haga clic en el siguiente <a href="https://demo.yesweticket.com/recuperar-contrasenya/{{ $client->reset_token }}">ligazón para facer o cambio</a>.
</p>
<p>
    Saúdos ingrávidos,
</p>
<hr>
<p style="font-weight: bold;">
    Sala Ingrávida <br>
    Rua Pérez Leirós, nº3 Baixo<br>
    36400, O Porriño, PO<br>
    T 986 331 622
</p>
@endsection
