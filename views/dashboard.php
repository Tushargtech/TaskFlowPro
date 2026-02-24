<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/classes/Constants.php';

$isAdmin = (int) ($_SESSION['user_role'] ?? 0) === 1;
$userCount = $isAdmin ? (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() : 0;
$projectCount = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
$taskCount = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE task_status = '" . Constants::TASK_STATUS_DUE . "'")->fetchColumn();
$cardCol = $isAdmin ? 'col-md-4' : 'col-md-6';
?>

      <div class="row g-4 text-center">
        <?php if ($isAdmin): ?>
        <div class="<?php echo $cardCol; ?>">
          <div class="card bg-primary text-white h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title">Total Employees</h5>
              <p class="display-5 fw-semibold mb-0"><?php echo $userCount; ?></p>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <div class="<?php echo $cardCol; ?>">
          <div class="card bg-success text-white h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title">Active Projects</h5>
              <p class="display-5 fw-semibold mb-0"><?php echo $projectCount; ?></p>
            </div>
          </div>
        </div>
        <div class="<?php echo $cardCol; ?>">
          <div class="card bg-warning text-dark h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title">Pending Tasks</h5>
              <p class="display-5 fw-semibold mb-0"><?php echo $taskCount; ?></p>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-5">
        <div class="col-12">
          <div class="list-group shadow-sm">
            <?php if (($_SESSION['user_role'] ?? null) === 1): ?>
            <a href="<?php echo APP_BASE; ?>/users" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="bi bi-people-fill me-2"></i>
              Manage Employees
            </a>
            <?php endif; ?>
            <a href="<?php echo APP_BASE; ?>/projects" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="bi bi-kanban me-2"></i>
              Manage Projects
            </a>
            <a href="<?php echo APP_BASE; ?>/tasks" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="bi bi-list-task me-2"></i>
              My Tasks
            </a>
          </div>
        </div>
      </div>
