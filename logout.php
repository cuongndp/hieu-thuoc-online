<?php
// logout.php - Xử lý đăng xuất thuần PHP
include 'config/dual_session.php';

// Ensure session is started
ensure_session_started();

// Xóa cookie remember token nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Xóa user session
user_logout();

// Chuyển về trang đăng nhập với thông báo đăng xuất thành công
header('Location: login.php?message=logout_success');
exit();
?>