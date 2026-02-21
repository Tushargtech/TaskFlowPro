<?php

declare(strict_types=1);

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
            requireAdmin();
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
            requireAdmin();
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
            requireAdmin();
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
