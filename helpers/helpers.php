<?php

/**
 * This file will be autoloaded by Composer. 
 * @see composer.json > autoload > files
 */

declare(strict_types=1);

if (!function_exists('env')) {
    /**
     * Get env value
     *
     * @param string $name Name of the env variable
     * @param mixed $fallback Fallback value to return if variable not found
     * @return mixed
     */
    function env(string $name, $fallback = null)
    {
        return array_key_exists($name, $_ENV) ? $_ENV[$name] : $fallback;
    }
}

if (!function_exists('safe_json_encode')) {
    function safe_json_encode($data)
    {
        if (is_string($data)) {
            if (!mb_check_encoding($data, 'UTF-8')) {
                // Try common encodings first
                $encodings = ['ASCII', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252'];
                foreach ($encodings as $encoding) {
                    $converted = @mb_convert_encoding($data, 'UTF-8', $encoding);
                    if (mb_check_encoding($converted, 'UTF-8')) {
                        $data = $converted;
                        break;
                    }
                }

                // If still not valid UTF-8, force encode
                if (!mb_check_encoding($data, 'UTF-8')) {
                    $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8//IGNORE');
                    // Clean up any remaining invalid characters
                    $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
                }
            }
        } elseif (is_array($data) || is_object($data)) {
            array_walk_recursive($data, function (&$item, $key) {
                if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                    // Use the same conversion logic for nested items
                    $item = safe_json_encode($item);
                }
            });
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
