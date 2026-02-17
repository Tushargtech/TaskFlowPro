<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = new User($pdo);

    if ($user->login($email, $password)) {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId !== null) {
            $user->logLogin((int) $userId);
        }

        header('Location: ../../views/dashboard.php');
        exit();
    }

    echo "<script>alert('Invalid Credentials'); window.location='../../index.php';</script>";
}
