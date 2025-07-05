<?php
include '../config/dual_session.php';
include '../config/database.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập admin
require_admin_login();

// Lấy tháng/năm từ GET, mặc định là tháng/năm hiện tại
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Lấy thống kê tổng quan với xử lý lỗi
$total_orders = 0;
$total_revenue = 0;
$total_products = 0;
$total_customers = 0;
$pending_orders = 0;
$low_stock = 0;

try {
    // Tổng số đơn hàng
    $result = $conn->query("SELECT COUNT(*) as count FROM don_hang");
    if ($result) {
        $total_orders = $result->fetch_assoc()['count'];
    }
    
    // Tổng doanh thu THÁNG HIỆN TẠI
    $result = $conn->query("SELECT SUM(tong_tien_thanh_toan) as total FROM don_hang WHERE trang_thai_thanh_toan = 'da_thanh_toan' AND trang_thai_don_hang = 'da_giao' AND MONTH(ngay_tao) = $month AND YEAR(ngay_tao) = $year");
    if ($result) {
        $total_revenue = $result->fetch_assoc()['total'] ?? 0;
    }
    
    // Tổng số sản phẩm
    $result = $conn->query("SELECT COUNT(*) as count FROM san_pham_thuoc WHERE trang_thai_hoat_dong = 1");
    if ($result) {
        $total_products = $result->fetch_assoc()['count'];
    }
    
    // Tổng số khách hàng
    $result = $conn->query("SELECT COUNT(*) as count FROM nguoi_dung WHERE vai_tro = 'khach_hang'");
    if ($result) {
        $total_customers = $result->fetch_assoc()['count'];
    }
    
    // Đơn hàng chờ xác nhận
    $result = $conn->query("SELECT COUNT(*) as count FROM don_hang WHERE trang_thai_don_hang = 'cho_xac_nhan'");
    if ($result) {
        $pending_orders = $result->fetch_assoc()['count'];
    }
    
    // Sản phẩm sắp hết hàng
    $result = $conn->query("SELECT COUNT(*) as count FROM san_pham_thuoc WHERE so_luong_ton_kho <= muc_ton_kho_toi_thieu AND trang_thai_hoat_dong = 1");
    if ($result) {
        $low_stock = $result->fetch_assoc()['count'];
    }
    
    // Đơn hàng mới nhất
    $recent_orders = $conn->query("
        SELECT dh.*, nd.ho_ten 
        FROM don_hang dh 
        JOIN nguoi_dung nd ON dh.ma_nguoi_dung = nd.ma_nguoi_dung 
        ORDER BY dh.ngay_tao DESC 
        LIMIT 5
    ");
    
    if (!$recent_orders) {
        $recent_orders = []; // Mảng rỗng nếu không có dữ liệu
    }
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $recent_orders = [];
}

$completed_orders_result = $conn->query("SELECT COUNT(*) as count FROM don_hang WHERE trang_thai_thanh_toan = 'da_thanh_toan' AND trang_thai_don_hang = 'da_giao'");
$completed_orders = 0;
if ($completed_orders_result) {
    $completed_orders = $completed_orders_result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VitaMeds Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #2c3e50;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            color: #ecf0f1;
            margin-bottom: 5px;
        }

        /*        .sidebar-header p {
                    font-size: 12px;
                    color: #bdc3c7;
                }*/

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu li.active a {
            background: linear-gradient(90deg, #3498db, #2980b9);
        }

        .sidebar-menu a i {
            width: 20px;
            margin-right: 10px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }

        .page-header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
        }

        .user-info {
            text-align: right;
        }

        .user-info span {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-info small {
            color: #7f8c8d;
            display: block;
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.bg-blue { background: linear-gradient(45deg, #3498db, #2980b9); }
        .stat-icon.bg-green { background: linear-gradient(45deg, #27ae60, #2ecc71); }
        .stat-icon.bg-orange { background: linear-gradient(45deg, #f39c12, #e67e22); }
        .stat-icon.bg-purple { background: linear-gradient(45deg, #9b59b6, #8e44ad); }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            background: linear-gradient(90deg, #f8f9fa, #ffffff);
            border-bottom: 1px solid #ecf0f1;
        }

        .card-header h3 {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        /* Alert Items */
        .alert-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
        }

        .alert-item i {
            font-size: 20px;
        }

        .text-warning { color: #f39c12; }
        .text-danger { color: #e74c3c; }

        .btn-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            margin-left: 10px;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-cho_xac_nhan { background: #fff3cd; color: #856404; }
        .status-da_xac_nhan { background: #d1ecf1; color: #0c5460; }
        .status-dang_xu_ly { background: #d4edda; color: #155724; }
        .status-dang_giao { background: #cce5ff; color: #004085; }
        .status-da_giao { background: #d1f2eb; color: #00695c; }
        .status-da_huy { background: #f8d7da; color: #721c24; }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary { background: #3498db; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <?php include '../includes/sidebar-admin.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                    <small>Administrator</small>
                    <div style="margin-top: 10px;">
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_orders); ?></h3>
                        <p>Tổng đơn hàng</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_revenue); ?>đ</h3>
                        <p>Tổng doanh thu</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_products); ?></h3>
                        <p>Sản phẩm</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_customers); ?></h3>
                        <p>Khách hàng</p>
                    </div>
                </div>
            </div>

            <!-- Alerts & Info -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Cảnh báo</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert-item">
                            <i class="fas fa-clock text-warning"></i>
                            <div>
                                <strong><?php echo $pending_orders; ?> đơn hàng</strong> chờ xác nhận
                                <a href="orders.php?status=cho_xac_nhan" class="btn-link">Xem ngay</a>
                            </div>
                        </div>
                        
                        <div class="alert-item">
                            <i class="fas fa-box text-danger"></i>
                            <div>
                                <strong><?php echo $low_stock; ?> sản phẩm</strong> sắp hết hàng
                                <a href="products.php?filter=low_stock" class="btn-link">Kiểm tra</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Thống kê nhanh</h3>
                    </div>
                    <div class="card-body">
                        <div style="text-align: center; padding: 20px;">
                            <h2 style="color: #3498db; margin-bottom: 10px;">
                                <?php echo number_format(($total_revenue / max($completed_orders, 1))); ?>đ
                            </h2>
                            <p style="color: #7f8c8d;">Giá trị đơn hàng trung bình</p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                            <div style="text-align: center;">
                                <h4 style="color: #27ae60;"><?php echo $total_products; ?></h4>
                                <small style="color: #7f8c8d;">Sản phẩm đang bán</small>
                            </div>
                            <div style="text-align: center;">
                                <h4 style="color: #e74c3c;"><?php echo $low_stock; ?></h4>
                                <small style="color: #7f8c8d;">Sắp hết hàng</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-shopping-cart"></i> Đơn hàng mới nhất</h3>
                    <a href="orders.php" class="btn btn-primary btn-sm">Xem tất cả</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th>Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['so_don_hang']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['ho_ten']); ?></td>
                                    <td><?php echo number_format($order['tong_tien_thanh_toan']); ?>đ</td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['trang_thai_don_hang']; ?>">
                                            <?php 
                                            $status_labels = [
                                                'cho_xac_nhan' => 'Chờ xác nhận',
                                                'da_xac_nhan' => 'Đã xác nhận',
                                                'dang_xu_ly' => 'Đang xử lý',
                                                'dang_giao' => 'Đang giao',
                                                'da_giao' => 'Đã giao',
                                                'da_huy' => 'Đã hủy'
                                            ];
                                            echo $status_labels[$order['trang_thai_don_hang']] ?? $order['trang_thai_don_hang'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['ma_don_hang']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <h3>Chưa có đơn hàng nào</h3>
                        <p>Các đơn hàng mới sẽ xuất hiện ở đây</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>