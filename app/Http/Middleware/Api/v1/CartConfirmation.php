<?php

namespace App\Http\Middleware\Api\v1;

use App\Exceptions\ApiException;
use Closure;

/**
 * This prevents to interact with an already confirmed cart
 */
class CartConfirmation
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
        // before middleware: check if cart is expired
        if ($cart->is_confirmed)
        {
            throw new ApiException(sprintf("Cart ID %s is confirmed", $cart->id), ApiException::ERROR_CART_CONFIRMED, null, 403);
        }

        return $next($request);
    }

}
