<?php

declare(strict_types=1);

$url = trim((string) ($_GET['url'] ?? ''), '/');
$url = $url === '' ? 'dashboard' : $url;
$urlParts = explode('/', $url);
$route = $urlParts[0] ?? 'dashboard';

$isApi = ($route === 'api');
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($_SESSION['user_id']) && !in_array($route, ['login', 'api'], true)) {
    $route = 'login';
}

if ($isApi) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Unauthorized']);
        exit;
    }

    require_once APP_ROOT . '/controllers/ApiController.php';
    exit;
}

if ($route === 'login' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
        header('Location: ' . APP_BASE . '/login?error=csrf');
        exit;
    }

    $user = new User($pdo);
    if ($user->login($email, $password)) {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            $user->logLogin($userId);
        }

        header('Location: ' . APP_BASE . '/dashboard');
        exit;
    }

    header('Location: ' . APP_BASE . '/login?error=invalid');
    exit;
}

if ($route === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
    header('Location: ' . APP_BASE . '/login');
    exit;
}

$viewFile = APP_ROOT . '/views/' . $route . '.php';
if (!is_file($viewFile)) {
    http_response_code(404);
    $viewFile = APP_ROOT . '/views/404.php';
}

if (!$isAjax && $route !== 'login') {
    require APP_ROOT . '/src/includes/header.php';
}

require $viewFile;

if (!$isAjax && $route !== 'login') {
    require APP_ROOT . '/src/includes/footer.php';
}
