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
           MIN(dh.ngay_tao) as don_hang_dau_tien
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

// Lấy địa chỉ
$addresses_stmt = $conn->prepare("SELECT * FROM dia_chi WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC");
$addresses_stmt->bind_param("i", $customer_id);
$addresses_stmt->execute();
$addresses = $addresses_stmt->get_result();

// Lấy đơn hàng gần nhất
$orders_stmt = $conn->prepare("
    SELECT dh.so_don_hang, dh.ngay_tao, dh.tong_tien_thanh_toan, dh.trang_thai_don_hang,
           COUNT(ct.ma_chi_tiet) as so_san_pham,
           dc.dia_chi_chi_tiet, dc.phuong_xa, dc.quan_huyen, dc.tinh_thanh
    FROM don_hang dh
    LEFT JOIN chi_tiet_don_hang ct ON dh.ma_don_hang = ct.ma_don_hang
    LEFT JOIN dia_chi dc ON dh.ma_dia_chi_giao_hang = dc.ma_dia_chi
    WHERE dh.ma_nguoi_dung = ?
    GROUP BY dh.ma_don_hang
    ORDER BY dh.ngay_tao DESC
    LIMIT 5
");
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $so_don_hang = $_POST['so_don_hang'];
    $trang_thai_moi = $_POST['trang_thai_don_hang'];
    // Chỉ cho phép các trạng thái hợp lệ
    $valid_status = ['cho_xac_nhan','da_xac_nhan','dang_xu_ly','dang_giao','da_giao','da_huy'];
    if (in_array($trang_thai_moi, $valid_status)) {
        $update_stmt = $conn->prepare("UPDATE don_hang SET trang_thai_don_hang = ? WHERE so_don_hang = ?");
        $update_stmt->bind_param("ss", $trang_thai_moi, $so_don_hang);
        $update_stmt->execute();
        // Reload lại trang để cập nhật
        header("Location: customer_detail.php?id=$customer_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết khách hàng - VitaMeds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #5a6268;
            color: white;
        }
        .header-card {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .header-card h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
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
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        .table-header h3 {
            margin: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .simple-table {
            width: 100%;
            border-collapse: collapse;
        }
        .simple-table th,
        .simple-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f8f9fa;
        }
        .simple-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .simple-table tbody tr:hover {
            background: #f8f9fa;
        }
        .address-item {
            background: #f8f9fa;
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .address-item.default {
            border-color: #27ae60;
            background: #d1f2eb;
        }
        .address-type {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .empty-message {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        .empty-message i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        .status-cho_xac_nhan { background: #fff3cd; color: #856404; }
        .status-da_xac_nhan { background: #d1ecf1; color: #0c5460; }
        .status-dang_xu_ly { background: #d4edda; color: #155724; }
        .status-dang_giao { background: #cce5ff; color: #004085; }
        .status-da_giao { background: #d1f2eb; color: #00695c; }
        .status-da_huy { background: #f8d7da; color: #721c24; }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="customers.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách khách hàng
        </a>

        <div class="header-card">
            <h1><?php echo htmlspecialchars($customer['ho_ten']); ?></h1>
            <p>
                <i class="fas fa-coins"></i> 
                Tổng chi tiêu: <?php echo number_format($customer['tong_chi_tieu']); ?>đ từ <?php echo $customer['tong_don_hang']; ?> đơn hàng
            </p>
        </div>

        <div class="info-grid">
            <!-- Thông tin cá nhân -->
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Thông tin cá nhân</h3>
                <div class="info-row">
                    <span class="info-label">ID:</span>
                    <span class="info-value"><?php echo $customer['ma_nguoi_dung']; ?></span>
                </div>
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
                    <span class="info-label">Ngày sinh:</span>
                    <span class="info-value">
                        <?php 
                        if ($customer['ngay_sinh']) {
                            echo date('d/m/Y', strtotime($customer['ngay_sinh']));
                        } else {
                            echo 'Chưa cập nhật';
                        }
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Giới tính:</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer['gioi_tinh'] ?? 'Chưa cập nhật'); ?></span>
                </div>
            </div>

            <!-- Thống kê mua hàng -->
            <div class="info-card">
                <h3><i class="fas fa-chart-bar"></i> Thống kê mua hàng</h3>
                <div class="info-row">
                    <span class="info-label">Tổng đơn hàng:</span>
                    <span class="info-value" style="color: #3498db; font-weight: 700;"><?php echo $customer['tong_don_hang']; ?> đơn</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tổng chi tiêu:</span>
                    <span class="info-value" style="color: #27ae60; font-weight: 700;">
                        <?php echo number_format($customer['tong_chi_tieu']); ?>đ
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Đơn hàng đầu tiên:</span>
                    <span class="info-value">
                        <?php 
                        if ($customer['don_hang_dau_tien']) {
                            echo date('d/m/Y', strtotime($customer['don_hang_dau_tien']));
                        } else {
                            echo 'Chưa có';
                        }
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mua hàng gần nhất:</span>
                    <span class="info-value">
                        <?php 
                        if ($customer['don_hang_gan_nhat']) {
                            echo date('d/m/Y H:i', strtotime($customer['don_hang_gan_nhat']));
                        } else {
                            echo 'Chưa có';
                        }
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái:</span>
                    <span class="info-value">
                        <span class="status-badge" style="background: #d1f2eb; color: #00695c;">Hoạt động</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Địa chỉ -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</h3>
            </div>
            <div style="padding: 20px;">
                <?php if ($addresses && $addresses->num_rows > 0): ?>
                    <?php while ($address = $addresses->fetch_assoc()): ?>
                    <div class="address-item <?php echo $address['la_dia_chi_mac_dinh'] ? 'default' : ''; ?>">
                        <div class="address-type">
                            <?php echo ucfirst(str_replace('_', ' ', $address['loai_dia_chi'])); ?>
                            <?php if ($address['la_dia_chi_mac_dinh']): ?>
                                <span style="color: #27ae60; font-size: 12px;">(Mặc định)</span>
                            <?php endif; ?>
                        </div>
                        <div style="color: #7f8c8d; line-height: 1.5;">
                            <strong><?php echo htmlspecialchars($address['ten_nguoi_nhan']); ?></strong> - <?php echo htmlspecialchars($address['so_dien_thoai']); ?><br>
                            <?php echo htmlspecialchars($address['dia_chi_chi_tiet']); ?><br>
                            <?php echo htmlspecialchars($address['phuong_xa'] . ', ' . $address['quan_huyen'] . ', ' . $address['tinh_thanh']); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-message">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Chưa có địa chỉ</h3>
                        <p>Khách hàng chưa thêm địa chỉ giao hàng nào</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Đơn hàng -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-shopping-cart"></i> Đơn hàng gần nhất</h3>
            </div>
            <?php if ($orders && $orders->num_rows > 0): ?>
            <table class="simple-table">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Ngày đặt</th>
                        <th>Số sản phẩm</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Địa chỉ giao hàng</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['so_don_hang']); ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                        <td><?php echo $order['so_san_pham']; ?> sản phẩm</td>
                        <td><strong><?php echo number_format($order['tong_tien_thanh_toan']); ?>đ</strong></td>
                        <td>
                            <span class="status-badge" style="background: #d1f2eb; color: #00695c;">
                                <?php echo ucfirst(str_replace('_', ' ', $order['trang_thai_don_hang'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            echo htmlspecialchars($order['dia_chi_chi_tiet']);
                            if ($order['phuong_xa'] || $order['quan_huyen'] || $order['tinh_thanh']) {
                                echo ', ' . htmlspecialchars($order['phuong_xa'] . ', ' . $order['quan_huyen'] . ', ' . $order['tinh_thanh']);
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline-block; min-width:120px;">
                                <input type="hidden" name="update_order_status" value="1">
                                <input type="hidden" name="so_don_hang" value="<?php echo htmlspecialchars($order['so_don_hang']); ?>">
                                <select name="trang_thai_don_hang" style="padding:3px 8px; border-radius:6px;">
                                    <?php
                                    $status_options = [
                                        'cho_xac_nhan' => 'Chờ xác nhận',
                                        'da_xac_nhan' => 'Đã xác nhận',
                                        'dang_xu_ly' => 'Đang xử lý',
                                        'dang_giao' => 'Đang giao',
                                        'da_giao' => 'Đã giao',
                                        'da_huy' => 'Đã hủy',
                                    ];
                                    foreach ($status_options as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php if($order['trang_thai_don_hang']==$key) echo 'selected'; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" style="padding:2px 8px; border-radius:6px; background:#3498db; color:white; border:none;">Lưu</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-message">
                <i class="fas fa-shopping-cart"></i>
                <h3>Chưa có đơn hàng</h3>
                <p>Khách hàng chưa thực hiện đơn hàng nào</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 