<?php

/**
 * This view will render cart-confirmation-html.blade transformed to HTML
 */
$view = view(sprintf(\Config::get('base.cart.views.email.html'), $cart->client->locale), ['cart' => $cart]);
$html = new \Html2Text\Html2Text($view->render());

echo $html->getText();
?>
