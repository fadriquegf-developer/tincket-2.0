@php
    $logo = !empty(brand_setting('ywt.header_mailing_image'))
        ? 'storage/uploads/' . brand_setting('ywt.header_mailing_image')
        : $brand->logo;

    $embededLogo = '';
    if (isset($message) && file_exists(public_path() . '/' . $logo)) {
        $embededLogo = $message->embed(public_path() . '/' . $logo);
    }
@endphp
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title>{{ brand_setting('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }

            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }

        /* Base */
        body,
        body *:not(html):not(style):not(br):not(tr):not(code) {
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif,
                'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
            position: relative;
        }

        body {
            -webkit-text-size-adjust: none;
            background-color: #ffffff;
            color: #74787e;
            height: 100%;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }

        p,
        ul,
        ol,
        blockquote {
            line-height: 1.4;
            text-align: left;
        }

        a {
            color: #3869d4;
        }

        a img {
            border: none;
        }

        /* Typography */

        h1 {
            color: #3d4852;
            font-size: 18px;
            font-weight: bold;
            margin-top: 0;
            text-align: left;
        }

        h2 {
            font-size: 16px;
            font-weight: bold;
            margin-top: 0;
            text-align: left;
        }

        h3 {
            font-size: 14px;
            font-weight: bold;
            margin-top: 0;
            text-align: left;
        }

        p {
            font-size: 16px;
            line-height: 1.5em;
            margin-top: 0;
            text-align: left;
        }

        p.sub {
            font-size: 12px;
        }

        img {
            max-width: 100%;
        }

        /* Layout */

        .wrapper {
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .content {
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        /* Header */

        .header {
            padding: 25px 0;
            text-align: center;
        }

        .header a {
            color: #3d4852;
            font-size: 19px;
            font-weight: bold;
            text-decoration: none;
        }

        /* Logo */

        .logo {
            height: 75px;
            max-height: 75px;
            /* width: 75px; */
        }

        /* Body */

        .body {
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
            background-color: #f4f4f4;
            border-bottom: 1px solid #f4f4f4;
            border-top: 1px solid #f4f4f4;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .inner-body {
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 570px;
            background-color: #ffffff;
            border-color: #e8e5ef;
            border-radius: 2px;
            border-width: 1px;
            box-shadow: 0 2px 0 rgba(0, 0, 150, 0.025), 2px 4px 0 rgba(0, 0, 150, 0.015);
            margin: 0 auto;
            padding: 0;
            width: 570px;
        }

        /* Subcopy */

        .subcopy {
            border-top: 1px solid #e8e5ef;
            margin-top: 25px;
            padding-top: 25px;
        }

        .subcopy p {
            font-size: 14px;
        }

        /* Footer */

        .footer {
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 570px;
            margin: 0 auto;
            padding: 0;
            text-align: center;
            width: 570px;
            color: #74787e;
        }

        .footer p {
            color: black;
            font-size: 12px;
            text-align: center;
        }

        .footer a {
            color: #74787e;
            text-decoration: underline;
        }

        /* Tables */

        .table table {
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
            margin: 30px auto;
            width: 100%;
        }

        .table th {
            border-bottom: 1px solid #edeff2;
            margin: 0;
            padding-bottom: 8px;
        }

        .table td {
            color: #74787e;
            font-size: 15px;
            line-height: 18px;
            margin: 0;
            padding: 10px 0;
        }

        .content-cell {
            max-width: 100vw;
            padding: 32px;
        }

        /* Table Inscriptions */
        .table-inscriptions {
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
            width: 100%;
        }

        /* Buttons */

        .action {
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
            margin: 30px auto;
            padding: 0;
            text-align: center;
            width: 100%;
            float: unset;
        }

        .button {
            -webkit-text-size-adjust: none;
            border-radius: 4px;
            color: #fff;
            display: inline-block;
            overflow: hidden;
            text-decoration: none;
        }

        .button-blue,
        .button-primary {
            background-color: #2d3748;
            border-bottom: 8px solid #2d3748;
            border-left: 18px solid #2d3748;
            border-right: 18px solid #2d3748;
            border-top: 8px solid #2d3748;
        }

        .button-green,
        .button-success {
            background-color: #48bb78;
            border-bottom: 8px solid #48bb78;
            border-left: 18px solid #48bb78;
            border-right: 18px solid #48bb78;
            border-top: 8px solid #48bb78;
        }

        .button-red,
        .button-error {
            background-color: #e53e3e;
            border-bottom: 8px solid #e53e3e;
            border-left: 18px solid #e53e3e;
            border-right: 18px solid #e53e3e;
            border-top: 8px solid #e53e3e;
        }

        /* Panels */

        .panel {
            border-left: #2d3748 solid 4px;
            margin: 21px 0;
        }

        .panel-content {
            background-color: #edf2f7;
            color: #718096;
            padding: 16px;
        }

        .panel-content p {
            color: #718096;
        }

        .panel-item {
            padding: 0;
        }

        .panel-item p:last-of-type {
            margin-bottom: 0;
            padding-bottom: 0;
        }

        /* Utilities */

        .break-all {
            word-break: break-all;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .center {
            display: block;
            margin: auto;
        }

        .text-align-center {
            text-align: center;
        }

        .link {
            text-align: center;
            width: 100%;
            display: block;
        }
    </style>
    <style>
        .ticket-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            /* padding: 20px; */
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        table.ticket {
            width: 100%;
            background: white;
            border: 2px solid #404040;
            border-spacing: 0px;
            padding: 0;
            border-radius: 10px;
            overflow: hidden;
        }

        .ticket-left {
            padding: 0px;
            background-color: #f4f4f4;
        }

        .ticket-middle {
            padding: 10px;
            border-bottom: 2px dashed #404040;
        }

        .ticket-middle span {
            font-size: 12px;
            font-weight: bold;
            margin: 5px 0;
        }

        .ticket-right {
            padding-top: 10px;
            background-color: #f4f4f4;
        }

        .ticket-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .ticket-details h1 {
            font-size: 20px;
            color: #2d3748;
            margin: 5px 0;
        }

        .ticket-details h2 {
            font-size: 14px;
            color: #c72e5b;
            margin: 5px 0;
        }

        .ticket-date,
        .ticket-time,
        .ticket-location {
            font-size: 12px;
            font-weight: bold;
            margin: 5px 0;
        }

        .ticket-barcode-text {
            font-size: 11px;
        }

        .ticket-barcode img {
            padding: 10px;
            /* background-color: white !important; */
            border-radius: 15px;
        }

        .ticket-logo {
            margin-top: 5px;
        }

        .ticket-logo img {
            max-width: 80px;
            margin-left: auto;
            display: block;
        }

        .text-nowrap {
            white-space: nowrap;
        }

        @media (prefers-color-scheme: dark) {
            .ticket-barcode img {
                /* background-color: white !important; */
            }
        }
    </style>
</head>

<body
    style="background-color: #ffffff; color: #74787e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; width: 100% !important;">
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color: #f4f4f4; margin: 0; padding: 0; width: 100%;">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="header" style="padding: 25px 0; text-align: center;">
                            @if (config('clients.frontend.url'))
                                <a href="{{ config('clients.frontend.url') }}" target="_blank"
                                    style="color: #3d4852; font-size: 19px; font-weight: bold; text-decoration: none;">
                            @endif

                            @if ($embededLogo)
                                <img alt="{{ $brand->name }}" src="{{ $embededLogo }}"
                                    style="height: 75px; max-height: 75px;">
                            @else
                                {{ $brand->name }}
                            @endif

                            @if (config('clients.frontend.url'))
                                </a>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0"
                            style="background-color: #f4f4f4; border-bottom: 1px solid #f4f4f4; border-top: 1px solid #f4f4f4; margin: 0; padding: 0; width: 100%;">
                            <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation"
                                style="background-color: #ffffff; border-color: #e8e5ef; border-radius: 2px; border-width: 1px; box-shadow: 0 2px 0 rgba(0, 0, 150, 0.025), 2px 4px 0 rgba(0, 0, 150, 0.015); margin: 0 auto; padding: 0; width: 570px;">
                                <tr>
                                    <td class="content-cell" style="max-width: 100vw; padding: 32px;">
                                        @yield('content')
                                    </td>
                                </tr>
                                <tr>
                                    <td class="content-cell" style="max-width: 100vw; padding: 32px;">
                                        @yield('extra_content')
                                    </td>
                                </tr>
                                {{-- Mensaje navideño --}}
                                @if (config('seasonal.christmas.enabled'))
                                    <tr>
                                        <td class="content-cell" style="padding: 0 32px 32px 32px; text-align: center;">
                                            <p style="font-size: 15px; margin: 0; color: #2d5a27;">
                                                {{ config('seasonal.christmas.messages.' . app()->getLocale(), config('seasonal.christmas.messages.ca')) }}
                                            </p>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation"
                                style=" -premailer-cellpadding: 0; -premailer-cellspacing: 0; -premailer-width: 570px;  margin: 0 auto; padding: 0; text-align: center; width: 570px; color: #74787e;">
                                <tr>
                                    <td class="content-cell" align="center"
                                        style="max-width: 100vw; padding: 32px; color: black; font-size: 12px; text-align: center;">
                                        © {{ date('Y') }} <a href="https://yesweticket.com/ca" target="_blank"
                                            style="color: #74787e; text-decoration: underline;">YesWeTicket</a>.
                                        Tots els drets reservats.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
