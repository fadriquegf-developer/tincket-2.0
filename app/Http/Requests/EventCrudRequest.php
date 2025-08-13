<?php

namespace App\Http\Requests;

use Backpack\CRUD\app\Http\Requests\CrudRequest as FormRequest;
use Backpack\Pro\Uploads\Validation\ValidDropzone;
use App\Rules\MinImageWidth;

class EventCrudRequest extends FormRequest
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
        return [
            'name' => 'required',
            'publish_on' => 'required',
            'image'      => ['nullable', new MinImageWidth(1200)],
            'banner'     => ['nullable', new MinImageWidth(1200)],
            'images' => ValidDropzone::field()
                ->file(['dimensions:min_width=1200']),
        ];
    }

    public function attributes()
    {
        return [
            'image' => __('backend.events.posterimage'),
            'banner' => __('backend.events.banner'),
            'images.*' => __('backend.events.extra_images'),
        ];
    }
}
