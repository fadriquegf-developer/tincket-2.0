<?php

namespace App\Services\Api;

/**
 * The client API consumer is using some cache to avoid to request some information
 * on every request (the menu, for instance). We need a way to notify the client
 * that need to clear its cache before the next request (for example a new menu
 * item has been added).
 * 
 * This service will know if a client needs to be notified to clean its cache
 *
 * @author miquel
 */
class CacheClientService
{

    private static $key = "client_cache_%s_needs_cleaned";

    public static function needsToBeCleaned()
    {
        return cache()->pull(sprintf(static::$key, request()->get('brand.id'))) ?: false;
    }

    public static function setToBeCleaned(bool $clean_it)
    {
        cache()->forever(sprintf(static::$key, request()->get('brand.id')), $clean_it);
    }

}
