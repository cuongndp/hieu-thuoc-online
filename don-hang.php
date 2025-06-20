<?php
session_start();
include 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

// Xử lý hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'] ?? 0;
    
    if ($order_id > 0) {
        // Kiểm tra đơn hàng có thuộc về user này không và có thể hủy không
        $check_sql = "SELECT trang_thai_don_hang FROM don_hang WHERE ma_don_hang = ? AND ma_nguoi_dung = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $order_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $order_data = $check_result->fetch_assoc();
            
            // Chỉ cho phép hủy đơn hàng ở trạng thái 'cho_xac_nhan'
            if ($order_data['trang_thai_don_hang'] === 'cho_xac_nhan') {
                $update_sql = "UPDATE don_hang SET trang_thai_don_hang = 'da_huy', ngay_cap_nhat = NOW() WHERE ma_don_hang = ? AND ma_nguoi_dung = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $order_id, $user_id);
                
                if ($update_stmt->execute()) {
                    // Redirect không có message để tránh URL encoding issues
                    header('Location: don-hang.php?cancelled=1');
                    exit;
                } else {
                    $error_message = "Có lỗi xảy ra khi hủy đơn hàng!";
                }
                $update_stmt->close();
            } else {
                $error_message = "Không thể hủy đơn hàng ở trạng thái này!";
            }
        } else {
            $error_message = "Không tìm thấy đơn hàng!";
        }
        $check_stmt->close();
    }
}

