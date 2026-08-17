<?php

namespace App\Support;

/**
 * Reads the .env file, replaces or appends the given KEY=value pairs, and
 * writes it back - the one piece the install wizard relies on to persist
 * DB/mail config across requests (and process restarts).
 */
class EnvFileWriter
{
    public static function set(array $values): void
    {
        $path = base_path('.env');
        $contents = file_exists($path) ? file_get_contents($path) : '';
        $lines = $contents === '' ? [] : explode("\n", $contents);

        foreach ($values as $key => $value) {
            $formatted = $key.'='.static::formatValue($value);
            $found = false;

            foreach ($lines as $i => $line) {
                if (preg_match('/^'.preg_quote($key, '/').'=/', $line)) {
                    $lines[$i] = $formatted;
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $lines[] = $formatted;
            }
        }

        file_put_contents($path, implode("\n", $lines));
    }

    private static function formatValue(string $value): string
    {
        if ($value === '' || preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
