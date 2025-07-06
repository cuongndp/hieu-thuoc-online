<?php
// includes/admin-header.php
$page_title = $page_title ?? 'Admin Panel';
$page_icon = $page_icon ?? 'fas fa-cogs';
?>
<div class="main-header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-title">
            <h1><i class="<?php echo $page_icon; ?>"></i> <?php echo htmlspecialchars($page_title); ?></h1>
            <div class="page-breadcrumb">
                <i class="fas fa-home"></i>
                <span>Admin / <?php echo htmlspecialchars($page_title); ?></span>
            </div>
        </div>
    </div>
    <div class="header-right">
        <div class="header-user">
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <small><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Administrator'); ?></small>
            </div>
            <div class="user-menu">
                <button class="user-menu-btn" id="userMenuBtn">
                    <i class="fas fa-user-circle"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
        </div>
    </div>
</div>



<style>
/* Additional responsive styles for header */
.header-title {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.page-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #7f8c8d;
    font-weight: 400;
}

.page-breadcrumb i {
    font-size: 12px;
    color: #3498db;
}



.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.admin-wrapper.sidebar-open .sidebar-overlay {
    opacity: 1;
    visibility: visible;
}

.sidebar-toggle {
    z-index: 1002 !important;
    position: relative !important;
    cursor: pointer !important;
    font-size: 18px !important;
    background: none !important;
    border: none !important;
    padding: 8px !important;
    color: #7f8c8d !important;
    border-radius: 4px !important;
    transition: all 0.3s ease !important;
}

.sidebar-toggle:hover {
    background: #ecf0f1 !important;
    color: #2c3e50 !important;
}

/* Responsive header styles */
@media (max-width: 991px) {
    .sidebar-toggle {
        display: block !important;
    }
}

@media (min-width: 992px) {
    .sidebar-toggle {
        display: none !important;
    }
}

/* Mobile responsive */
@media (max-width: 767px) {
    .header-title {
        gap: 2px;
    }
    
    .page-breadcrumb {
        display: none;
    }
    

    
    .user-dropdown {
        right: -20px;
    }
}

@media (max-width: 575px) {
    .header-right {
        justify-content: flex-end;
    }
}
</style> 