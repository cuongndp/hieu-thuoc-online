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
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>">
    <style>
        /* Modern Dashboard Styles */
        .dashboard-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            z-index: 0;
        }
        
        .dashboard-inner {
            position: relative;
            z-index: 1;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .filter-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .filter-form label {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .filter-form select {
            padding: 12px 15px;
            border-radius: 12px;
            border: 2px solid #e3f2fd;
            background: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .filter-form select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .filter-form .btn {
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .filter-form .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, #667eea, #764ba2);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon.bg-blue {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }
        
        .stat-icon.bg-green {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
        }
        
        .stat-icon.bg-orange {
            background: linear-gradient(45deg, #f39c12, #e67e22);
        }
        
        .stat-icon.bg-purple {
            background: linear-gradient(45deg, #9b59b6, #8e44ad);
        }
        
        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .stat-info p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0;
            font-weight: 500;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            padding: 25px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header .btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .card-header .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }
        
        .card-body {
            padding: 25px;
        }
        
        .alert-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 12px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }
        
        .alert-item:hover {
            background: #e3f2fd;
            transform: translateX(5px);
        }
        
        .alert-item:last-child {
            margin-bottom: 0;
        }
        
        .alert-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        .alert-item strong {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .alert-item p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .alert-item small {
            color: #95a5a6;
            font-size: 12px;
        }
        
        .btn-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .btn-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                padding: 20px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .filter-form {
                padding: 20px;
            }
            
            .filter-form {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>
        
        <div class="main-content">
            <?php 
            $page_title = 'Dashboard';
            $page_icon = 'fas fa-tachometer-alt';
            include '../includes/admin-header.php'; 
            ?>
            
            <div class="dashboard-content">
                <div class="dashboard-inner">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h1><i class="fas fa-chart-line"></i> VitaMeds Dashboard</h1>
                        <p>Quản lý và theo dõi hoạt động của nhà thuốc trực tuyến</p>
                    </div>

                    <!-- Filter Form -->
                    <form method="GET" class="filter-form">
                        <div style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap;">
                            <div>
                                <label>
                                    <i class="fas fa-calendar-alt"></i>
                                    Tháng
                                </label>
                                <select name="month">
                                    <?php for($m=1;$m<=12;$m++): ?>
                                        <option value="<?php echo $m; ?>" <?php if($m==$month) echo 'selected'; ?>><?php echo $m; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label>
                                    <i class="fas fa-calendar"></i>
                                    Năm
                                </label>
                                <select name="year">
                                    <?php for($y=date('Y')-3;$y<=date('Y');$y++): ?>
                                        <option value="<?php echo $y; ?>" <?php if($y==$year) echo 'selected'; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Xem thống kê
                                </button>
                            </div>
                        </div>
                    </form>

                <!-- Stats Grid -->
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
                            <p>Doanh thu tháng <?php echo $month; ?>/<?php echo $year; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-orange">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($total_products); ?></h3>
                            <p>Sản phẩm đang bán</p>
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

                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Recent Orders -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock"></i> Đơn hàng gần đây</h3>
                            <a href="orders.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <div class="alert-item">
                                        <i class="fas fa-shopping-cart text-primary"></i>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['so_don_hang']); ?></strong>
                                            <p><?php echo htmlspecialchars($order['ho_ten']); ?> - <?php echo number_format($order['tong_tien_thanh_toan']); ?>đ</p>
                                            <small><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="alert-item">
                                    <i class="fas fa-info-circle text-muted"></i>
                                    <p>Không có đơn hàng nào</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Cảnh báo</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert-item">
                                <i class="fas fa-clock text-warning"></i>
                                <div>
                                    <strong><?php echo $pending_orders; ?> đơn hàng chờ xác nhận</strong>
                                    <p>Cần xử lý ngay</p>
                                    <a href="orders.php?status=cho_xac_nhan" class="btn-link">Xem chi tiết</a>
                                </div>
                            </div>
                            <div class="alert-item">
                                <i class="fas fa-box text-danger"></i>
                                <div>
                                    <strong><?php echo $low_stock; ?> sản phẩm sắp hết hàng</strong>
                                    <p>Cần nhập thêm hàng</p>
                                    <a href="products.php?filter=low_stock" class="btn-link">Xem chi tiết</a>
                                </div>
                            </div>
                            <div class="alert-item">
                                <i class="fas fa-check-circle text-success"></i>
                                <div>
                                    <strong><?php echo $completed_orders; ?> đơn hàng đã hoàn thành</strong>
                                    <p>Đã giao và thanh toán</p>
                                    <a href="orders.php?status=da_giao" class="btn-link">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>