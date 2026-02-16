<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User($pdo);

    if (empty($_POST['email']) || empty($_POST['password'])) {
        header('Location: ../../views/users.php?error=empty_fields');
        exit();
    }

    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'login' => trim($_POST['login'] ?? ''),
        'role_id' => (int) ($_POST['role_id'] ?? 2),
        'password' => $_POST['password'] ?? '',
        'status' => $_POST['status'] ?? 'Active',
    ];

    if ($user->createUser($data)) {
        header('Location: ../../views/users.php?success=user_added');
        exit();
    }

    header('Location: ../../views/users.php?error=failed');
    exit();
}

header('Location: ../../views/users.php');
exit();
