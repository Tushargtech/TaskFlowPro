<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../classes/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/projects.php');
    exit();
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF Token Validation Failed.');
}

if (($_SESSION['user_role'] ?? null) !== 1) {
    header('Location: ../../views/projects.php?error=unauthorized');
    exit();
}

$projectId = (int) ($_POST['project_id'] ?? 0);
$title = trim($_POST['project_title'] ?? '');
$description = trim($_POST['project_description'] ?? '');
$status = $_POST['project_status'] ?? 'Active';

$allowedStatuses = ['Active', 'Inactive'];

if ($projectId <= 0 || $title === '' || !in_array($status, $allowedStatuses, true)) {
    header('Location: ../../views/projects.php?error=invalid_input');
    exit();
}

$project = new Project($pdo);

$data = [
    'id' => $projectId,
    'title' => $title,
    'description' => $description,
    'status' => $status,
    'modified_by' => (int) ($_SESSION['user_id'] ?? 0),
];

try {
    if ($project->updateProject($data)) {
        header('Location: ../../views/projects.php?success=project_updated');
        exit();
    }

    header('Location: ../../views/projects.php?error=update_failed');
    exit();
} catch (Throwable $exception) {
    error_log('project_update.php error: ' . $exception->getMessage());
    header('Location: ../../views/projects.php?error=update_exception');
    exit();
}
