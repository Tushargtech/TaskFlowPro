<?php

declare(strict_types=1);

function handleTasks(string $method, ?int $id, Task $taskObj): void
{
    switch ($method) {
        case 'GET':
            $userId = (($_SESSION['user_role'] ?? null) == 1) ? null : (int) ($_SESSION['user_id'] ?? 0);
            $data = $id !== null ? $taskObj->getTaskById($id) : $taskObj->getTasks($userId);
            respond($data ?? ['message' => 'Task not found'], $data ? 200 : 404);
            break;
        case 'POST':
            requireAdmin();
            $input = readJsonInput();
            validateRequired($input, ['title', 'project_id', 'assigned_to', 'due_date']);
            $input['created_by'] = (int) ($_SESSION['user_id'] ?? 0);
            $success = $taskObj->createTask($input);
            respond(['success' => $success], $success ? 201 : 400);
            break;
        case 'PUT':
            if ($id === null) {
                respond(['message' => 'Task id is required'], 400);
            }
            $input = readJsonInput();

            if (isset($input['title'])) {
                requireAdmin();
                validateRequired($input, ['title', 'project_id', 'assigned_to', 'due_date', 'status']);
                $payload = [
                    'id' => $id,
                    'title' => $input['title'],
                    'description' => $input['description'] ?? '',
                    'project_id' => (int) $input['project_id'],
                    'assigned_to' => (int) $input['assigned_to'],
                    'due_date' => $input['due_date'],
                    'status' => $input['status'],
                    'modified_by' => (int) ($_SESSION['user_id'] ?? 0),
                ];
                $success = $taskObj->updateTask($payload);
                respond(['success' => $success]);
            }

            if (!isset($input['status']) || $input['status'] === '') {
                respond(['message' => 'Invalid input'], 422);
            }

            $task = $taskObj->getTaskById($id);
            if ($task === null) {
                respond(['message' => 'Task not found'], 404);
            }

            $isAdmin = (($_SESSION['user_role'] ?? null) === 1);
            $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
            if (!$isAdmin && (int) ($task['task_assigned_to'] ?? 0) !== $currentUserId) {
                respond(['message' => 'Forbidden'], 403);
            }

            $success = $taskObj->updateTaskStatus($id, $input['status']);
            respond(['success' => $success]);
            break;
        case 'DELETE':
            requireAdmin();
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
