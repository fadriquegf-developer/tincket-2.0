<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backpack\CRUD preferences
    |--------------------------------------------------------------------------
    */

    /*
    |------------
    | CREATE
    |------------
    */

    /*
    |------------
    | READ
    |------------
    */

    // LIST VIEW (table view)

        // How many items should be shown by default by the Datatable?
        // This value can be overwritten on a specific CRUD by calling
        // $this->crud->setDefaultPageLength(50);
        'default_page_length' => 25,

    // PREVIEW

    /*
    |------------
    | UPDATE
    |------------
    */

    /*
    |------------
    | DELETE
    |------------
    */

    /*
    |------------
    | REORDER
    |------------
    */

    /*
    |------------
    | DETAILS ROW
    |------------
    */

  /*
    |-------------------
    | TRANSLATABLE CRUDS
    |-------------------
    */
    'show_translatable_field_icon' => true,
    'translatable_field_icon_position' => 'right', // left or right
    
];
