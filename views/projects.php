<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/includes/auth_middleware.php';

include __DIR__ . '/../src/includes/header.php';

$projects = $pdo->query('SELECT project_title, project_description, project_status FROM projects')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><i class="bi bi-kanban"></i> Projects</h2>
  <button class="btn btn-success" type="button" disabled>
    <i class="bi bi-plus-lg"></i>
    New Project
  </button>
</div>

<div class="row">
  <?php foreach ($projects as $project): ?>
  <div class="col-md-4 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($project['project_title'], ENT_QUOTES, 'UTF-8'); ?></h5>
        <p class="card-text text-muted">
          <?php
          $description = $project['project_description'] ?? '';
          echo htmlspecialchars(mb_strimwidth($description, 0, 80, '...'), ENT_QUOTES, 'UTF-8');
          ?>
        </p>
        <span class="badge bg-secondary">
          <?php echo htmlspecialchars($project['project_status'], ENT_QUOTES, 'UTF-8'); ?>
        </span>
      </div>
      <div class="card-footer bg-transparent">
        <a href="#" class="btn btn-sm btn-link disabled">View Tasks</a>
        <button class="btn btn-sm btn-outline-primary float-end" type="button" disabled>Edit</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
