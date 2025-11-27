@extends('brands.tmf.emails.layout')
@section('content')
    <p>
        Hello {{ $cart->client->name or '{name}' }},
    </p>
    <p>
        In this email you will find attached the tickets to attend the Festival. Remember to print
        them or have them downloaded on your mobile phone. You will be able to access the theater quickly from 15 minutes
        before the session starts.<br>
        If you have any questions you can contact us at this email address:
        <a href="mailto:info@torellomountainfilm.cat">info@torellomountainfilm.cat</a>.
    </p>
    <p>
        Your purchase code is: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
    </p>
    <p>
        Are you planning to eat something before entering the cinema? Are you short on time? Or would you
        prefer to eat or drink leisurely while waiting for the screening to begin?
    </p>
    <p>
        At <b>Camp Base</b> you will find a bar service with our friends from Animal Bar. They will bring us
        different options of snacks, both hot and cold, and some gourmet sandwiches, with a
        vegetarian option. There will be great music and a good atmosphere.
    </p>
    <p>
        From the 17th to the 21st, open every day from 6:00 PM to 10:30 PM
    </p>
@endsection
