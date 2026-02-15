<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/includes/auth_middleware.php';

if (($_SESSION['user_role'] ?? null) !== 1) {
    header('Location: dashboard.php');
    exit();
}

include __DIR__ . '/../src/includes/header.php';

$query = 'SELECT u.user_first_name, u.user_last_name, u.user_email, u.user_status, r.role_title FROM users u JOIN user_roles r ON u.user_role_id = r.role_id';
$users = $pdo->query($query)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><i class="bi bi-people"></i> Employee Management</h2>
  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addUserModal">
    <i class="bi bi-person-plus"></i>
    Add Employee
  </button>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th scope="col">Name</th>
          <th scope="col">Email</th>
          <th scope="col">Role</th>
          <th scope="col">Status</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
          <td><?php echo htmlspecialchars(trim($user['user_first_name'] . ' ' . $user['user_last_name']), ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($user['user_email'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <span class="badge bg-info text-dark">
              <?php echo htmlspecialchars($user['role_title'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
          </td>
          <td>
            <?php $isActive = ($user['user_status'] === 'Active'); ?>
            <span class="badge <?php echo $isActive ? 'bg-success' : 'bg-danger'; ?>">
              <?php echo htmlspecialchars($user['user_status'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-secondary" type="button" disabled>
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" type="button" disabled>
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
