<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Project.php';
require_once __DIR__ . '/../classes/Task.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
$request = $pathInfo === '' ? [] : explode('/', $pathInfo);
$resource = $request[0] ?? '';
$id = isset($request[1]) ? (int) $request[1] : null;

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

function handleUsers(string $method, ?int $id, User $userObj): void
{
    switch ($method) {
        case 'GET':
            if ($id !== null) {
                $user = $userObj->getUserById($id);
                if ($user === null) {
                    respond(['message' => 'User not found'], 404);
                }
                respond($user);
            }
            respond($userObj->getAllUsers());
            break;
        case 'POST':
            $input = readJsonInput();
            validateRequired($input, ['login', 'email', 'password', 'first_name', 'last_name', 'role_id']);
            $payload = [
                'login' => $input['login'],
                'email' => $input['email'],
                'password' => $input['password'],
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'role_id' => (int) $input['role_id'],
                'status' => $input['status'] ?? 'Active',
            ];

            $success = $userObj->createUser($payload);
            respond(['success' => $success], $success ? 201 : 400);
            break;
        case 'PUT':
            if ($id === null) {
                respond(['message' => 'User id is required'], 400);
            }
            $input = readJsonInput();
            validateRequired($input, ['first_name', 'last_name', 'email', 'role_id', 'status']);
            $payload = [
                'fname' => $input['first_name'],
                'lname' => $input['last_name'],
                'email' => $input['email'],
                'role' => (int) $input['role_id'],
                'status' => $input['status'],
                'mod_by' => (int) ($_SESSION['user_id'] ?? 0),
                'id' => $id,
            ];

            $success = $userObj->updateUser($payload);
            respond(['success' => $success]);
            break;
        case 'DELETE':
            if ($id === null) {
                respond(['message' => 'User id is required'], 400);
            }
            $modifierId = (int) ($_SESSION['user_id'] ?? 0);
            if ($modifierId <= 0) {
                respond(['message' => 'Unauthorized'], 401);
            }
            $success = $userObj->deactivateUser($id, $modifierId);
            respond(['success' => $success]);
            break;
        default:
            respond(['message' => 'Method not allowed'], 405);
    }
}

function handleProjects(string $method, ?int $id, Project $projectObj): void
{
    switch ($method) {
        case 'GET':
            $data = $id !== null ? $projectObj->getProjectById($id) : $projectObj->getAllProjects();
            respond($data ?? ['message' => 'Project not found'], $data ? 200 : 404);
            break;
        case 'POST':
            $input = readJsonInput();
            $title = $input['title'] ?? '';
            $description = $input['desc'] ?? ($input['description'] ?? null);
            $createdBy = (int) ($_SESSION['user_id'] ?? 0);
            if ($title === '' || $createdBy <= 0) {
                respond(['message' => 'Invalid input'], 422);
            }
            $success = $projectObj->createProject($title, $description, $createdBy);
            respond(['success' => $success], $success ? 201 : 400);
            break;
        case 'PUT':
            if ($id === null) {
                respond(['message' => 'Project id is required'], 400);
            }
            $input = readJsonInput();
            $input['id'] = $id;
            if (!isset($input['title'])) {
                respond(['message' => 'Invalid input'], 422);
            }
            $success = $projectObj->updateProject($input);
            respond(['success' => $success]);
            break;
        case 'DELETE':
            if ($id === null) {
                respond(['message' => 'Project id is required'], 400);
            }
            $success = $projectObj->deactivateProject($id);
            respond(['success' => $success]);
            break;
        default:
            respond(['message' => 'Method not allowed'], 405);
    }
}

function handleTasks(string $method, ?int $id, Task $taskObj): void
{
    switch ($method) {
        case 'GET':
            $userId = (($_SESSION['user_role'] ?? null) == 1) ? null : (int) ($_SESSION['user_id'] ?? 0);
            $data = $id !== null ? $taskObj->getTaskById($id) : $taskObj->getTasks($userId);
            respond($data ?? ['message' => 'Task not found'], $data ? 200 : 404);
            break;
        case 'POST':
            $input = readJsonInput();
            $input['created_by'] = (int) ($_SESSION['user_id'] ?? 0);
            $success = $taskObj->createTask($input);
            respond(['success' => $success]);
            break;
        case 'PUT':
            if ($id === null) {
                respond(['message' => 'Task id is required'], 400);
            }
            $input = readJsonInput();
            if (!isset($input['status'])) {
                respond(['message' => 'Invalid input'], 422);
            }
            $success = $taskObj->updateTaskStatus($id, $input['status']);
            respond(['success' => $success]);
            break;
        case 'DELETE':
            if ($id === null) {
                respond(['message' => 'Task id is required'], 400);
            }
            $success = $taskObj->deleteTask($id);
            respond(['success' => $success]);
            break;
        default:
            respond(['message' => 'Method not allowed'], 405);
    }
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

function respond($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}
