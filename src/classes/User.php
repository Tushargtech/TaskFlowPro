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
