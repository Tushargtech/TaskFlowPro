<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$basePath = rtrim((string) dirname($scriptName), '/');
if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}

define('APP_BASE', $basePath);
define('APP_ROOT', __DIR__);

spl_autoload_register(function (string $className): void {
    $paths = [
        APP_ROOT . '/models/' . $className . '.php',
        APP_ROOT . '/controllers/' . $className . '.php',
        APP_ROOT . '/src/classes/' . $className . '.php',
    ];

    foreach ($paths as $filePath) {
        if (is_file($filePath)) {
            require_once $filePath;
            return;
        }
    }
});

require_once APP_ROOT . '/config/db.php';
