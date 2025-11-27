<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\MenuItem;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

/**
 * This controller returns information about backend classes structure like
 * models, requests, etc. unlike the other controllers that return data
 *
 * @author miquel
 */
class MetaController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Laravel client consuming this API needs to know information about
     * translatable fields in order to be able to use HasTranslation Trait
     * in its logic
     *
     * @param string $model_name. Assuming Models live in \App. Needs to be hacked
     * in case of new model namespace is created
     * @throws \Exception
     */
    public function getModel($model_name)
    {
        $model_namespace = "App\\Models\\$model_name";
        if (!class_exists($model_namespace)) {
            throw new \Exception('This model does not exist.', 404);
        }

        $model = new $model_namespace();

        return $this->json([
            'translatable' => $model->translatable,
            'fillable' => $model->getFillable(),
            'dates' => $model->getDates(),
        ]);
    }

    /**
     * Returns architecture information about a FormRequest class
     * to be able to be applied in client end.
     *
     * FormRequests are expected to live in App\\Http\\Requests\\Api
     *
     * @param string $request_name
     * @return object json
     */
    public function getFormRequest($request_name)
    {

        $request_name = explode(':', $request_name);

        $arguments = (isset($request_name[1])) ? explode(',', $request_name[1]) : [];

        $request_namespace = "App\\Http\\Requests\\Api\\" . $request_name[0];

        // if not FormRequest class does not exist, any input is acceptable, so no
        // rules returned. Empty array returned.
        // Anyway, when client sends data it may be validated again in API endpoint

        $rules = [];

        if (class_exists($request_namespace)) {
            $rules = call_user_func_array(
                array(
                    new $request_namespace(),
                    'rules'
                ),
                $arguments
            );
        }

        return $this->json([
            'rules' => $rules
        ]);
    }


    /**
     * Returns config information.
     *
     * @param string $path
     * @return object json
     */

    public function getConfig($path, Request $request)
    {

        // Caution! This is *STICTRLY* necessary
        // Since in the config() we store the app.key,
        // and the database.connection values here,
        // this should never be exposed to the API.

        $allowedConfs = [
            'app.locale',
            'app.fallback_locale',
            'laravellocalization',
        ];

        $brand = $request->get('brand');
        $path = str_replace('/', '.', $path);

        $config = config($path);

        if (isset($config) && in_array($path, $allowedConfs)) {
            return $this->json($config);
        }

        return response()->json([
            'message' => 'Configuration not allowed or does not exist'
        ], 404);
    }

    /**
     * Returns a json array
     * of cliend side directly
     * accesible settings listed here
     */
    public function getSettings()
    {
        $brand = get_current_brand() ?: request()->get('brand');
        $settings = $this->getSettingsData($brand);
        return $this->json($settings);
    }

    public function getMenu()
    {
        $tree = MenuItem::getTree();

        return $this->json($tree);
    }

    public function getInitialConfig(Request $request)
    {
        $brand = $request->get('brand');

        if (!$brand) {
            return response()->json(['error' => 'Brand not found'], 404);
        }

        // Obtener todas las configuraciones de una vez
        $settings = $this->getSettingsData($brand);

        return $this->json([
            'locale' => config('app.locale'),
            'fallback_locale' => config('app.fallback_locale'),
            'laravellocalization' => config('laravellocalization'),
            'brand_id' => $brand->id,
            'settings' => $settings
        ]);
    }

    // Extraer la lógica de getSettings a un método privado
    private function getSettingsData($brand)
    {
        $brand->disableTranslations();

        $extra_config = array_merge((array) $brand->extra_config, [
            'alert' => (array) $brand->alert,
            'alert_status' => $brand->alert_status,
            'footer' => (array) $brand->footer,
            'description' => (array) $brand->description,
            'privacy_policy' => (array) $brand->privacy_policy,
            'legal_notice' => (array) $brand->legal_notice,
            'cookies_policy' => (array) $brand->cookies_policy,
            'general_conditions' => (array) $brand->general_conditions,
            'gdpr_text' => (array) $brand->gdpr_text,
            'logo' => $brand->getAttributes()['logo'] ? $brand->logo : null,
            'brand_color' => $brand->brand_color,
            'custom_script' => $brand->custom_script,
            'aux_code' => $brand->aux_code,
            'general_maintance' => env('GENERAL_MAINTANCE')
        ]);

        $accessible_properties = [
            'main_taxonomy_id',
            'hidden_taxonomies',
            'posting_taxonomy_id',
            'seasons_taxonomy_id',
            'footer',
            'privacy_policy',
            'legal_notice',
            'cookies_policy',
            'general_conditions',
            'gdpr_text',
            'brand_color',
            'logo',
            'alert',
            'alert_status',
            'custom_script',
            'aux_code',
            'recaptcha_secret_key',
            'recaptcha_site_key',
            'privacy_page',
            'region_filter',
            'clausule_general_status',
            'responsable_tratamiento',
            'delegado_proteccion',
            'description',
            'maintance',
            'general_maintance'
        ];

        return \Illuminate\Support\Arr::only((array) $extra_config, $accessible_properties);
    }
}
