<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Archivos
            'logo' => 'nullable',
            'banner' => 'nullable',

            // Colores y códigos
            'brand_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/i',

            // Scripts - solo si es superuser
            'custom_script' => $this->isSuperuser() ? 'nullable|string|max:10000' : 'prohibited',
            'aux_code' => $this->isSuperuser() ? 'nullable|string|max:5000' : 'prohibited',

            // Configuración de carrito
            'cartTTL' => 'nullable|integer|min:1|max:1440',
            'maxCartTTL' => 'nullable|integer|min:1|max:2880',

            // Claves API
            'google_recaptcha_secret_key' => 'nullable|string|max:255',
            'google_recaptcha_site_key' => 'nullable|string|max:255',

            // URLs
            'link_politica_privacidad' => 'nullable|url|max:500',

            // Switches
            'alert_status' => 'boolean',
            'mantenimiento' => 'boolean',
            'barra_de_cerca_filtre_comarca' => 'boolean',
            'enable_seasons_grouping' => 'boolean',
            'clausule_general_status' => 'boolean',

            // IDs de relaciones
            'main_taxonomy_id' => 'nullable|exists:taxonomies,id,brand_id,' . get_current_brand_id(),
            'posting_taxonomy_id' => 'nullable|exists:taxonomies,id,brand_id,' . get_current_brand_id(),
            'seasons_taxonomy_id' => 'nullable|exists:taxonomies,id,brand_id,' . get_current_brand_id(),
            'default_tpv_id' => 'nullable|exists:tpvs,id,brand_id,' . get_current_brand_id(),

            // Textos
            'description' => 'nullable|string|max:5000',
            'footer' => 'nullable|string|max:5000',
            'alert' => 'nullable|string|max:2000',
            'responsable_tratamiento' => 'nullable|string|max:255',
            'delegado_proteccion' => 'nullable|string|max:255',

            // Arrays de inputs de registro
            'input' => 'nullable|array',
            'input.*.id' => 'required_with:input|exists:register_inputs,id',
            'input.*.active' => 'boolean',
            'input.*.required' => 'boolean',

            // Arrays de taxonomías ocultas
            'hidden_taxonomies' => 'nullable|array',
            'hidden_taxonomies.*' => 'exists:taxonomies,id,brand_id,' . get_current_brand_id(),
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'logo.max' => __('backend.brand_settings.validation.logo_max'),
            'banner.max' => __('backend.brand_settings.validation.banner_max'),
            'brand_color.regex' => __('backend.brand_settings.validation.brand_color_regex'),
            'link_politica_privacidad.url' => __('backend.brand_settings.validation.link_politica_privacidad_url'),
            'custom_script.prohibited' => __('backend.brand_settings.validation.custom_script_prohibited'),
            'aux_code.prohibited' => __('backend.brand_settings.validation.aux_code_prohibited'),
            'cartTTL.min' => __('backend.brand_settings.validation.cart_ttl_min'),
            'cartTTL.max' => __('backend.brand_settings.validation.cart_ttl_max'),
            'maxCartTTL.min' => __('backend.brand_settings.validation.max_cart_ttl_min'),
            'maxCartTTL.max' => __('backend.brand_settings.validation.max_cart_ttl_max'),
            'main_taxonomy_id.exists' => __('backend.brand_settings.validation.main_taxonomy_exists'),
            'posting_taxonomy_id.exists' => __('backend.brand_settings.validation.posting_taxonomy_exists'),
            'seasons_taxonomy_id.exists' => __('backend.brand_settings.validation.seasons_taxonomy_exists'),
            'default_tpv_id.exists' => __('backend.brand_settings.validation.default_tpv_exists'),
            'hidden_taxonomies.*.exists' => __('backend.brand_settings.validation.hidden_taxonomies_exists'),
        ];
    }

    /**
     * Preparar datos antes de la validación
     */
    protected function prepareForValidation(): void
    {
        // Convertir checkboxes no marcados a false
        $this->merge([
            'alert_status' => $this->alert_status ?? false,
            'mantenimiento' => $this->mantenimiento ?? false,
            'barra_de_cerca_filtre_comarca' => $this->barra_de_cerca_filtre_comarca ?? false,
            'enable_seasons_grouping' => $this->enable_seasons_grouping ?? false,
            'clausule_general_status' => $this->clausule_general_status ?? false,
        ]);

        // Si no es superuser, eliminar campos peligrosos
        if (!$this->isSuperuser()) {
            $this->request->remove('custom_script');
            $this->request->remove('aux_code');
        }
    }

    /**
     * Verificar si el usuario es superuser
     */
    private function isSuperuser(): bool
    {
        $superuserIds = config('superusers.ids', [1]);
        return in_array(auth()->id(), $superuserIds);
    }
}
