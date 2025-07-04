<?php
// includes/sidebar-admin.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-pills"></i> VitaMeds Admin</h3>
        <p><?php echo htmlspecialchars($_SESSION['admin_name'] ?? ''); ?></p>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
            <a href="products.php"><i class="fas fa-pills"></i> Quản lý sản phẩm</a>
        </li>
        <li class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Quản lý đơn hàng</a>
        </li>
        <li class="<?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
            <a href="customers.php"><i class="fas fa-users"></i> Quản lý khách hàng</a>
        </li>
        <li class="<?php echo $current_page === 'revenue.php' ? 'active' : ''; ?>">
            <a href="revenue.php"><i class="fas fa-chart-line"></i> Thống kê doanh thu</a>
        </li>
        <li>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </li>
    </ul>
</nav> 