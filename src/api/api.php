<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Project.php';
require_once __DIR__ . '/../classes/Task.php';
require_once __DIR__ . '/user.api.php';
require_once __DIR__ . '/project.api.php';
require_once __DIR__ . '/task.api.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
$request = $pathInfo === '' ? [] : explode('/', $pathInfo);
$resource = $request[0] ?? '';
$id = isset($request[1]) ? (int) $request[1] : null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    validateCsrfToken();
}

$userObj = new User($pdo);
$projectObj = new Project($pdo);
$taskObj = new Task($pdo);

switch ($resource) {
    case 'users':
        handleUsers($method, $id, $userObj);
        break;
    case 'projects':
        handleProjects($method, $id, $projectObj);
        break;
    case 'tasks':
        handleTasks($method, $id, $taskObj);
        break;
    default:
        respond(['message' => 'Endpoint not found'], 404);
}

function readJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        respond(['message' => 'Invalid JSON payload'], 400);
    }

    return $input;
}

function validateRequired(array $input, array $required): void
{
    foreach ($required as $field) {
        if (!array_key_exists($field, $input) || $input[$field] === '') {
            respond(['message' => 'Missing required field: ' . $field], 422);
        }
    }
}

function requireAdmin(): void
{
    if (($_SESSION['user_role'] ?? null) !== 1) {
        respond(['message' => 'Unauthorized'], 403);
    }
}

function validateCsrfToken(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $requestToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($requestToken) || $requestToken === '') {
        respond(['message' => 'CSRF token missing'], 419);
    }

    if (!hash_equals($sessionToken, $requestToken)) {
        respond(['message' => 'CSRF token mismatch'], 419);
    }
}

function respond($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}
