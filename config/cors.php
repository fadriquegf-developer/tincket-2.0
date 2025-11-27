<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'apivalidation/*', 'sanctum/csrf-cookie', 'public/v1/*' /* 'storage/*', 'build/assets/*' */],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        //Brands
        'https://acord-managers.yesweticket.com',
        'https://aeda.yesweticket.com',
        'https://afa-santa-eulalia.yesweticket.com',
        'https://agenda-ondara.yesweticket.com',
        'https://ajuntament-alcarra.yesweticket.com',
        'https://ajuntament-berga.yesweticket.com',
        'https://ajuntament-caldes.yesweticket.com',
        'https://ajuntament-de-riudarenes.yesweticket.com',
        'https://ajuntament-navas.yesweticket.com',
        'https://ajuntament-premia-de-dalt.yesweticket.com',
        'https://ajuntament-sils.yesweticket.com',
        'https://ajuntament-tavernoles.yesweticket.com',
        'https://albert-naturavidapositiva.yesweticket.com',
        'https://aleix-terres.yesweticket.com',
        'https://apm.yesweticket.com',
        'https://argentona.yesweticket.com',
        'https://associacio-comissio-de-festes-barri-de-larumi.yesweticket.com',
        'https://associacio-de-families-dalumnes-la-benaula.yesweticket.com',
        'https://associacio-juvenil-femseb.yesweticket.com',
        'https://associacio-musicargentona.yesweticket.com',
        'https://barq-festival.yesweticket.com',
        'https://barri-del-rossinyol.yesweticket.com',
        'https://basic.tincket.test',
        'https://benaisit.yesweticket.com',
        'https://carballo.yesweticket.com',
        'https://castell-montesquiu.yesweticket.com',
        'https://castellers-de-berga.yesweticket.com',
        'https://cerdanyola.yesweticket.com',
        'https://chipiron-de-granada.yesweticket.com',
        'https://civitas-cultura.yesweticket.com',
        'https://colla-gegantera-can-fornaca.yesweticket.com',
        'https://comissio-de-festes-de-salt.yesweticket.com',
        'https://concertsalafresca.yesweticket.com',
        'https://demo-manager.yesweticket.com',
        'https://el9nou.yesweticket.com',
        'https://elmeuteatre.yesweticket.com',
        'https://engine.tincket.test',
        'https://escenaris.yesweticket.com',
        'https://escoladansamado.yesweticket.com',
        'https://fabricadejoguines.yesweticket.com',
        'https://fabricavella.yesweticket.com',
        'https://festadelrenaixement.yesweticket.com',
        'https://fornellsdelaselva.yesweticket.com',
        'https://fundacio-privada-tutelar-del-bergueda.yesweticket.com',
        'https://institut-del-teatre.yesweticket.com',
        'https://la-closca.yesweticket.com',
        'https://la-garrinada.yesweticket.com',
        'https://la-simpatica-produccions.yesweticket.com',
        'https://la-xarxa-argentona.yesweticket.com',
        'https://llagostera.yesweticket.com',
        'https://llanca.yesweticket.com',
        'https://magic-grup-maresme.yesweticket.com',
        'https://memorial-ricard-cuadra.yesweticket.com',
        'https://montcada.yesweticket.com',
        'https://museudelatorneria.yesweticket.com',
        'https://museutortosa.yesweticket.com',
        'https://periferia-cultural.yesweticket.com',
        'https://premiadedalt.yesweticket.com',
        'https://promotor.tincket.test',
        'https://punt-jove-gurb.yesweticket.com',
        'https://quico-el-celio.yesweticket.com',
        'https://rella.yesweticket.com',
        'https://riudomenc.yesweticket.com',
        'https://rotary-club-del-bergueda.yesweticket.com',
        'https://santjuliaderamis.yesweticket.com',
        'https://sarriadeter.yesweticket.com',
        'https://tct.yesweticket.com',
        'https://teatre-essela.yesweticket.com',
        'https://teatre-pimpinella.yesweticket.com',
        'https://teatreauditoridelmercatvell.yesweticket.com',
        'https://teatreauditoritortosa.yesweticket.com',
        'https://teatredemanacor.yesweticket.com',
        'https://teatrenuriaespert.yesweticket.com',
        'https://teatresdespi.yesweticket.com',
        'https://ticketara.yesweticket.com',
        'https://tmcbarbera.yesweticket.com',
        'https://tortosaturisme.yesweticket.com',
        'https://traspunt-teatre.yesweticket.com',
        'https://turoseuvella.yesweticket.com',
        'https://ulissesxendofest.yesweticket.com',
        'https://vilafranca.yesweticket.com',

        /** APP ROUTES **/
        // Development - Ionic serve
        'http://localhost:8100',

        // Capacitor iOS (default)
        'capacitor://localhost',

        // Capacitor Android (Capacitor 6+)
        'https://localhost',

        // If you're using older Capacitor (<6) or explicitly set androidScheme to 'http'
        'http://localhost',

        // Alternative schemes (if you migrated from Cordova)
        'ionic://localhost',
    ],

    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.yesweticket\.com$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
