<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Capability;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition()
    {
        $name = $this->faker->company;
        $code = Str::slug($name, '_') . '_' . uniqid();

        return [
            'name' => $name,
            'code_name' => $code,
            'key' => bin2hex(random_bytes(16)), // 32 caracteres hex
            'allowed_host' => $code . '.yesweticket.com',
            'capability_id' => 2, // Por defecto Basic
            'parent_id' => null,
            'brand_color' => $this->faker->hexColor,
            'logo' => null,
            'banner' => null,
            'extra_config' => null,
            'description' => $this->faker->sentence,
            'footer' => null,
            'legal_notice' => null,
            'privacy_policy' => null,
            'cookies_policy' => null,
            'gdpr_text' => null,
            'general_conditions' => null,
            'alert_status' => 0,
            'alert' => null,
            'custom_script' => null,
            'aux_code' => null,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
            'comment' => null,
        ];
    }

    /**
     * Indicate that the brand is a promotor.
     */
    public function promotor()
    {
        return $this->state(function (array $attributes) {
            return [
                'capability_id' => 3,
                'parent_id' => Brand::where('capability_id', 2)->first()?->id ?? 1,
            ];
        });
    }

    /**
     * Indicate that the brand is an engine.
     */
    public function engine()
    {
        return $this->state(function (array $attributes) {
            return [
                'capability_id' => 1,
            ];
        });
    }
}
