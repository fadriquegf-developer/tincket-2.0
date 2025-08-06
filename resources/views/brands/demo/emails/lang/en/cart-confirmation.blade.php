@extends('brands.demo.emails.layout')
@section('content')
<p>
    Dear {{ $cart->client->name or '{name}' }},<br>
    <br>
    You can find attached your ticket. Please, bring them printed or downloaded
    on your phone the day of the show.
</p>
<p>
    Your confirmation code is: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
</p>
<p>
    Enjoy the show!
</p>
<p>
    Regards,
</p>
@endsection
