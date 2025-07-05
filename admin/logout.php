<?php
include '../config/simple_session.php';

// Ensure session is started
ensure_session_started();

// Xóa admin session
admin_logout();

// Xóa cookie nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect về trang login
header('Location: login.php');
exit;
?> 