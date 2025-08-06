<?php

return [
    // Vista de táboa CRUD
    'trashed'                     => 'Papeleira',
    'trash'                       => 'Enviar á papeleira',
    'destroy'                     => 'Eliminar',
    'restore'                     => 'Restaurar',

    // Mensaxes e avisos de confirmación
    'trash_confirm'                              => 'Estás seguro de que queres enviar este elemento á papeleira?',
    'trash_confirmation_title'                   => 'Elemento enviado á papeleira',
    'trash_confirmation_message'                 => 'O elemento enviouse á papeleira correctamente.',
    'trash_confirmation_not_title'               => 'NON enviado á papeleira',
    'trash_confirmation_not_message'             => 'Produciuse un erro. Pode que o elemento non se enviase á papeleira.',

    'destroy_confirm'                            => 'Estás seguro de que queres eliminar permanentemente este elemento?',
    'restore_confirm'                            => 'Estás seguro de que queres restaurar este elemento?',
    'restore_success_title'                      => 'Elemento restaurado',
    'restore_success_message'                    => 'O elemento restaurouse correctamente.',
    'restore_error_title'                        => 'NON restaurado',
    'restore_error_message'                      => 'Produciuse un erro. O elemento non se restaurou.',

    // Accións masivas
    'bulk_trash_confirm'         => 'Estás seguro de que queres enviar estes :number elementos á papeleira?',
    'bulk_trash_success_title'   => 'Elementos enviados á papeleira',
    'bulk_trash_success_message' => ' elementos enviáronse á papeleira',
    'bulk_trash_error_title'     => 'Erro ao enviar á papeleira',
    'bulk_trash_error_message'   => 'Un ou máis elementos non puideron enviarse á papeleira',

    'bulk_destroy_confirm'         => 'Estás seguro de que queres eliminar permanentemente estes :number elementos?',
    'bulk_destroy_success_title'   => 'Elementos eliminados permanentemente',
    'bulk_destroy_success_message' => ' elementos foron eliminados',
    'bulk_destroy_error_title'     => 'Erro ao eliminar',
    'bulk_destroy_error_message'   => 'Un ou máis elementos non puideron eliminarse',

    'bulk_restore_confirm'        => 'Estás seguro de que queres restaurar estes :number elementos?',
    'bulk_restore_sucess_title'   => 'Elementos restaurados',
    'bulk_restore_sucess_message' => ' elementos restauráronse',
    'bulk_restore_error_title'    => 'Erro ao restaurar',
    'bulk_restore_error_message'  => 'Un ou máis elementos non puideron restaurarse',
];
