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

    'accepted' => ':attribute debe ser aceptado.',
    'active_url' => ':attribute non é unha URL válida.',
    'after' => ':attribute debe ser unha data posterior a :date.',
    'after_or_equal' => ':attribute debe ser unha data posterior ou igual a :date.',
    'alpha' => ':attribute só debe conter letras.',
    'alpha_dash' => ':attribute só debe conter letras, números e guiones.',
    'alpha_num' => ':attribute só debe conter letras e números.',
    'array' => ':attribute debe ser un conxunto.',
    'before' => ':attribute debe ser unha data anterior a :date.',
    'before_or_equal' => ':attribute debe ser unha data anterior ou igual a :date.',
    'between' => [
        'numeric' => ':attribute ten que estar entre :min - :max.',
        'file' => ':attribute debe pesar entre :min - :max kilobytes.',
        'string' => ':attribute ten que ter entre :min - :max caracteres.',
        'array' => ':attribute ten que ter entre :min - :max ítems.',
    ],
    'boolean' => 'O campo :attribute debe ter un valor verdadeiro ou falso.',
    'confirmed' => 'A confirmación de :attribute non coinciden.',
    'date' => ':attribute non é unha data válida.',
    'date_format' => ':attribute non corresponde ao formato :format.',
    'different' => ':attribute y :other deben ser diferentes.',
    'digits' => ':attribute debe ter :digits díxitos.',
    'digits_between' => ':attribute debe ter entre :min e :max dígitos.',
    'dimensions' => 'Las dimensiones de la imagen :attribute no son válidas.',
    'distinct' => 'O campo :attribute contén un valor duplicado.',
    'email' => ':attribute non é un correo válido',
    'exists' => ':attribute é inválido.',
    'file' => 'El campo :attribute debe ser un arquivo.',
    'filled' => 'O campo: atributo é obrigatorio.',
    'image' => ':attribute debe ser unha imaxe.',
    'in' => ':attribute é inválido.',
    'in_array' => 'El campo :attribute no existe en :other.',
    'integer' => ':attribute debe ser un número entero.',
    'ip' => ':attribute debe ser unha dirección IP válida.',
    'ipv4' => ':attribute debe ser unha dirección IPv4 válida',
    'ipv6' => ':attribute debe ser unha dirección IPv6 válida.',
    'json' => 'O campo :attribute debe ter unha cadea JSON válida.',
    'max' => [
        'numeric' => ':attribute non debe ser maior a :max.',
        'file' => ':attribute non debe ser maior que :max kilobytes.',
        'string' => ':attribute non debe ser maior que :max caracteres.',
        'array' => ':attribute non debe ter máis de :max elementos.',
    ],
    'mimes' => ':attribute debe ser un arquivo co formato: :values.',
    'mimetypes' => ':attribute debe ser un arquivo co formato: :values.',
    'min' => [
        'numeric' => 'O tamaño de :attribute debe ser polo menos :min.',
        'file' => 'O tamaño de :attribute debe ser polo menos :min kilobytes.',
        'string' => ':attribute debe conter polo menos :min caracteres.',
        'array' => ':attribute debe ter polo menos elementos :min.',
    ],
    'not_in' => ':attribute non é válido.',
    'numeric' => ':attribute debe ser numérico.',
    'present' => 'O campo :attribute debe estar presente.',
    'regex' => 'O formato de :attribute non é válido.',
    'required' => 'O campo :attribute é obrigatorio.',
    'required_if' => 'O campo :attribute é obrigatorio cando :other é :value.',
    'required_unless' => 'O campo :attribute é obrigatorio a menos que :other estea en :values.',
    'required_with' => 'O campo :attribute é obrigatorio cando :values ​​está presente.',
    'required_with_all' => 'O campo :attribute é obrigatorio cando :values ​​está presente.',
    'required_without' => 'O campo :attribute é obrigatorio cando :values ​​​​non está presente.',
    'required_without_all' => 'O campo :attribute é obrigatorio cando ningún dos valores : está presente.',
    'same' => ':attribute y :other deben coincidir.',
    'size' => [
        'numeric' => 'O tamaño de :attribute debe ser :size.',
        'file' => 'O tamaño de :attribute debe ser :size kilobytes.',
        'string' => ':attribute debe conter caracteres :size.',
        'array' => ':attribute debe conter elementos :size.',
    ],
    'string' => 'O campo :attribute debe ser unha cadea.',
    'timezone' => 'O atributo : debe ser unha zona válida.',
    'unique' => ':attribute xa foi rexistrado.',
    'uploaded' => 'Subir :attribute ha fallado.',
    'url' => 'El formato :attribute es inválido.',

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
        'password' => [
            'min' => 'La :attribute debe conter máis de :min personaxes',
        ],
        'email' => [
            'unique' => 'El :attribute xa foi rexistrado.',
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
        'name' => 'Nome',
        'username' => 'Usuario',
        'slug' => 'Perfil nome usuario',
        'email' => 'Correo electrónico',
        'first_name' => 'Nome',
        'last_name' => 'Apelido',
        'password' => 'Contrasinal',
        'password_confirmation' => 'Confirmación do contrasinal',
        'city' => 'Cidade',
        'country' => 'País',
        'address' => 'Enderezo',
        'phone' => 'Teléfono',
        'mobile' => 'Móbil',
        'age' => 'Idade',
        'sex' => 'Sexo',
        'gender' => 'Xénero',
        'year' => 'Ano',
        'month' => 'Mes',
        'day' => 'Día',
        'hour' => 'Hora',
        'minute' => 'Minuto',
        'second' => 'Segundo',
        'title' => 'Título',
        'content' => 'Contido',
        'body' => 'Contido',
        'description' => 'Descrición',
        'excerpt' => 'Extracto',
        'date' => 'Data',
        'time' => 'Hora',
        'subject' => 'Asunto',
        'message' => 'Mensaxe',
        'surname' => 'Apelido',
        'postal_code' => 'Código postal',
        'city_id' => 'Cidade',
        'key' => 'Chave',
        'config.*.key' => 'Chave',
        'config.*.value' => 'Valor',
        'publish_on' => 'Publicación',
        'event' => 'Evento',
        'space' => 'Espazo',
        'code_type' => 'Limitación de compra',
        'capacity' => 'Capacidade do espazo',
        'space_id' => 'Espazo',
    ],

];
