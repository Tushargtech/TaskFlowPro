<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/includes/auth_middleware.php';
require_once __DIR__ . '/../src/classes/Task.php';

if (!isset($_GET['id'])) {
    header('Location: tasks.php?error=missing_task');
    exit();
}

$taskId = (int) $_GET['id'];
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? null;

if ($taskId <= 0 || $userId <= 0) {
    header('Location: tasks.php?error=invalid_task');
    exit();
}

$task = new Task($pdo);
$isAdmin = ($userRole === 1);

try {
    if ($task->markComplete($taskId, $userId, $isAdmin)) {
        header('Location: tasks.php?success=task_completed');
        exit();
    }

    header('Location: tasks.php?error=complete_failed');
    exit();
} catch (Throwable $exception) {
    error_log('task_complete.php error: ' . $exception->getMessage());
    header('Location: tasks.php?error=complete_exception');
    exit();
}
