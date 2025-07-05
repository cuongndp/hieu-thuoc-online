<?php
// Test script để kiểm tra tất cả trang admin
echo "<h2>Test Trang Admin - VitaMeds</h2>";

$admin_pages = [
    'admin/index.php' => 'Trang chủ admin',
    'admin/login.php' => 'Đăng nhập admin',
    'admin/dashboard.php' => 'Dashboard (cần login)',
    'admin/products.php' => 'Quản lý sản phẩm (cần login)',
    'admin/orders.php' => 'Quản lý đơn hàng (cần login)',
    'admin/customers.php' => 'Quản lý khách hàng (cần login)',
    'admin/customer_detail.php' => 'Chi tiết khách hàng (cần login)',
    'admin/reviews.php' => 'Quản lý đánh giá (cần login)',
    'admin/staffs.php' => 'Quản lý nhân viên (cần login)',
    'admin/revenue.php' => 'Thống kê doanh thu (cần login)',
    'admin/logout.php' => 'Đăng xuất admin'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Trang</th><th>Mô tả</th><th>Trạng thái</th></tr>";

foreach ($admin_pages as $page => $description) {
    echo "<tr>";
    echo "<td><a href='$page' target='_blank'>$page</a></td>";
    echo "<td>$description</td>";
    
    // Kiểm tra file có tồn tại không
    if (file_exists($page)) {
        echo "<td style='color: green;'>✅ File tồn tại</td>";
    } else {
        echo "<td style='color: red;'>❌ File không tồn tại</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

echo "<h3>Hướng dẫn test:</h3>";
echo "<ol>";
echo "<li><strong>Test login:</strong> Truy cập admin/login.php để đăng nhập</li>";
echo "<li><strong>Test sau khi login:</strong> Test tất cả các trang cần login</li>";
echo "<li><strong>Kiểm tra lỗi:</strong> Nếu có lỗi Fatal Error về session hoặc function, báo ngay</li>";
echo "</ol>";

echo "<h3>Session hiện tại:</h3>";
if (!isset($_SESSION)) {
    include 'config/simple_session.php';
    ensure_session_started();
}

echo "<pre>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : "INACTIVE") . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Admin Logged In: " . (is_admin_logged_in() ? "YES" : "NO") . "\n";
if (is_admin_logged_in()) {
    echo "Admin ID: " . ($_SESSION['admin_id'] ?? 'N/A') . "\n";
    echo "Admin Name: " . ($_SESSION['admin_name'] ?? 'N/A') . "\n";
}
echo "</pre>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th, td { padding: 10px; text-align: left; }
    th { background: #f0f0f0; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .error { color: red; font-weight: bold; }
    .success { color: green; font-weight: bold; }
</style> 