<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/includes/auth_middleware.php';

include __DIR__ . '/../src/includes/header.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? null;
$isAdmin = ($userRole === 1);

$baseQuery = 'SELECT t.task_title, t.task_description, t.task_due_date, t.task_status, p.project_title
    FROM tasks t
    LEFT JOIN projects p ON p.project_id = t.task_project_id';

if ($isAdmin) {
  $stmt = $pdo->query($baseQuery);
  $tasks = $stmt->fetchAll();
} else {
  $sql = $baseQuery . ' WHERE t.task_assigned_to = :userId';
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['userId' => $userId]);
  $tasks = $stmt->fetchAll();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><i class="bi bi-list-task"></i> My Tasks</h2>
  <button class="btn btn-secondary" type="button" disabled>
    <i class="bi bi-plus-square"></i>
    Add Task
  </button>
</div>

<?php if (empty($tasks)): ?>
  <div class="alert alert-info" role="alert">
    No tasks assigned yet. Enjoy the calm!
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($tasks as $task): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title"><?php echo htmlspecialchars($task['task_title'], ENT_QUOTES, 'UTF-8'); ?></h5>
          <h6 class="card-subtitle mb-2 text-muted">
            <?php echo htmlspecialchars($task['project_title'] ?? 'Unassigned Project', ENT_QUOTES, 'UTF-8'); ?>
          </h6>
          <p class="card-text">
            <?php
            $description = $task['task_description'] ?? '';
            echo htmlspecialchars(mb_strimwidth($description, 0, 120, '...'), ENT_QUOTES, 'UTF-8');
            ?>
          </p>
        </div>
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
          <span class="badge <?php echo $task['task_status'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark'; ?>">
            <?php echo htmlspecialchars($task['task_status'], ENT_QUOTES, 'UTF-8'); ?>
          </span>
          <small class="text-muted">
            Due: <?php echo htmlspecialchars($task['task_due_date'] ?? 'No due date', ENT_QUOTES, 'UTF-8'); ?>
          </small>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
