<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/includes/auth_middleware.php';
require_once __DIR__ . '/../src/classes/Task.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tasks.php');
    exit();
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF Token Validation Failed.');
}

if (($_SESSION['user_role'] ?? null) !== 1) {
    header('Location: tasks.php?error=unauthorized');
    exit();
}

$title = trim($_POST['title'] ?? '');
$projectId = (int) ($_POST['project_id'] ?? 0);
$assignedTo = (int) ($_POST['assigned_to'] ?? 0);
$dueDate = $_POST['due_date'] ?? '';

if ($title === '' || $projectId <= 0 || $assignedTo <= 0 || $dueDate === '') {
    header('Location: tasks.php?error=invalid_input');
    exit();
}

if (strtotime($dueDate) < strtotime(date('Y-m-d'))) {
    header('Location: tasks.php?error=past_date');
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
        header('Location: tasks.php?success=task_created');
        exit();
    }

    header('Location: tasks.php?error=task_failed');
    exit();
} catch (Throwable $exception) {
    error_log('task_create.php error: ' . $exception->getMessage());
    header('Location: tasks.php?error=task_exception');
    exit();
}
