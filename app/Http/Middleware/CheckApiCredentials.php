<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Application;
use Illuminate\Auth\AuthenticationException;

class CheckApiCredentials
{

    // TPV callback endpoint must not be protected by credentials cheking
    protected $except = [
        '*/payment/callback',
        '*/rate/*/checkCode',
        '*/rate/*/checkDni',
        '*/rate/*/checkEmail',
        '*/rate/*/checkIsFull'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Is this URI must be excluded from validation?
        if ($this->shouldPassThrough($request))
        {
            return $next($request);
        }

        // ID and KEY should be sent with header.
        // Headers travel throught SSL. Request URL parameters does not
        //
        // X-TK-KEY
        // X-TK-ID
        $brand = $this->setBrandInformation($request);

        if (!is_null($brand))
        {
            // we do not check key in local env
            if ($request->header('X-TK-BRAND-KEY') === $brand->key)
            {
                
                $this->loadBrandConfig($brand->code_name);
                

                return $next($request);
            }
        }

        return abort(403, 'Access denied');
    }

    public function setBrandInformation($request)
    {
        $application = Application::where('code_name', '!=', 'public_api')->where('key', $request->header('X-TK-APPLICATION-KEY'))->first();
        $brand = $application ? $application->brand : null;
        
        if (!is_null($brand))
        {            
            // Store on Request
            $request->attributes->add(['brand' => $brand]);
            $request->attributes->add(['brand.id' => $brand->id]);

            // the user is API appliaction (not a logged system's user)
            $request->attributes->add(['user' => $application]);
        }
        
        return $brand;
    }

    /**
     * Merges brand specific configs onto default
     *
     * If config values exists in:
     * /config/brand/{brand-code-name}
     *
     * @return void
     */
    public function loadBrandConfig($codeName)
    {
        (new CheckBrandHost())->loadBrandConfig($codeName);
    }

    /**
     * Determine if the request has a URI that should pass through 
     * ApiCredentials verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        foreach ($this->except as $except)
        {
            if ($except !== '/')
            {
                $except = trim($except, '/');
            }

            if ($request->is($except))
            {
                return true;
            }
        }

        return false;
    }

}
