<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/includes/auth_middleware.php';
require_once __DIR__ . '/../src/classes/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit();
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF Token Validation Failed.');
}

if (($_SESSION['user_role'] ?? null) !== 1) {
    header('Location: dashboard.php');
    exit();
}

$userId = (int) ($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: users.php?error=invalid_user');
    exit();
}

$data = [
    'fname' => trim($_POST['first_name'] ?? ''),
    'lname' => trim($_POST['last_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'role' => (int) ($_POST['role_id'] ?? 2),
    'status' => $_POST['status'] ?? 'Active',
    'mod_by' => $_SESSION['user_id'] ?? null,
    'id' => $userId,
];

if ($data['email'] === '') {
    header('Location: users.php?error=empty_fields');
    exit();
}

$user = new User($pdo);

try {
    if ($user->updateUser($data)) {
        header('Location: users.php?success=user_updated');
        exit();
    }

    header('Location: users.php?error=failed');
    exit();
} catch (Throwable $exception) {
    error_log('user_update.php error: ' . $exception->getMessage());
    header('Location: users.php?error=' . urlencode('update_failed'));
    exit();
}
