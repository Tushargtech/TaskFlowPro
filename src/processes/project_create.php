<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../classes/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/projects.php');
    exit();
}

if (($_SESSION['user_role'] ?? null) !== 1) {
    header('Location: ../../views/projects.php?error=invalid_input');
    exit();
}

$title = trim($_POST['project_title'] ?? '');
$description = trim($_POST['project_description'] ?? '');
$status = $_POST['project_status'] ?? 'Active';

if ($title === '') {
    header('Location: ../../views/projects.php?error=invalid_input');
    exit();
}

$project = new Project($pdo);
$createdBy = (int) ($_SESSION['user_id'] ?? 0);

try {
    if ($project->createProject($title, $description !== '' ? $description : null, $createdBy, $status)) {
        header('Location: ../../views/projects.php?success=project_created');
        exit();
    }

    header('Location: ../../views/projects.php?error=create_failed');
    exit();
} catch (Throwable $exception) {
    error_log('project_create.php error: ' . $exception->getMessage());
    header('Location: ../../views/projects.php?error=create_exception');
    exit();
}
