@extends('core.emails.layout')
@php
    $brand = $cart->brand;
    setlocale(LC_TIME, 'es_ES.utf8');
    $emailInfo = config('ywt.contact_mail', 'info@yesweticket.com');
@endphp
@section('content')
    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        Hola {{ $cart->client->name or '{name}' }},<br>
        Aquí tens el detall de la teva compra:<br>
        <br>
        <b>Data d'operació</b>: {{ $cart->updated_at->format('d/m/Y') }}
        <span class="text-nowrap" style="white-space: nowrap;">{{ $cart->updated_at->format('h:m') }} h.</span><br>
        <b>Codi de confirmació</b>: <span class="text-nowrap" style="white-space: nowrap;">{{ $cart->confirmation_code or '{code}' }}</span><br>
        <b>Import total de l'operació</b>:
        <span class="text-nowrap" style="white-space: nowrap;">{{ sprintf('%s €', number_format($cart->priceSold, 2)) }}</span><br>
        <b>Client</b>: {{ $cart->client->name or '{name}' }} {{ $cart->client->surname or '' }}<br>
    </p>

    @foreach ($cart->inscriptions->groupBy('session_id') as $set)
        @php
            $baseInscription = $set->first();
        @endphp
        <table class="table-inscriptions"
            style="margin-bottom: 25px;  -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 100%; width: 100%;">
            <thead>
                <tr align="left">
                    <th colspan="3">
                        <span class="text-uppercase"
                            style="text-transform: uppercase;">{{ $baseInscription->session->event->name }}</span><br>
                        <small>
                            {{ $baseInscription->session->starts_on->formatLocalized('%d de %b de %Y %H:%M') }} h.
                        </small>
                        <br>
                    </th>
                </tr>
                <tr>
                    <th align="left">
                        Seient
                    </th>
                    <th align="left">
                        Tarifa
                    </th>
                    <th align="right">
                        Preu
                    </th>
                </tr>
            </thead>
            </thead>
            <tbody>
                @foreach ($set as $inscription)
                    <tr>
                        <td width="40%">
                            @if ($inscription->slot_id != null)
                                {{ $inscription->slot->name }}
                            @else
                                {{ trans('tincket/backend.session.nonumbered') }}
                            @endif
                        </td>
                        <td width="40%">
                            {{ $inscription->getRateName() }}
                        </td>
                        <td width="20%" align="right">
                            {{ sprintf('%s €', number_format($inscription->price_sold, 2)) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @foreach ($cart->groupPacks as $groupPack)
        <table class="table-inscriptions"
            style="margin-bottom: 25px;  -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 100%; width: 100%;">
            <thead>
                <tr align="left">
                    <th colspan="3">
                        <span class="text-uppercase">{{ $groupPack->pack->name }}</span><br>
                    </th>
                </tr>
                <tr>
                    <th align="left">
                        Esdeveniment
                    </th>
                    <th align="left">
                        Seient
                    </th>
                </tr>
            </thead>
            </thead>
            <tbody>
                @foreach ($groupPack->inscriptions as $inscription)
                    <tr>
                        <td width="40%">
                            {{ $inscription->session->event->name }}<br>
                            <small>{{ $inscription->session->starts_on->formatLocalized('%d de %b de %Y') }}
                                <span class="text-nowrap" style="white-space: nowrap;">{{ $inscription->session->starts_on->formatLocalized('%H:%M') }}
                                    h.</span>
                            </small>
                            <br>
                        </td>
                        <td width="40%">
                            @if ($inscription->slot_id != null)
                                {{ $inscription->slot->name }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2" align="right">
                        {{ sprintf('%s €', number_format($groupPack->price, 2)) }}
                    </td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if ($cart->gift_cards->isNotEmpty())
        <table class="table-inscriptions"
            style="margin-bottom: 25px;  -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 100%; width: 100%;">
            <thead>
                <tr align="left">
                    <th colspan="3">
                        <span class="text-uppercase">{{ trans('tincket/backend.events.enable_gift_cards') }}</span><br>
                    </th>
                </tr>
                <tr>
                    <th align="left">
                        {{ trans('tincket/backend.cart.inc.event') }}
                    </th>
                    <th align="left">
                        {{ trans('tincket/backend.gift_card.code') }}
                    </th>
                    <th align="right">
                        Preu
                    </th>
                </tr>
            </thead>
            </thead>
            <tbody>
                @foreach ($cart->gift_cards as $giftCard)
                    <tr>
                        <td>{{ $giftCard->event->name }}</td>
                        <td>{{ $giftCard->code }}</td>
                        <td width="20%" align="right">
                            {{ sprintf('%s €', number_format($giftCard->price, 2)) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tbody>
            <tr align="right">
                <td style="border-top:1px solid #ddd">
                    <b>Total&nbsp;</b> {{ sprintf('%s €', number_format($cart->priceSold, 2)) }}
                </td>
            </tr>
        </tbody>
    </table>

    <table class="action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 30px auto; padding: 0; text-align: center; width: 100%;">
        <tbody>
            <tr>
                <td align="center">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody>
                            <tr>
                                <td align="center">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                                        <tbody class="text-align" style="text-align: center;">
                                            <tr>
                                                <td>
                                                    <a href="{{ $brand->frontendProfile() }}" class="button button-primary"
                                                        target="_blank" rel="noopener"
                                                        style=" width: 100%; color: #fff;  -webkit-text-size-adjust: none; border-radius: 4px; color: #fff; display: inline-block; overflow: hidden; text-decoration: none;  background-color: #2d3748; border-bottom: 8px solid #2d3748;  border-left: 18px solid #2d3748; border-right: 18px solid #2d3748;  border-top: 8px solid #2d3748;">
                                                        DESCARREGA LES TEVES ENTRADES
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <a href="{{ $brand->frontendProfile() }}" class="link" target="_blank"
        style=" text-align: center; width: 100%; display: block;">
        Fes clic aquí per accedir a la teva Zona Personal, des d'on podràs imprimir les teves entrades
    </a><br>
    <br>
    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        A continuació t'adjuntem les teves entrades en un format òptim per portar-les al teu telèfon mòvil:
    </p>


    @foreach ($cart->inscriptions as $inscription)
        @include('core.emails.inc.ticket', ['inscription' => $inscription])
    @endforeach

    @foreach ($cart->groupPacks as $groupPack)
        @foreach ($groupPack->inscriptions as $inscription)
            @include('core.emails.inc.ticket', ['inscription' => $inscription])
        @endforeach
    @endforeach

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        <b>Nota important</b>: Aquest correu és una confirmació de la teva compra. Recorda revisar les condicions d'ús i
        política de devolucions abans de l'esdeveniment.<br>
        <br>
        Si tens qualsevol dubte o necessites assistència, pots contactar-nos a <a
            href="mailto:{{ $emailInfo }}">{{ $emailInfo }}</a>.<br>
        <br>
        Gràcies per la teva confiança i gaudeix de l'esdeveniment! 🎟️<br>
    </p>
@endsection
