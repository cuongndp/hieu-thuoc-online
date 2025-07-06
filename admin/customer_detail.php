<?php
include '../config/dual_session.php';
include '../config/database.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập admin
if (!is_admin_logged_in()) {
    echo '<div style="text-align: center; padding: 20px; color: #e74c3c;">Không có quyền truy cập</div>';
    exit;
}

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$customer_id) {
    echo '<div style="text-align: center; padding: 20px; color: #e74c3c;">ID khách hàng không hợp lệ</div>';
    exit;
}

// Lấy thông tin khách hàng
$stmt = $conn->prepare("
    SELECT nd.*, 
           COUNT(DISTINCT dh.ma_don_hang) as tong_don_hang,
           COALESCE(SUM(CASE WHEN dh.trang_thai_thanh_toan = 'da_thanh_toan' THEN dh.tong_tien_thanh_toan ELSE 0 END), 0) as tong_chi_tieu,
           MAX(dh.ngay_tao) as don_hang_gan_nhat,
           MIN(dh.ngay_tao) as don_hang_dau_tien,
           AVG(dh.tong_tien_thanh_toan) as gia_tri_don_hang_tb,
           COUNT(DISTINCT CASE WHEN dh.trang_thai_don_hang = 'da_huy' THEN dh.ma_don_hang END) as don_hang_huy,
           COUNT(DISTINCT CASE WHEN dh.trang_thai_don_hang = 'da_giao' THEN dh.ma_don_hang END) as don_hang_thanh_cong
    FROM nguoi_dung nd 
    LEFT JOIN don_hang dh ON nd.ma_nguoi_dung = dh.ma_nguoi_dung 
    WHERE nd.ma_nguoi_dung = ? AND nd.vai_tro = 'khach_hang'
    GROUP BY nd.ma_nguoi_dung
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    echo '<div style="text-align: center; padding: 20px; color: #e74c3c;">Không tìm thấy khách hàng</div>';
    exit;
}

