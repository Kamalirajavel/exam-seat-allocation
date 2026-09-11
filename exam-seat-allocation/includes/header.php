<?php
require_once __DIR__ . '/auth.php';
require_login();
$currentPage = basename($_SERVER['PHP_SELF']);
function navClass(string $page, string $current): string {
    return $page === $current ? 'nav-link active' : 'nav-link';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?>Exam Seat Allocation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Exam Seat Allocation</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="<?= navClass('dashboard.php', $currentPage) ?>" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="<?= navClass('departments.php', $currentPage) ?>" href="departments.php">Departments</a></li>
        <li class="nav-item"><a class="<?= navClass('students.php', $currentPage) ?>" href="students.php">Students</a></li>
        <li class="nav-item"><a class="<?= navClass('halls.php', $currentPage) ?>" href="halls.php">Halls</a></li>
        <li class="nav-item"><a class="<?= navClass('exams.php', $currentPage) ?>" href="exams.php">Exams</a></li>
        <li class="nav-item"><a class="<?= navClass('allocate.php', $currentPage) ?>" href="allocate.php">Allocate Seats</a></li>
        <li class="nav-item"><a class="<?= navClass('view_allocation.php', $currentPage) ?>" href="view_allocation.php">View / Reports</a></li>
      </ul>
      <span class="navbar-text text-light me-3"><i class="bi bi-person-circle me-1"></i><?= h(current_staff_name()) ?></span>
      <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
  </div>
</nav>
<div class="container-fluid px-4 py-4">
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-<?= h($_SESSION['flash']['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($_SESSION['flash']['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
