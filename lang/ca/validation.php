<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages.
    |
    */

    'accepted' => ':attribute ha de ser acceptat.',
    'active_url' => ':attribute no és un URL vàlid.',
    'after' => ':attribute ha de ser una data posterior a :date.',
    'after_or_equal' => ':attribute ha de ser una data posterior o igual a :date.',
    'alpha' => ':attribute només pot contenir lletres.',
    'alpha_dash' => ':attribute només pot contenir lletres, números i guions.',
    'alpha_num' => ':attribute només pot contenir lletres i números.',
    'array' => ':attribute ha de ser una matriu.',
    'before' => ':attribute ha de ser una data anterior a :date.',
    'before_or_equal' => ':attribute ha de ser una data anterior o igual a :date.',
    'between' => [
        'numeric' => ":attribute ha d'estar entre :min - :max.",
        'file' => ':attribute ha de pesar entre :min - :max kilobytes.',
        'string' => ':attribute ha de tenir entre :min - :max caràcters.',
        'array' => ':attribute ha de tenir entre :min - :max ítems.',
    ],
    'boolean' => 'El camp :attribute ha de ser verdader o fals',
    'confirmed' => 'La confirmació de :attribute no coincideix.',
    'date' => ':attribute no és una data vàlida.',
    'date_format' => 'El camp :attribute no concorda amb el format :format.',
    'different' => ':attribute i :other han de ser diferents.',
    'digits' => ':attribute ha de tenir :digits dígits.',
    'digits_between' => ':attribute ha de tenir entre :min i :max dígits.',
    'dimensions' => 'Les dimensions de la imatge :attribute no són vàlides.',
    'distinct' => 'El camp :attribute té un valor duplicat.',
    'email' => ':attribute no és un e-mail vàlid',
    'exists' => ':attribute és invàlid.',
    'file' => 'El camp :attribute ha de ser un arxiu.',
    'filled' => 'El camp :attribute és obligatori.',
    'image' => ':attribute ha de ser una imatge.',
    'in' => ':attribute és invàlid',
    'in_array' => 'El camp :attribute no existeix dintre de :other.',
    'integer' => ':attribute ha de ser un nombre enter.',
    'ip' => ':attribute ha de ser una adreça IP vàlida.',
    'ipv4' => ':attribute ha de ser una adreça IPv4 vàlida.',
    'ipv6' => ':attribute ha de ser una adreça IPv6 vàlida.',
    'json' => 'El camp :attribute ha de ser una cadena JSON vàlida.',
    'max' => [
        'numeric' => ':attribute no pot ser més gran que :max.',
        'file' => ':attribute no pot ser més gran que :max kilobytes.',
        'string' => ':attribute no pot ser més gran que :max caràcters.',
        'array' => ':attribute no pot tenir més de :max ítems.',
    ],
    'mimes' => ':attribute ha de ser un arxiu amb format: :values.',
    'mimetypes' => ':attribute ha de ser un arxiu amb format: :values.',
    'min' => [
        'numeric' => "El tamany de :attribute ha de ser d'almenys :min.",
        'file' => "El tamany de :attribute ha de ser d'almenys :min kilobytes.",
        'string' => ':attribute ha de contenir almenys :min caràcters.',
        'array' => ':attribute ha de tenir almenys :min ítems.',
    ],
    'not_in' => ':attribute és invàlid.',
    'numeric' => ':attribute ha de ser numèric.',
    'present' => 'El camp :attribute ha d\'existir.',
    'regex' => 'El format de :attribute és invàlid.',
    'required' => 'El camp :attribute és obligatori.',
    'required_if' => 'El camp :attribute és obligatori quan :other és :value.',
    'required_unless' => 'El camp :attribute és obligatori a no ser que :other sigui a :values.',
    'required_with' => 'El camp :attribute és obligatori quan hi ha :values.',
    'required_with_all' => 'El camp :attribute és obligatori quan hi ha :values.',
    'required_without' => 'El camp :attribute és obligatori quan no hi ha :values.',
    'required_without_all' => 'El camp :attribute és obligatori quan no hi ha cap valor dels següents: :values.',
    'same' => ':attribute i :other han de coincidir.',
    'size' => [
        'numeric' => 'El tamany de :attribute ha de ser :size.',
        'file' => 'El tamany de :attribute ha de ser :size kilobytes.',
        'string' => ':attribute ha de contenir :size caràcters.',
        'array' => ':attribute ha de contenir :size ítems.',
    ],
    'string' => 'El camp :attribute ha de ser una cadena.',
    'timezone' => 'El camp :attribute ha de ser una zona vàlida.',
    'unique' => ':attribute ja està registrat i no es pot repetir.',
    'uploaded' => ':attribute ha fallat al pujar.',
    'url' => ':attribute no és una adreça web vàlida.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'name' => 'Nom',
        'username' => 'Usuari',
        'slug' => 'Perfil nom usuari',
        'email' => 'Correu electrònic',
        'first_name' => 'Nom',
        'last_name' => 'Cognom',
        'password' => 'Contrasenya',
        'password_confirmation' => 'Confirmació de la contrasenya',
        'city' => 'Ciutat',
        'country' => 'País',
        'address' => 'Adreça',
        'phone' => 'Telèfon',
        'mobile' => 'Mòbil',
        'age' => 'Edat',
        'sex' => 'Sexe',
        'gender' => 'Gènere',
        'year' => 'Any',
        'month' => 'Mes',
        'day' => 'Dia',
        'hour' => 'Hora',
        'minute' => 'Minut',
        'second' => 'Segon',
        'title' => 'Títol',
        'content' => 'Contingut',
        'body' => 'Contingut',
        'description' => 'Descripció',
        'excerpt' => 'Extracte',
        'date' => 'Data',
        'time' => 'Hora',
        'subject' => 'Assumpte',
        'message' => 'Missatge',
        'surname' => 'Cognom',
        'postal_code' => 'Codi postal',
        'city_id' => 'Ciutat',
        'key' => 'Clau',
        'config.*.key' => 'Clau',
        'config.*.value' => 'Valor',
        'publish_on' => 'Publicació',
        'event' => 'Esdeveniment',
        'space' => 'Espai',
        'code_type' => 'Limitació de compra',
        'capacity' => 'Capacitat de l\'espai',
        'space_id' => 'Espai',
        'is_numbered' => 'Numerat',
    ],
    'brand' => [
        'name' => [
            'required' => 'El nom de la marca és obligatori.',
        ],
        'code_name' => [
            'required' => 'El codi de la marca és obligatori.',
            'alpha_dash' => 'El codi només pot contenir lletres, números, guions i guions baixos.',
            'regex' => 'El codi només pot contenir lletres minúscules, números, guions i guions baixos.',
            'unique' => 'Aquest codi ja està en ús.',
        ],
        'allowed_host' => [
            'regex' => 'El domini ha de ser vàlid (ex: exemple.com).',
            'unique' => 'Aquest domini ja està en ús.',
        ],
        'capability_id' => [
            'required' => 'La capacitat és obligatòria.',
            'exists' => 'La capacitat seleccionada no és vàlida.',
        ],
        'parent_id' => [
            'exists' => 'La marca pare seleccionada no és vàlida.',
            'required' => 'La marca pare és obligatòria per a capacitats de tipus promotor.',
        ],
    ],

    'client' => [
        'name_required' => 'El nom és obligatori.',
        'surname_required' => 'Els cognoms són obligatoris.',
        'email_required' => 'El correu electrònic és obligatori.',
        'email_invalid' => 'El correu electrònic ha de ser una adreça vàlida.',
        'email_unique' => 'Aquest correu electrònic ja està registrat.',
        'password_required' => 'La contrasenya és obligatòria.',
        'password_confirmed' => 'La confirmació de la contrasenya no coincideix.',
        'password_min' => 'La contrasenya ha de tenir almenys 8 caràcters.',
        'password_mixed' => 'La contrasenya ha de contenir almenys una majúscula i una minúscula.',
        'password_numbers' => 'La contrasenya ha de contenir almenys un número.',
        'password_symbols' => 'La contrasenya ha de contenir almenys un símbol.',
        'password_uncompromised' => 'Aquesta contrasenya ha aparegut en una filtració de dades. Si us plau, tria una contrasenya diferent.',
        'date_birth_before' => 'La data de naixement ha de ser una data vàlida en el passat.',
        'date_birth_after' => 'La data de naixement ha de ser posterior a 1900.',
    ],
];
