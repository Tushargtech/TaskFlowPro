<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../classes/Task.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/tasks.php');
    exit();
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF Token Validation Failed.');
}

if (($_SESSION['user_role'] ?? null) !== 1) {
    header('Location: ../../views/tasks.php?error=unauthorized');
    exit();
}

$taskId = (int) ($_POST['task_id'] ?? 0);
$title = trim($_POST['task_title'] ?? '');
$description = trim($_POST['task_description'] ?? '');
$projectId = (int) ($_POST['project_id'] ?? 0);
$assignedTo = (int) ($_POST['assigned_to'] ?? 0);
$dueDate = $_POST['task_due_date'] ?? '';
$status = trim($_POST['task_status'] ?? '');

if ($taskId <= 0 || $title === '' || $projectId <= 0 || $assignedTo <= 0 || $dueDate === '' || $status === '') {
    header('Location: ../../views/tasks.php?error=invalid_input');
    exit();
}

$data = [
    'id' => $taskId,
    'title' => $title,
    'description' => $description,
    'project_id' => $projectId,
    'assigned_to' => $assignedTo,
    'due_date' => $dueDate,
    'status' => $status,
    'modified_by' => (int) ($_SESSION['user_id'] ?? 0),
];

$task = new Task($pdo);

try {
    if ($task->updateTask($data)) {
        header('Location: ../../views/tasks.php?success=task_updated');
        exit();
    }

    header('Location: ../../views/tasks.php?error=task_update_failed');
    exit();
} catch (Throwable $exception) {
    error_log('task_update.php error: ' . $exception->getMessage());
    header('Location: ../../views/tasks.php?error=task_update_failed');
    exit();
}
