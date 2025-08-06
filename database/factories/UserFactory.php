<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'email' => 'admin@javajan.com',
            'password' => bcrypt('123456789'), 
            'remember_token' => Str::random(10),
            'name' => 'admin',
            'surname' => 'admin',
            'company_name' => $this->faker->company,
            'slug' => function (array $attributes) {
                return Str::slug($attributes['name']);
            },
            'address' => $this->faker->address,
            'postal_code' => $this->faker->postcode,
            'phone' => $this->faker->phoneNumber,
            'site' => $this->faker->url,
            'social' => null,
            'about' => $this->faker->paragraph,
            'profile_image' => $this->faker->imageUrl(200, 200, 'people'),
            'id_card' => Str::random(10),
            'id_vat' => Str::random(12),
            'bank_account_holder' => $this->faker->name,
            'iban' => $this->faker->iban('ES'),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null, 
        ];
    }
    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
