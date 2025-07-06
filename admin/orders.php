<?php
include '../config/dual_session.php';
include '../config/database.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập admin
require_admin_login();

$message = '';

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $update_stmt = $conn->prepare("UPDATE don_hang SET trang_thai_don_hang = ? WHERE ma_don_hang = ?");
    $update_stmt->bind_param("si", $new_status, $order_id);
    
    if ($update_stmt->execute()) {
        // Nếu chuyển sang 'da_giao', kiểm tra phương thức thanh toán
        if ($new_status === 'da_giao') {
            $pm_stmt = $conn->prepare("SELECT phuong_thuc_thanh_toan FROM don_hang WHERE ma_don_hang = ?");
            $pm_stmt->bind_param("i", $order_id);
            $pm_stmt->execute();
            $pm_stmt->bind_result($phuong_thuc);
            $pm_stmt->fetch();
            $pm_stmt->close();
            if ($phuong_thuc === 'tien_mat') {
                $pay_stmt = $conn->prepare("UPDATE don_hang SET trang_thai_thanh_toan = 'da_thanh_toan' WHERE ma_don_hang = ?");
                $pay_stmt->bind_param("i", $order_id);
                $pay_stmt->execute();
                $pay_stmt->close();
            }
        }
        $message = "Cập nhật trạng thái đơn hàng thành công!";
    } else {
        $message = "Lỗi khi cập nhật trạng thái đơn hàng!";
    }
}

// Lấy thông tin tìm kiếm và lọc
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

// Lấy thông tin khách hàng nếu có customer_id
$customer_info = null;
if ($customer_id) {
    $customer_stmt = $conn->prepare("SELECT ho_ten, email, so_dien_thoai FROM nguoi_dung WHERE ma_nguoi_dung = ?");
    $customer_stmt->bind_param("i", $customer_id);
    $customer_stmt->execute();
    $customer_info = $customer_stmt->get_result()->fetch_assoc();
}

// Lấy tháng/năm từ GET, mặc định là tháng/năm hiện tại
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Xây dựng điều kiện WHERE
$where_conditions = [];
$params = [];
$types = "";

