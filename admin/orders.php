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
    $stats_result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN trang_thai_don_hang = 'cho_xac_nhan' THEN 1 ELSE 0 END) as cho_xac_nhan,
            SUM(CASE WHEN trang_thai_don_hang = 'da_giao' THEN 1 ELSE 0 END) as da_giao,
            SUM(CASE WHEN trang_thai_don_hang = 'da_huy' THEN 1 ELSE 0 END) as da_huy,
            SUM(CASE WHEN trang_thai_thanh_toan = 'da_thanh_toan' AND trang_thai_don_hang = 'da_giao' AND MONTH(ngay_tao) = $month AND YEAR(ngay_tao) = $year THEN tong_tien_thanh_toan ELSE 0 END) as total_revenue
        FROM don_hang
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-icon.bg-blue { background: linear-gradient(45deg, #3498db, #2980b9); }
        .stat-icon.bg-orange { background: linear-gradient(45deg, #f39c12, #e67e22); }
        .stat-icon.bg-green { background: linear-gradient(45deg, #27ae60, #2ecc71); }
        .stat-icon.bg-red { background: linear-gradient(45deg, #e74c3c, #c0392b); }
        .stat-icon.bg-purple { background: linear-gradient(45deg, #9b59b6, #8e44ad); }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Filters */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
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
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        /* Table */
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 13px;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            white-space: nowrap;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-cho_xac_nhan { background: #fff3cd; color: #856404; }
        .status-da_xac_nhan { background: #d1ecf1; color: #0c5460; }
        .status-dang_xu_ly { background: #d4edda; color: #155724; }
        .status-dang_giao { background: #cce5ff; color: #004085; }
        .status-da_giao { background: #d1f2eb; color: #00695c; }
        .status-da_huy { background: #f8d7da; color: #721c24; }

        /* Customer info */
        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .customer-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .customer-contact {
            color: #7f8c8d;
            font-size: 12px;
        }

        /* Order info */
        .order-code {
            font-weight: 600;
            color: #3498db;
        }

        .order-date {
            color: #7f8c8d;
            font-size: 12px;
        }

        /* Alert */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #d1f2eb;
            color: #00695c;
            border-left: 4px solid #27ae60;
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

        /* Status update form */
        .status-update {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-update select {
            padding: 4px 8px;
            border: 1px solid #ecf0f1;
            border-radius: 4px;
            font-size: 12px;
        }

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
            .filter-row {
                grid-template-columns: 1fr;
            }
        }

        .filter-form-responsive {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
        .filter-form-responsive input,
        .filter-form-responsive select {
            min-width: 180px;
            margin-bottom: 0;
        }
        .filter-form-responsive .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        @media (max-width: 900px) {
            .filter-form-responsive input,
            .filter-form-responsive select {
                min-width: 120px;
                flex: 1 1 100%;
            }
            .filter-form-responsive {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-form-responsive .filter-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-shopping-cart"></i> Quản lý Đơn hàng</h1>
            </div>

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

            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filter-form-responsive">
                    <input type="text" name="search" placeholder="Mã đơn, tên khách hàng, email..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="cho_xac_nhan" <?php if($status_filter === 'cho_xac_nhan') echo 'selected'; ?>>Chờ xác nhận</option>
                        <option value="da_xac_nhan" <?php if($status_filter === 'da_xac_nhan') echo 'selected'; ?>>Đã xác nhận</option>
                        <option value="dang_xu_ly" <?php if($status_filter === 'dang_xu_ly') echo 'selected'; ?>>Đang xử lý</option>
                        <option value="dang_giao" <?php if($status_filter === 'dang_giao') echo 'selected'; ?>>Đang giao</option>
                        <option value="da_giao" <?php if($status_filter === 'da_giao') echo 'selected'; ?>>Đã giao</option>
                        <option value="da_huy" <?php if($status_filter === 'da_huy') echo 'selected'; ?>>Đã hủy</option>
                    </select>
                    <select name="payment_status">
                        <option value="">Tất cả trạng thái thanh toán</option>
                        <option value="chua_thanh_toan" <?php if($payment_status === 'chua_thanh_toan') echo 'selected'; ?>>Chưa thanh toán</option>
                        <option value="da_thanh_toan" <?php if($payment_status === 'da_thanh_toan') echo 'selected'; ?>>Đã thanh toán</option>
                    </select>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm kiếm</button>
                        <a href="orders.php" class="btn btn-secondary"><i class="fas fa-times"></i> Xóa bộ lọc</a>
                        <a href="?status=cho_xac_nhan" class="btn btn-warning"><i class="fas fa-clock"></i> Chờ xác nhận (<?php echo $stats['cho_xac_nhan']; ?>)</a>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Danh sách đơn hàng (<?php echo $total_orders; ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if ($orders && $orders->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mã đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Ngày đặt</th>
                                <th>Sản phẩm</th>
                                <th>Tổng tiền</th>
                                <th>Địa chỉ giao</th>
                                <th>Trạng thái</th>
                                <th>Trạng thái thanh toán</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="order-code"><?php echo htmlspecialchars($order['so_don_hang']); ?></div>
                                    <div class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?php echo htmlspecialchars($order['ho_ten']); ?></div>
                                        <div class="customer-contact"><?php echo htmlspecialchars($order['email']); ?></div>
                                        <div class="customer-contact"><?php echo htmlspecialchars($order['so_dien_thoai'] ?? 'Chưa có SĐT'); ?></div>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                                <td>
                                    <strong><?php echo $order['so_san_pham']; ?></strong> sản phẩm
                                </td>
                                <td>
                                    <strong style="color: #27ae60;"><?php echo number_format($order['tong_tien_thanh_toan']); ?>đ</strong><br>
                                    <small style="color: #7f8c8d;">
                                        <?php 
                                        $payment_methods = [
                                            'tien_mat' => 'Tiền mặt',
                                            'chuyen_khoan' => 'Chuyển khoản',
                                            'the_tin_dung' => 'Thẻ tín dụng',
                                            'vi_dien_tu' => 'Ví điện tử'
                                        ];
                                        echo $payment_methods[$order['phuong_thuc_thanh_toan']] ?? 'Chưa chọn';
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="font-size: 12px; color: #7f8c8d;">
                                        <?php 
                                        if ($order['dia_chi_chi_tiet']) {
                                            echo htmlspecialchars(substr($order['dia_chi_chi_tiet'], 0, 30) . '...');
                                            echo '<br>' . htmlspecialchars($order['quan_huyen'] . ', ' . $order['tinh_thanh']);
                                        } else {
                                            echo 'Chưa có địa chỉ';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" class="status-update">
                                        <input type="hidden" name="order_id" value="<?php echo $order['ma_don_hang']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="new_status" onchange="this.form.submit()">
                                            <option value="cho_xac_nhan" <?php echo $order['trang_thai_don_hang'] === 'cho_xac_nhan' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                            <option value="da_xac_nhan" <?php echo $order['trang_thai_don_hang'] === 'da_xac_nhan' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                            <option value="dang_xu_ly" <?php echo $order['trang_thai_don_hang'] === 'dang_xu_ly' ? 'selected' : ''; ?>>Đang xử lý</option>
                                            <option value="dang_giao" <?php echo $order['trang_thai_don_hang'] === 'dang_giao' ? 'selected' : ''; ?>>Đang giao</option>
                                            <option value="da_giao" <?php echo $order['trang_thai_don_hang'] === 'da_giao' ? 'selected' : ''; ?>>Đã giao</option>
                                            <option value="da_huy" <?php echo $order['trang_thai_don_hang'] === 'da_huy' ? 'selected' : ''; ?>>Đã hủy</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <?php if($order['trang_thai_thanh_toan'] === 'da_thanh_toan') {
                                        echo '<span style="color:#27ae60;font-weight:bold;">Đã thanh toán</span>';
                                    } else {
                                        echo '<span style="color:#e67e22;font-weight:bold;">Chưa thanh toán</span>';
                                    } ?>
                                </td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo $order['ma_don_hang']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </a>
                                    <a href="customer_detail.php?id=<?php echo $order['ma_nguoi_dung']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-user"></i> Khách hàng
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <h3>Không tìm thấy đơn hàng nào</h3>
                        <p>Thử thay đổi bộ lọc tìm kiếm hoặc kiểm tra lại thông tin</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto hide alert
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);

        // Confirm before changing status
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelects = document.querySelectorAll('select[name="new_status"]');
            statusSelects.forEach(select => {
                const originalValue = select.value;
                select.addEventListener('change', function() {
                    const orderCode = this.closest('tr').querySelector('.order-code').textContent;
                    const newStatus = this.options[this.selectedIndex].text;
                    
                    if (confirm(`Bạn có chắc muốn cập nhật trạng thái đơn hàng ${orderCode} thành "${newStatus}"?`)) {
                        this.form.submit();
                    } else {
                        this.value = originalValue;
                    }
                });
            });
        });
    </script>
</body>
</html>