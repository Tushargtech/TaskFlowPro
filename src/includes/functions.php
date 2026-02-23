<?php

declare(strict_types=1);

function checkPermission(PDO $pdo, string $rightTitle): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    $sql = "SELECT m.access_status 
            FROM user_role_mapping m
            JOIN user_access_rights r ON m.access_right_id = r.right_id
            WHERE m.access_role_id = :role_id 
            AND r.right_title = :right_title 
            AND m.access_status = 'Yes'";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'role_id' => $_SESSION['user_role'],
            'right_title' => $rightTitle
        ]);

        return $stmt->fetch() ? true : false;
    } catch (PDOException $e) {
        error_log('Permission check error: ' . $e->getMessage());
        return false;
    }
}
