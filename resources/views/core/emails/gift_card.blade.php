@extends('core.emails.layout')
@php
    $brand = $gift->brand;
    $text = $event->getTextWithVariables($event->gift_card_email_text, $gift);
@endphp

@section('content')
    @if ($text)
        {!! $text ?? '' !!}
    @else
        <p>
            Enhorabona, el teu amic {{ $gift->cart->client->name or '{name}' }} t'ha regalat un cec regal amb el codi
            <b>{{ $gift->code }}</b> per a l'espectacle <b>{{ $event->name or '{event}' }}</b>.
        </p>
        <p>
            Salutacions
        </p>
    @endif
@endsection
