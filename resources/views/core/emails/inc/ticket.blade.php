<div class="ticket-container"
    style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);">
    <table class="ticket"
        style="width: 100%; background: white; border: 2px solid #404040; border-spacing: 0px; padding: 0; border-radius: 10px; overflow: hidden;">
        @if (isset($inscription->session->event->image) && $inscription->session->event->image != '')
            <tr>
                <td class="ticket-left" style="padding: 0px; background-color: #f4f4f4;">
                    <img src="{{ $inscription->session->event->image }}" alt="{{ $inscription->session->event->name }}"
                        style="max-width: 100%;" />
                </td>
            </tr>
        @endif
        <tr>
            <td class="ticket-middle" align="left" style="padding: 10px; border-bottom: 2px dashed #404040;">
                <div class="ticket-details">
                    <h1 style="font-size: 20px; color: #2d3748; margin: 5px 0;">{{ $inscription->session->event->name }}
                    </h1>
                    @if ($inscription->session->event->name != $inscription->session->name)
                        <h2 style="font-size: 14px; color: #c72e5b; margin: 5px 0;">{{ $inscription->session->name }}
                        </h2>
                    @endif
                </div>
                <span class="ticket-date" style="font-size: 12px; font-weight: bold; margin: 5px 0;">
                    {{ $inscription->session->starts_on->formatLocalized('%d de %b de %Y') }}
                    <span class="text-nowrap"
                        style="white-space: nowrap;">{{ $inscription->session->starts_on->formatLocalized('%H:%M') }}
                        h.</span>
                </span><br>
                <span class="ticket-time" style="font-size: 12px; font-weight: bold; margin: 5px 0;">
                    @if (isset($inscription->group_pack->pack->name))
                        <b>{{ $inscription->group_pack->pack->name }}</b>
                    @else
                        <b>ENTRADA INDIVIDUAL - {{ $inscription->getRateName() }}</b>
                    @endif
                </span><br>
                <span class="ticket-location"
                    style="font-size: 12px; font-weight: bold; margin: 5px 0;">{{ $inscription->session->space->name }}</span>
                @if ($inscription->session->event->brand->id != $cart->brand->id)
                    <div class="ticket-logo">
                        <img class="img-fluid" src="{{ $inscription->session->event->brand->logo }}"
                            style="max-width: 80px; margin-left: auto; display: block;" />
                    </div>
                @endif
            </td>
        </tr>

        <tr>
            <td class="ticket-right text-align"
                style="padding-top: 10px; background-color: #f4f4f4; text-align: center;">
                <div class="ticket-barcode">
                    @if (isset($message))
                        <img class="img-fluid"
                            style="padding: 10px; border-radius: 15px; background-color: #ffffff; -webkit-print-color-adjust: exact;"
                            src="{{ $message->embedData(base64_decode(DNS2DWhiteBg::getBarcodePNG($inscription->barcode, 'QRCODE', 8, 8)), $inscription->barcode . '.png', 'image/png') }}"
                            alt="QR code" />
                    @endif
                </div>
                <span class="ticket-barcode-text" style="font-size: 11px;">{{ $inscription->barcode }}</span>
            </td>
        </tr>
    </table>
</div>
