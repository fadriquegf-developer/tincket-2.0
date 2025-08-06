<?php

/**
 * Backpack\CRUD preferences.
 */

return [

    /*
    |-------------------
    | TRANSLATABLE CRUDS
    |-------------------
    */

    'show_translatable_field_icon' => true,
    'translatable_field_icon_position' => 'right', // left or right

    'locales' => [
        "ca" => "Catalan",
        "gl" => "Gallego",
        "es" => "Español",
    ],

    'view_namespaces' => [
        'buttons' => [
            'crud::buttons', // falls back to 'resources/views/vendor/backpack/crud/buttons'
        ],
        'columns' => [
            'crud::columns', // falls back to 'resources/views/vendor/backpack/crud/columns'
        ],
        'fields' => [
            'crud::fields', // falls back to 'resources/views/vendor/backpack/crud/fields'
        ],
        'filters' => [
            'crud::filters', // falls back to 'resources/views/vendor/backpack/crud/filters'
        ],
    ],
    // the uploaders for the `withFiles` macro
    'uploaders' => [
        'withFiles' => [
            'image' => \Backpack\CRUD\app\Library\Uploaders\SingleBase64Image::class,
            'upload' => \Backpack\CRUD\app\Library\Uploaders\SingleFile::class,
            'upload_multiple' => \Backpack\CRUD\app\Library\Uploaders\MultipleFiles::class,
        ],
    ],

    'file_name_generator' => \Backpack\CRUD\app\Library\Uploaders\Support\FileNameGenerator::class,

];
