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

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$order_id) {
    echo '<div style="text-align: center; padding: 20px; color: #e74c3c;">ID đơn hàng không hợp lệ</div>';
    exit;
}

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    $valid_status = ['cho_xac_nhan','da_xac_nhan','dang_xu_ly','dang_giao','da_giao','da_huy'];
    if (in_array($new_status, $valid_status)) {
        $update_stmt = $conn->prepare("UPDATE don_hang SET trang_thai_don_hang = ? WHERE ma_don_hang = ?");
        $update_stmt->bind_param("si", $new_status, $order_id);
        $update_stmt->execute();
        // Nếu chuyển sang 'da_giao' và là tiền mặt thì tự động cập nhật trạng thái thanh toán
        if ($new_status === 'da_giao') {
            // Lấy phương thức thanh toán hiện tại
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
        header("Location: order_detail.php?id=$order_id");
        exit;
    }
}

// Thêm xử lý cập nhật trạng thái thanh toán ở đầu file:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $new_payment = $_POST['new_payment'];
    $valid_payment = ['chua_thanh_toan', 'da_thanh_toan'];
    if (in_array($new_payment, $valid_payment)) {
        $update_stmt = $conn->prepare("UPDATE don_hang SET trang_thai_thanh_toan = ? WHERE ma_don_hang = ?");
        $update_stmt->bind_param("si", $new_payment, $order_id);
        $update_stmt->execute();
        header("Location: order_detail.php?id=$order_id");
        exit;
    }
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT dh.*, nd.ho_ten, nd.email, nd.so_dien_thoai, dc.dia_chi_chi_tiet, dc.phuong_xa, dc.quan_huyen, dc.tinh_thanh
    FROM don_hang dh
    JOIN nguoi_dung nd ON dh.ma_nguoi_dung = nd.ma_nguoi_dung
    LEFT JOIN dia_chi dc ON dh.ma_dia_chi_giao_hang = dc.ma_dia_chi
    WHERE dh.ma_don_hang = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    echo '<div style="text-align: center; padding: 20px; color: #e74c3c;">Không tìm thấy đơn hàng</div>';
    exit;
}

