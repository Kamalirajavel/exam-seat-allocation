<?php
// =====================================================================
// Authentication helpers
// =====================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['staff_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function current_staff_name(): string {
    return $_SESSION['staff_name'] ?? 'Staff';
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
