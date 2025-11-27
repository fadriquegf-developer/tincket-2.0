<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Client;
use App\Models\User;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition()
    {
        $seller = User::first() ?? User::factory()->create();

        return [
            'brand_id' => Brand::first()->id ?? Brand::factory()->create()->id,
            'client_id' => Client::factory()->create()->id,
            'expires_on' => Carbon::now()->addMinutes(15),
            'seller_id' => $seller->id,
            'seller_type' => get_class($seller),
            'confirmation_code' => null,
        ];
    }

    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            return [
                'confirmation_code' => 'TEST-' . uniqid(),
                'expires_on' => null,
            ];
        });
    }
}
