<!doctype html>
<html class="no-js" lang="">
    <head>
    </head>
    <body>
        <table style="text-align: left; width: 650px; font-family: Helvetica,Arial,sans-serif;" border="0" cellpadding="2" cellspacing="8">
            <tbody>
                <tr>                    
                    <td bgcolor="#7e7e7e" colspan="2" rowspan="1" style="vertical-align: top; background-color: #7e7e7e !important; padding: 30px 200px 30px 30px;">
                        <a href="http://entrades.teatresdespi.cat/">
                            <img alt="Teatres Despí" src="https://teatresdespi.yesweticket.com/storage/uploads/teatresdespi/media/logo-teatresdespi.png">                            
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
                <tr>
                    <td td colspan="2" rowspan="1">
                        @yield('footer')
                    </td>
                </tr>                
            </tbody>
        </table>
    </body>
</html>
