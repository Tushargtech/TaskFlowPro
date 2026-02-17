<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

if (!isset($_GET['id'], $_SESSION['user_id'])) {
    header('Location: ../../views/users.php?error=missing_parameters');
    exit();
}

$user = new User($pdo);
$targetId = (int) $_GET['id'];
$adminId = (int) $_SESSION['user_id'];

if ($user->deactivateUser($targetId, $adminId)) {
    header('Location: ../../views/users.php?success=deactivated');
    exit();
}

header('Location: ../../views/users.php?error=failed');
exit();
