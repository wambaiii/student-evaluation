<?php
declare(strict_types=1);

/**
 * Tiny .env loader + env() helper.
 *
 * Local XAMPP:  put your settings in a file called ".env" in the project root
 *               (copy .env.example). .env is in .gitignore so it is NEVER pushed.
 * Azure VM:     Member 1 can either create a .env on the server, or set the same
 *               names with Apache SetEnv. Real environment values always win.
 */
function load_env(string $file): void
{
    if (!is_readable($file)) {
        return;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip optional surrounding quotes
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? false; // Apache SetEnv lands in $_SERVER
    }
    return ($value === false || $value === '') ? $default : (string)$value;
}
