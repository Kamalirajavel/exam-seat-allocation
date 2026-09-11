<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Exam Seat Allocation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
  body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
  .login-card { max-width: 400px; width: 100%; }
  .login-icon { font-size: 2.5rem; color: var(--primary); }
</style>
</head>
<body>
  <div class="card login-card p-4">
    <div class="text-center mb-3">
      <i class="bi bi-grid-3x3-gap-fill login-icon"></i>
      <h4 class="mt-2 mb-0">Exam Seat Allocation</h4>
      <p class="text-muted small">Staff Login</p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <form action="login.php" method="post">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" class="form-control" name="username" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <p class="text-muted small mt-3 mb-0 text-center">Default: admin / admin123</p>
  </div>
</body>
</html>
