<?php

namespace App\Http\Requests;

use Backpack\CRUD\app\Http\Requests\CrudRequest as FormRequest;

class MailingCrudRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        $id = $this->id;

        return [
            'name' => 'required',
            'locale' => 'required',
            'subject' => 'required',
            'interests' => 'nullable',
            'slug' => 'alpha_dash|unique:mailings,slug'.($id ? ','.$id : ''),
            'content' => 'required',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('backend.mail.campaign_name'),
            'slug' => __('backend.mail.campaign_slug'),
            'locale' => __('backend.mail.locale'),
            'subject' => __('backend.mail.subject'),
            'content' => __('backend.mail.contents'),
        ];
    }

    
}
