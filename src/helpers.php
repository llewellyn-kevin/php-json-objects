<?php

if (!function_exists('array_dot_get')) {
    function array_dot_get(array $target, string $key, mixed $default = null): mixed
    {
        $fields = explode('.', $key, 2);
        if (count($fields) === 2) {
            if (!array_key_exists($fields[0], $target)) {
                return $default;
            }

            return array_dot_get($target[$fields[0]], $fields[1], $default);
        }

        if (!array_key_exists($key, $target)) {
            return $default;
        }

        return $target[$key];
    }
}

if (!function_exists('array_dot_set')) {
    function array_dot_set(array &$target, string $key, mixed $value): void
    {
        $fields = explode('.', $key, 2);

        if (count($fields) === 2) {
            array_dot_set($target[$fields[0]], $fields[1], $value);
            return;
        }

        $target[$key] = $value;
    }
}
