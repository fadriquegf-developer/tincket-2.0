<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Description of PostApiController
 *
 * @author miquel
 */
class PostApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * List the posts of a brand. It can be filtered with query parameter $taxonomy
     * separated by commas.
     *
     * As well, an optional parameter $language can be added to specify the language
     * of the searched taxonomies. If empty, the default client language will be used.
     *
     * @param Request $request
     * 
     */
    public function index(Request $request)
    {
        // Validar el parámetro 'limit'
        $limit = is_numeric($request->get('limit')) && $request->get('limit') > 0
            ? (int) $request->get('limit')
            : null;

        // Validar y obtener taxonomías
        $taxonomies = $request->filled('taxonomy')
            ? explode(',', $request->get('taxonomy'))
            : null;

        // Validar el parámetro 'language'
        $language = array_key_exists($request->get('language'), config('backpack.crud.locales'))
            ? $request->get('language')
            : config('app.locale');

        // Construir la consulta base
        $query = $this->allBuilder();
        $query->where('publish_on', '<', \Carbon\Carbon::now());
        $query->orderBy('publish_on', 'desc');

        // Filtrar por taxonomías si están presentes
        if (!empty($taxonomies)) {
            $query->whereHas('taxonomies', function ($query) use ($taxonomies, $language) {
                foreach ($taxonomies as $taxonomy) {
                    // Método seguro de consulta sobre JSON para evitar inyección SQL
                    $query->orWhere(\DB::raw("json_unquote(json_extract(taxonomies.slug, '$.\"$language\"'))"), '=', $taxonomy);
                }
            });
        }

        // Si se proporciona un límite, aplicamos limit()
        if (!empty($limit)) {
            $query->limit($limit);
        }

        // Retornar los resultados como JSON
        return $this->json($query->get());
    }

    /**
     * Display a single post with its related taxonomies.
     *
     * @param int $id
     */
    public function show($id)
    {
        return $this->json(
            $this->allBuilder()
                ->with('taxonomies')
                ->findOrFail($id)
        );
    }

    /**
     * Builds the base query for fetching posts, applying brand ownership.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function allBuilder()
    {
        return Post::ownedByBrand();
    }
}
