<?php

namespace App\Support;

use RuntimeException;

/**
 * Minimal, dependency-free editor for a dotenv file. Updates a key in place
 * (uncommenting it if needed) or appends it, leaving everything else as is.
 */
class EnvFileEditor
{
    public function __construct(private readonly string $path) {}

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function set(string $key, ?string $value): void
    {
        $contents = $this->exists() ? (string) file_get_contents($this->path) : '';
        $line = $key.'='.$this->format($value ?? '');
        $quoted = preg_quote($key, '/');

        // Prefer an active assignment, then a commented-out one, else append.
        if (preg_match('/^'.$quoted.'=.*$/m', $contents)) {
            $contents = preg_replace('/^'.$quoted.'=.*$/m', $this->escapeReplacement($line), $contents, 1);
        } elseif (preg_match('/^#\s*'.$quoted.'=.*$/m', $contents)) {
            $contents = preg_replace('/^#\s*'.$quoted.'=.*$/m', $this->escapeReplacement($line), $contents, 1);
        } else {
            $contents = rtrim($contents, "\r\n").($contents === '' ? '' : PHP_EOL).$line.PHP_EOL;
        }

        if (file_put_contents($this->path, $contents) === false) {
            throw new RuntimeException('No se pudo escribir el archivo de entorno.');
        }
    }

    public function get(string $key): ?string
    {
        if (! $this->exists()) {
            return null;
        }

        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', (string) file_get_contents($this->path), $m)) {
            return null;
        }

        $value = trim($m[1]);

        if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
            return preg_replace('/\\\\(["\\\\$])/', '$1', substr($value, 1, -1));
        }

        return trim($value, '\'');
    }

    private function format(string $value): string
    {
        if ($value !== '' && ! preg_match('/[\s#"\'\\\\$=]/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    private function escapeReplacement(string $line): string
    {
        return str_replace(['\\', '$'], ['\\\\', '\\$'], $line);
    }
}
