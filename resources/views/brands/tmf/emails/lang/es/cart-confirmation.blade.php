@extends('brands.tmf.emails.layout')
@section('content')
<p>
    Hola {{ $cart->client->name ?? '{name}' }},
</p>
<p>
    En este correo encontrarás adjuntas las entradas para asistir al Festival. Recuerda imprimirlas o llevarlas descargadas en tu móvil. Podrás acceder a la sala a partir de 15 minutos antes del inicio de la sesión.<br>
    Si tienes cualquier duda, puedes ponerte en contacto con nosotros en esta dirección de correo: <a href="mailto:info@torellomountainfilm.cat">info@torellomountainfilm.cat</a>.
</p>
<p>
    Tu código de compra es: <span style="font-weight: bold;">{{ $cart->confirmation_code ?? '{code}' }}</span>
</p>
<p>
    ¿Tienes previsto comer algo antes de entrar al cine? ¿Vas con el tiempo justo? ¿O prefieres comer o beber tranquilamente mientras esperas a que comience la proyección?
</p>
<p>
    En <b>Camp Base</b> encontrarás servicio de barra con nuestros amigos de Animal Bar. Nos ofrecerán diferentes opciones de pinchos, tanto calientes como fríos, y algunos bocadillos de autor, con opción vegetariana. No faltará buena música y buen ambiente.
</p>
<p>
    Abierto del 18 al 22 todos los días de 18:00 a 22:30.
</p>
@endsection
