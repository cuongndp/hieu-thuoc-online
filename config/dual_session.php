<?php
// Dual Session System - Tách riêng session admin và user

function ensure_session_started() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ===== USER SESSION FUNCTIONS =====
function is_user_logged_in() {
    ensure_session_started();
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function get_user_info() {
    ensure_session_started();
    if (!is_user_logged_in()) return null;
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null
    ];
}

// Helper functions để lấy thông tin user dễ dàng
function get_user_id() {
    ensure_session_started();
    return $_SESSION['user_id'] ?? 0;
}

function get_user_name() {
    ensure_session_started();
    return $_SESSION['user_name'] ?? '';
}

function get_user_email() {
    ensure_session_started();
    return $_SESSION['user_email'] ?? '';
}

function user_login($user_id, $user_name, $user_email) {
    ensure_session_started();
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $user_name;
    $_SESSION['user_email'] = $user_email;
}

function user_logout() {
    ensure_session_started();
    // Chỉ xóa session user, giữ nguyên session admin
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
}

function require_user_login() {
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// ===== ADMIN SESSION FUNCTIONS =====
function is_admin_logged_in() {
    ensure_session_started();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function get_admin_info() {
    ensure_session_started();
    if (!is_admin_logged_in()) return null;
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'name' => $_SESSION['admin_name'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null,
        'role' => $_SESSION['admin_role'] ?? null
    ];
}

// Helper functions để lấy thông tin admin dễ dàng
function get_admin_id() {
    ensure_session_started();
    return $_SESSION['admin_id'] ?? 0;
}

function get_admin_name() {
    ensure_session_started();
    return $_SESSION['admin_name'] ?? '';
}

function get_admin_email() {
    ensure_session_started();
    return $_SESSION['admin_email'] ?? '';
}

function get_admin_role() {
    ensure_session_started();
    return $_SESSION['admin_role'] ?? '';
}

function admin_login($admin_id, $admin_name, $admin_email, $admin_role = 'admin') {
    ensure_session_started();
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_name'] = $admin_name;
    $_SESSION['admin_email'] = $admin_email;
    $_SESSION['admin_role'] = $admin_role;
}

function admin_logout() {
    ensure_session_started();
    // Chỉ xóa session admin, giữ nguyên session user
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_role']);
    unset($_SESSION['user_role']); // Xóa cả user_role nếu có
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// ===== UTILITY FUNCTIONS =====
function get_current_user_type() {
    ensure_session_started();
    if (is_admin_logged_in()) return 'admin';
    if (is_user_logged_in()) return 'user';
    return 'guest';
}

function debug_session_info() {
    ensure_session_started();
    echo "<!-- DEBUG SESSION INFO:\n";
    echo "User logged in: " . (is_user_logged_in() ? 'YES' : 'NO') . "\n";
    echo "Admin logged in: " . (is_admin_logged_in() ? 'YES' : 'NO') . "\n";
    echo "Session ID: " . session_id() . "\n";
    echo "All SESSION vars: " . print_r($_SESSION, true) . "\n";
    echo "-->\n";
}
?> 