<?php
include '../config/dual_session.php';
include '../config/database.php';
include 'includes/permissions.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập admin
require_admin_login();

// Kiểm tra quyền xem dashboard
requirePermission('dashboard_view');

// Thêm chọn tháng/năm
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Lấy thống kê tổng quan
// 1. Tổng doanh thu tháng này (chỉ đơn đã giao)
$revenue_sql = "SELECT 
    SUM(tong_tien_thanh_toan) as doanh_thu_thuc,
    COUNT(*) as don_thanh_cong
    FROM don_hang 
    WHERE trang_thai_don_hang = 'da_giao'
    AND MONTH(ngay_tao) = ? 
    AND YEAR(ngay_tao) = ?";
$revenue_stmt = $conn->prepare($revenue_sql);
$revenue_stmt->bind_param('ii', $month, $year);
$revenue_stmt->execute();
$revenue_data = $revenue_stmt->get_result()->fetch_assoc();

// Tổng đơn hàng/thành công/chờ xử lý trong tháng
$order_stats_sql = "SELECT 
    COUNT(*) as tong_don_hang,
    SUM(CASE WHEN trang_thai_don_hang = 'da_giao' THEN 1 ELSE 0 END) as don_thanh_cong,
    SUM(CASE WHEN trang_thai_don_hang = 'cho_xac_nhan' THEN 1 ELSE 0 END) as don_cho_xu_ly
    FROM don_hang 
    WHERE MONTH(ngay_tao) = ? 
    AND YEAR(ngay_tao) = ?";
$order_stats_stmt = $conn->prepare($order_stats_sql);
$order_stats_stmt->bind_param('ii', $month, $year);
$order_stats_stmt->execute();
$order_stats = $order_stats_stmt->get_result()->fetch_assoc();

// 2. Tổng sản phẩm
$product_stats_sql = "SELECT 
    COUNT(*) as tong_san_pham,
    SUM(CASE WHEN so_luong_ton_kho <= muc_ton_kho_toi_thieu THEN 1 ELSE 0 END) as sp_sap_het,
    SUM(CASE WHEN so_luong_ton_kho = 0 THEN 1 ELSE 0 END) as sp_het_hang
    FROM san_pham_thuoc 
    WHERE trang_thai_hoat_dong = 1";
$product_stats = $conn->query($product_stats_sql)->fetch_assoc();

// 3. Tổng khách hàng
$customer_stats_sql = "SELECT 
    COUNT(*) as tong_khach_hang,
    SUM(CASE WHEN DATE(ngay_tao) = CURRENT_DATE() THEN 1 ELSE 0 END) as khach_moi_hom_nay
    FROM nguoi_dung 
    WHERE vai_tro = 'khach_hang'";
$customer_stats = $conn->query($customer_stats_sql)->fetch_assoc();

// 4. Đơn hàng mới nhất
$recent_orders_sql = "SELECT 
    dh.*, 
    nd.ho_ten as ten_khach_hang,
    nd.so_dien_thoai
    FROM don_hang dh
    JOIN nguoi_dung nd ON dh.ma_nguoi_dung = nd.ma_nguoi_dung
    ORDER BY dh.ngay_tao DESC
    LIMIT 10";
$recent_orders = $conn->query($recent_orders_sql);

// 5. Sản phẩm bán chạy
$top_products_sql = "SELECT 
    sp.ten_san_pham,
    sp.gia_ban,
    SUM(ctdh.so_luong) as tong_ban,
    SUM(ctdh.thanh_tien) as doanh_thu
    FROM chi_tiet_don_hang ctdh
    JOIN san_pham_thuoc sp ON ctdh.ma_san_pham = sp.ma_san_pham
    JOIN don_hang dh ON ctdh.ma_don_hang = dh.ma_don_hang
    WHERE MONTH(dh.ngay_tao) = MONTH(CURRENT_DATE())
    GROUP BY sp.ma_san_pham
    ORDER BY tong_ban DESC
    LIMIT 5";
$top_products = $conn->query($top_products_sql);

