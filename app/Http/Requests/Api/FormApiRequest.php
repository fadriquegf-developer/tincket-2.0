<?php

namespace App\Http\Requests\Api;

use App\Models\FormField;
use Illuminate\Foundation\Http\FormRequest;

class FormApiRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * TODO: Refactor this code to remove ..$arguments from rules method.
     * Instead maybe try to create a method to Add parameters from the URL.
     * Go to the MetaController.php public function getFormRequest($request_name)
     * And add the arguments there after the explode.
     *
     * @return array
     */
    public function rules(...$arguments)
    {
        $id = (isset($arguments[0])) ? $arguments[0] : \Route::getCurrentRoute()->parameter('form');

        // Upcoming on next releases:
        // here $id will be the form_id to load.

        $fields = FormField::where('brand_id', $id)->get();

        $rules = $fields->map(function($field){
            if(isset($field->config->rules)){
                return ['answer-'.$field->type.'-'.$field->id => $field->config->rules];
            } else {
                return ['answer-'.$field->type.'-'.$field->id => ""];
            }
        })->collapse()->toArray();

        return $rules;
    }

}
