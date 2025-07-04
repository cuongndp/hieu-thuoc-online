<?php
// admin/includes/permissions.php - Hệ thống kiểm tra quyền đơn giản

// Định nghĩa quyền cho từng vai trò
$role_permissions = [
    'quan_tri' => [
        'dashboard_view',
        'products_view', 'products_add', 'products_edit', 'products_delete',
        'orders_view', 'orders_edit', 'orders_delete',
        'customers_view', 'customers_edit', 'customers_delete',
        'reviews_view', 'reviews_edit', 'reviews_delete',
        'admin_users_view', 'admin_users_add', 'admin_users_edit', 'admin_users_delete',
        'reports_view', 'settings_view', 'settings_edit'
    ],
    'nhan_vien' => [
        'dashboard_view',
        'products_view',
        'orders_view', 'orders_edit',
        'customers_view',
        'reviews_view'
    ],
    'quan_ly' => [
        'dashboard_view',
        'products_view', 'products_add', 'products_edit',
        'orders_view', 'orders_edit',
        'customers_view', 'customers_edit',
        'reviews_view', 'reviews_edit',
        'reports_view'
    ]
];

// Function kiểm tra quyền
function checkPermission($permission) {
    global $role_permissions;
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        return false;
    }
    
    // Lấy vai trò từ session hoặc database
    $user_role = $_SESSION['user_role'] ?? 'quan_tri';
    
    // Kiểm tra quyền
    if (isset($role_permissions[$user_role])) {
        return in_array($permission, $role_permissions[$user_role]);
    }
    
    return false;
}

// Function kiểm tra quyền và redirect nếu không có quyền
function requirePermission($permission, $redirect_url = 'dashboard.php') {
    if (!checkPermission($permission)) {
        $_SESSION['error_message'] = "Bạn không có quyền truy cập trang này!";
        header("Location: $redirect_url");
        exit;
    }
}

// Function hiển thị menu dựa trên quyền
function showMenuIf($permission) {
    return checkPermission($permission) ? 'style="display: block;"' : 'style="display: none;"';
}

// Function hiển thị nút dựa trên quyền
function showButtonIf($permission) {
    return checkPermission($permission) ? '' : 'style="display: none;"';
}

// Function kiểm tra có phải Super Admin không
function isSuperAdmin() {
    return checkPermission('admin_users_delete'); // Chỉ Super Admin mới có quyền xóa admin
}

// Function lấy danh sách quyền của user
function getUserPermissions() {
    global $role_permissions;
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        return [];
    }
    
    $user_role = $_SESSION['user_role'] ?? 'quan_tri';
    
    return $role_permissions[$user_role] ?? [];
}

// Function lấy tên vai trò
function getRoleName($role) {
    $role_names = [
        'quan_tri' => 'Quản Trị',
        'nhan_vien' => 'Nhân Viên',
        'quan_ly' => 'Quản Lý'
    ];
    
    return $role_names[$role] ?? $role;
}
?> 