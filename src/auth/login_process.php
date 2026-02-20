<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF Token Validation Failed.');
    }

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
