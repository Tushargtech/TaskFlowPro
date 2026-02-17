<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../classes/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/users.php');
    exit();
}

if (($_SESSION['user_role'] ?? null) !== 1) {
    header('Location: ../../views/dashboard.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ../../views/users.php?error=empty_fields');
    exit();
}

$data = [
    'first_name' => trim($_POST['first_name'] ?? ''),
    'last_name' => trim($_POST['last_name'] ?? ''),
    'email' => $email,
    'login' => trim($_POST['login'] ?? ''),
    'role_id' => (int) ($_POST['role_id'] ?? 2),
    'password' => $password,
    'status' => $_POST['status'] ?? 'Active',
];

$user = new User($pdo);

try {
    if ($user->createUser($data)) {
        header('Location: ../../views/users.php?success=user_added');
        exit();
    }

    header('Location: ../../views/users.php?error=failed');
    exit();
} catch (Throwable $exception) {
    error_log('user_create.php error: ' . $exception->getMessage());
    header('Location: ../../views/users.php?error=' . urlencode('creation_failed'));
    exit();
}
