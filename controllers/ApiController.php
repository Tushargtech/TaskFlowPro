<?php

declare(strict_types=1);

$apiPath = implode('/', array_slice(explode('/', trim((string) ($_GET['url'] ?? ''), '/')), 1));
$_SERVER['PATH_INFO'] = '/' . $apiPath;

require APP_ROOT . '/src/api/api.php';
