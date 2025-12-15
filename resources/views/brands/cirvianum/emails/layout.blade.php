<!doctype html>
<html class="no-js" lang="">

<head>
</head>

<body>
    <table style="text-align: left; width: 650px; font-family: Helvetica,Arial,sans-serif;" border="0" cellpadding="2"
        cellspacing="8">
        <tbody>
            <tr>
                <td bgcolor="#998714" colspan="2" rowspan="1"
                    style="vertical-align: top; background-color: #998714 !important; padding: 70px 200px 35px 35px;">
                    <a href="http://teatrecirvianum.cat">
                        <img alt="Teatre Cirviànum de Torelló"
                            src="https://api.yesweticket.com/storage/uploads/cirvianum/media/logo-tct.png">
                    </a>
                </td>
            </tr>
            <tr>
                <td colspan="2" rowspan="1" style="vertical-align: top;">
                    @yield('content')
                </td>
            </tr>
            <tr>
                <td colspan="2" rowspan="1" style="">
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
            <tr>
                <td colspan="2" rowspan="1" style=""></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
