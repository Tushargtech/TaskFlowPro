<?php

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login');
    exit;
}

$errorCode = $_GET['error'] ?? null;
$errorMessage = null;

if ($errorCode === 'invalid') {
    $errorMessage = 'Please provide a valid password (minimum 8 characters).';
} elseif ($errorCode === 'mismatch') {
    $errorMessage = 'New password and confirm password do not match.';
} elseif ($errorCode === 'csrf') {
    $errorMessage = 'Session expired. Please try again.';
} elseif ($errorCode === 'failed') {
    $errorMessage = 'Unable to update password. Please try again.';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskFlow Pro | Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_BASE; ?>/public/css/app.css">
    <style>
      body { background: #f8f9fa; }
    </style>
  </head>
  <body>
    <main class="d-flex align-items-center" style="min-height: 100vh;">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow border-0">
              <div class="card-body p-4 p-sm-5">
                <h2 class="text-center mb-2">Change Password</h2>
                <p class="text-muted text-center mb-4">You must change your temporary password before continuing.</p>

                <?php if ($errorMessage): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form action="<?php echo APP_BASE; ?>/change-password" method="post" novalidate>
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                  </div>
                  <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[action="<?php echo APP_BASE; ?>/change-password"]');
        if (!form) {
          return;
        }

        form.addEventListener('submit', function (event) {
          const newPasswordInput = document.getElementById('new_password');
          const confirmPasswordInput = document.getElementById('confirm_password');

          const newPassword = String(newPasswordInput?.value || '');
          const confirmPassword = String(confirmPasswordInput?.value || '');

          if (newPassword.length < 8) {
            event.preventDefault();
            alert('Password must be at least 8 characters long.');
            newPasswordInput?.focus();
            return;
          }

          if (newPassword !== confirmPassword) {
            event.preventDefault();
            alert('New password and confirm password must match.');
            confirmPasswordInput?.focus();
          }
        });
      });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
