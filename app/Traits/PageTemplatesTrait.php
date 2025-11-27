<?php

namespace App\Traits;

trait PageTemplatesTrait
{
    /*
    |--------------------------------------------------------------------------
    | Page Templates for Backpack\PageManager
    |--------------------------------------------------------------------------
    |
    | Each page template has its own method, that define what fields should show up using the Backpack\CRUD API.
    | Use snake_case for naming and PageManager will make sure it looks pretty in the create/update form
    | template dropdown.
    |
    | Any fields defined here will show up after the standard page fields:
    | - select template
    | - page name (only seen by admins)
    | - page title
    | - page slug
    */

    private function basic_page()
    {
        $this->crud->addField([
            'name' => 'content',
            'label' => __('backend.page.content'),
            'type' => 'ckeditor',
            //'extraPlugins' => ['oembed'],
            'options' => [
                'toolbar' => [
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    'strikethrough',
                    '|',
                    'fontSize',
                    'fontColor',
                    'fontBackgroundColor',
                    '|',
                    'bulletedList',
                    'numberedList',
                    'outdent',
                    'indent',
                    '|',
                    'link',
                    'blockQuote',
                    'insertTable',
                    'mediaEmbed',
                    'imageUpload',
                    '|',
                    'undo',
                    'redo',
                    'removeFormat',
                    'sourceEditing',
                ],
            ],
            'tab' => __('backend.page.content')
        ]);
    }

}
