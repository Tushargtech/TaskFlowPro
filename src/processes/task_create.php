<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../classes/Task.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/tasks.php');
    exit();
}

if (($_SESSION['user_role'] ?? null) !== 1) {
    header('Location: ../../views/tasks.php?error=unauthorized');
    exit();
}

$title = trim($_POST['title'] ?? '');
$projectId = (int) ($_POST['project_id'] ?? 0);
$assignedTo = (int) ($_POST['assigned_to'] ?? 0);
$dueDate = $_POST['due_date'] ?? '';

if ($title === '' || $projectId <= 0 || $assignedTo <= 0 || $dueDate === '') {
    header('Location: ../../views/tasks.php?error=invalid_input');
    exit();
}

$data = [
    'title' => $title,
    'description' => trim($_POST['description'] ?? ''),
    'project_id' => $projectId,
    'assigned_to' => $assignedTo,
    'due_date' => $dueDate,
    'created_by' => (int) ($_SESSION['user_id'] ?? 0),
];

$task = new Task($pdo);

try {
    if ($task->createTask($data)) {
        header('Location: ../../views/tasks.php?success=task_created');
        exit();
    }

    header('Location: ../../views/tasks.php?error=task_failed');
    exit();
} catch (Throwable $exception) {
    error_log('task_create.php error: ' . $exception->getMessage());
    header('Location: ../../views/tasks.php?error=task_exception');
    exit();
}
