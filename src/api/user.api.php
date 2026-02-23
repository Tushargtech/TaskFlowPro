<?php

declare(strict_types=1);

function handleUsers(string $method, ?int $id, User $userObj): void
{
    switch ($method) {
        case Constants::METHOD_GET:
            if ($id !== null) {
                $user = $userObj->getUserById($id);
                if ($user === null) {
                    respond(['message' => 'User not found'], 404);
                }
                respond($user);
            }
            respond($userObj->getAllUsers());
            break;
        case Constants::METHOD_POST:
            requireAdmin();
            $input = readJsonInput();
            validateRequired($input, ['login', 'email', 'first_name', 'last_name', 'role_id']);
            
            $username = trim($input['login']);
            if ($userObj->usernameExists($username)) {
                respond(['success' => false, 'message' => 'Username already exists. Please choose a different username.'], 422);
            }
            
            $plainPassword = $userObj->generateRandomPassword(10);
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

            if (!$hashedPassword) {
                respond(['success' => false, 'message' => 'Unable to generate password.'], 500);
            }

            $payload = [
                'login' => $username,
                'email' => $input['email'],
                'password_hash' => $hashedPassword,
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'role_id' => (int) $input['role_id'],
                'status' => $input['status'] ?? Constants::USER_STATUS_ACTIVE,
            ];

            $success = $userObj->createUser($payload);

            if (!$success) {
                respond(['success' => false], 400);
            }

            require_once APP_ROOT . '/libraries/MailHelper.php';
            $fullName = trim((string) ($input['first_name'] ?? '') . ' ' . (string) ($input['last_name'] ?? ''));
            $mailSent = MailHelper::sendPassword((string) $input['email'], $fullName, $plainPassword);

            respond(['success' => true, 'mail_sent' => $mailSent], 201);
            break;
        case Constants::METHOD_PUT:
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
        case Constants::METHOD_DELETE:
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