if ($search) {
    $where_conditions[] = "(dh.so_don_hang LIKE ? OR nd.ho_ten LIKE ? OR nd.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if ($status_filter) {
    $where_conditions[] = "dh.trang_thai_don_hang = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_from) {
    $where_conditions[] = "DATE(dh.ngay_tao) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where_conditions[] = "DATE(dh.ngay_tao) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if ($payment_status) {
    $where_conditions[] = "dh.trang_thai_thanh_toan = ?";
    $params[] = $payment_status;
    $types .= "s";
}

if ($customer_id) {
    $where_conditions[] = "dh.ma_nguoi_dung = ?";
    $params[] = $customer_id;
    $types .= "i";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Đếm tổng đơn hàng
$count_sql = "SELECT COUNT(*) as total FROM don_hang dh 
              JOIN nguoi_dung nd ON dh.ma_nguoi_dung = nd.ma_nguoi_dung 
              $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_orders = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_orders / $per_page);

// Lấy danh sách đơn hàng
$orders_sql = "
    SELECT dh.ma_don_hang, dh.so_don_hang, dh.ma_nguoi_dung, dh.ngay_tao, 
           dh.tong_tien_thanh_toan, dh.trang_thai_don_hang, dh.trang_thai_thanh_toan,
           dh.phuong_thuc_thanh_toan, nd.ho_ten, nd.email, nd.so_dien_thoai,
           COUNT(ct.ma_chi_tiet) as so_san_pham,
           dc.dia_chi_chi_tiet, dc.quan_huyen, dc.tinh_thanh
    FROM don_hang dh
    JOIN nguoi_dung nd ON dh.ma_nguoi_dung = nd.ma_nguoi_dung
    LEFT JOIN chi_tiet_don_hang ct ON dh.ma_don_hang = ct.ma_don_hang
    LEFT JOIN dia_chi dc ON dh.ma_dia_chi_giao_hang = dc.ma_dia_chi
    $where_clause
    GROUP BY dh.ma_don_hang
    ORDER BY dh.ngay_tao DESC
    LIMIT $per_page OFFSET $offset
";

if (!empty($params)) {
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param($types, ...$params);
    $orders_stmt->execute();
    $orders = $orders_stmt->get_result();
} else {
    $orders = $conn->query($orders_sql);
}

// Thống kê tổng quan
$stats = [
    'total' => 0,
    'cho_xac_nhan' => 0,
    'da_giao' => 0,
    'da_huy' => 0,
    'total_revenue' => 0
];

try {
    $stats_where = $customer_id ? "WHERE ma_nguoi_dung = $customer_id" : "";
    $stats_result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN trang_thai_don_hang = 'cho_xac_nhan' THEN 1 ELSE 0 END) as cho_xac_nhan,
            SUM(CASE WHEN trang_thai_don_hang = 'da_giao' THEN 1 ELSE 0 END) as da_giao,
            SUM(CASE WHEN trang_thai_don_hang = 'da_huy' THEN 1 ELSE 0 END) as da_huy,
            SUM(CASE WHEN trang_thai_thanh_toan = 'da_thanh_toan' AND trang_thai_don_hang = 'da_giao' AND MONTH(ngay_tao) = $month AND YEAR(ngay_tao) = $year THEN tong_tien_thanh_toan ELSE 0 END) as total_revenue
        FROM don_hang
        $stats_where
    ");
    
    if ($stats_result) {
        $stats = $stats_result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Orders stats error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - VitaMeds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>">
        <style>
        /* Order-specific styles */
        .order-filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .filter-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-info { background: #17a2b8; color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Order Status Badges */
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-cho_xac_nhan {
            background: #fff3cd;
            color: #856404;
        }

        .status-da_xac_nhan {
            background: #cce5ff;
            color: #0056b3;
        }

        .status-dang_xu_ly {
            background: #d4edda;
            color: #155724;
        }

        .status-dang_giao {
            background: #cce5ff;
            color: #0056b3;
        }

        .status-da_giao {
            background: #d1f2eb;
            color: #00695c;
        }

        .status-da_huy {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-chua_thanh_toan {
            background: #fff3cd;
            color: #856404;
        }

        .payment-da_thanh_toan {
            background: #d1f2eb;
            color: #00695c;
        }

        .payment-da_hoan_tien {
            background: #cce5ff;
            color: #0056b3;
        }

        /* Order Table */
        .orders-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            min-width: 1000px;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-number {
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
        }

        .order-date {
            color: #7f8c8d;
            font-size: 12px;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .customer-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .customer-contact {
            color: #7f8c8d;
            font-size: 12px;
        }

        .order-total {
            font-size: 16px;
            font-weight: 700;
            color: #27ae60;
        }

        .order-items {
            color: #7f8c8d;
            font-size: 12px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Status Update Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 12px 12px 0 0;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #2c3e50;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-submit {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        /* Alert */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #d1f2eb;
            color: #00695c;
            border-left: 4px solid #27ae60;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            padding: 20px;
        }

        .page-link {
            padding: 8px 12px;
            color: #3498db;
            text-decoration: none;
            border: 1px solid #ecf0f1;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .page-link:hover,
        .page-link.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #2c3e50;
        }

        /* Customer Info Banner Responsive */
        .customer-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .customer-banner-content {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 25px;
        }
        
        .customer-info-text {
            flex: 1;
        }
        
        .customer-banner h3 {
            margin: 0 0 8px 0;
            color: white;
            font-size: 18px;
            font-weight: 600;
        }
        
        .customer-banner p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .customer-info-contact {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .customer-info-contact span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .customer-banner .btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .customer-banner .btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .modal-content {
                margin: 10px;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table th,
            .table td {
                padding: 8px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            /* Customer Banner Mobile */
            .customer-banner-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 20px;
            }
            
            .customer-banner h3 {
                font-size: 16px;
                line-height: 1.4;
            }
            
            .customer-banner p {
                font-size: 13px;
                line-height: 1.5;
            }
            
            .customer-banner .btn {
                width: 100%;
                text-align: center;
                padding: 12px 20px;
            }
        }

        @media (max-width: 480px) {
            .admin-wrapper {
                padding: 10px;
            }
            
            .dashboard-content {
                padding: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .order-filters {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .filter-form {
                gap: 15px;
            }
            
            .filter-group {
                gap: 6px;
            }
            
            .filter-group label {
                font-size: 13px;
            }
            
            .filter-group input,
            .filter-group select {
                padding: 10px;
                font-size: 14px;
            }
            
            .orders-table {
                margin-bottom: 15px;
            }
            
            .table-header {
                padding: 15px;
            }
            
            .table-header h3 {
                font-size: 16px;
            }
            
            .table th,
            .table td {
                padding: 6px;
                font-size: 11px;
            }
            
            /* Customer Banner Small Mobile */
            .customer-banner-content {
                padding: 15px;
                gap: 12px;
            }
            
            .customer-banner h3 {
                font-size: 14px;
                margin-bottom: 6px;
            }
            
            .customer-banner p {
                font-size: 12px;
            }
            
            .customer-info-contact {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
        }

        @media (max-width: 320px) {
            .customer-banner-content {
                padding: 12px;
            }
            
            .customer-banner h3 {
                font-size: 13px;
            }
            
            .customer-banner p {
                font-size: 11px;
            }
            
            .stats-grid {
                gap: 10px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .order-filters {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Quản lý Đơn hàng';
            $page_icon = 'fas fa-shopping-cart';
            include '../includes/admin-header.php'; 
            ?>
            
            <div class="dashboard-content">

            <?php if ($message): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total'] ?? 0); ?></h3>
                        <p>Tổng đơn hàng</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['cho_xac_nhan'] ?? 0); ?></h3>
                        <p>Chờ xác nhận</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['da_giao'] ?? 0); ?></h3>
                        <p>Đã giao thành công</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-red">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['da_huy'] ?? 0); ?></h3>
                        <p>Đã hủy</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_revenue'] ?? 0); ?>đ</h3>
                        <p>Tổng doanh thu</p>
                    </div>
                </div>
            </div>

            <!-- Customer Filter Info -->
            <?php if ($customer_info): ?>
            <div class="customer-banner">
                <div class="customer-banner-content">
                    <div class="customer-info-text">
                        <h3>
                            <i class="fas fa-user-circle"></i>
                            Đơn hàng của khách hàng: <?php echo htmlspecialchars($customer_info['ho_ten']); ?>
                        </h3>
                        <div class="customer-info-contact">
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer_info['email']); ?></span>
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer_info['so_dien_thoai'] ?: 'Chưa cập nhật'); ?></span>
                        </div>
                    </div>
                    <a href="orders.php" class="btn">
                        <i class="fas fa-times"></i> Xem tất cả đơn hàng
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Search and Filter Section -->
            <div class="order-filters">
                <form method="GET" class="filter-form">
                    <?php if ($customer_id): ?>
                    <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                    <?php endif; ?>
                    <div class="filter-group">
                        <label>Tìm kiếm</label>
                        <input type="text" name="search" placeholder="Mã đơn, tên khách hàng, email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Trạng thái đơn hàng</label>
                        <select name="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="cho_xac_nhan" <?php if($status_filter === 'cho_xac_nhan') echo 'selected'; ?>>Chờ xác nhận</option>
                            <option value="da_xac_nhan" <?php if($status_filter === 'da_xac_nhan') echo 'selected'; ?>>Đã xác nhận</option>
                            <option value="dang_xu_ly" <?php if($status_filter === 'dang_xu_ly') echo 'selected'; ?>>Đang xử lý</option>
                            <option value="dang_giao" <?php if($status_filter === 'dang_giao') echo 'selected'; ?>>Đang giao</option>
                            <option value="da_giao" <?php if($status_filter === 'da_giao') echo 'selected'; ?>>Đã giao</option>
                            <option value="da_huy" <?php if($status_filter === 'da_huy') echo 'selected'; ?>>Đã hủy</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Trạng thái thanh toán</label>
                        <select name="payment_status">
                            <option value="">Tất cả</option>
                            <option value="chua_thanh_toan" <?php if($payment_status === 'chua_thanh_toan') echo 'selected'; ?>>Chưa thanh toán</option>
                            <option value="da_thanh_toan" <?php if($payment_status === 'da_thanh_toan') echo 'selected'; ?>>Đã thanh toán</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Từ ngày</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Đến ngày</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </form>
                
                <div class="filter-actions">
                    <button type="submit" form="filter-form" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                    <?php if ($search || $status_filter || $date_from || $date_to || $payment_status): ?>
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                    <?php endif; ?>
                    <a href="?status=cho_xac_nhan" class="btn btn-warning">
                        <i class="fas fa-clock"></i> Chờ xác nhận (<?php echo $stats['cho_xac_nhan']; ?>)
                    </a>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="orders-table">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Danh sách đơn hàng (<?php echo $total_orders; ?>)</h3>
                </div>
                
                <div class="table-responsive">
                    <?php if ($orders && $orders->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Thông tin đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Sản phẩm & Tổng tiền</th>
                                <th>Địa chỉ giao hàng</th>
                                <th>Trạng thái</th>
                                <th>Thanh toán</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="order-info">
                                        <div class="order-number"><?php echo htmlspecialchars($order['so_don_hang']); ?></div>
                                        <div class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?php echo htmlspecialchars($order['ho_ten']); ?></div>
                                        <div class="customer-contact"><?php echo htmlspecialchars($order['email']); ?></div>
                                        <div class="customer-contact"><?php echo htmlspecialchars($order['so_dien_thoai'] ?? 'Chưa có SĐT'); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-total"><?php echo number_format($order['tong_tien_thanh_toan']); ?>đ</div>
                                    <div class="order-items"><?php echo $order['so_san_pham']; ?> sản phẩm</div>
                                    <div class="order-items">
                                        <?php 
                                        $payment_methods = [
                                            'tien_mat' => 'Tiền mặt',
                                            'chuyen_khoan' => 'Chuyển khoản',
                                            'the_tin_dung' => 'Thẻ tín dụng',
                                            'vi_dien_tu' => 'Ví điện tử'
                                        ];
                                        echo $payment_methods[$order['phuong_thuc_thanh_toan']] ?? 'Chưa chọn';
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 12px; color: #7f8c8d; max-width: 200px;">
                                        <?php 
                                        if ($order['dia_chi_chi_tiet']) {
                                            echo htmlspecialchars(substr($order['dia_chi_chi_tiet'], 0, 40) . '...');
                                            echo '<br>' . htmlspecialchars($order['quan_huyen'] . ', ' . $order['tinh_thanh']);
                                        } else {
                                            echo 'Chưa có địa chỉ';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="order-status status-<?php echo $order['trang_thai_don_hang']; ?>">
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
                                    <br>
                                    <button class="btn btn-sm btn-info" onclick="openStatusModal(<?php echo $order['ma_don_hang']; ?>, '<?php echo $order['trang_thai_don_hang']; ?>', '<?php echo str_replace("'", "\\'", htmlspecialchars($order['so_don_hang'])); ?>')">
                                        <i class="fas fa-edit"></i> Cập nhật
                                    </button>
                                </td>
                                <td>
                                    <span class="payment-status payment-<?php echo $order['trang_thai_thanh_toan']; ?>">
                                        <?php 
                                        echo $order['trang_thai_thanh_toan'] === 'da_thanh_toan' ? 'Đã thanh toán' : 'Chưa thanh toán';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="order_detail.php?id=<?php echo $order['ma_don_hang']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="customer_detail.php?id=<?php echo $order['ma_nguoi_dung']; ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-user"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php 
                        $pagination_params = [
                            'search' => urlencode($search),
                            'status' => urlencode($status_filter),
                            'payment_status' => urlencode($payment_status),
                            'date_from' => urlencode($date_from),
                            'date_to' => urlencode($date_to)
                        ];
                        if ($customer_id) {
                            $pagination_params['customer_id'] = $customer_id;
                        }
                        
                        function buildPaginationUrl($page_num, $params) {
                            $params['page'] = $page_num;
                            return '?' . http_build_query(array_filter($params));
                        }
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?php echo buildPaginationUrl($page-1, $pagination_params); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <a href="<?php echo buildPaginationUrl($i, $pagination_params); ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo buildPaginationUrl($page+1, $pagination_params); ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Không tìm thấy đơn hàng nào</h3>
                        <p>Thử thay đổi bộ lọc tìm kiếm hoặc kiểm tra lại thông tin</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-edit"></i> Cập nhật trạng thái đơn hàng</h3>
                <button class="modal-close" onclick="closeStatusModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="statusUpdateForm">
                    <input type="hidden" name="order_id" id="modal_order_id">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="form-group">
                        <label>Đơn hàng: <span id="modal_order_number"></span></label>
                    </div>
                    
                    <div class="form-group">
                        <label>Trạng thái mới</label>
                        <select name="new_status" id="modal_new_status" required>
                            <option value="cho_xac_nhan">Chờ xác nhận</option>
                            <option value="da_xac_nhan">Đã xác nhận</option>
                            <option value="dang_xu_ly">Đang xử lý</option>
                            <option value="dang_giao">Đang giao</option>
                            <option value="da_giao">Đã giao</option>
                            <option value="da_huy">Đã hủy</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeStatusModal()">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
    <script>
        // Status Modal Functions
        function openStatusModal(orderId, currentStatus, orderNumber) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modal_order_number').textContent = orderNumber;
            document.getElementById('modal_new_status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'flex';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Form submission confirmation
        document.getElementById('statusUpdateForm').addEventListener('submit', function(e) {
            const orderNumber = document.getElementById('modal_order_number').textContent;
            const newStatus = document.getElementById('modal_new_status');
            const statusText = newStatus.options[newStatus.selectedIndex].text;
            
            if (!confirm(`Bạn có chắc muốn cập nhật trạng thái đơn hàng ${orderNumber} thành "${statusText}"?`)) {
                e.preventDefault();
            }
        });

        // Auto hide alert
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);

        // Add form ID for filter form
        document.querySelector('.filter-form').id = 'filter-form';
    </script>
</body>
</html>