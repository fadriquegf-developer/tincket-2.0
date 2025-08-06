<?php

namespace App\Traits;

trait TMFTemplatesTrait
{
    private function default()
    {
        $this->crud->addField([
            'name' => 'content',
            'label' => trans('backpack::pagemanager.content'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
        ]);
    }

    private function inaguracio()
    {
        $this->crud->addField([
            'name' => 'date',
            'label' => 'Data/hora Inaguració',
            'type' => 'datetime',
            'fake' => true,
            'store_in' => 'extras'
        ]);

        $this->crud->addField([
            'name' => 'image',
            'label' => 'Imatge Principal',
            'type' => 'browse',
            'fake' => true,
            'store_in' => 'extras',
        ]);

        $this->crud->addField([
            'name' => 'title_film',
            'label' => 'Títol película',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'extras'
        ]);

        $this->crud->addField([
            'name' => 'present',
            'label' => 'Presenta',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
            'fake' => true,
            'store_in' => 'extras'
        ]);

        $this->crud->addField([
            'name' => 'sinopsis',
            'label' => 'Sinópsis',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
            'fake' => true,
            'store_in' => 'extras'
        ]);

        $this->crud->addField([
            'name' => 'content',
            'label' => trans('backpack::pagemanager.content'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
        ]);
    }

    private function qui_som()
    {

        $this->crud->addField([
            'name' => 'fundacio',
            'label' => 'La Fundacio',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
            'fake' => true,
            'store_in' => 'extras'
        ]);

        $this->crud->addField([
            'name' => 'patronat',
            'label' => 'El Patronat',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
            'fake' => true,
            'store_in' => 'extras'
        ]);

        $this->crud->addField([
            'name' => 'history',
            'label' => 'La història',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
            'fake' => true,
            'store_in' => 'extras'
        ]);

        $this->crud->addField([
            'name' => 'formen_part',
            'label' => 'Formen Part',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
            'fake' => true,
            'store_in' => 'extras'
        ]);
    }

    private function palmares()
    {
        for ($film = 1; $film <= 12; $film++) {
            $this->crud->addField([
                'name' => 'separator_' . $film,
                'type' => 'custom_html',
                'value' => '<hr>'
            ]);
            $this->crud->addField([
                'name' => 'premio_' . $film,
                'label' => 'Premis Pelicula ' . $film,
                'type' => 'ckeditor',
                'extraPlugins' => ['oembed'],
                'placeholder' => trans('backpack::pagemanager.content_placeholder'),
                'fake' => true,
                'store_in' => 'extras'
            ]);

            $this->crud->addField([
                'name' => 'title_film_' . $film,
                'label' => 'Títol película ' . $film,
                'type' => 'text',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-6'
                ],
                'fake' => true,
                'store_in' => 'extras'
            ]);

            $this->crud->addField([
                'name' => 'title_director_' . $film,
                'label' => 'Títol director ' . $film,
                'type' => 'text',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-3'
                ],
                'fake' => true,
                'store_in' => 'extras'
            ]);

            $this->crud->addField([
                'name' => 'location_' . $film,
                'label' => 'Localització / Any ' . $film,
                'type' => 'text',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-3'
                ],
                'fake' => true,
                'store_in' => 'extras'
            ]);
        }
    }

    private function jurado()
    {
        for ($jurat = 1; $jurat <= 9; $jurat++) {
            $this->crud->addField([
                'name' => 'separator_' . $jurat,
                'type' => 'custom_html',
                'value' => '<hr>'
            ]);

            $this->crud->addField([
                'name' => 'image_jurat_' . $jurat,
                'label' => 'Imatge Jurat ' . $jurat,
                'type' => 'browse',
                'fake' => true,
                'store_in' => 'extras',
            ]);

            $this->crud->addField([
                'name' => 'title_jurat_' . $jurat,
                'label' => 'Nom Jurat ' . $jurat,
                'type' => 'text',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-6'
                ],
                'fake' => true,
                'store_in' => 'extras'
            ]);

            $this->crud->addField([
                'name' => 'descripcio_jurat_' . $jurat,
                'label' => 'Descripció Jurat ' . $jurat,
                'type' => 'ckeditor',
                'extraPlugins' => ['oembed'],
                'placeholder' => trans('backpack::pagemanager.content_placeholder'),
                'fake' => true,
                'store_in' => 'extras'
            ]);
        }
    }

    private function edicions_anteriors()
    {
        $this->crud->addField([
            'name' => 'content',
            'label' => trans('backpack::pagemanager.content'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
        ]);

        $edicions_array = [];
        for ($edicio = 1; $edicio <= 50; $edicio++) {
            $edicions_array += [ $edicio => 'Veure Edició '.$edicio ];
        }

        $this->crud->addField([ // select_from_array
            'name' => 'select_ediciton',
            'label' => "Selecciona edició",
            'type' => 'select_from_array',
            'options' => $edicions_array,
            'allows_null' => true,
            'attributes' => [
                'id' => 'select_edition'
            ]
        ]);

        $this->crud->addField([
            'name' => 'script',
            'type' => 'custom_html',
            'value' => "<script>
                document.getElementById('select_edition').addEventListener('change', function() {
                    
                    var editions = document.getElementsByClassName('container-edicio');
                    for (var i = 0; i < editions.length; i ++) {
                        editions[i].style.display = 'none';
                    }
                    document.getElementsByClassName('container-' + this.value)[0].style.display = 'block';
                    console.log('You selected: ', this.value);
                });
            </script>"
        ]);

        for ($edicio = 1; $edicio <= 50; $edicio++) {

            
            $this->crud->addField([
                'name' => 'container_start_' . $edicio,
                'type' => 'custom_html',
                'value' => '<div class"container">',
                'wrapperAttributes' => [
                    'class' => "form-group col-md-12 container-edicio container-$edicio",
                    'style' => 'display:none;'
                ],
            ]);

            $this->crud->addField([
                'name' => 'title_' . $edicio,
                'type' => 'custom_html',
                'value' => '<h3>Edició '.$edicio.'</h3>'
            ]);


            $this->crud->addField([
                'name' => 'title_edicio_' . $edicio,
                'label' => 'Tìtol edició ' . $edicio,
                'type' => 'text',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-6'
                ],
                'fake' => true,
                'store_in' => 'extras'
            ]);

            $this->crud->addField([
                'name' => 'title_diseny_' . $edicio,
                'label' => 'Nom disenyador ' . $edicio,
                'type' => 'text',
                'fake' => true,
                'store_in' => 'extras',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-6'
                ],
            ]);

            $this->crud->addField([
                'name' => 'cartell_' . $edicio,
                'label' => 'Cartell Principal' . $edicio,
                'type' => 'browse',
                'fake' => true,
                'store_in' => 'extras',
            ]);

            $this->crud->addField([
                'name' => 'num_films_' . $edicio,
                'label' => 'Num. Pel·licules ' . $edicio,
                'type' => 'text',
                'fake' => true,
                'store_in' => 'extras',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-4'
                ],
            ]);

            $this->crud->addField([
                'name' => 'num_countrys_' . $edicio,
                'label' => 'Num. Paisos ' . $edicio,
                'type' => 'text',
                'fake' => true,
                'store_in' => 'extras',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-4'
                ],
            ]);

            $this->crud->addField([
                'name' => 'num_expos_' . $edicio,
                'label' => 'Num. Exposicions ' . $edicio,
                'type' => 'text',
                'fake' => true,
                'store_in' => 'extras',
                'wrapperAttributes' => [
                    'class' => 'form-group col-md-4'
                ],
            ]);

            $this->crud->addField([
                'name' => 'palmares' . $edicio,
                'label' => 'Palmares',
                'type' => 'ckeditor',
                'extraPlugins' => ['oembed'],
                'placeholder' => trans('backpack::pagemanager.content_placeholder'),
                'fake' => true,
                'store_in' => 'extras',
            ]);

            $this->crud->addField([
                'name' => 'container_end_' . $edicio,
                'type' => 'custom_html',
                'value' => '</div>'
            ]);
        }
    }

    private function edicio()
    {
        $this->crud->addField([
            'name' => 'title_diseny',
            'label' => 'Nom disenyador',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'extras',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        $this->crud->addField([
            'name' => 'cartell',
            'label' => 'Cartell Principal',
            'type' => 'browse',
            'fake' => true,
            'store_in' => 'extras',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        $this->crud->addField([
            'name' => 'num_films',
            'label' => 'Num. Pel·licules',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'extras',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'num_countrys',
            'label' => 'Num. Paisos',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'extras',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'num_expos',
            'label' => 'Num. Exposicions',
            'type' => 'text',
            'fake' => true,
            'store_in' => 'extras',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ],
        ]);

        $this->crud->addField([
            'name' => 'content',
            'label' => 'Palmares',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
        ]);
    }

    private function wines()
    {
        $this->crud->addField([
            'name' => 'image',
            'label' => 'Imatge Principal',
            'type' => 'browse',
            'fake' => true,
            'store_in' => 'extras',
            'tab' => 'Principal'
        ]);

        $this->crud->addField([
            'name' => 'date',
            'label' => 'Data/hora Inaguració',
            'type' => 'datetime',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
            'fake' => true,
            'store_in' => 'extras',
            'tab' => 'Principal'
        ]);

        $this->crud->addField([
            'name' => 'title_event',
            'label' => 'Tìtol event',
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
            'fake' => true,
            'store_in' => 'extras',
            'tab' => 'Principal'
        ]);

        $this->crud->addField([
            'name' => 'content',
            'label' => 'Contingut',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
            'tab' => 'Principal'
        ]);

        $this->crud->addField([
            'name' => 'events',
            'label' => "Activitats relacionades",
            'type' => 'select2_from_array',
            'options' => \App\Models\Session::pluck('name', 'id')->toArray(),
            'allows_null' => true,
            'allows_multiple' => true,
            'fake' => true,
            'store_in' => 'extras',
            'tab' => 'Principal'
        ]);

        $this->crud->addField([
            'name' => 'packs',
            'label' => "Packs relacionats",
            'type' => 'select2_from_array',
            'options' => \App\Models\Pack::pluck('name', 'id')->toArray(),
            'allows_null' => true,
            'allows_multiple' => true,
            'fake' => true,
            'store_in' => 'extras',
            'tab' => 'Principal'
        ]);

        $this->crud->addField([
            'name' => 'content_2',
            'label' => 'Contingut Final',
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'placeholder' => trans('backpack::pagemanager.content_placeholder'),
            'fake' => true,
            'store_in' => 'extras',
            'tab' => 'Principal'
        ]);
    }

    private function camp_base()
    {
        for ($tab = 1; $tab <= 4; $tab++) {
            $this->crud->addField([
                'name' => 'title_bloque_' . $tab,
                'label' => 'Titol Bloc ' . $tab,
                'type' => 'text',
                'fake' => true,
                'store_in' => 'extras',
                'tab' => 'Bloque ' . $tab,
            ]);

            $this->crud->addField([
                'name' => 'orientacion_bloque_' . $tab,
                'label' => 'Orientació Bloc ' . $tab,
                'type' => 'select_from_array',
                'options' => ['vertical' => 'Vertical', 'horizontal' => 'Horitzontal'],
                'fake' => true,
                'store_in' => 'extras',
                'tab' => 'Bloque ' . $tab,
            ]);

            for ($bloque = 1; $bloque <= 6; $bloque++) {
                $this->crud->addField([
                    'name' => 'separator_bloque_' . $tab . '_' . $bloque,
                    'type' => 'custom_html',
                    'value' => '<hr>',
                    'tab' => 'Bloque ' . $tab,
                ]);

                $this->crud->addField([
                    'name' => 'top_left_bloque_' . $tab . '_' . $bloque,
                    'label' => 'Text Adalt Esquerra ' . $bloque,
                    'type' => 'text',
                    'wrapperAttributes' => [
                        'class' => 'form-group col-md-6'
                    ],
                    'fake' => true,
                    'store_in' => 'extras',
                    'tab' => 'Bloque ' . $tab,
                ]);

                $this->crud->addField([
                    'name' => 'top_right_bloque_' . $tab . '_' . $bloque,
                    'label' => 'Text Adalt ' . $bloque,
                    'type' => 'text',
                    'wrapperAttributes' => [
                        'class' => 'form-group col-md-6'
                    ],
                    'fake' => true,
                    'store_in' => 'extras',
                    'tab' => 'Bloque ' . $tab,
                ]);

                $this->crud->addField([
                    'name' => 'content_bloque_' . $tab . '_' . $bloque,
                    'label' => 'Contingut ' . $bloque,
                    'type' => 'ckeditor',
                    'extraPlugins' => ['oembed'],
                    'placeholder' => trans('backpack::pagemanager.content_placeholder'),
                    'tab' => 'Bloque ' . $tab,
                    'fake' => true,
                    'store_in' => 'extras'
                ]);

                $this->crud->addField([
                    'name' => 'image_bloque_' . $tab . '_' . $bloque,
                    'label' => 'Imatge ' . $bloque,
                    'type' => 'browse',
                    'fake' => true,
                    'store_in' => 'extras',
                    'tab' => 'Bloque ' . $tab
                ]);
            }
        }
    }

    private function fest_off()
    {
        for ($tab = 1; $tab <= 4; $tab++) {
            $this->crud->addField([
                'name' => 'title_bloque_' . $tab,
                'label' => 'Titol Bloc ' . $tab,
                'type' => 'text',
                'fake' => true,
                'store_in' => 'extras',
                'tab' => 'Bloque ' . $tab,
            ]);

            for ($bloque = 1; $bloque <= 6; $bloque++) {
                $this->crud->addField([
                    'name' => 'separator_bloque_' . $tab . '_' . $bloque,
                    'type' => 'custom_html',
                    'value' => '<hr>',
                    'tab' => 'Bloque ' . $tab,
                ]);

                $this->crud->addField([
                    'name' => 'top_left_bloque_' . $tab . '_' . $bloque,
                    'label' => 'Text Adalt Esquerra ' . $bloque,
                    'type' => 'text',
                    'wrapperAttributes' => [
                        'class' => 'form-group col-md-6'
                    ],
                    'fake' => true,
                    'store_in' => 'extras',
                    'tab' => 'Bloque ' . $tab,
                ]);

                $this->crud->addField([
                    'name' => 'top_right_bloque_' . $tab . '_' . $bloque,
                    'label' => 'Text Adalt ' . $bloque,
                    'type' => 'text',
                    'wrapperAttributes' => [
                        'class' => 'form-group col-md-6'
                    ],
                    'fake' => true,
                    'store_in' => 'extras',
                    'tab' => 'Bloque ' . $tab,
                ]);

                $this->crud->addField([
                    'name' => 'content_bloque_' . $tab . '_' . $bloque,
                    'label' => 'Contingut ' . $bloque,
                    'type' => 'ckeditor',
                    'extraPlugins' => ['oembed'],
                    'placeholder' => trans('backpack::pagemanager.content_placeholder'),
                    'tab' => 'Bloque ' . $tab,
                    'fake' => true,
                    'store_in' => 'extras'
                ]);

                $this->crud->addField([
                    'name' => 'image_bloque_' . $tab . '_' . $bloque,
                    'label' => 'Imatge ' . $bloque,
                    'type' => 'browse',
                    'fake' => true,
                    'store_in' => 'extras',
                    'tab' => 'Bloque ' . $tab
                ]);
            }
        }
    }
}