// 6. Sản phẩm sắp hết hàng
$low_stock_sql = "SELECT 
    ten_san_pham,
    so_luong_ton_kho,
    muc_ton_kho_toi_thieu
    FROM san_pham_thuoc
    WHERE so_luong_ton_kho <= muc_ton_kho_toi_thieu
    AND trang_thai_hoat_dong = 1
    ORDER BY so_luong_ton_kho ASC
    LIMIT 10";
$low_stock_products = $conn->query($low_stock_sql);

// 7. Thống kê theo danh mục
$category_stats_sql = "SELECT 
    dm.ten_danh_muc,
    COUNT(sp.ma_san_pham) as so_san_pham,
    SUM(sp.so_luong_ton_kho * sp.gia_ban) as gia_tri_ton_kho
    FROM danh_muc_thuoc dm
    LEFT JOIN san_pham_thuoc sp ON dm.ma_danh_muc = sp.ma_danh_muc
    WHERE dm.trang_thai_hoat_dong = 1
    GROUP BY dm.ma_danh_muc
    ORDER BY so_san_pham DESC";
$category_stats = $conn->query($category_stats_sql);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị - VitaMeds</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
        }

        .sidebar-header {
            background: #34495e;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #243342;
        }

        .sidebar-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            border-bottom: 1px solid #34495e;
        }

        .sidebar-menu a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            transition: background 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #34495e;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2c3e50;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-card .info {
            color: #95a5a6;
            font-size: 13px;
        }

        .stat-card.primary {
            background: #3498db;
            color: white;
        }

        .stat-card.success {
            background: #27ae60;
            color: white;
        }

        .stat-card.warning {
            background: #f39c12;
            color: white;
        }

        .stat-card.danger {
            background: #e74c3c;
            color: white;
        }

        .stat-card.primary h3,
        .stat-card.success h3,
        .stat-card.warning h3,
        .stat-card.danger h3 {
            color: rgba(255,255,255,0.9);
        }

        .stat-card.primary .number,
        .stat-card.success .number,
        .stat-card.warning .number,
        .stat-card.danger .number {
            color: white;
        }

        /* Tables */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            color: #2c3e50;
        }

        .card-body {
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }

        table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        /* Status badges */
        .badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>VitaMeds</h2>
                <p>Hệ thống quản trị</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li <?php echo showMenuIf('orders_view'); ?>><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn Hàng</a></li>
                <li <?php echo showMenuIf('products_view'); ?>><a href="products.php"><i class="fas fa-pills"></i> Sản Phẩm</a></li>
                <li <?php echo showMenuIf('customers_view'); ?>><a href="customers.php"><i class="fas fa-users"></i> Khách Hàng</a></li>
                <li <?php echo showMenuIf('reviews_view'); ?>><a href="reviews.php"><i class="fas fa-star"></i> Đánh Giá</a></li>
                <li <?php echo showMenuIf('admin_users_view'); ?>><a href="admin_users.php"><i class="fas fa-user-shield"></i> Quản Lý Admin</a></li>
                <li <?php echo showMenuIf('reports_view'); ?>><a href="reports.php"><i class="fas fa-chart-bar"></i> Báo Cáo</a></li>
                <li <?php echo showMenuIf('settings_view'); ?>><a href="settings.php"><i class="fas fa-cog"></i> Cài Đặt</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Bảng điều khiển';
            $page_icon = 'fas fa-tachometer-alt';
            include '../includes/admin-header.php'; 
            ?>
            
            <div class="dashboard-content">

            <!-- Alerts -->
            <?php if ($product_stats['sp_sap_het'] > 0): ?>
            <div class="alert alert-warning">
                <strong>Cảnh báo:</strong> Có <?php echo $product_stats['sp_sap_het']; ?> sản phẩm sắp hết hàng cần nhập thêm!
            </div>
            <?php endif; ?>

            <!-- Thêm form chọn tháng/năm -->
            <form method="GET" style="margin-bottom:20px;">
                <label>Tháng:
                    <select name="month">
                        <?php for($m=1;$m<=12;$m++): ?>
                            <option value="<?php echo $m; ?>" <?php if($m==$month) echo 'selected'; ?>><?php echo $m; ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Năm:
                    <select name="year">
                        <?php for($y=date('Y')-3;$y<=date('Y');$y++): ?>
                            <option value="<?php echo $y; ?>" <?php if($y==$year) echo 'selected'; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <button type="submit">Xem thống kê</button>
            </form>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <h3>Doanh thu tháng <?php echo $month; ?>/<?php echo $year; ?></h3>
                    <div class="number"><?php echo number_format($revenue_data['doanh_thu_thuc'] ?? 0); ?>đ</div>
                    <div class="info"><?php echo $revenue_data['don_thanh_cong'] ?? 0; ?> đơn đã giao</div>
                </div>

                <div class="stat-card success">
                    <h3>Tổng đơn hàng</h3>
                    <div class="number"><?php echo $order_stats['tong_don_hang'] ?? 0; ?></div>
                    <div class="info"><?php echo $order_stats['don_cho_xu_ly'] ?? 0; ?> đơn chờ xử lý</div>
                </div>

                <div class="stat-card warning">
                    <h3>Sản phẩm</h3>
                    <div class="number"><?php echo $product_stats['tong_san_pham']; ?></div>
                    <div class="info"><?php echo $product_stats['sp_het_hang']; ?> hết hàng</div>
                </div>

                <div class="stat-card danger">
                    <h3>Khách hàng</h3>
                    <div class="number"><?php echo $customer_stats['tong_khach_hang']; ?></div>
                    <div class="info">+<?php echo $customer_stats['khach_moi_hom_nay']; ?> hôm nay</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">Đơn hàng mới nhất</div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo substr($order['so_don_hang'], -8); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['ten_khach_hang']); ?><br>
                                        <small><?php echo $order['so_dien_thoai']; ?></small>
                                    </td>
                                    <td><strong><?php echo number_format($order['tong_tien_thanh_toan']); ?>đ</strong></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($order['trang_thai_don_hang']) {
                                            case 'cho_xac_nhan':
                                                $status_class = 'warning';
                                                $status_text = 'Chờ xác nhận';
                                                break;
                                            case 'da_xac_nhan':
                                            case 'dang_xu_ly':
                                            case 'dang_giao':
                                                $status_class = 'info';
                                                $status_text = 'Đang xử lý';
                                                break;
                                            case 'da_giao':
                                                $status_class = 'success';
                                                $status_text = 'Đã giao';
                                                break;
                                            case 'da_huy':
                                                $status_class = 'danger';
                                                $status_text = 'Đã hủy';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td><?php echo date('d/m H:i', strtotime($order['ngay_tao'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="card">
                    <div class="card-header">Top sản phẩm bán chạy</div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>SL bán</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $top_products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($product['ten_san_pham']); ?><br>
                                        <small><?php echo number_format($product['gia_ban']); ?>đ</small>
                                    </td>
                                    <td><strong><?php echo $product['tong_ban']; ?></strong></td>
                                    <td><?php echo number_format($product['doanh_thu']); ?>đ</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="card">
                <div class="card-header">Sản phẩm sắp hết hàng</div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Tên sản phẩm</th>
                                <th>Tồn kho</th>
                                <th>Mức tối thiểu</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $low_stock_products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['ten_san_pham']); ?></td>
                                <td><strong><?php echo $product['so_luong_ton_kho']; ?></strong></td>
                                <td><?php echo $product['muc_ton_kho_toi_thieu']; ?></td>
                                <td>
                                    <?php if ($product['so_luong_ton_kho'] == 0): ?>
                                        <span class="badge badge-danger">Hết hàng</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Sắp hết</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Category Stats -->
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">Thống kê theo danh mục</div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Danh mục</th>
                                <th>Số sản phẩm</th>
                                <th>Giá trị tồn kho</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($category = $category_stats->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['ten_danh_muc']); ?></td>
                                <td><?php echo $category['so_san_pham']; ?></td>
                                <td><?php echo number_format($category['gia_tri_ton_kho'] ?? 0); ?>đ</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
</body>
</html>