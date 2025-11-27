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

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute is not a valid date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The :attribute must be a valid email address.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'filled' => 'The :attribute field is required.',
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'max' => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'string' => 'The :attribute may not be greater than :max characters.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
        'array' => 'The :attribute must have at least :min items.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'present' => 'The :attribute field must be present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobytes.',
        'string' => 'The :attribute must be :size characters.',
        'array' => 'The :attribute must contain :size items.',
    ],
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid zone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute format is invalid.',

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
        'name' => 'Name',
        'username' => 'Username',
        'slug' => 'Profile username',
        'email' => 'Email',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'password' => 'Password',
        'password_confirmation' => 'Password confirmation',
        'city' => 'City',
        'country' => 'Country',
        'address' => 'Address',
        'phone' => 'Phone',
        'mobile' => 'Mobile',
        'age' => 'Age',
        'sex' => 'Sex',
        'gender' => 'Gender',
        'year' => 'Year',
        'month' => 'Month',
        'day' => 'Day',
        'hour' => 'Hour',
        'minute' => 'Minute',
        'second' => 'Second',
        'title' => 'Title',
        'content' => 'Content',
        'body' => 'Body',
        'description' => 'Description',
        'excerpt' => 'Excerpt',
        'date' => 'Date',
        'time' => 'Time',
        'subject' => 'Subject',
        'message' => 'Message',
        'surname' => 'Surname',
        'postal_code' => 'Postal code',
        'city_id' => 'City',
        'key' => 'Key',
        'config.*.key' => 'Key',
        'config.*.value' => 'Value',
        'publish_on' => 'Publication',
        'event' => 'Event',
        'space' => 'Space',
        'code_type' => 'Purchase limitation',
        'capacity' => 'Space capacity',
        'space_id' => 'Space',
        'is_numbered' => 'Numbered',
    ],
    'brand' => [
        'name' => [
            'required' => 'The brand name is required.',
        ],
        'code_name' => [
            'required' => 'The brand code is required.',
            'alpha_dash' => 'The code may only contain letters, numbers, dashes and underscores.',
            'regex' => 'The code may only contain lowercase letters, numbers, dashes and underscores.',
            'unique' => 'This code is already in use.',
        ],
        'allowed_host' => [
            'regex' => 'The domain must be valid (e.g., example.com).',
            'unique' => 'This domain is already in use.',
        ],
        'capability_id' => [
            'required' => 'The capability is required.',
            'exists' => 'The selected capability is not valid.',
        ],
        'parent_id' => [
            'exists' => 'The selected parent brand is not valid.',
            'required' => 'The parent brand is required for promoter type capabilities.',
        ],
    ],

    'client' => [
        'name_required' => 'The name is required.',
        'surname_required' => 'The surname is required.',
        'email_required' => 'The email is required.',
        'email_invalid' => 'The email must be a valid address.',
        'email_unique' => 'This email is already registered.',
        'password_required' => 'The password is required.',
        'password_confirmed' => 'The password confirmation does not match.',
        'password_min' => 'The password must be at least 8 characters.',
        'password_mixed' => 'The password must contain at least one uppercase and one lowercase letter.',
        'password_numbers' => 'The password must contain at least one number.',
        'password_symbols' => 'The password must contain at least one symbol.',
        'password_uncompromised' => 'This password has appeared in a data breach. Please choose a different password.',
        'date_birth_before' => 'The date of birth must be a valid date in the past.',
        'date_birth_after' => 'The date of birth must be after 1900.',
    ],
];