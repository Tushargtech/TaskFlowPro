<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../classes/Task.php';

if (!isset($_GET['id'])) {
    header('Location: ../../views/tasks.php?error=missing_task');
    exit();
}

$taskId = (int) $_GET['id'];
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? null;

if ($taskId <= 0 || $userId <= 0) {
    header('Location: ../../views/tasks.php?error=invalid_task');
    exit();
}

$task = new Task($pdo);
$isAdmin = ($userRole === 1);

try {
    if ($task->markComplete($taskId, $userId, $isAdmin)) {
        header('Location: ../../views/tasks.php?success=task_completed');
        exit();
    }

    header('Location: ../../views/tasks.php?error=complete_failed');
    exit();
} catch (Throwable $exception) {
    error_log('task_complete.php error: ' . $exception->getMessage());
    header('Location: ../../views/tasks.php?error=complete_exception');
    exit();
}
