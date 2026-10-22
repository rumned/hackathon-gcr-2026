<?php

declare(strict_types=1);

// Loads App\Foo\Bar from src/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    $message = 'config.php is missing. Copy config.example.php to config.php and fill it in.';
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    }
    exit(1);
}

App\Config::set(require $configFile);

// All dates are stored and returned in UTC, as in SuiteCRM.
date_default_timezone_set('UTC');
