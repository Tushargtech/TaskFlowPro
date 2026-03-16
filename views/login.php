<?php

declare(strict_types=1);

$errorCode = $_GET['error'] ?? null;
$successCode = $_GET['success'] ?? null;
$errorMessage = null;
$successMessage = null;
$remainingSeconds = 0;

if ($errorCode === 'invalid') {
  $remainingAttempts = max(0, (int) ($_GET['remaining_attempts'] ?? 0));
  if ($remainingAttempts > 0) {
    $errorMessage = 'Invalid credentials. ' . $remainingAttempts . ' attempt(s) remaining before a 10-minute timeout.';
  } else {
    $errorMessage = 'Invalid credentials. Please try again.';
  }
} elseif ($errorCode === 'csrf') {
    $errorMessage = 'Session expired. Please try again.';
} elseif ($errorCode === 'locked') {
  $remainingSeconds = max(0, (int) ($_GET['remaining'] ?? 0));
  if ($remainingSeconds === 0) {
    $lockState = $_SESSION['login_limit'] ?? [];
    $lockedUntil = (int) ($lockState['locked_until'] ?? 0);
    $remainingSeconds = max(0, $lockedUntil - time());
  }

  if ($remainingSeconds > 0) {
    $minutesRemaining = (int) ceil($remainingSeconds / 60);
    $errorMessage = 'Too many failed login attempts. Please wait ' . $minutesRemaining . ' minute(s) before trying again.';
  } else {
    $errorMessage = 'Login timeout ended. Please try signing in again.';
  }
}

if ($errorMessage === null) {
  $lockState = $_SESSION['login_limit'] ?? [];
  if (is_array($lockState)) {
    $lockedUntil = (int) ($lockState['locked_until'] ?? 0);
    $remainingSeconds = max(0, $lockedUntil - time());
    if ($remainingSeconds > 0) {
      $minutesRemaining = (int) ceil($remainingSeconds / 60);
      $errorMessage = 'Too many failed login attempts. Please wait ' . $minutesRemaining . ' minute(s) before trying again.';
    }
  }
}

if ($successCode === 'password_reset') {
  $successMessage = 'Password reset successful. Please sign in with your new password.';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskFlow Pro | Login</title>
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
                <h2 class="text-center mb-4">TaskFlow Pro</h2>
                <?php if ($successMessage): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($errorMessage): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form action="<?php echo APP_BASE; ?>/login" method="post" novalidate>
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                  </div>
                  <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                  </div>
                  <div class="text-end mb-3">
                    <a href="<?php echo APP_BASE; ?>/forgot-password">Forgot password?</a>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Sign In</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[action="<?php echo APP_BASE; ?>/login"]');
        if (!form) {
          return;
        }

        form.addEventListener('submit', function (event) {
          const emailInput = form.querySelector('#email');
          const passwordInput = form.querySelector('#password');

          const email = String(emailInput?.value || '').trim();
          const password = String(passwordInput?.value || '');
          const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

          if (!emailPattern.test(email)) {
            event.preventDefault();
            alert('Please enter a valid email address.');
            emailInput?.focus();
            return;
          }

          if (password.trim().length < 6) {
            event.preventDefault();
            alert('Password must be at least 6 characters.');
            passwordInput?.focus();
          }
        });
      });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
