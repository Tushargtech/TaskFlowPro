<?php

declare(strict_types=1);

$errorCode = $_GET['error'] ?? null;
$successCode = $_GET['success'] ?? null;

$errorMessage = null;
$successMessage = null;

if ($errorCode === 'invalid') {
    $errorMessage = 'Please provide a valid email address.';
} elseif ($errorCode === 'csrf') {
    $errorMessage = 'Session expired. Please try again.';
}

if ($successCode === 'sent') {
    $successMessage = 'If an active account exists for that email, a password reset link has been sent.';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskFlow Pro | Forgot Password</title>
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
                <h2 class="text-center mb-2">Forgot Password</h2>
                <p class="text-muted text-center mb-4">Enter your account email to receive a password reset link.</p>

                <?php if ($successMessage): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form action="<?php echo APP_BASE; ?>/forgot-password" method="post" novalidate>
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="mb-4">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                </form>

                <div class="text-center mt-3">
                  <a href="<?php echo APP_BASE; ?>/login">Back to login</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[action="<?php echo APP_BASE; ?>/forgot-password"]');
        if (!form) {
          return;
        }

        form.addEventListener('submit', function (event) {
          const emailInput = document.getElementById('email');
          const email = String(emailInput?.value || '').trim();
          const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

          if (!emailPattern.test(email)) {
            event.preventDefault();
            alert('Please enter a valid email address.');
            emailInput?.focus();
          }
        });
      });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
