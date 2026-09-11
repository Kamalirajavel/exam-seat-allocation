<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM staff WHERE username = ?');
$stmt->execute([$username]);
$staff = $stmt->fetch();

if ($staff && password_verify($password, $staff['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['staff_id'] = $staff['id'];
    $_SESSION['staff_name'] = $staff['full_name'];
    header('Location: dashboard.php');
    exit;
}

$_SESSION['login_error'] = 'Invalid username or password.';
header('Location: index.php');
exit;
