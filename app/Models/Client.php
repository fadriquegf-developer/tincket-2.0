<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\Brand;
use App\Models\FormField;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Models\FormFieldAnswer;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Client extends BaseModel
{

    use CrudTrait;
    use LogsActivity;
    use SetsBrandOnCreate;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use OwnedModelTrait;

    protected $hidden = [];
    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'mobile_phone',
        'locale',
        'date_birth',
        'dni',
        'province',
        'city',
        'address',
        'postal_code',
        'newsletter',
        'brand_id',
        'password',
    ];

    protected $casts = [
        'newsletter' => 'boolean',
        'date_birth' => 'date:Y-m-d',
    ];


    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function setPasswordAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::needsRehash($value)
                ? Hash::make($value)
                : $value;
        }
    }


    public function exportButton($crud = false)
    {
        $url = route('client.export');

        return '<a href="' . $url . '" class="btn btn-primary" title="' . __('backend.client.export_basic') . '">
                <i class="la la-upload"></i> ' . __('backend.client.export_basic') . '
            </a>';
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function answers()
    {
        return $this->hasMany(FormFieldAnswer::class);
    }

    public function openDeletedList($crud = false)
    {
        return '<a class="btn btn-primary" href="' . route('crud.client.deleted-list') . '"><i class="fa fa-trash"></i> Clients eliminats</a>';
    }

    public function getNumSessionAttribute()
    {
        $sessions = [];

        foreach ($this->carts as $cart) {
            //Comprovar si el carrito esta pagado
            if ($cart->confirmation_code) {
                foreach ($cart->inscriptions as $inscription) {
                    //Vamos añadiendo sessiones, sin repetir
                    if (!in_array($inscription->session_id, $sessions)) {
                        array_push($sessions, $inscription->session_id);
                    }
                }
            }
        }

        return count($sessions);
    }

    public function getNumSessions()
    {
        $sessions = [];

        foreach ($this->carts as $cart) {
            //Comprovar si el carrito esta pagado
            if ($cart->confirmation_code) {
                foreach ($cart->inscriptions as $inscription) {
                    //Vamos añadiendo sessiones, sin repetir
                    if (!in_array($inscription->session_id, $sessions)) {
                        array_push($sessions, $inscription->session_id);
                    }
                }
            }
        }

        return count($sessions);
    }

    public function getFullNameEmailAttribute(): string
    {
        return "{$this->surname}, {$this->name} ({$this->email})";
    }

    public function sales()
    {
        $query = StatsSales::leftJoin('inscriptions', 'inscriptions.id', '=', 'stats_sales.inscription_id')
            ->leftJoin('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->select(['stats_sales.*', 'carts.confirmation_code', 'carts.id as cart_id'])
            ->orderBy('created_at', 'DESC');

        return new \Illuminate\Database\Eloquent\Relations\HasMany($query, $this, 'stats_sales.client_id', 'id');
    }

    /* public function getPreferenceResponse(FormField $preference)
    {
        return $this->answers()->where('field_id', '=', $preference->id)->first();
    } */

    /**
     * Check if the user has all the required fields completed in the register form 
     */
    public function registerCheckRequired()
    {
        // old system
        $result = true;
        $fields = FormField::ownedByBrand()->whereNull('is_editable')->where('config', 'like', '%required%')->get();
        $answers = $this->answers()->whereIn('field_id', $fields->pluck('id'))->get();

        foreach($fields as $field){
            if($answers->where('field_id', $field->id)->first() === null){
                $result = false;
            }
        }

        // new system stored in clients table
        $register_inputs = request()->get('brand')->register_inputs;

        foreach($register_inputs as $input){
            if($input->pivot->required && empty($this->{$input->name_form})){
                $result = false;
            }
        }

        return $result;
    }
}
