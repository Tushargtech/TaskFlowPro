<?php

declare(strict_types=1);

$url = trim((string) ($_GET['url'] ?? ''), '/');
$url = $url === '' ? 'dashboard' : $url;
$urlParts = explode('/', $url);
$route = $urlParts[0] ?? 'dashboard';

$isApi = ($route === 'api');
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($_SESSION['user_id']) && !in_array($route, ['login', 'forgot-password', 'reset-password', 'api'], true)) {
    $route = 'login';
}

if (isset($_SESSION['user_id'])
    && (int) ($_SESSION['needs_password_change'] ?? 0) === 1
    && !in_array($route, ['change-password', 'logout', 'api'], true)
) {
    header('Location: ' . APP_BASE . '/change-password');
    exit;
}

if ($isApi) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Unauthorized']);
        exit;
    }

    $apiPath = implode('/', array_slice(explode('/', trim((string) ($_GET['url'] ?? ''), '/')), 1));
    $_SERVER['PATH_INFO'] = '/' . $apiPath;
    require_once APP_ROOT . '/controllers/api.php';
    exit;
}

if ($route === 'login' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $maxLoginAttempts = 4;
    $lockDurationSeconds = 10 * 60;
    $loginLimit = $_SESSION['login_limit'] ?? [];

    if (!is_array($loginLimit)) {
        $loginLimit = [];
    }

    $failedAttempts = (int) ($loginLimit['failed_attempts'] ?? 0);
    $lockedUntil = (int) ($loginLimit['locked_until'] ?? 0);
    $currentTime = time();

    if ($lockedUntil > $currentTime) {
        $remainingSeconds = $lockedUntil - $currentTime;
        header('Location: ' . APP_BASE . '/login?error=locked&remaining=' . $remainingSeconds);
        exit;
    }

    if ($lockedUntil > 0 && $lockedUntil <= $currentTime) {
        $failedAttempts = 0;
        $lockedUntil = 0;
        $_SESSION['login_limit'] = [
            'failed_attempts' => 0,
            'locked_until' => 0,
        ];
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
        header('Location: ' . APP_BASE . '/login?error=csrf');
        exit;
    }

    $user = new User($pdo);
    if ($user->login($email, $password)) {
        unset($_SESSION['login_limit']);

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            $user->logLogin($userId);
        }

        header('Location: ' . APP_BASE . '/dashboard');
        exit;
    }

    $failedAttempts++;
    $_SESSION['login_limit'] = [
        'failed_attempts' => $failedAttempts,
        'locked_until' => 0,
    ];

    if ($failedAttempts >= $maxLoginAttempts) {
        $_SESSION['login_limit'] = [
            'failed_attempts' => $maxLoginAttempts,
            'locked_until' => $currentTime + $lockDurationSeconds,
        ];

        header('Location: ' . APP_BASE . '/login?error=locked&remaining=' . $lockDurationSeconds);
        exit;
    }

    $remainingAttempts = max(0, $maxLoginAttempts - $failedAttempts);
    header('Location: ' . APP_BASE . '/login?error=invalid&remaining_attempts=' . $remainingAttempts);
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

if ($route === 'change-password' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_BASE . '/login');
        exit;
    }

    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
        header('Location: ' . APP_BASE . '/change-password?error=csrf');
        exit;
    }

    $newPassword = trim((string) ($_POST['new_password'] ?? ''));
    $confirmPassword = trim((string) ($_POST['confirm_password'] ?? ''));

    if ($newPassword === '' || $confirmPassword === '' || strlen($newPassword) < 8) {
        header('Location: ' . APP_BASE . '/change-password?error=invalid');
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        header('Location: ' . APP_BASE . '/change-password?error=mismatch');
        exit;
    }

    $user = new User($pdo);
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId <= 0 || !$user->updatePassword($userId, $newPassword)) {
        header('Location: ' . APP_BASE . '/change-password?error=failed');
        exit;
    }

    $_SESSION['needs_password_change'] = 0;
    header('Location: ' . APP_BASE . '/dashboard?success=password_changed');
    exit;
}

if ($route === 'forgot-password' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
        header('Location: ' . APP_BASE . '/forgot-password?error=csrf');
        exit;
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . APP_BASE . '/forgot-password?error=invalid');
        exit;
    }

    $user = new User($pdo);
    $requestIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $resetPayload = $user->createPasswordResetRequest($email, $requestIp);

    if ($resetPayload !== null) {
        require_once APP_ROOT . '/libraries/MailHelper.php';

        $selector = urlencode((string) ($resetPayload['selector'] ?? ''));
        $token = urlencode((string) ($resetPayload['token'] ?? ''));
        $relativeResetPath = APP_BASE . '/reset-password?selector=' . $selector . '&token=' . $token;

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $https = (string) ($_SERVER['HTTPS'] ?? '');
        $scheme = ($https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
        $resetUrl = $host !== '' ? ($scheme . '://' . $host . $relativeResetPath) : $relativeResetPath;

        $mailSent = MailHelper::sendPasswordResetLink(
            (string) ($resetPayload['email'] ?? $email),
            (string) ($resetPayload['full_name'] ?? 'User'),
            $resetUrl
        );

        if (!$mailSent) {
            error_log('Forgot password email send failed for user email: ' . $email);
        }
    }

    // Always return the same response to prevent email enumeration.
    header('Location: ' . APP_BASE . '/forgot-password?success=sent');
    exit;
}

if ($route === 'reset-password' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
        header('Location: ' . APP_BASE . '/reset-password?error=csrf');
        exit;
    }

    $selector = trim((string) ($_POST['selector'] ?? ''));
    $token = trim((string) ($_POST['token'] ?? ''));
    $newPassword = trim((string) ($_POST['new_password'] ?? ''));
    $confirmPassword = trim((string) ($_POST['confirm_password'] ?? ''));

    if ($selector === '' || $token === '') {
        header('Location: ' . APP_BASE . '/reset-password?error=invalid_link');
        exit;
    }

    $queryToken = 'selector=' . urlencode($selector) . '&token=' . urlencode($token);

    if ($newPassword === '' || $confirmPassword === '' || strlen($newPassword) < 8) {
        header('Location: ' . APP_BASE . '/reset-password?' . $queryToken . '&error=invalid');
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        header('Location: ' . APP_BASE . '/reset-password?' . $queryToken . '&error=mismatch');
        exit;
    }

    $user = new User($pdo);
    $success = $user->consumePasswordResetToken($selector, $token, $newPassword);

    if (!$success) {
        header('Location: ' . APP_BASE . '/reset-password?error=invalid_link');
        exit;
    }

    header('Location: ' . APP_BASE . '/login?success=password_reset');
    exit;
}

$viewFile = APP_ROOT . '/views/' . $route . '.php';
if (!is_file($viewFile)) {
    http_response_code(404);
    $viewFile = APP_ROOT . '/views/404.php';
}

if (!$isAjax && !in_array($route, ['login', 'change-password', 'forgot-password', 'reset-password'], true)) {
    require APP_ROOT . '/src/includes/header.php';
}

require $viewFile;

if (!$isAjax && !in_array($route, ['login', 'change-password', 'forgot-password', 'reset-password'], true)) {
    require APP_ROOT . '/src/includes/footer.php';
}
