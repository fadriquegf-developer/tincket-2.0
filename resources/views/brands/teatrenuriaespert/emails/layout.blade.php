<!doctype html>
<html class="no-js" lang="">

<head>
</head>

<body>
    <table style="text-align: left; width: 650px; font-family: Helvetica,Arial,sans-serif;" border="0" cellpadding="2"
        cellspacing="8">
        <tbody>
            <tr>
                @if (!empty(config('ywt.header_mailing_image')))
                    <td colspan="2" rowspan="1">
                        @if (config('clients.frontend.url'))
                            <a href="{{ config('clients.frontend.url') }}">
                        @endif
                        <img alt="{{ $brand->name }}"
                            src="{{ asset('storage/uploads/' . config('ywt.header_mailing_image')) }}">
                        @if (config('clients.frontend.url'))
                            </a>
                        @endif
                    </td>
                @else
                    <td colspan="2" rowspan="1" style="vertical-align: top; padding: 70px 200px 35px 35px;">
                        @if ($brand->getAttributes()['logo'])
                            @if (config('clients.frontend.url'))
                                <a href="{{ config('clients.frontend.url') }}">
                            @endif
                            <img alt="{{ $brand->name }}" src="{{ $brand->logo }}" style="max-height: 120px;">
                            @if (config('clients.frontend.url'))
                                </a>
                            @endif
                        @endif
                    </td>
                @endif
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
                @if (!empty(config('ywt.footer_mailing_image')))
                    <td colspan="2" rowspan="1">
                        @if (config('clients.frontend.url'))
                            <a href="{{ config('clients.frontend.url') }}">
                        @endif
                        <img alt="{{ $brand->name }}"
                            src="{{ asset('storage/uploads/' . config('ywt.footer_mailing_image')) }}">
                        @if (config('clients.frontend.url'))
                            </a>
                        @endif
                    </td>
                @else
                    <td colspan="2" rowspan="1"></td>
                @endif
            </tr>
        </tbody>
    </table>
</body>

</html>
