<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Simple Transactional Email</title>
    <style>
        /* -------------------------------------
          GLOBAL RESETS
      ------------------------------------- */

        /*All the styling goes here*/

        img {
            border: none;
            -ms-interpolation-mode: bicubic;
            max-width: 100%;
        }

        body {
            font-family: sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        table {
            border-collapse: separate;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            width: 100%;
        }

        table td {
            font-family: sans-serif;
            font-size: 14px;
            vertical-align: top;
        }

        /* -------------------------------------
          BODY & CONTAINER
      ------------------------------------- */

        .body {
            width: 100%;
        }

        /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
        .container {
            display: block;
            margin: 0 auto !important;
            /* makes it centered */
            max-width: 660px;
            padding: 10px;
            width: 660px;
        }

        /* This should also be a block element, so that it will fill 100% of the .container */
        .content {
            box-sizing: border-box;
            display: block;
            margin: 0 auto;
            max-width: 660px;
            padding: 10px;
        }

        /* -------------------------------------
          HEADER, FOOTER, MAIN
      ------------------------------------- */
        .main {
            background: #ffffff;
            border-radius: 3px;
            width: 100%;
        }

        .wrapper {
            box-sizing: border-box;
        }

        .header {
            padding: 6px 0px;
            text-align: center;
            color: white;
            font-size: 20px;
        }

        .body {
            padding: 28px 20px;
        }

        .body .code {
            border: 1px solid black;
            padding: 4px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }

        .footer {
            text-align: center;
            font-size: 9px;
            padding: 4px 0px;
            color: white;
        }

        .img-fluid {
            height: auto;
            max-width: 100%;
        }



        /* -------------------------------------
          BUTTONS
      ------------------------------------- */
        .btn {
            box-sizing: border-box;
            width: 100%;
        }

        .btn>tbody>tr>td {
            padding-bottom: 15px;
        }

        .btn table {
            width: auto;
        }

        .btn table td {
            background-color: #ffffff;
            border-radius: 5px;
            text-align: center;
        }

        .btn a {
            background-color: #ffffff;
            border: solid 1px #3498db;
            border-radius: 5px;
            box-sizing: border-box;
            color: #3498db;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            padding: 12px 25px;
            text-decoration: none;
            text-transform: capitalize;
        }

        .btn-primary table td {
            background-color: #3498db;
        }

        .btn-primary a {
            background-color: #3498db;
            border-color: #3498db;
            color: #ffffff;
        }

        /* -------------------------------------
          OTHER STYLES THAT MIGHT BE USEFUL
      ------------------------------------- */
        .last {
            margin-bottom: 0;
        }

        .first {
            margin-top: 0;
        }

        .align-center {
            text-align: center;
        }

        .align-right {
            text-align: right;
        }

        .align-left {
            text-align: left;
        }

        .clear {
            clear: both;
        }

        .mt0 {
            margin-top: 0;
        }

        .mb0 {
            margin-bottom: 0;
        }

        .preheader {
            color: transparent;
            display: none;
            height: 0;
            max-height: 0;
            max-width: 0;
            opacity: 0;
            overflow: hidden;
            mso-hide: all;
            visibility: hidden;
            width: 0;
        }

        .powered-by a {
            text-decoration: none;
        }

        hr {
            border: 0;
            border-bottom: 1px solid #f6f6f6;
            margin: 20px 0;
        }

        .logo {
            max-height: 100px;
        }

        .text-center {
            text-align: center !important;
        }

        /* -------------------------------------
          RESPONSIVE AND MOBILE FRIENDLY STYLES
      ------------------------------------- */
        @media only screen and (max-width: 660px) {
            table.body h1 {
                font-size: 28px !important;
                margin-bottom: 10px !important;
            }

            table.body p,
            table.body ul,
            table.body ol,
            table.body td,
            table.body span,
            table.body a {
                font-size: 16px !important;
            }


            table.body .content {
                padding: 0 !important;
            }

            table.body .container {
                padding: 0 !important;
                width: 100% !important;
            }

            table.body .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }

            table.body .btn table {
                width: 100% !important;
            }

            table.body .btn a {
                width: 100% !important;
            }

            table.body .img-responsive {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
            }
        }
    </style>
</head>

<body>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
        <tr>
            <td>&nbsp;</td>
            <td class="container">
                <div class="content">

                    <!-- START CENTERED WHITE CONTAINER -->
                    <table role="presentation" class="main">

                        <!-- START MAIN CONTENT AREA -->
                        <tr>
                            <td class="wrapper">
                                <div class="header" style="background: pink;">
                                    <b>Val Regal</b> Ajuntament de Vilafranca
                                </div>

                                <table class="body">
                                    <tr>
                                        <td style="width: 30%">
                                            <img src="https://tct.yesweticket.com/storage/uploads/cirvianum/event/event-image-gkf6s5pY.webp"
                                                class="img-fluid" alt="">
                                            <div class="code">
                                                SY981vYKbW
                                            </div>
                                        </td>
                                        <td style="width: 5%"></td>
                                        <td style="width: 65%">
                                            <b style="font-size: 18px;">Val per bescanviar per una sessió de:<br>
                                                NOM DE L'EVENT
                                            </b>
                                            <div class="content-custom" style="font-size: 11px;padding-top: 6px;">
                                                Pasos a seguir per validar el regal i realitzar la reserva:<br>
                                                1 - Anar a la taquilla que tenim situada al Carrer... en horari de
                                                dilluns a divendres de 10h a 14h<br>
                                                2 - En el moment de la compra us demanarem el xec regal per poder
                                                bescanviar-lo i us emetrem les entrades
                                            </div>
                                            <table style="padding-top: 12px;">
                                                <tr>
                                                    <td style="width: 50%;">
                                                        <img src="https://www.vilafranca.cat/sites/all/themes/vila/images/logo_vila_color.png" class="img-fluid" alt="">
                                                    </td>
                                                    <td style="width: 50%; padding-top: 12px;">
                                                        <span style="font-size: 10px;">Núm:</span> <b style="font-size: 18px;">SY981vYKbW</b>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <div class="footer" style="background: pink;">

                                    Ajuntament de Vilafranca del Penedès | Carrer de la Cort, 14, 08720 Vilafranca
                                    del
                                    Penedès, Barcelona | 938 92 03 58

                                </div>

                            </td>
                        </tr>

                        <!-- END MAIN CONTENT AREA -->
                    </table>
                    <!-- END CENTERED WHITE CONTAINER -->

                </div>
            </td>
            <td>&nbsp;</td>
        </tr>
    </table>
</body>

</html>
