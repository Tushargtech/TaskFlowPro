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

    public function createUser(array $data): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (user_login, user_email, user_password, user_first_name, user_last_name, user_role_id, user_status, user_created_by)
                VALUES (:login, :email, :password, :fname, :lname, :role, :status, :created_by)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'login' => $data['login'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'fname' => $data['first_name'],
            'lname' => $data['last_name'],
            'role' => $data['role_id'],
            'status' => $data['status'],
            'created_by' => $_SESSION['user_id'] ?? null,
        ]);
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
}
