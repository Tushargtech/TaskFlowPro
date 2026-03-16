<?php

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login');
    exit();
}
