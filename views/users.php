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

$messages = [
  'success' => [
    'user_added' => 'Employee added successfully.',
    'user_updated' => 'Employee updated successfully.',
    'deactivated' => 'Employee deactivated successfully.',
  ],
  'error' => [
    'empty_fields' => 'Please fill in all required fields.',
    'failed' => 'Operation failed. Please try again.',
    'creation_failed' => 'Could not create employee. Possibly duplicate email or username.',
    'update_failed' => 'Could not update employee. Please review the details.',
    'invalid_user' => 'Invalid employee selected.',
    'missing_parameters' => 'Required details were missing for that action.',
  ],
];

$alert = null;
foreach (['success', 'error'] as $type) {
  if (isset($_GET[$type])) {
    $code = $_GET[$type];
    if (isset($messages[$type][$code])) {
      $alert = ['type' => $type, 'message' => $messages[$type][$code]];
    }
    break;
  }
}

$editModals = [];

include __DIR__ . '/../src/includes/header.php';
?>

<?php if ($alert): ?>
  <div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

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
        <?php
          $userId = (int) ($user['user_id'] ?? 0);
          $userIdEsc = htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8');
          $firstName = htmlspecialchars($user['user_first_name'] ?? '', ENT_QUOTES, 'UTF-8');
          $lastName = htmlspecialchars($user['user_last_name'] ?? '', ENT_QUOTES, 'UTF-8');
          $fullName = trim($firstName . ' ' . $lastName);
          $userEmail = htmlspecialchars($user['user_email'] ?? '', ENT_QUOTES, 'UTF-8');
          $roleTitle = htmlspecialchars($user['role_title'] ?? '', ENT_QUOTES, 'UTF-8');
          $isActive = ($user['user_status'] === 'Active');
          $statusBadge = $isActive ? 'bg-success' : 'bg-danger';

          ob_start();
          ?>
          <div class="modal fade" id="editUser<?php echo $userIdEsc; ?>" tabindex="-1" aria-labelledby="editUserLabel<?php echo $userIdEsc; ?>" aria-hidden="true">
            <div class="modal-dialog">
              <form action="../src/processes/user_update.php" method="post" class="modal-content">
                <input type="hidden" name="user_id" value="<?php echo $userIdEsc; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="modal-header">
                  <h5 class="modal-title" id="editUserLabel<?php echo $userIdEsc; ?>">Edit Employee: <?php echo $firstName; ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label" for="edit_first_name_<?php echo $userIdEsc; ?>">First Name</label>
                      <input type="text" id="edit_first_name_<?php echo $userIdEsc; ?>" name="first_name" class="form-control" value="<?php echo $firstName; ?>" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="edit_last_name_<?php echo $userIdEsc; ?>">Last Name</label>
                      <input type="text" id="edit_last_name_<?php echo $userIdEsc; ?>" name="last_name" class="form-control" value="<?php echo $lastName; ?>" required>
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="edit_email_<?php echo $userIdEsc; ?>">Email</label>
                      <input type="email" id="edit_email_<?php echo $userIdEsc; ?>" name="email" class="form-control" value="<?php echo $userEmail; ?>" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="edit_status_<?php echo $userIdEsc; ?>">Status</label>
                      <select id="edit_status_<?php echo $userIdEsc; ?>" name="status" class="form-select">
                        <option value="Active" <?php echo $user['user_status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $user['user_status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="edit_role_<?php echo $userIdEsc; ?>">Role</label>
                      <select id="edit_role_<?php echo $userIdEsc; ?>" name="role_id" class="form-select">
                        <option value="1" <?php echo ((int) ($user['user_role_id'] ?? 0) === 1) ? 'selected' : ''; ?>>Admin</option>
                        <option value="2" <?php echo ((int) ($user['user_role_id'] ?? 0) === 2) ? 'selected' : ''; ?>>User</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary">Update Changes</button>
                </div>
              </form>
            </div>
          </div>
          <?php
          $editModals[] = ob_get_clean();
        ?>
        <tr>
          <td><?php echo $fullName; ?></td>
          <td><?php echo $userEmail; ?></td>
          <td>
            <span class="badge bg-info text-dark">
              <?php echo $roleTitle; ?>
            </span>
          </td>
          <td>
            <span class="badge <?php echo $statusBadge; ?>">
              <?php echo htmlspecialchars($user['user_status'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editUser<?php echo $userIdEsc; ?>">
              <i class="bi bi-pencil"></i>
            </button>
            <a
              href="../src/processes/user_deactivate.php?id=<?php echo $userIdEsc; ?>"
              class="btn btn-sm btn-outline-danger"
              onclick="return confirm('Are you sure you want to deactivate this employee?');"
            >
              <i class="bi bi-trash"></i>
            </a>
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
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
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

  <?php
  foreach ($editModals as $modalHtml) {
    echo $modalHtml;
  }
  ?>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
