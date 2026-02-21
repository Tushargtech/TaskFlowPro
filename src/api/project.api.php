<?php

declare(strict_types=1);

function handleProjects(string $method, ?int $id, Project $projectObj): void
{
    switch ($method) {
        case 'GET':
            $data = $id !== null ? $projectObj->getProjectById($id) : $projectObj->getAllProjects();
            respond($data ?? ['message' => 'Project not found'], $data ? 200 : 404);
            break;
        case 'POST':
            requireAdmin();
            $input = readJsonInput();
            $title = $input['title'] ?? '';
            $description = $input['desc'] ?? ($input['description'] ?? null);
            $status = $input['status'] ?? 'Active';
            $createdBy = (int) ($_SESSION['user_id'] ?? 0);
            if ($title === '' || $createdBy <= 0) {
                respond(['message' => 'Invalid input'], 422);
            }
            $success = $projectObj->createProject($title, $description, $createdBy, $status);
            respond(['success' => $success], $success ? 201 : 400);
            break;
        case 'PUT':
            requireAdmin();
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
            requireAdmin();
            if ($id === null) {
                respond(['message' => 'Project id is required'], 400);
            }
            $success = $projectObj->deactivateProject($id, (int) ($_SESSION['user_id'] ?? 0));
            respond(['success' => $success]);
            break;
        default:
            respond(['message' => 'Method not allowed'], 405);
    }
}
