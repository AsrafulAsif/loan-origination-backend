<?php

namespace App\Utils;

class PlaceholderReplacer
{
    private const PLACEHOLDER_PATTERN = '/\{([^{}]+)}/';
    private const EXACT_PLACEHOLDER_PATTERN = '/^\{([^{}]+)}$/';

    /**
     * Replace placeholders in a template (array, string, or scalar) using
     * values resolved from the root object/array. Arrays are walked
     * recursively, preserving structure/keys.
     *
     * - A string that is *exactly* one placeholder (e.g. "{data.tags}")
     *   resolves to the value's native type (array, int, bool, null...).
     * - A string with embedded placeholders (e.g. "Hi {name}!") is
     *   interpolated as a string.
     */
    public function replaceInTemplate(mixed $template, array|object $rootObject): mixed
    {
        if (is_array($template)) {
            return array_map(fn ($value) => $this->replaceInTemplate($value, $rootObject), $template);
        }

        if (is_string($template)) {
            return $this->replaceInString($template, $rootObject);
        }

        return $template;
    }

    /**
     * Kept for backward compatibility: replace placeholders in a single
     * string template, always returning a string.
     */
    public function replacePlaceholders(string $template, array|object $rootObject): string
    {
        $result = $this->replaceInString($template, $rootObject);

        return $result === null ? '' : (string) $result;
    }

    //second bracket from template
    private function replaceInString(string $template, array|object $rootObject): mixed
    {
        if (preg_match(self::EXACT_PLACEHOLDER_PATTERN, $template, $matches)) {
            return $this->resolvePath($matches[1], $rootObject);
        }

        return preg_replace_callback(self::PLACEHOLDER_PATTERN, function ($matches) use ($rootObject) {
            $value = $this->resolvePath($matches[1], $rootObject);

            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }

            return $value !== null ? (string) $value : '';
        }, $template);
    }

    /**
     * Resolve a dotted path in nested array/object structure.
     */
    private function resolvePath(string $path, array|object $current): mixed
    {
        $parts = explode('.', $path);

        foreach ($parts as $part) {
            if ($current === null) {
                return null;
            }

            if (preg_match('/^\[\d+]$/', $part)) {
                $index = (int) preg_replace('/[\[\]]/', '', $part);

                if (is_array($current) && isset($current[$index])) {
                    $current = $current[$index];
                } else {
                    return null;
                }
            } elseif (preg_match('/^(.+)\[\d+]$/', $part, $matches)) {
                $key = $matches[1];
                $index = (int) preg_replace('/\D/', '', substr($part, strlen($key)));

                $mapValue = $this->getValueFromCurrent($current, $key);

                if (is_array($mapValue) && isset($mapValue[$index])) {
                    $current = $mapValue[$index];
                } else {
                    return null;
                }
            } else {
                if (!is_array($current) && !is_object($current)) {
                    return null;
                }

                $current = $this->getValueFromCurrent($current, $part);

                if ($current === null) {
                    return null;
                }
            }
        }

        return $current;
    }

    private function getValueFromCurrent(array|object $current, string $key): mixed
    {
        if (is_array($current)) {
            return $current[$key] ?? null;
        }

        if (is_object($current)) {
            return $current->$key ?? null;
        }

        return null;
    }
}
