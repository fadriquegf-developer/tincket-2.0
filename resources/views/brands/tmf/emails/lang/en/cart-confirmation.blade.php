@extends('brands.tmf.emails.layout')
@section('content')
<p>
    Hello {{ $cart->client->name ?? '{name}' }},
</p>
<p>
    In this email, you will find the tickets attached to attend the Festival. Remember to print them or bring them downloaded on your phone. You will be able to access the venue 15 minutes before the session starts.<br>
    If you have any questions, you can contact us at this email address: <a href="mailto:info@torellomountainfilm.cat">info@torellomountainfilm.cat</a>.
</p>
<p>
    Your purchase code is: <span style="font-weight: bold;">{{ $cart->confirmation_code ?? '{code}' }}</span>
</p>
<p>
    Are you planning to eat something before entering the cinema? Are you running short on time? Or do you want to eat or drink calmly while waiting for the screening to begin?
</p>
<p>
    At <b>Camp Base</b>, you will find a bar service with our friends from Animal Bar. They will offer different pintxo options, both hot and cold, and some signature sandwiches, with a vegetarian option. There will be great music and a good atmosphere.
</p>
<p>
    Open from the 18th to the 22nd every day from 18:00 to 22:30.
</p>
@endsection
