<?php

namespace App\Http\Middleware\Api\v1;

use Closure;
use App\Exceptions\ApiException;

/**
 * Checks if Cart is expired.
 * 
 * Used in Api Controllers
 */
class CartExpiration
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $cart = $request->route('cart');
        // before middleware: check if cart is expired and not confirmed. 
        // The confirmation state will be checked by other Middleware
        if (!$cart->is_confirmed && $cart->is_expired)
        {
            throw new ApiException(sprintf("Cart ID %s is expired", $cart->id), ApiException::ERROR_CART_EXPIRED, null, 403);
        }

        $response = $next($request);

        // after middleware: touch expires_on datastamp
        $cart->extendTime();

        return $response;
    }

}
