<?php

declare(strict_types=1);

function handleUsers(string $method, ?int $id, User $userObj): void
{
    global $pdo;

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
                respond(['success' => false, 'message' => 'username_exists'], 422);
            }
            
            $email = trim((string) ($input['email'] ?? ''));
            $emailCheckStmt = $pdo->prepare('SELECT 1 FROM users WHERE user_email = ?');
            $emailCheckStmt->execute([$email]);
            if ($emailCheckStmt->rowCount() > 0) {
                respond(['success' => false, 'message' => 'email_exists'], 422);
            }
            
            $plainPassword = $userObj->generateRandomPassword(10);
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

            if (!$hashedPassword) {
                respond(['success' => false, 'message' => 'Unable to generate password.'], 500);
            }

            $payload = [
                'login' => $username,
                'email' => $email,
                'password_hash' => $hashedPassword,
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'role_id' => (int) $input['role_id'],
                'status' => $input['status'] ?? Constants::USER_STATUS_ACTIVE,
            ];

            $success = $userObj->createUser($payload);

            if (!$success) {
                respond(['success' => false, 'message' => 'email_exists'], 422);
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
            if ($userObj->isSuperAdmin($id) && ($input['status'] ?? '') === Constants::USER_STATUS_INACTIVE) {
                respond(['success' => false, 'message' => 'Super admin cannot be set to inactive.'], 403);
            }
            if ($userObj->isSuperAdmin($id) && (int) ($input['role_id'] ?? 0) !== Constants::ROLE_ADMIN) {
                respond(['success' => false, 'message' => 'super_admin_role'], 403);
            }
            $email = trim((string) ($input['email'] ?? ''));
            if ($email !== '' && $userObj->emailExistsForOther($email, $id)) {
                respond(['success' => false, 'message' => 'email_exists'], 422);
            }
            $payload = [
                'fname' => $input['first_name'],
                'lname' => $input['last_name'],
                'email' => $email,
                'role' => (int) $input['role_id'],
                'status' => $input['status'],
                'mod_by' => (int) ($_SESSION['user_id'] ?? 0),
                'id' => $id,
            ];

            $success = $userObj->updateUser($payload);
            if (!$success && $email !== '' && $userObj->emailExistsForOther($email, $id)) {
                respond(['success' => false, 'message' => 'email_exists'], 422);
            }
            respond(['success' => $success, 'message' => $success ? null : 'update_failed']);
            break;
        case Constants::METHOD_DELETE:
            requireAdmin();
            if ($id === null) {
                respond(['message' => 'User id is required'], 400);
            }
            if ($userObj->isSuperAdmin($id)) {
                respond(['success' => false, 'message' => 'Super admin cannot be deactivated.'], 403);
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
