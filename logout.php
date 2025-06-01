<?php
// logout.php - Xử lý đăng xuất thuần PHP
session_start();
include 'config/database.php';

// Xóa remember token khỏi database nếu có
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "UPDATE nguoi_dung SET remember_token = NULL WHERE ma_nguoi_dung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Xóa cookie remember token
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Xóa tất cả session
session_destroy();

// Chuyển về trang đăng nhập
header('Location: Login.php');
exit();
?>