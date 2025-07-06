<?php
// includes/sidebar-admin.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-pills"></i> VitaMeds Admin</h3>
        <p><?php echo htmlspecialchars($_SESSION['admin_name'] ?? ''); ?></p>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> 
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
            <a href="products.php">
                <i class="fas fa-pills"></i> 
                <span>Quản lý sản phẩm</span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
            <a href="orders.php">
                <i class="fas fa-shopping-cart"></i> 
                <span>Quản lý đơn hàng</span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
            <a href="customers.php">
                <i class="fas fa-users"></i> 
                <span>Quản lý khách hàng</span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'revenue.php' ? 'active' : ''; ?>">
            <a href="revenue.php">
                <i class="fas fa-chart-line"></i> 
                <span>Thống kê doanh thu</span>
            </a>
        </li>

        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin'): ?>
        <li class="<?php echo $current_page === 'staffs.php' ? 'active' : ''; ?>">
            <a href="staffs.php">
                <i class="fas fa-user-tie"></i> 
                <span>Quản lý nhân viên</span>
            </a>
        </li>

        <?php endif; ?>
        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'nhan_vien'): ?>
        <li class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <a href="profile.php">
                <i class="fas fa-user"></i> 
                <span>Thông tin cá nhân</span>
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> 
                <span>Đăng xuất</span>
            </a>
        </li>
    </ul>
</nav>
<div class="sidebar-overlay" id="sidebarOverlay"></div> 