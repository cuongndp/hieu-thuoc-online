<?php
// logout.php - Xử lý đăng xuất thuần PHP
session_start();

// Xóa cookie remember token nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Xóa tất cả session
session_unset();
session_destroy();

// Chuyển về trang đăng nhập với thông báo đăng xuất thành công
header('Location: Login.php?message=logout_success');
exit();
?>