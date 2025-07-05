<?php
// Simple session management for VitaMeds
// Chỉ start session nếu chưa có, không thay đổi session name

function ensure_session_started() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Helper functions for user login check
function is_user_logged_in() {
    ensure_session_started();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function require_user_login() {
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function get_user_info() {
    ensure_session_started();
    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

function user_logout() {
    ensure_session_started();
    session_unset();
    session_destroy();
}

// Helper functions for admin login check  
function is_admin_logged_in() {
    ensure_session_started();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_logout() {
    ensure_session_started();
    session_unset();
    session_destroy();
}
?> 