// Lấy thông báo từ URL (sau khi redirect)
if (isset($_GET['cancelled']) && $_GET['cancelled'] == '1') {
    $success_message = "Đơn hàng đã được hủy thành công!";
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Lấy danh sách đơn hàng của user - BỎ QUA CÁC ĐÔN HÀNG ĐÃ HỦY
$orders_sql = "SELECT 
                dh.ma_don_hang,
                dh.so_don_hang,
                dh.trang_thai_don_hang,
                dh.phuong_thuc_thanh_toan,
                dh.tong_tien_hang,
                dh.phi_van_chuyen,
                dh.tien_giam_gia,
                dh.tong_tien_thanh_toan,
                dh.ghi_chu,
                dh.ngay_giao_du_kien,
                dh.ngay_giao_thuc_te,
                dh.ngay_tao,
                COALESCE(dc.ten_nguoi_nhan, '') as ten_nguoi_nhan,
                COALESCE(dc.so_dien_thoai, '') as so_dien_thoai,
                COALESCE(dc.dia_chi_chi_tiet, '') as dia_chi_chi_tiet,
                COALESCE(dc.phuong_xa, '') as phuong_xa,
                COALESCE(dc.quan_huyen, '') as quan_huyen,
                COALESCE(dc.tinh_thanh, '') as tinh_thanh
            FROM don_hang dh
            LEFT JOIN dia_chi dc ON dh.ma_dia_chi_giao_hang = dc.ma_dia_chi
            WHERE dh.ma_nguoi_dung = ? 
            AND dh.trang_thai_don_hang != 'da_huy'
            ORDER BY dh.ngay_tao DESC";

$orders_stmt = $conn->prepare($orders_sql);
if (!$orders_stmt) {
    die("Lỗi prepare statement: " . $conn->error);
}

$orders_stmt->bind_param("i", $user_id);
if (!$orders_stmt->execute()) {
    die("Lỗi execute: " . $orders_stmt->error);
}

$orders_result = $orders_stmt->get_result();

// Lấy chi tiết từng đơn hàng
$orders = [];
while ($order = $orders_result->fetch_assoc()) {
    $order_id = $order['ma_don_hang'];
    
    // Lấy chi tiết sản phẩm của đơn hàng với thông tin hình ảnh
    $details_sql = "SELECT 
                        COALESCE(ct.ma_san_pham, 0) as ma_san_pham,
                        COALESCE(ct.ten_san_pham, 'Sản phẩm') as ten_san_pham,
                        COALESCE(ct.so_luong, 0) as so_luong,
                        COALESCE(ct.don_gia, 0) as don_gia,
                        COALESCE(ct.thanh_tien, 0) as thanh_tien,
                        COALESCE(ha.duong_dan_hinh_anh, '') as duong_dan_hinh_anh
                    FROM chi_tiet_don_hang ct
                    LEFT JOIN san_pham_thuoc sp ON ct.ma_san_pham = sp.ma_san_pham
                    LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = 1
                    WHERE ct.ma_don_hang = ?";
    
    $details_stmt = $conn->prepare($details_sql);
    if ($details_stmt) {
        $details_stmt->bind_param("i", $order_id);
        if ($details_stmt->execute()) {
            $details_result = $details_stmt->get_result();
            
            $order['items'] = [];
            while ($item = $details_result->fetch_assoc()) {
                $order['items'][] = $item;
            }
        }
        $details_stmt->close();
    }
    
    // Đảm bảo có ít nhất một mảng rỗng cho items
    if (!isset($order['items'])) {
        $order['items'] = [];
    }
    
    $orders[] = $order;
}
$orders_stmt->close();

// Function format giá tiền
function formatPrice($price) {
    $price = $price ?? 0;
    return number_format($price, 0, ',', '.');
}

// Function format ngày
function formatDate($date) {
    if (!$date || $date == '0000-00-00 00:00:00') return 'Chưa có';
    return date('d/m/Y H:i', strtotime($date));
}

// Function lấy trạng thái đơn hàng
function getOrderStatus($status) {
    $statuses = [
        'cho_xac_nhan' => ['text' => 'Chờ xác nhận', 'color' => '#f39c12', 'icon' => 'fas fa-clock'],
        'da_xac_nhan' => ['text' => 'Đã xác nhận', 'color' => '#3498db', 'icon' => 'fas fa-check'],
        'dang_xu_ly' => ['text' => 'Đang xử lý', 'color' => '#9b59b6', 'icon' => 'fas fa-cog'],
        'dang_giao' => ['text' => 'Đang giao hàng', 'color' => '#e67e22', 'icon' => 'fas fa-truck'],
        'da_giao' => ['text' => 'Đã giao hàng', 'color' => '#27ae60', 'icon' => 'fas fa-check-circle'],
        'da_huy' => ['text' => 'Đã hủy', 'color' => '#e74c3c', 'icon' => 'fas fa-times-circle']
    ];
    
    return $statuses[$status] ?? ['text' => ucfirst($status), 'color' => '#95a5a6', 'icon' => 'fas fa-question'];
}

// Function lấy phương thức thanh toán
function getPaymentMethod($method) {
    $methods = [
        'tien_mat' => ['text' => 'Tiền mặt (COD)', 'icon' => 'fas fa-money-bill-wave'],
        'chuyen_khoan' => ['text' => 'Chuyển khoản', 'icon' => 'fas fa-university'],
        'the_tin_dung' => ['text' => 'Thẻ tín dụng', 'icon' => 'fas fa-credit-card'],
        'vi_dien_tu' => ['text' => 'Ví điện tử', 'icon' => 'fab fa-google-wallet']
    ];
    
    return $methods[$method] ?? ['text' => ucfirst($method), 'icon' => 'fas fa-question'];
}

// Function lấy URL hình ảnh
function getImageUrl($image_path, $product_name = 'Product') {
    if (empty($image_path)) {
        return 'https://via.placeholder.com/60x60?text=' . urlencode($product_name);
    }
    
    // Kiểm tra xem đường dẫn đã có thư mục chưa
    if (strpos($image_path, 'images/') !== 0) {
        return 'images/products/' . $image_path;
    }
    
    return $image_path;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <style>
        /* CSS cho trạng thái đơn hàng */
        .status-cho_xac_nhan { color: #f39c12; }
        .status-da_xac_nhan { color: #3498db; }
        .status-dang_xu_ly { color: #9b59b6; }
        .status-dang_giao { color: #e67e22; }
        .status-da_giao { color: #27ae60; }
        .status-da_huy { color: #e74c3c; }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .page-header p {
            color: #666;
            margin: 0;
        }

        .orders-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 30px rgba(0,0,0,0.15);
        }

        .order-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px 30px;
            border-bottom: 1px solid #e0e6ed;
        }

        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .order-id {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .order-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 14px;
        }

        .order-total {
            font-weight: 600;
            font-size: 18px;
            color: #ff4757;
        }

        .order-body {
            padding: 30px;
        }

        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .image-container {
            width: 60px;
            height: 60px;
            flex-shrink: 0;
            border-radius: 8px;
            overflow: hidden;
            background-color: #f8f9fa;
            border: 1px solid #e0e6ed;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .item-info {
            font-size: 12px;
            color: #666;
        }

        .item-price {
            font-weight: 600;
            color: #333;
            text-align: right;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-row:last-child {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            padding-top: 10px;
            border-top: 1px solid #e0e6ed;
            margin-top: 10px;
            margin-bottom: 0;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .detail-section h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-section p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #4285f4;
            color: white;
        }

        .btn-primary:hover {
            background: #3367d6;
            transform: translateY(-1px);
            text-decoration: none;
            color: white;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #e0e6ed;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            text-decoration: none;
        }

        .btn-danger {
            background: #ff4757;
            color: white;
        }

        .btn-danger:hover {
            background: #ff3742;
        }

        .empty-orders {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .empty-orders i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-orders h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .empty-orders p {
            color: #666;
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        /* Confirm Modal CSS - Pure CSS Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal.show {
            display: block;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .modal p {
            color: #666;
            margin-bottom: 25px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-confirm {
            background: #ff4757;
            color: white;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .order-info {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .order-details {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .order-actions {
                flex-direction: column;
            }
            
            .order-body {
                padding: 20px;
            }
            
            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .modal-content {
                margin: 30% auto;
                padding: 20px;
            }

            .modal-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-shopping-bag"></i> Đơn hàng của tôi</h1>
            <p>Theo dõi và quản lý các đơn hàng của bạn</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Chưa có đơn hàng nào</h3>
                    <p>Bạn chưa có đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i>
                        Mua sắm ngay
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <?php 
                    $status_info = getOrderStatus($order['trang_thai_don_hang']); 
                    $payment_info = getPaymentMethod($order['phuong_thuc_thanh_toan']); 
                    ?>
                    
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <div>
                                    <div class="order-id">Đơn hàng #<?php echo htmlspecialchars($order['so_don_hang']); ?></div>
                                    <div class="order-date">Đặt ngày: <?php echo formatDate($order['ngay_tao']); ?></div>
                                </div>
                                <div>
                                    <div class="order-status status-<?php echo $order['trang_thai_don_hang']; ?>">
                                        <i class="<?php echo getOrderStatus($order['trang_thai_don_hang'])['icon']; ?>"></i>
                                        <?php echo getOrderStatus($order['trang_thai_don_hang'])['text']; ?>
                                    </div>
                                    <?php if (!empty($order['ngay_giao_du_kien'])): ?>
                                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                            Dự kiến giao: <?php echo formatDate($order['ngay_giao_du_kien']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                                        <i class="<?php echo $payment_info['icon']; ?>"></i>
                                        <?php echo $payment_info['text']; ?>
                                    </div>
                                    <div style="font-size: 12px; color: #666;">
                                        <?php echo count($order['items']); ?> sản phẩm
                                    </div>
                                </div>
                                <div class="order-total">
                                    <?php echo formatPrice($order['tong_tien_thanh_toan']); ?>đ
                                </div>
                            </div>
                        </div>

                        <div class="order-body">
                            <!-- Danh sách sản phẩm -->
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <div class="image-container">
                                            <img src="<?php echo getImageUrl($item['duong_dan_hinh_anh'] ?? '', $item['ten_san_pham']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>" 
                                                 class="item-image"
                                                 loading="lazy"
                                                 onerror="this.src='https://via.placeholder.com/60x60?text=Product'">
                                        </div>
                                        <div class="item-details">
                                            <div class="item-name"><?php echo htmlspecialchars($item['ten_san_pham']); ?></div>
                                            <div class="item-info">
                                                Số lượng: <?php echo $item['so_luong']; ?> | 
                                                Đơn giá: <?php echo formatPrice($item['don_gia']); ?>đ
                                            </div>
                                        </div>
                                        <div class="item-price">
                                            <?php echo formatPrice($item['thanh_tien']); ?>đ
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Tóm tắt giá -->
                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Tạm tính:</span>
                                    <span><?php echo formatPrice($order['tong_tien_hang']); ?>đ</span>
                                </div>
                                <div class="summary-row">
                                    <span>Phí vận chuyển:</span>
                                    <span>
                                        <?php if ($order['phi_van_chuyen'] > 0): ?>
                                            <?php echo formatPrice($order['phi_van_chuyen']); ?>đ
                                        <?php else: ?>
                                            <span style="color: #27ae60;">Miễn phí</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($order['tien_giam_gia'] > 0): ?>
                                    <div class="summary-row">
                                        <span>Giảm giá:</span>
                                        <span style="color: #27ae60;">-<?php echo formatPrice($order['tien_giam_gia']); ?>đ</span>
                                    </div>
                                <?php endif; ?>
                                <div class="summary-row">
                                    <span>Tổng cộng:</span>
                                    <span style="color: #ff4757;"><?php echo formatPrice($order['tong_tien_thanh_toan']); ?>đ</span>
                                </div>
                            </div>

                            <!-- Chi tiết đơn hàng -->
                            <div class="order-details">
                                <div class="detail-section">
                                    <h4><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</h4>
                                    <p><strong><?php echo htmlspecialchars($order['ten_nguoi_nhan']); ?></strong></p>
                                    <p><?php echo htmlspecialchars($order['so_dien_thoai']); ?></p>
                                    <p><?php echo htmlspecialchars($order['dia_chi_chi_tiet']); ?></p>
                                    <p><?php echo htmlspecialchars($order['phuong_xa'] . ', ' . $order['quan_huyen'] . ', ' . $order['tinh_thanh']); ?></p>
                                </div>
                                <div class="detail-section">
                                    <h4><i class="fas fa-info-circle"></i> Thông tin thêm</h4>
                                    <?php if (!empty($order['ghi_chu'])): ?>
                                        <p><strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['ghi_chu']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($order['ngay_giao_thuc_te'])): ?>
                                        <p><strong>Đã giao lúc:</strong> <?php echo formatDate($order['ngay_giao_thuc_te']); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Ngày đặt:</strong> <?php echo formatDate($order['ngay_tao']); ?></p>
                                </div>
                            </div>

                            <!-- Hành động -->
                            <div class="order-actions">
                                <?php if ($order['trang_thai_don_hang'] == 'cho_xac_nhan'): ?>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                                        <input type="hidden" name="order_id" value="<?= $order['ma_don_hang'] ?>">
                                        <button type="submit" name="cancel_order" class="btn btn-danger">
                                            <i class="fas fa-times"></i>
                                            Hủy đơn hàng
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($order['trang_thai_don_hang'] == 'da_giao'): ?>
                                    <a href="reorder.php?order_id=<?= $order['ma_don_hang'] ?>" class="btn btn-primary">
                                        <i class="fas fa-redo"></i>
                                        Mua lại
                                    </a>
                                    <a href="review.php?order_id=<?= $order['ma_don_hang'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-star"></i>
                                        Đánh giá
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>VitaMeds</h3>
                    <p>Hiệu thuốc trực tuyến uy tín</p>
                </div>
                <div class="footer-section">
                    <h3>Liên hệ</h3>
                    <p><i class="fas fa-phone"></i> 1900-1234</p>
                    <p><i class="fas fa-envelope"></i> info@vitameds.com</p>
                </div>
                <div class="footer-section">
                    <h3>Hỗ trợ</h3>
                    <p><a href="orders.php">Đơn hàng của tôi</a></p>
                    <p><a href="contact.php">Liên hệ</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- AUTO-HIDE ALERTS - Pure CSS Animation -->
    <style>
        .alert {
            animation: slideIn 0.5s ease-in-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Auto-hide alerts after 5 seconds */
        .alert {
            animation: slideIn 0.5s ease-in-out, slideOut 0.5s ease-in-out 4.5s forwards;
        }
        
        @keyframes slideOut {
            to {
                opacity: 0;
                transform: translateY(-20px);
                height: 0;
                padding: 0;
                margin: 0;
                overflow: hidden;
            }
        }
    </style>
</body>
</html>