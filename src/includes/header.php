<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_middleware.php';

$userName = $_SESSION['user_name'] ?? 'User';
$firstName = explode(' ', trim((string) $userName))[0] ?? 'User';
$userRole = $_SESSION['user_role'] ?? null;
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskFlow Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../public/css/app.css">
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
      <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
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
              <a class="nav-link" href="users.php">Employees</a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a class="nav-link" href="projects.php">Projects</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="tasks.php">Tasks</a>
            </li>
          </ul>
          <div class="navbar-nav">
            <form class="d-flex me-3" role="search">
              <input class="form-control form-control-sm" type="search" placeholder="Search..." aria-label="Search">
              <button class="btn btn-outline-light btn-sm ms-2" type="submit">
                <i class="bi bi-search"></i>
              </button>
            </form>
            <a class="nav-link btn btn-outline-danger btn-sm ms-lg-2 text-white" href="../src/auth/logout.php">Logout</a>
          </div>
        </div>
      </div>
    </nav>
    <div class="container mt-4">