// Lấy chi tiết sản phẩm trong đơn hàng
$details_sql = "SELECT ct.*, sp.ten_san_pham, ha.duong_dan_hinh_anh FROM chi_tiet_don_hang ct LEFT JOIN san_pham_thuoc sp ON ct.ma_san_pham = sp.ma_san_pham LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = 1 WHERE ct.ma_don_hang = ?";
$details_stmt = $conn->prepare($details_sql);
$details_stmt->bind_param("i", $order_id);
$details_stmt->execute();
$details = $details_stmt->get_result();

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}
function formatDate($date) {
    if (!$date || $date == '0000-00-00 00:00:00') return 'Chưa có';
    return date('d/m/Y H:i', strtotime($date));
}
function getOrderStatus($status) {
    $map = [
        'cho_xac_nhan' => ['Chờ xác nhận', '#f39c12'],
        'da_xac_nhan' => ['Đã xác nhận', '#3498db'],
        'dang_xu_ly' => ['Đang xử lý', '#9b59b6'],
        'dang_giao' => ['Đang giao hàng', '#e67e22'],
        'da_giao' => ['Đã giao hàng', '#27ae60'],
        'da_huy' => ['Đã hủy', '#e74c3c']
    ];
    return $map[$status] ?? [$status, '#95a5a6'];
}
$status_options = [
    'cho_xac_nhan' => 'Chờ xác nhận',
    'da_xac_nhan' => 'Đã xác nhận',
    'dang_xu_ly' => 'Đang xử lý',
    'dang_giao' => 'Đang giao hàng',
    'da_giao' => 'Đã giao hàng',
    'da_huy' => 'Đã hủy'
];
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?php echo htmlspecialchars($order['so_don_hang']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #2c3e50; }
        .order-detail-container { max-width: 950px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 32px; }
        h1 { font-size: 2rem; margin-bottom: 10px; }
        .order-meta { display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 24px; }
        .order-meta > div { min-width: 220px; }
        .order-status { font-weight: bold; padding: 6px 16px; border-radius: 16px; color: #fff; display: inline-block; }
        .order-products { margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 8px; border-bottom: 1px solid #ecf0f1; text-align: left; }
        th { background: #f8f9fa; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
        .total-row td { font-weight: bold; color: #27ae60; font-size: 1.1em; }
        .back-link { display: inline-block; margin-top: 30px; color: #3498db; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .order-section { margin-bottom: 30px; }
        .order-section h2 { font-size: 1.2em; margin-bottom: 10px; color: #2980b9; }
        .order-note { background: #f8f9fa; border-left: 4px solid #3498db; padding: 12px 18px; border-radius: 8px; margin-top: 15px; color: #2c3e50; }
        .status-form { margin-top: 10px; }
        .status-form select { padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc; }
        .status-form button { padding: 6px 16px; border-radius: 6px; background: #3498db; color: #fff; border: none; margin-left: 10px; cursor: pointer; }
        .status-form button:hover { background: #2980b9; }
        @media (max-width: 600px) { .order-detail-container { padding: 10px; } .order-meta { flex-direction: column; gap: 10px; } }
    </style>
</head>
<body>
<div class="order-detail-container">
    <h1>Đơn hàng #<?php echo htmlspecialchars($order['so_don_hang']); ?></h1>
    <div class="order-meta">
        <div>
            <strong>Khách hàng:</strong><br>
            <?php echo htmlspecialchars($order['ho_ten']); ?><br>
            <span style="color:#888;font-size:13px;">Email: <?php echo htmlspecialchars($order['email']); ?></span><br>
            <span style="color:#888;font-size:13px;">SĐT: <?php echo htmlspecialchars($order['so_dien_thoai']); ?></span>
        </div>
        <div>
            <strong>Địa chỉ giao hàng:</strong><br>
            <?php echo htmlspecialchars($order['dia_chi_chi_tiet']); ?><?php if($order['phuong_xa']||$order['quan_huyen']||$order['tinh_thanh']) echo ', '.htmlspecialchars($order['phuong_xa'].', '.$order['quan_huyen'].', '.$order['tinh_thanh']); ?>
        </div>
        <div>
            <strong>Ngày tạo:</strong><br>
            <?php echo formatDate($order['ngay_tao']); ?>
        </div>
        <div>
            <strong>Trạng thái:</strong><br>
            <?php list($txt, $color) = getOrderStatus($order['trang_thai_don_hang']); ?>
            <span class="order-status" style="background:<?php echo $color; ?>;"> <?php echo $txt; ?> </span>
            <form method="POST" class="status-form">
                <select name="new_status">
                    <?php foreach($status_options as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php if($order['trang_thai_don_hang']==$val) echo 'selected'; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="update_status">Cập nhật</button>
            </form>
        </div>
        <div>
            <strong>Thanh toán:</strong><br>
            <?php echo $order['trang_thai_thanh_toan'] == 'da_thanh_toan' ? '<span style="color:#27ae60;font-weight:bold;">Đã thanh toán</span>' : '<span style="color:#e67e22;font-weight:bold;">Chưa thanh toán</span>'; ?>
            <form method="POST" class="status-form" style="margin-top:5px;">
                <select name="new_payment">
                    <option value="chua_thanh_toan" <?php if($order['trang_thai_thanh_toan']=='chua_thanh_toan') echo 'selected'; ?>>Chưa thanh toán</option>
                    <option value="da_thanh_toan" <?php if($order['trang_thai_thanh_toan']=='da_thanh_toan') echo 'selected'; ?>>Đã thanh toán</option>
                </select>
                <button type="submit" name="update_payment">Cập nhật</button>
            </form>
        </div>
    </div>
    <div class="order-section">
        <h2>Danh sách sản phẩm</h2>
        <table>
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
            <?php $total = 0; foreach ($details as $item): $total += $item['thanh_tien']; ?>
                <tr>
                    <td><img class="product-img" src="../<?php echo $item['duong_dan_hinh_anh'] ?: 'images/products/no-image.png'; ?>" alt=""></td>
                    <td><?php echo htmlspecialchars($item['ten_san_pham'] ?: $item['ma_san_pham']); ?></td>
                    <td><?php echo $item['so_luong']; ?></td>
                    <td><?php echo formatPrice($item['don_gia']); ?></td>
                    <td><?php echo formatPrice($item['thanh_tien']); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Tổng cộng:</td>
                <td><?php echo formatPrice($total); ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php if (!empty($order['ghi_chu'])): ?>
    <div class="order-section">
        <h2>Ghi chú đơn hàng</h2>
        <div class="order-note"><?php echo nl2br(htmlspecialchars($order['ghi_chu'])); ?></div>
    </div>
    <?php endif; ?>
    <a href="orders.php" class="back-link"><i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng</a>
</div>
</body>
</html>
