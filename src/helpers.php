<?php

if (!function_exists('data_get')) {
    function data_get(mixed $target, string $key, mixed $default = null): mixed
    {
        $fields = explode('.', $key, 2);
        if (count($fields) === 2) {
            if (!array_key_exists($fields[0], $target)) {
                return $default;
            }

            return data_get($target[$fields[0]], $fields[1], $default);
        }

        if (!array_key_exists($key, $target)) {
            return $default;
        }

        return $target[$key];
    }
}