// Lấy đơn hàng gần đây
$orders_stmt = $conn->prepare("
    SELECT dh.ma_don_hang, dh.so_don_hang, dh.ngay_tao, dh.tong_tien_thanh_toan, 
           dh.trang_thai_don_hang, dh.trang_thai_thanh_toan, dh.phuong_thuc_thanh_toan,
           COUNT(ct.ma_chi_tiet) as so_san_pham,
           SUM(ct.so_luong) as tong_so_luong
    FROM don_hang dh
    LEFT JOIN chi_tiet_don_hang ct ON dh.ma_don_hang = ct.ma_don_hang
    WHERE dh.ma_nguoi_dung = ?
    GROUP BY dh.ma_don_hang
    ORDER BY dh.ngay_tao DESC
    LIMIT 10
");
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $so_don_hang = $_POST['so_don_hang'];
    $trang_thai_moi = $_POST['trang_thai_don_hang'];
    
    $valid_status = ['cho_xac_nhan','da_xac_nhan','dang_xu_ly','dang_giao','da_giao','da_huy'];
    if (in_array($trang_thai_moi, $valid_status)) {
        $update_stmt = $conn->prepare("UPDATE don_hang SET trang_thai_don_hang = ? WHERE so_don_hang = ?");
        $update_stmt->bind_param("ss", $trang_thai_moi, $so_don_hang);
        $update_stmt->execute();
        
        if ($trang_thai_moi === 'da_giao') {
            $payment_update_stmt = $conn->prepare("
                UPDATE don_hang 
                SET trang_thai_thanh_toan = 'da_thanh_toan' 
                WHERE so_don_hang = ? 
                AND phuong_thuc_thanh_toan = 'tien_mat' 
                AND trang_thai_thanh_toan = 'chua_thanh_toan'
            ");
            $payment_update_stmt->bind_param("s", $so_don_hang);
            $payment_update_stmt->execute();
        }
        
        header("Location: customer_detail.php?id=$customer_id");
        exit;
    }
}

// Tính toán metrics
$success_rate = $customer['tong_don_hang'] > 0 ? 
    round(($customer['don_hang_thanh_cong'] / $customer['tong_don_hang']) * 100, 1) : 0;

function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

function getStatusBadge($status) {
    $badges = [
        'cho_xac_nhan' => '<span class="badge badge-warning">Chờ xác nhận</span>',
        'da_xac_nhan' => '<span class="badge badge-info">Đã xác nhận</span>',
        'dang_xu_ly' => '<span class="badge badge-primary">Đang xử lý</span>',
        'dang_giao' => '<span class="badge badge-secondary">Đang giao</span>',
        'da_giao' => '<span class="badge badge-success">Đã giao</span>',
        'da_huy' => '<span class="badge badge-danger">Đã hủy</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-light">Không xác định</span>';
}

function getPaymentBadge($status) {
    $badges = [
        'chua_thanh_toan' => '<span class="badge badge-warning">Chưa thanh toán</span>',
        'da_thanh_toan' => '<span class="badge badge-success">Đã thanh toán</span>',
        'hoan_tien' => '<span class="badge badge-info">Hoàn tiền</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-light">Không xác định</span>';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết khách hàng: <?php echo htmlspecialchars($customer['ho_ten']); ?> - VitaMeds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2c3e50;
        }
        
        .main-wrapper {
            background: #f8fafc;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header Section */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .customer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .customer-details h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .customer-meta {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 14px;
        }
        

        
        .back-btn {
            background: #6b7280;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #4b5563;
            transform: translateY(-2px);
            color: white;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--color, #667eea);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            background: var(--color, #667eea);
        }
        
        .stat-title {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .stat-change {
            font-size: 12px;
            color: #10b981;
            font-weight: 500;
        }
        
        /* Card Styles */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .card-header {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Orders Table */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .orders-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .orders-table tr:hover {
            background: #f8fafc;
        }
        
        /* Badges */
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-primary { background: #e0e7ff; color: #3730a3; }
        .badge-secondary { background: #f3f4f6; color: #374151; }
        .badge-light { background: #f8f9fa; color: #6c757d; }
        
        /* Status Update Form */
        .status-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .status-select {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 12px;
            background: white;
        }
        
        .update-btn {
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .update-btn:hover {
            background: #5a67d8;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        
        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .info-card h3 i {
            color: #3498db;
            font-size: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .info-value {
            color: #7f8c8d;
            font-weight: 500;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-wrapper {
                padding: 16px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .customer-info {
                flex-direction: column;
                text-align: center;
            }
            
            .customer-meta {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                font-size: 14px;
            }
            
            .orders-table th,
            .orders-table td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="customer-info">
                        <div class="customer-avatar">
                            <?php echo strtoupper(substr($customer['ho_ten'], 0, 1)); ?>
                        </div>
                        <div class="customer-details">
                            <h1><?php echo htmlspecialchars($customer['ho_ten']); ?></h1>
                            <div class="customer-meta">
                                <div class="meta-item">
                                    <i class="fas fa-envelope"></i>
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-phone"></i>
                                    <?php echo htmlspecialchars($customer['so_dien_thoai'] ?? 'Chưa cập nhật'); ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    Tham gia: <?php echo date('d/m/Y', strtotime($customer['ngay_tao'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <a href="customers.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                            Quay lại
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card" style="--color: #667eea;">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-title">Tổng đơn hàng</div>
                    <div class="stat-value"><?php echo number_format($customer['tong_don_hang']); ?></div>
                    <div class="stat-change">
                        <?php echo $customer['don_hang_thanh_cong']; ?> đơn thành công
                    </div>
                </div>
                
                <div class="stat-card" style="--color: #10b981;">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-title">Tổng chi tiêu</div>
                    <div class="stat-value"><?php echo formatCurrency($customer['tong_chi_tieu']); ?></div>
                    <div class="stat-change">
                        TB: <?php echo formatCurrency($customer['gia_tri_don_hang_tb'] ?? 0); ?>
                    </div>
                </div>
                
                <div class="stat-card" style="--color: #f59e0b;">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                    <div class="stat-title">Tỷ lệ thành công</div>
                    <div class="stat-value"><?php echo $success_rate; ?>%</div>
                    <div class="stat-change">
                        <?php echo $customer['don_hang_huy']; ?> đơn bị hủy
                    </div>
                </div>
                
                <div class="stat-card" style="--color: #ef4444;">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-title">Đơn hàng gần nhất</div>
                    <div class="stat-value">
                        <?php 
                        if ($customer['don_hang_gan_nhat']) {
                            $days = floor((time() - strtotime($customer['don_hang_gan_nhat'])) / (60 * 60 * 24));
                            echo $days . ' ngày';
                        } else {
                            echo 'Chưa có';
                        }
                        ?>
                    </div>
                    <div class="stat-change">
                        <?php echo $customer['don_hang_gan_nhat'] ? date('d/m/Y', strtotime($customer['don_hang_gan_nhat'])) : 'N/A'; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="info-grid">
                <div class="info-card">
                    <h3>
                        <i class="fas fa-user"></i>
                        Thông tin cá nhân
                    </h3>
                    <div class="info-row">
                        <span class="info-label">Họ tên:</span>
                        <span class="info-value"><?php echo htmlspecialchars($customer['ho_ten']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($customer['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số điện thoại:</span>
                        <span class="info-value"><?php echo htmlspecialchars($customer['so_dien_thoai'] ?? 'Chưa cập nhật'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày tham gia:</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($customer['ngay_tao'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Trạng thái:</span>
                        <span class="badge badge-success">Hoạt động</span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3>
                        <i class="fas fa-chart-bar"></i>
                        Thống kê hoạt động
                    </h3>
                    <div class="info-row">
                        <span class="info-label">Tổng đơn hàng:</span>
                        <span class="info-value"><?php echo $customer['tong_don_hang']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Đơn thành công:</span>
                        <span class="info-value"><?php echo $customer['don_hang_thanh_cong']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Đơn bị hủy:</span>
                        <span class="info-value"><?php echo $customer['don_hang_huy']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tỷ lệ thành công:</span>
                        <span class="info-value"><?php echo $success_rate; ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-list"></i>
                        Đơn hàng gần đây
                    </h2>
                    <a href="orders.php?customer_id=<?php echo $customer_id; ?>" class="back-btn" style="padding: 8px 16px; font-size: 14px;">
                        Xem tất cả
                    </a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if ($orders->num_rows > 0): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn hàng</th>
                                    <th>Ngày tạo</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thanh toán</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['so_don_hang']); ?></strong>
                                            <br>
                                            <small style="color: #64748b;">
                                                <?php echo $order['so_san_pham']; ?> sản phẩm
                                            </small>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                                        <td><?php echo formatCurrency($order['tong_tien_thanh_toan']); ?></td>
                                        <td><?php echo getStatusBadge($order['trang_thai_don_hang']); ?></td>
                                        <td><?php echo getPaymentBadge($order['trang_thai_thanh_toan']); ?></td>
                                        <td>
                                            <div class="status-form">
                                                <form method="POST" style="display: flex; gap: 8px;">
                                                    <input type="hidden" name="so_don_hang" value="<?php echo $order['so_don_hang']; ?>">
                                                    <select name="trang_thai_don_hang" class="status-select">
                                                        <option value="cho_xac_nhan" <?php echo $order['trang_thai_don_hang'] == 'cho_xac_nhan' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                                        <option value="da_xac_nhan" <?php echo $order['trang_thai_don_hang'] == 'da_xac_nhan' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                                        <option value="dang_xu_ly" <?php echo $order['trang_thai_don_hang'] == 'dang_xu_ly' ? 'selected' : ''; ?>>Đang xử lý</option>
                                                        <option value="dang_giao" <?php echo $order['trang_thai_don_hang'] == 'dang_giao' ? 'selected' : ''; ?>>Đang giao</option>
                                                        <option value="da_giao" <?php echo $order['trang_thai_don_hang'] == 'da_giao' ? 'selected' : ''; ?>>Đã giao</option>
                                                        <option value="da_huy" <?php echo $order['trang_thai_don_hang'] == 'da_huy' ? 'selected' : ''; ?>>Đã hủy</option>
                                                    </select>
                                                    <button type="submit" name="update_order_status" class="update-btn">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                            <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                            <h3>Chưa có đơn hàng nào</h3>
                            <p>Khách hàng chưa thực hiện đơn hàng nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 