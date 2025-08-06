<?php

namespace App\Traits;

trait SetsUserOnCreate
{
    public static function bootSetsUserOnCreate()
    {
        static::creating(function ($model) {
            if (in_array('user_id', $model->getFillable()) && empty($model->user_id)) {
                $user = backpack_user(); 
                if ($user) {
                    $model->user_id = $user->id;
                }
            }
        });
    }
}
