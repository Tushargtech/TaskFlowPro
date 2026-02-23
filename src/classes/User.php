<?php

declare(strict_types=1);

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function login(string $email, string $password): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_email = ? AND user_status = 'Active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['user_password'])) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['user_role_id'];
            $_SESSION['user_name'] = trim($user['user_first_name'] . ' ' . $user['user_last_name']);
            $_SESSION['needs_password_change'] = (int) ($user['needs_password_change'] ?? 1);

            return true;
        }

        return false;
    }

    public function getAllUsers(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, r.role_title
             FROM users u
             JOIN user_roles r ON u.user_role_id = r.role_id
             ORDER BY u.user_created_on DESC"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, r.role_title
             FROM users u
             JOIN user_roles r ON u.user_role_id = r.role_id
             WHERE u.user_id = :id"
        );

        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE user_login = ?");
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }

    public function generateRandomPassword(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxIndex = strlen($characters) - 1;
        $password = '';

        for ($index = 0; $index < $length; $index++) {
            $password .= $characters[random_int(0, $maxIndex)];
        }

        return $password;
    }

    public function createUser(array $data): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $hashedPassword = $data['password_hash'] ?? password_hash((string) ($data['password'] ?? ''), PASSWORD_BCRYPT);

        if (!$hashedPassword) {
            return false;
        }

        $sql = "INSERT INTO users (user_login, user_email, user_password, needs_password_change, user_first_name, user_last_name, user_role_id, user_status, user_created_by)
            VALUES (:login, :email, :password, :needs_password_change, :fname, :lname, :role, :status, :created_by)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'login' => $data['login'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'needs_password_change' => 1,
            'fname' => $data['first_name'],
            'lname' => $data['last_name'],
            'role' => $data['role_id'],
            'status' => $data['status'],
            'created_by' => $_SESSION['user_id'] ?? null,
        ]);
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        if (!$hashedPassword) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE users
             SET user_password = :password,
                 needs_password_change = 0,
                 user_modified_by = :modifier_id
             WHERE user_id = :user_id"
        );

        return $stmt->execute([
            'password' => $hashedPassword,
            'modifier_id' => $userId,
            'user_id' => $userId,
        ]);
    }

    public function deactivateUser(int $id, int $modifierId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET user_status = 'Inactive', user_modified_by = ? WHERE user_id = ?"
        );

        return $stmt->execute([$modifierId, $id]);
    }

    public function updateUser(array $data): bool
    {
        $sql = "UPDATE users SET
                user_first_name = :fname,
                user_last_name = :lname,
                user_email = :email,
                user_role_id = :role,
                user_status = :status,
                user_modified_by = :mod_by
                WHERE user_id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($data);
    }

    public function logLogin(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO user_login_records (login_user_id, login_date_time) VALUES (?, NOW())"
        );

        return $stmt->execute([$userId]);
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();

        header('Location: ../../index.php');
        exit();
    }

    public function hasRight(string $rightTitle): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $roleId = $_SESSION['user_role'] ?? null;
        
        if ($roleId === null) {
            return false;
        }

        $sql = "SELECT m.access_status 
                FROM user_role_mapping m
                JOIN user_access_rights r ON m.access_right_id = r.right_id
                WHERE m.access_role_id = :role_id AND r.right_title = :right_title AND m.access_status = 'Yes'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['role_id' => $roleId, 'right_title' => $rightTitle]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result !== false;
    }

    public function getUserRights(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $roleId = $_SESSION['user_role'] ?? null;
        
        if ($roleId === null) {
            return [];
        }

        $sql = "SELECT r.right_title, r.right_id, m.access_status
                FROM user_role_mapping m
                JOIN user_access_rights r ON m.access_right_id = r.right_id
                WHERE m.access_role_id = :role_id AND r.right_status = 'Active'
                ORDER BY r.right_title";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['role_id' => $roleId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
