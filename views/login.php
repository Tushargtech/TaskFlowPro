<?php

declare(strict_types=1);

$errorCode = $_GET['error'] ?? null;
$errorMessage = null;
if ($errorCode === 'invalid') {
    $errorMessage = 'Invalid credentials. Please try again.';
} elseif ($errorCode === 'csrf') {
    $errorMessage = 'Session expired. Please try again.';
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
                  <button type="submit" class="btn btn-primary w-100">Sign In</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
