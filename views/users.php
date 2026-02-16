<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/includes/auth_middleware.php';
require_once __DIR__ . '/../src/classes/User.php';

if (($_SESSION['user_role'] ?? null) !== 1) {
  header('Location: dashboard.php');
  exit();
}

$userObj = new User($pdo);
$users = $userObj->getAllUsers();

include __DIR__ . '/../src/includes/header.php';
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

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="../src/processes/user_create.php" method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">Add New Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="login">Username</label>
            <input type="text" id="login" name="login" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="role_id">Role</label>
            <select id="role_id" name="role_id" class="form-select">
              <option value="1">Admin</option>
              <option value="2">User</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Employee</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
