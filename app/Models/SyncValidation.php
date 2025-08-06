<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsUserOnCreate;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

/**
 * It is used to register validations from the app for offline inscriptions
 */
class SyncValidation extends BaseModel
{

    use CrudTrait;
    use OwnedModelTrait;
    use SetsBrandOnCreate;
    use SetsUserOnCreate;
    use LogsActivity;


    protected $fillable = [
        'request',
        'response',
        'event_id',
        'user_id',
        'brand_id'
    ];


    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function getTextInscription($inscription)
    {
        if (isset($inscription->error)) {
            switch ($inscription->error) {
                case 'already_out':
                    $text = __('backend.validation.errors.already_scanned_out');
                    break;
                case 'already_scanned':
                    $text = __('backend.validation.errors.already_scanned_simple');
                    break;
                case 'not_found':
                    $text = __('backend.validation.errors.not_found');
                    break;
                default:
                    $text = 'Error';
            }

            return $text;
        }

        return 'Ok';
    }

    public function getColorInscription($inscription)
{
    if (isset($inscription->error)) {
        switch ($inscription->error) {
            case 'already_out':
            case 'already_scanned':
                return 'badge bg-warning text-dark';
            case 'not_found':
                return 'badge bg-danger';
            default:
                return 'badge bg-secondary';
        }
    }

    return 'badge bg-success';
}
}
