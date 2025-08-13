<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Backpack\Pro\Uploads\Validation\ValidDropzone;

class PostCrudRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
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
            'slug' => 'alpha_dash',
            'gallery' => ValidDropzone::field()
                ->file(['dimensions:min_width=1200']),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('backend.post.posttitle'),
            'slug' => __('backend.post.slug'),
            'gallery' => __('backend.post.gallery'),
        ];
    }



}
