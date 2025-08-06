<?php

namespace App\Services;

use Illuminate\Support\Arr;

class TpvConfigurationDecoder
{
    private $obj;

    public function __construct($json)
    {
        if (is_array($json) || is_object($json)) {
            $this->obj = $json;
        } else {
            $this->obj = json_decode($json);
        }
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function get($name, $default = null)
    {
        $result = Arr::first($this->obj, function ($item) use ($name) {
            if (is_array($item)) {
                return isset($item['key']) && $item['key'] === $name;
            }
            if (is_object($item)) {
                return isset($item->key) && $item->key === $name;
            }
            return false;
        });

        if (is_object($result) && isset($result->value)) {
            return $result->value;
        }
        if (is_array($result) && isset($result['value'])) {
            return $result['value'];
        }
        return $default;
    }
}


