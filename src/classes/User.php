<?php

declare(strict_types=1);

class User
{
    private const SUPER_ADMIN_EMAIL = 'admin@taskflow.com';
    private const PASSWORD_RESET_TOKEN_TTL_SECONDS = 900;
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

    public function getUserCount(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users');
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getUsersPaginated(int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $stmt = $this->pdo->prepare(
            "SELECT u.*, r.role_title
             FROM users u
             JOIN user_roles r ON u.user_role_id = r.role_id
             ORDER BY u.user_created_on DESC
             LIMIT :limit OFFSET :offset"
        );

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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

    public function emailExistsForOther(string $email, int $excludeId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE user_email = ? AND user_id <> ?');
        $stmt->execute([$email, $excludeId]);
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
        if ($this->isSuperAdmin($id)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE users SET user_status = 'Inactive', user_modified_by = ? WHERE user_id = ?"
        );

        return $stmt->execute([$modifierId, $id]);
    }

    public function updateUser(array $data): bool
    {
        $requestedStatus = (string) ($data['status'] ?? '');
        $targetId = (int) ($data['id'] ?? 0);

        if ($requestedStatus === 'Inactive' && $targetId > 0 && $this->isSuperAdmin($targetId)) {
            return false;
        }

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

    public function isSuperAdmin(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT user_email FROM users WHERE user_id = ?');
        $stmt->execute([$id]);
        $email = (string) ($stmt->fetchColumn() ?: '');

        return strtolower($email) === self::SUPER_ADMIN_EMAIL;
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

    public function createPasswordResetRequest(string $email, string $requestIp = ''): ?array
    {
        if (!$this->ensurePasswordResetTableExists()) {
            return null;
        }

        $columnMap = $this->getPasswordResetColumnMap();
        if ($columnMap === []) {
            return null;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $user = $this->findActiveUserByEmail($email);
        if ($user === null) {
            return null;
        }

        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresOn = date('Y-m-d H:i:s', time() + self::PASSWORD_RESET_TOKEN_TTL_SECONDS);

        $userIdColumn = $columnMap['user_id'];
        $selectorColumn = $columnMap['selector'];
        $tokenHashColumn = $columnMap['token_hash'];
        $expiresOnColumn = $columnMap['expires_on'];
        $requestIpColumn = $columnMap['request_ip'];
        $usedOnColumn = $columnMap['used_on'];

        try {
            $this->pdo->beginTransaction();

            $invalidateSql = "UPDATE password_reset_tokens
                              SET {$usedOnColumn} = NOW()
                              WHERE {$userIdColumn} = :user_id
                                AND {$usedOnColumn} IS NULL
                                AND {$expiresOnColumn} > NOW()";
            $invalidateStmt = $this->pdo->prepare($invalidateSql);
            $invalidateStmt->execute(['user_id' => (int) $user['user_id']]);

            $insertColumns = [$userIdColumn, $tokenHashColumn, $expiresOnColumn];
            $insertPlaceholders = [':user_id', ':token_hash', ':expires_on'];
            $insertParams = [
                'user_id' => (int) $user['user_id'],
                'token_hash' => $tokenHash,
                'expires_on' => $expiresOn,
            ];

            if ($selectorColumn !== null) {
                $insertColumns[] = $selectorColumn;
                $insertPlaceholders[] = ':selector';
                $insertParams['selector'] = $selector;
            }

            if ($requestIpColumn !== null) {
                $insertColumns[] = $requestIpColumn;
                $insertPlaceholders[] = ':request_ip';
                $insertParams['request_ip'] = $requestIp;
            }

            $insertSql = 'INSERT INTO password_reset_tokens (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertPlaceholders) . ')';
            $insertStmt = $this->pdo->prepare($insertSql);
            $insertStmt->execute($insertParams);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Password reset request creation failed: ' . $exception->getMessage());
            return null;
        }

        return [
            'email' => (string) $user['user_email'],
            'full_name' => $this->buildDisplayName($user),
            'selector' => $selector,
            'token' => $token,
        ];
    }

    public function isPasswordResetTokenValid(string $selector, string $token): bool
    {
        if (!$this->ensurePasswordResetTableExists()) {
            return false;
        }

        $resetRecord = $this->getPasswordResetRecord($selector, $token);
        if ($resetRecord === null) {
            return false;
        }

        if ((string) ($resetRecord['user_status'] ?? '') !== Constants::USER_STATUS_ACTIVE) {
            return false;
        }

        if (!empty($resetRecord['reset_used_on'])) {
            return false;
        }

        $expiresAt = strtotime((string) ($resetRecord['reset_expires_on'] ?? ''));
        if ($expiresAt === false || $expiresAt < time()) {
            return false;
        }

        $incomingTokenHash = hash('sha256', $token);
        return hash_equals((string) $resetRecord['reset_token_hash'], $incomingTokenHash);
    }

    public function consumePasswordResetToken(string $selector, string $token, string $newPassword): bool
    {
        if (!$this->ensurePasswordResetTableExists()) {
            return false;
        }

        $columnMap = $this->getPasswordResetColumnMap();
        if ($columnMap === []) {
            return false;
        }

        $userIdColumn = $columnMap['user_id'];
        $selectorColumn = $columnMap['selector'];
        $tokenHashColumn = $columnMap['token_hash'];
        $expiresOnColumn = $columnMap['expires_on'];
        $usedOnColumn = $columnMap['used_on'];

        if (strlen($newPassword) < 8) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        if (!$hashedPassword) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            if ($selectorColumn !== null) {
                $lockSql = "SELECT r.reset_id,
                                   r.{$userIdColumn} AS reset_user_id,
                                   r.{$tokenHashColumn} AS reset_token_hash,
                                   r.{$expiresOnColumn} AS reset_expires_on,
                                   r.{$usedOnColumn} AS reset_used_on,
                                   u.user_status
                            FROM password_reset_tokens r
                            JOIN users u ON u.user_id = r.{$userIdColumn}
                            WHERE r.{$selectorColumn} = :selector
                            LIMIT 1
                            FOR UPDATE";
                $lockStmt = $this->pdo->prepare($lockSql);
                $lockStmt->execute(['selector' => $selector]);
            } else {
                $lockSql = "SELECT r.reset_id,
                                   r.{$userIdColumn} AS reset_user_id,
                                   r.{$tokenHashColumn} AS reset_token_hash,
                                   r.{$expiresOnColumn} AS reset_expires_on,
                                   r.{$usedOnColumn} AS reset_used_on,
                                   u.user_status
                            FROM password_reset_tokens r
                            JOIN users u ON u.user_id = r.{$userIdColumn}
                            WHERE r.{$tokenHashColumn} = :token_hash
                            LIMIT 1
                            FOR UPDATE";
                $lockStmt = $this->pdo->prepare($lockSql);
                $lockStmt->execute(['token_hash' => hash('sha256', $token)]);
            }

            $resetRecord = $lockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$resetRecord) {
                $this->pdo->rollBack();
                return false;
            }

            if ((string) ($resetRecord['user_status'] ?? '') !== Constants::USER_STATUS_ACTIVE) {
                $this->pdo->rollBack();
                return false;
            }

            if (!empty($resetRecord['reset_used_on'])) {
                $this->pdo->rollBack();
                return false;
            }

            $expiresAt = strtotime((string) ($resetRecord['reset_expires_on'] ?? ''));
            if ($expiresAt === false || $expiresAt < time()) {
                $this->pdo->rollBack();
                return false;
            }

            $incomingTokenHash = hash('sha256', $token);
            if (!hash_equals((string) $resetRecord['reset_token_hash'], $incomingTokenHash)) {
                $this->pdo->rollBack();
                return false;
            }

            $targetUserId = (int) ($resetRecord['reset_user_id'] ?? 0);
            if ($targetUserId <= 0) {
                $this->pdo->rollBack();
                return false;
            }

            $updatePasswordStmt = $this->pdo->prepare(
                "UPDATE users
                 SET user_password = :password,
                     needs_password_change = 0,
                     user_modified_by = :modifier_id
                 WHERE user_id = :user_id"
            );
            $updatePasswordStmt->execute([
                'password' => $hashedPassword,
                'modifier_id' => $targetUserId,
                'user_id' => $targetUserId,
            ]);

            $consumeTokenStmt = $this->pdo->prepare(
                'UPDATE password_reset_tokens SET ' . $usedOnColumn . ' = NOW() WHERE reset_id = :reset_id'
            );
            $consumeTokenStmt->execute([
                'reset_id' => (int) $resetRecord['reset_id'],
            ]);

            $expireOtherSql = 'UPDATE password_reset_tokens SET ' . $usedOnColumn . ' = NOW() WHERE ' . $userIdColumn . ' = :user_id AND ' . $usedOnColumn . ' IS NULL';
            $expireOtherTokensStmt = $this->pdo->prepare($expireOtherSql);
            $expireOtherTokensStmt->execute([
                'user_id' => $targetUserId,
            ]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Password reset consume failed: ' . $exception->getMessage());
            return false;
        }
    }

    private function findActiveUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_id, user_email, user_first_name, user_last_name, user_status FROM users WHERE user_email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        if ((string) ($user['user_status'] ?? '') !== Constants::USER_STATUS_ACTIVE) {
            return null;
        }

        return $user;
    }

    private function getPasswordResetRecord(string $selector, string $token): ?array
    {
        $columnMap = $this->getPasswordResetColumnMap();
        if ($columnMap === []) {
            return null;
        }

        $userIdColumn = $columnMap['user_id'];
        $selectorColumn = $columnMap['selector'];
        $tokenHashColumn = $columnMap['token_hash'];
        $expiresOnColumn = $columnMap['expires_on'];
        $usedOnColumn = $columnMap['used_on'];

        if ($selectorColumn !== null) {
            if ($selector === '') {
                return null;
            }

            $sql = "SELECT r.reset_id,
                           r.{$userIdColumn} AS reset_user_id,
                           r.{$tokenHashColumn} AS reset_token_hash,
                           r.{$expiresOnColumn} AS reset_expires_on,
                           r.{$usedOnColumn} AS reset_used_on,
                           u.user_status
                    FROM password_reset_tokens r
                    JOIN users u ON u.user_id = r.{$userIdColumn}
                    WHERE r.{$selectorColumn} = :selector
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['selector' => $selector]);
        } else {
            if ($token === '') {
                return null;
            }

            $sql = "SELECT r.reset_id,
                           r.{$userIdColumn} AS reset_user_id,
                           r.{$tokenHashColumn} AS reset_token_hash,
                           r.{$expiresOnColumn} AS reset_expires_on,
                           r.{$usedOnColumn} AS reset_used_on,
                           u.user_status
                    FROM password_reset_tokens r
                    JOIN users u ON u.user_id = r.{$userIdColumn}
                    WHERE r.{$tokenHashColumn} = :token_hash
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['token_hash' => hash('sha256', $token)]);
        }

        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ?: null;
    }

