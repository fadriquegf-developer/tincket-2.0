<!doctype html>
<html class="no-js" lang="">

<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title></title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="manifest" href="site.webmanifest">
  <link rel="apple-touch-icon" href="icon.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
  <!-- Place favicon.ico in the root directory -->
  <style>
    /* Framework */

    html,
    body {
      height: 100%;
      width: 100%;
      margin: 0;
      padding: 0;
      left: 0;
      top: 0;
      font-size: 14px;
    }

    body {
      font-family: Helvetica, Arial, sans-serif;
      line-height: 1.12;
    }

    h1 {
      font-size: 2.5rem;
    }

    h2 {
      font-size: 2rem;
    }

    h3 {
      font-size: 1.375rem;
    }

    h4 {
      font-size: 1.125rem;
    }

    h5 {
      font-size: 1rem;
    }

    h6 {
      font-size: 0.875rem;
    }

    p {
      font-size: 1.125rem;
      font-weight: 200;
    }

    .font-light {
      font-weight: 300;
    }

    .font-regular {
      font-weight: 400;
    }

    .font-heavy {
      font-weight: 700;
    }

    /* POSITIONING */

    .left {
      text-align: left;
    }

    .right {
      text-align: right;
    }

    .center {
      text-align: center;
      margin-left: auto;
      margin-right: auto;
    }

    .justify {
      text-align: justify;
    }

    /* ==== GRID SYSTEM ==== */

    .container {
      max-width: 1140px;
      width: 100%;
      margin-left: auto;
      margin-right: auto;
    }

    .row {
      position: relative;
      width: 100%;
    }

    .row [class^="col"] {
      float: left;
    }

    .col-1 {
      width: 8.33%;
    }

    .col-2 {
      width: 16.66%;
    }
    .col-25 {
      width: 20%;
    }

    .col-3 {
      width: 25%;
    }

    .col-4 {
      width: 33.33%;
    }

    .col-5 {
      width: 47.66%;
    }

    .col-6 {
      width: 50%;
    }

    .col-7 {
      width: 58.33%;
    }

    .col-8 {
      width: 66.66%;
    }

    .col-9 {
      width: 75%;
    }

    .col-10 {
      width: 83.33%;
    }

    .col-11 {
      width: 91.66%;
    }

    .col-12 {
      width: 100%;
    }

    .row::after {
      content: "";
      display: table;
      clear: both;
    }

    .hidden {
      display: none;
    }

    .py {
      padding-top: 10px;
      padding-bottom: 10px;
    }

    .pr {
      padding-right: 10px;
    }

    .pl {
      padding-left: 10px;
    }

    .p-0 {
      padding: 0px;
    }

    .m-0 {
      margin: 0;
    }

    .spacer {
      padding-top: 10px;
    }

    .spacer-big {
      padding-top: 20px;
    }


    /* particular styles */
    .title-overflow {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .border-bottom {
      border-bottom: 1px solid #000;
    }

    .img-fluid {
      max-width: 100%;
      max-height: 80px;
      height: auto;
    }

    .img-custom-logo{
      max-width: 100%;
      max-height: 100px;
      height: auto;
    }
    .text-center{
      text-align: center;
    }

    .img-fluid.logo {
      max-height: 35px;
    }

    .text-extra-small {
      font-size: .5em;
    }

    .text-small {
      font-size: .8em;
    }
    strong{
      font-weight: 700;
    }
    .container-qr{
      border: 1px solid black;
    }
    .img-qr{
        width: 100%;
      padding: 20px;
    }
    .info-wrapper{
      padding: 16px;
      background-color: #E7EAE8 !important;
    }
    .money-line{
      text-align: end;
      padding-top: 12px;
    }
    .small{
      font-size: 65%;
    }
  </style>

</head>

<body>
  <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->
        
    <div class="container">
        <div class="row" style="margin: 16px 8px;">
                <div class="col-4">
                    <h3 style="margin: 0px;text-transform: uppercase;"><b>{{ $inscription->session->event->name }}</b></h3>
                    <h5 style="font-weight:400; margin-top: 4px; margin-bottom: 0px;">{{$inscription->session->starts_on->formatLocalized('%d de %b de %Y')}}</h5>
                </div>
                <div class="col-8 right">
                    @if($inscription->session->event->custom_logo)
                        <img src="/storage/uploads/{{ $inscription->session->event->custom_logo }}" height="46px" style="margin-right:40px;" />
                     @endif
                </div>
        </div>
    </div>
    @if($inscription->getBanner() != NULL)
        <img src="{{$inscription->getBanner()}}"  style="width: 100%;" alt="Banner">
    @endif
    <div class="container" style="padding: 16px 20px 16px 50px;">
        <div class="row">
          <div class="col-3 container-qr" style="background-color:white;">
            <img class="img-qr" style="border: 1px solid white;" src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, "QRCODE", 5,5) !!}" />
          </div>
          <div class="col-9" style="padding-left: 16px;">
              <div class="info-wrapper">
                <h3 class="m-0"><b>{{ $inscription->getRateName() }}</b></h3>
                <div style="margin:4px;">
                  <b>Código de entrada:</b> <span class="text-small">{{ $inscription->barcode }}</span>
                </div>
                <div style="margin:4px;">
                  <b>Butaca:</b> @if(isset($inscription->slot->name)) {{ $inscription->slot->name }} @endif
                </div>
                <div class="money-line">
                    <h1 class="money m-0"><b>{{number_format($inscription->price_sold, 2)}} €</b></h1>
                    <div class="small">Precio base: {{number_format($inscription->price_sold, 2)}} €</div>
                    <div class="small">Impuestos incluidos</div>
                </div>
            </div>
          </div>
        </div>
    <div class="spacer"></div><div class="spacer"></div>
    <div class="row" style="padding:0px 12px;">
      <div class="col-12">
        <div style="margin: 6px;">
          <b>Incluye:</b> <span style="text-transform: uppercase;">{{ sprintf("%s - %s €", $inscription->getRateName(), number_format($inscription->price_sold, 2)) }}</span>
        </div>
        <div style="margin: 6px;">
          <b>Ubicación:</b> 
          <span>
            {{ $inscription->session->space->name }} 
                  @if($inscription->session->space->name != $inscription->session->space->location->name)
                  | {{ $inscription->session->space->location->name }}
                  @endif
                 - 
                {{ $inscription->session->space->location->address }} 
                -
                {{ $inscription->session->space->location->postal_code }} {{ $inscription->session->space->location->town->name }}
          </span>
        </div>
        <div style="margin: 6px;">
          <b>Inicio del evento:</b>
          <span>
            {{$inscription->session->starts_on->formatLocalized('%d de %b de %Y - Inicio: %H:%M')}} 
          </span>
        </div>
        <div style="margin: 6px;">
          <b>Fecha de generación: </b> {{$inscription->created_at->formatLocalized('%d/%m/%Y %H:%M')}}
        </div>
      </div>

      <div class="col-12 " style="margin-top: 26px;">
        <h6 class="m-0 small"><b>Condiciones generales de la entrada</b></h6>
        <p class="m-0 small" style="margin:4px 0px;">
          1. Queda prohibido introducir alcohol, sustancias ilegales, armas u objetos peligrosos al evento
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          2. Admisión, en virtud de lo dispuesto en la Ley de Espectáculos Públicos vigente.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          3. Es importante para todos los asistentes llevar consigo el DNI u otro documento identificativo válido original.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          4. Queda limitada la entrada y/o permanencia en el evento a toda persona que:
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          - Se encuentre en estado de embríaguez, porte o consuma cualquier tipo de estupefacientes o cualquier tipo de sustancia ilegal.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          - Porte armas u objetos contundentes, cortantes o potencialmente peligrosos, susceptibles de causar daño a personas y/u objetos.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          - Provoque o incide cualquier desorden dentro del evento o haya causado alborotos comprobados.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          5. Todo asistente podrá ser sometido a un registro por el equipo seguridad en el acceso al evento, siguiendo las directrices de Ley de Espectáculos Públicos
           y Seguridad Privada. En caso de negarse al registro, le será denegada la entrada al evento.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          6. Cualquier entrada rota o con indicios de falsificación autorizará al organizador a privar a su portador del acceso al evento.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          7. La organización del evento no se hace responsable de las entradas robadas.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          8. Está terminantemente prohibido grabar, retransmitir y/o filmar el evento con equipo profesional sin permiso previo de la organización.
        </p>
        <p class="m-0 small" style="margin:4px 0px;">
          9. La organización podrá grabar, retransmitir y filmar a los asistentes.
        </p>
      </div>
    </div>

    <div class="row" style="padding-top: 80px;">
      <div class="col-2" style="background-color:white;">
        <img src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, "QRCODE", 5,5) !!}" style="height:60px;border: 1px solid white;" />
      </div>
      <div class="col-7" style="font-size:80%;padding-top:12px;">
        <div>
            <b>Empresa promotora:</b>
        </div>
        <div>
            AJUNTAMENT D'ONDARA
        </div>
        <div>
            P0309500G
        </div>
      </div>
      <div class="col-3" style="padding-top:12px;">
        <div class="bar-code" style="text-align: center; background-color:white;">
          <img width="100%" src="{{ sprintf('data:image/png;base64,%s',  (DNS1D::getBarcodePNG(strtoupper($inscription->barcode), "C39", 3, 90))) }}" class="img-fluid img-responsive" style="border: 1px solid white;" />
          <p class="m-0 small" style="padding-top: 4px;">{{ strtoupper($inscription->barcode) }}</p>
      </div>
      </div>
    </div>
  </div>


</body>

</html>