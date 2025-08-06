<?php

namespace App\Traits;


use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Trait HasImages
 *
 * Adds support for a “prominent” image (index 0) plus an array of additional images.
 */
trait HasImages
{
    /**
     * Whether this model uses a “prominent” (main) image in slot 0.
     *
     * @var bool
     */
    protected $has_prominent_image = false;

    /**
     * Enable or disable the “prominent image” behavior.
     */
    public function hasProminentImage(bool $hasIt): void
    {
        $this->has_prominent_image = $hasIt;
    }

    /**
     * Accessor for the images array (minus the prominent image if set).
     *
     * @param  mixed  $value  Could be JSON string or already‐cast array.
     * @return array
     */
    public function getImagesAttribute($value): array
    {
        if (is_array($value)) {
            $images = $value;
        } elseif (is_string($value)) {
            $images = json_decode($value, true) ?: [];
        } else {
            $images = [];
        }

        // If there’s a prominent image, drop it from the returned list
        if ($this->has_prominent_image) {
            return array_slice($images, 1);
        }

        return $images;
    }

    /**
     * Accessor for the single “prominent” image (index 0).
     *
     * @return string
     * @throws \Exception if model wasn’t configured for a prominent image
     */
    public function getImageAttribute(): string
    {
        $default = config(
            'base.default_img',
            'https://engine.yesweticket.com/images/placeholder.png'
        );

        if (! $this->has_prominent_image) {
            throw new \Exception(sprintf(
                "Model %s has no prominent image configured.",
                static::class
            ));
        }

        $images = json_decode($this->attributes['images'] ?? '[]', true) ?: [];
        $first  = $images[0] ?? null;

        if (! $first) {
            return $default;
        }

        // brand_asset + Storage::url to generate full URL
        return brand_asset(Storage::url($first), $this->brand);
    }

    /**
     * Mutator for the full images array.
     *
     * @param  array  $images
     */
    public function setImagesAttribute($images): void
    {
        if (! is_array($images)) {
            $images = [];
        }

        // Normalize each path: if it already starts with “uploads”, keep it;
        // otherwise rebase it under config('elfinder.dir').
        $normalized = array_map(function ($img) {
            if (Str::startsWith($img, 'uploads')) {
                return $img;
            }
            $parts = explode('/', $img);
            array_shift($parts);
            return rtrim(config('elfinder.dir'), '/')
                 . '/'
                 . implode('/', $parts);
        }, $images);

        // If there's a prominent image, re‐insert the old index 0
        if ($this->has_prominent_image) {
            $oldJson = json_decode($this->attributes['images'] ?? '[]', true) ?: [];
            $prom = $oldJson[0] ?? null;
            array_unshift($normalized, $prom);
        }

        $this->attributes['images'] = json_encode($normalized);
    }

    /**
     * Mutator for the single prominent image slot (base64 upload).
     *
     * @param  mixed  $value  Base64 string, or empty to delete.
     * @throws \Exception
     */
    public function setImageAttribute($value): void
    {
        if (! $this->has_prominent_image) {
            throw new \Exception(sprintf(
                "Model %s has no prominent image configured.",
                static::class
            ));
        }

        // Determine a folder name from the model class
        $parts = explode('\\', static::class);
        $folder = strtolower(Arr::last($parts));

        // If base64, decode and store under disk “brand”
        if (is_string($value) && Str::startsWith($value, 'data:image')) {
            $img = Image::make($value);
            $filename = sprintf(
                "%d-%s-%s.jpg",
                $this->id,
                Str::slug($this->name ?? 'img'),
                substr(md5($value . time()), 0, 5)
            );

            $path = "$folder/$filename";
            if (Storage::disk('brand')->put($path, $img->stream())) {
                $raw = json_decode($this->attributes['images'] ?? '[]', true) ?: [];
                array_unshift($raw, config('elfinder.dir') . "/$path");
                $this->attributes['images'] = json_encode($raw);
            }
        }
        // If the value is “empty”, remove the first image and delete the file
        elseif (empty($value) && isset($this->attributes['images'])) {
            $raw = json_decode($this->attributes['images'], true) ?: [];
            $removed = array_shift($raw);
            if ($removed) {
                Storage::disk('public')->delete($removed);
            }
            $this->attributes['images'] = json_encode($raw);
        }
    }
}