    private function buildDisplayName(array $user): string
    {
        $fullName = trim((string) ($user['user_first_name'] ?? '') . ' ' . (string) ($user['user_last_name'] ?? ''));
        return $fullName !== '' ? $fullName : 'User';
    }

    private function ensurePasswordResetTableExists(): bool
    {
        try {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS password_reset_tokens (
                    reset_id INT PRIMARY KEY AUTO_INCREMENT,
                    reset_user_id INT NOT NULL,
                    reset_selector CHAR(16) NOT NULL UNIQUE,
                    reset_token_hash CHAR(64) NOT NULL,
                    reset_expires_on DATETIME NOT NULL,
                    reset_request_ip VARCHAR(45) DEFAULT NULL,
                    reset_created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    reset_used_on DATETIME DEFAULT NULL,
                    INDEX idx_password_reset_user (reset_user_id),
                    INDEX idx_password_reset_expires (reset_expires_on),
                    CONSTRAINT fk_password_reset_user
                        FOREIGN KEY (reset_user_id)
                        REFERENCES users(user_id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                )"
            );

            return true;
        } catch (Throwable $exception) {
            error_log('Failed ensuring password_reset_tokens table: ' . $exception->getMessage());
            return false;
        }
    }

    private function getPasswordResetColumnMap(): array
    {
        $columns = $this->getPasswordResetColumns();
        if ($columns === []) {
            return [];
        }

        $has = static fn (string $name): bool => in_array($name, $columns, true);

        $userIdColumn = $has('reset_user_id') ? 'reset_user_id' : ($has('user_id') ? 'user_id' : null);
        $tokenHashColumn = $has('reset_token_hash') ? 'reset_token_hash' : ($has('token_hash') ? 'token_hash' : null);
        $expiresOnColumn = $has('reset_expires_on') ? 'reset_expires_on' : ($has('expires_at') ? 'expires_at' : null);
        $usedOnColumn = $has('reset_used_on') ? 'reset_used_on' : ($has('used_at') ? 'used_at' : null);
        $selectorColumn = $has('reset_selector') ? 'reset_selector' : null;
        $requestIpColumn = $has('reset_request_ip') ? 'reset_request_ip' : null;

        if ($userIdColumn === null || $tokenHashColumn === null || $expiresOnColumn === null || $usedOnColumn === null) {
            return [];
        }

        return [
            'user_id' => $userIdColumn,
            'selector' => $selectorColumn,
            'token_hash' => $tokenHashColumn,
            'expires_on' => $expiresOnColumn,
            'used_on' => $usedOnColumn,
            'request_ip' => $requestIpColumn,
        ];
    }

    private function getPasswordResetColumns(): array
    {
        try {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM password_reset_tokens');
            $columns = [];

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $field = (string) ($row['Field'] ?? '');
                if ($field !== '') {
                    $columns[] = $field;
                }
            }

            return $columns;
        } catch (Throwable $exception) {
            error_log('Unable to read password_reset_tokens columns: ' . $exception->getMessage());
            return [];
        }
    }
}
