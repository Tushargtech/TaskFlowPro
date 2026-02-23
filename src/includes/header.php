<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/functions.php';

$userName = $_SESSION['user_name'] ?? 'User';
$firstName = explode(' ', trim((string) $userName))[0] ?? 'User';
$userRole = $_SESSION['user_role'] ?? null;
$csrfToken = $_SESSION['csrf_token'] ?? '';
$basePath = defined('APP_BASE') ? APP_BASE : '';
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskFlow Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>/public/css/app.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <script>
      window.APP_CONFIG = {
        apiBase: '<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>/api',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      };

      window.apiRequest = async function apiRequest(resourcePath, options = {}) {
        const endpoint = window.APP_CONFIG.apiBase.replace(/\/$/, '') + '/' + String(resourcePath).replace(/^\//, '');
        const headers = Object.assign({
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.APP_CONFIG.csrfToken
        }, options.headers || {});

        const response = await fetch(endpoint, Object.assign({}, options, { headers }));
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
          const errorMessage = payload.message || 'Request failed.';
          throw new Error(errorMessage);
        }

        return payload;
      };
    </script>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
      <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>/dashboard">
          <i class="bi bi-speedometer2"></i>
          TaskFlow Pro
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <?php if ($userRole === 1): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>/users">Employees</a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>/projects">Projects</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>/tasks">Tasks</a>
            </li>
          </ul>
          <div class="navbar-nav">
            <form class="d-flex me-3" role="search">
              <input class="form-control form-control-sm" type="search" placeholder="Search..." aria-label="Search">
              <button class="btn btn-outline-light btn-sm ms-2" type="submit">
                <i class="bi bi-search"></i>
              </button>
            </form>
            <a class="nav-link btn btn-outline-danger btn-sm ms-lg-2 text-white" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>/logout">Logout</a>
          </div>
        </div>
      </div>
    </nav>
    <div class="container mt-4">
