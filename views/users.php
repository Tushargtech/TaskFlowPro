<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/classes/Constants.php';
require_once __DIR__ . '/../src/classes/User.php';

if (($_SESSION['user_role'] ?? null) !== Constants::ROLE_ADMIN) {
  header('Location: ' . APP_BASE . '/dashboard');
  exit();
}

$userObj = new User($pdo);

$perPage = 8;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$totalUsers = $userObj->getUserCount();
$totalPages = max(1, (int) ceil($totalUsers / $perPage));

if ($currentPage > $totalPages) {
  $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;
$users = $userObj->getUsersPaginated($perPage, $offset);

$startItem = $totalUsers > 0 ? ($offset + 1) : 0;
$endItem = $totalUsers > 0 ? ($offset + count($users)) : 0;

$pageParams = $_GET;
unset($pageParams['url']);
unset($pageParams['success'], $pageParams['error']);

$buildPageUrl = static function (int $page) use ($pageParams): string {
  $params = $pageParams;
  $params['page'] = $page;

  return APP_BASE . '/users?' . http_build_query($params);
};

$messages = [
  'success' => [
    'user_added'   => 'Employee added successfully. Login credentials have been emailed.',
    'user_updated' => 'Employee updated successfully.',
    'deactivated'  => 'Employee deactivated successfully.',
  ],
  'error' => [
    'empty_fields'     => 'Please fill in all required fields.',
    'username_exists'  => 'Username already exists. Please choose a different username.',
    'email_exists'     => 'Email already exists. Please choose a different email.',
    'super_admin_role' => 'Super admin role cannot be changed.',
    'mail_failed'      => 'Employee was not created because the welcome email could not be sent. Please verify the mail configuration and try again.',
    'failed'           => 'Operation failed. Please try again.',
    'creation_failed'  => 'Could not create employee. Please try again.',
    'update_failed'    => 'Could not update employee. Please review the details.',
    'invalid_user'     => 'Invalid employee selected.',
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
?>

<?php if ($alert): ?>
  <div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><i class="bi bi-people"></i> Employee Management</h2>
  <?php if (checkPermission($pdo, 'Create_User')): ?>
  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addUserModal">
    <i class="bi bi-person-plus"></i>
    Add Employee
  </button>
  <?php endif; ?>
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
        <?php if (empty($users)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted py-4">No employees found.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($users as $user): ?>
        <?php
          $userId = (int) ($user['user_id'] ?? 0);
          $userIdEsc = htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8');
          $firstName = htmlspecialchars($user['user_first_name'] ?? '', ENT_QUOTES, 'UTF-8');
          $lastName = htmlspecialchars($user['user_last_name'] ?? '', ENT_QUOTES, 'UTF-8');
          $fullName = trim($firstName . ' ' . $lastName);
          $userEmail = htmlspecialchars($user['user_email'] ?? '', ENT_QUOTES, 'UTF-8');
          $roleTitle = htmlspecialchars($user['role_title'] ?? '', ENT_QUOTES, 'UTF-8');
          $isActive = ($user['user_status'] === Constants::USER_STATUS_ACTIVE);
          $statusBadge = $isActive ? 'bg-success' : 'bg-danger';
          $isSuperAdmin = (strtolower($user['user_email'] ?? '') === 'admin@taskflow.com');

          ob_start();
          ?>
          <div class="modal fade" id="editUser<?php echo $userIdEsc; ?>" tabindex="-1" aria-labelledby="editUserLabel<?php echo $userIdEsc; ?>" aria-hidden="true">
            <div class="modal-dialog">
              <form class="modal-content user-update-form" data-user-id="<?php echo $userIdEsc; ?>">
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
                      <select id="edit_status_<?php echo $userIdEsc; ?>" name="status" class="form-select" <?php echo $isSuperAdmin ? 'disabled' : ''; ?>>
                        <option value="<?php echo Constants::USER_STATUS_ACTIVE; ?>" <?php echo $user['user_status'] === Constants::USER_STATUS_ACTIVE ? 'selected' : ''; ?>><?php echo Constants::USER_STATUS_ACTIVE; ?></option>
                        <option value="<?php echo Constants::USER_STATUS_INACTIVE; ?>" <?php echo $user['user_status'] === Constants::USER_STATUS_INACTIVE ? 'selected' : ''; ?>><?php echo Constants::USER_STATUS_INACTIVE; ?></option>
                      </select>
                      <?php if ($isSuperAdmin): ?>
                        <div class="form-text">Super admin status cannot be changed.</div>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="edit_role_<?php echo $userIdEsc; ?>">Role</label>
                      <select id="edit_role_<?php echo $userIdEsc; ?>" name="role_id" class="form-select" <?php echo $isSuperAdmin ? 'disabled' : ''; ?>>
                        <option value="<?php echo Constants::ROLE_ADMIN; ?>" <?php echo ((int) ($user['user_role_id'] ?? 0) === Constants::ROLE_ADMIN) ? 'selected' : ''; ?>><?php echo Constants::getRoleName(Constants::ROLE_ADMIN); ?></option>
                        <option value="<?php echo Constants::ROLE_USER; ?>" <?php echo ((int) ($user['user_role_id'] ?? 0) === Constants::ROLE_USER) ? 'selected' : ''; ?>><?php echo Constants::getRoleName(Constants::ROLE_USER); ?></option>
                      </select>
                      <?php if ($isSuperAdmin): ?>
                        <div class="form-text">Super admin role cannot be changed.</div>
                      <?php endif; ?>
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
            <?php if (checkPermission($pdo, 'Edit_User')): ?>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editUser<?php echo $userIdEsc; ?>">
              <i class="bi bi-pencil"></i>
            </button>
            <?php endif; ?>
            <?php if (checkPermission($pdo, 'Edit_User') && !$isSuperAdmin): ?>
            <button
              type="button"
              class="btn btn-sm btn-outline-danger user-deactivate-btn"
              data-user-id="<?php echo $userIdEsc; ?>"
            >
              <i class="bi bi-trash"></i>
            </button>
            <?php endif; ?>
          </td>
        </tr>

        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <p class="mb-0 text-muted small">
      Showing <?php echo htmlspecialchars((string) $startItem, ENT_QUOTES, 'UTF-8'); ?>
      to <?php echo htmlspecialchars((string) $endItem, ENT_QUOTES, 'UTF-8'); ?>
      of <?php echo htmlspecialchars((string) $totalUsers, ENT_QUOTES, 'UTF-8'); ?> employees
    </p>

    <?php if ($totalPages > 1): ?>
    <nav aria-label="Users pagination">
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo htmlspecialchars($buildPageUrl(max(1, $currentPage - 1)), ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
        </li>

        <?php
          $windowStart = max(1, $currentPage - 2);
          $windowEnd = min($totalPages, $currentPage + 2);
          for ($page = $windowStart; $page <= $windowEnd; $page++):
        ?>
        <li class="page-item <?php echo $page === $currentPage ? 'active' : ''; ?>">
          <a class="page-link" href="<?php echo htmlspecialchars($buildPageUrl($page), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $page, ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <?php endfor; ?>

        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo htmlspecialchars($buildPageUrl(min($totalPages, $currentPage + 1)), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="createUserForm" class="modal-content">
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
              <option value="<?php echo Constants::ROLE_ADMIN; ?>"><?php echo Constants::getRoleName(Constants::ROLE_ADMIN); ?></option>
              <option value="<?php echo Constants::ROLE_USER; ?>"><?php echo Constants::getRoleName(Constants::ROLE_USER); ?></option>
            </select>
          </div>
          <div class="col-12">
            <div class="alert alert-info mb-0">
              A secure temporary password will be generated automatically and sent to the employee email.
            </div>
          </div>
          <div class="col-12">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select">
              <option value="<?php echo Constants::USER_STATUS_ACTIVE; ?>"><?php echo Constants::USER_STATUS_ACTIVE; ?></option>
              <option value="<?php echo Constants::USER_STATUS_INACTIVE; ?>"><?php echo Constants::USER_STATUS_INACTIVE; ?></option>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
  const currentPage = <?php echo (int) $currentPage; ?>;
  const namePattern = /^[A-Za-z][A-Za-z\s'-]{1,49}$/;
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const usernamePattern = /^[A-Za-z0-9_]{3,30}$/;

  function redirectUsers(queryString) {
    window.location.href = '<?php echo APP_BASE; ?>/users?' + queryString + '&page=' + encodeURIComponent(String(currentPage));
  }

  function isValidCreatePayload(payload) {
    return namePattern.test(payload.first_name)
      && namePattern.test(payload.last_name)
      && emailPattern.test(payload.email)
      && usernamePattern.test(payload.login)
      && payload.role_id > 0;
  }

  function isValidUpdatePayload(payload) {
    return namePattern.test(payload.first_name)
      && namePattern.test(payload.last_name)
      && emailPattern.test(payload.email)
      && payload.role_id > 0;
  }

  const createUserForm = document.getElementById('createUserForm');
  if (createUserForm) {
    createUserForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      const formData = new FormData(createUserForm);
      const payload = {
        first_name: String(formData.get('first_name') || '').trim(),
        last_name: String(formData.get('last_name') || '').trim(),
        email: String(formData.get('email') || '').trim(),
        login: String(formData.get('login') || '').trim(),
        role_id: Number(formData.get('role_id') || 0),
        status: String(formData.get('status') || '<?php echo Constants::USER_STATUS_ACTIVE; ?>')
      };

      if (!isValidCreatePayload(payload)) {
        redirectUsers('error=empty_fields');
        return;
      }

      try {
        const response = await window.apiRequest('users', {
          method: 'POST',
          body: JSON.stringify(payload)
        });

        if (response.success) {
          redirectUsers('success=user_added');
          return;
        }

        if (response.message === 'email_exists') {
          redirectUsers('error=email_exists');
          return;
        }

        if (response.message === 'username_exists') {
          redirectUsers('error=username_exists');
          return;
        }

        redirectUsers('error=creation_failed');
      } catch (error) {
        if (error.message === 'email_exists') {
          redirectUsers('error=email_exists');
          return;
        }
        if (error.message === 'username_exists') {
          redirectUsers('error=username_exists');
          return;
        }
        if (error.message === 'mail_failed') {
          redirectUsers('error=mail_failed');
          return;
        }
        redirectUsers('error=failed');
      }
    });
  }

  document.querySelectorAll('.user-update-form').forEach(function (updateForm) {
    updateForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      const userId = Number(updateForm.getAttribute('data-user-id') || 0);
      const formData = new FormData(updateForm);
      const payload = {
        first_name: String(formData.get('first_name') || '').trim(),
        last_name: String(formData.get('last_name') || '').trim(),
        email: String(formData.get('email') || '').trim(),
        role_id: Number(formData.get('role_id') || 0),
        status: String(formData.get('status') || '<?php echo Constants::USER_STATUS_ACTIVE; ?>')
      };

      if (userId <= 0 || !isValidUpdatePayload(payload)) {
        redirectUsers('error=empty_fields');
        return;
      }

      try {
        const response = await window.apiRequest('users/' + userId, {
          method: 'PUT',
          body: JSON.stringify(payload)
        });

        if (response.success) {
          redirectUsers('success=user_updated');
          return;
        }

        if (response.message === 'email_exists') {
          redirectUsers('error=email_exists');
          return;
        }

        if (response.message === 'super_admin_role') {
          redirectUsers('error=super_admin_role');
          return;
        }

        redirectUsers('error=update_failed');
      } catch (error) {
        if (error.message === 'email_exists') {
          redirectUsers('error=email_exists');
          return;
        }
        if (error.message === 'super_admin_role') {
          redirectUsers('error=super_admin_role');
          return;
        }
        redirectUsers('error=failed');
      }
    });
  });

  document.querySelectorAll('.user-deactivate-btn').forEach(function (deactivateBtn) {
    deactivateBtn.addEventListener('click', async function () {
      const userId = Number(deactivateBtn.getAttribute('data-user-id') || 0);
      if (userId <= 0) {
        redirectUsers('error=invalid_user');
        return;
      }

      if (!window.confirm('Are you sure you want to deactivate this employee?')) {
        return;
      }

      try {
        const response = await window.apiRequest('users/' + userId, {
          method: 'DELETE'
        });

        if (response.success) {
          redirectUsers('success=deactivated');
          return;
        }

        redirectUsers('error=failed');
      } catch (error) {
        redirectUsers('error=failed');
      }
    });
  });
});
</script>
