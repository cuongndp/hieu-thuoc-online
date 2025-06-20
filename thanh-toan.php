<?php
session_start();
include 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

// Lấy giỏ hàng từ database 
$cart_sql = "SELECT ma_san_pham, so_luong FROM gio_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_cap_nhat DESC";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

// Định nghĩa thông tin sản phẩm mẫu
$product_info = [
    1 => ['name' => 'Paracetamol 500mg', 'price' => 25000],
    2 => ['name' => 'Vitamin C 1000mg', 'price' => 120000],
    3 => ['name' => 'Amoxicillin 250mg', 'price' => 45000],
    4 => ['name' => 'Omega-3 Fish Oil', 'price' => 180000],
    5 => ['name' => 'Calcium + D3', 'price' => 95000],
    6 => ['name' => 'Glucosamine 1500mg', 'price' => 320000],
];

$cart_items = [];

// Lấy danh sách sản phẩm trong giỏ hàng
while ($row = $cart_result->fetch_assoc()) {
    $product_id = $row['ma_san_pham'];
    $quantity = $row['so_luong'];
    
    // Lấy thông tin sản phẩm từ database
    $product_sql = "SELECT 
                        sp.ten_san_pham,
                        sp.gia_ban,
                        sp.gia_khuyen_mai,
                        ha.duong_dan_hinh_anh
                    FROM san_pham_thuoc sp
                    LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = 1
                    WHERE sp.ma_san_pham = ?";
    
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $product_data = $product_result->fetch_assoc();
    $product_stmt->close();
    
    // Sử dụng thông tin từ database
    $product_name = $product_data['ten_san_pham'] ?? "Sản phẩm " . $product_id;
    $product_price = $product_data['gia_khuyen_mai'] ?? $product_data['gia_ban'] ?? 100000;
    $product_image = $product_data['duong_dan_hinh_anh'] ?? '';
    
    $cart_items[] = [
        'ma_san_pham' => $product_id,
        'ten_san_pham' => $product_name,
        'gia_ban' => $product_price,
        'so_luong' => $quantity,
        'thanh_tien' => $product_price * $quantity,
        'duong_dan_hinh_anh' => $product_image
    ];
}

// Tính tổng tiền
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['thanh_tien'];
}

$shipping = $subtotal >= 500000 ? 0 : 30000;
$total = $subtotal + $shipping;

// Lấy thông tin user để điền sẵn form
$user_info = null;
if ($user_id) {
    $sql = "SELECT ho_ten, email, so_dien_thoai, dia_chi FROM nguoi_dung WHERE ma_nguoi_dung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
    $stmt->close();
}

// Xử lý đặt hàng
$order_success = false;
$success_order_id = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        // Validate dữ liệu
        $required_fields = ['ho_ten', 'so_dien_thoai', 'tinh_thanh', 'quan_huyen', 'phuong_xa', 'dia_chi_chi_tiet', 'phuong_thuc_thanh_toan'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc: " . $field);
            }
        }

        // Validate email nếu có
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email không hợp lệ");
        }

        // Validate số điện thoại
        if (!preg_match('/^[0-9]{10,11}$/', $_POST['so_dien_thoai'])) {
            throw new Exception("Số điện thoại không hợp lệ");
        }

        if (empty($cart_items)) {
            throw new Exception("Giỏ hàng trống!");
        }

        // Bắt đầu transaction
        $conn->autocommit(FALSE);

        // Tạo mã đơn hàng
        $ma_don_hang = 'DH' . date('YmdHis') . rand(100, 999);

        // 1. Tạo địa chỉ giao hàng trước
        $dia_chi_day_du = $_POST['dia_chi_chi_tiet'] . ', ' . $_POST['phuong_xa'] . ', ' . $_POST['quan_huyen'] . ', ' . $_POST['tinh_thanh'];
        
        $sql = "INSERT INTO dia_chi (
                    ma_nguoi_dung,
                    loai_dia_chi,
                    ten_nguoi_nhan,
                    so_dien_thoai,
                    dia_chi_chi_tiet,
                    phuong_xa,
                    quan_huyen,
                    tinh_thanh,
                    ma_buu_dien,
                    la_dia_chi_mac_dinh,
                    ngay_tao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $loai_dia_chi = 'nha_rieng'; 
        $ma_buu_dien = $_POST['ma_buu_dien'] ?? '';
        $la_mac_dinh = 0;
        
        $stmt->bind_param("issssssssi", 
            $user_id,
            $loai_dia_chi,
            $_POST['ho_ten'],
            $_POST['so_dien_thoai'],
            $dia_chi_day_du,
            $_POST['phuong_xa'],
            $_POST['quan_huyen'],
            $_POST['tinh_thanh'],
            $ma_buu_dien,
            $la_mac_dinh
        );

        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi lưu địa chỉ giao hàng: " . $stmt->error);
        }
        
        // Lấy ID địa chỉ vừa tạo
        $ma_dia_chi = $conn->insert_id;
        $stmt->close();

        // 2. Tạo đơn hàng với địa chỉ giao hàng
        $sql = "INSERT INTO don_hang (
                    ma_nguoi_dung, 
                    so_don_hang,
                    trang_thai_don_hang,
                    phuong_thuc_thanh_toan,
                    tong_tien_hang,
                    phi_van_chuyen,
                    tien_giam_gia,
                    tong_tien_thanh_toan,
                    ma_dia_chi_giao_hang,
                    can_don_thuoc,
                    hinh_anh_don_thuoc,
                    ghi_chu,
                    ngay_giao_du_kien,
                    ngay_tao,
                    ngay_cap_nhat
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 3 DAY), NOW(), NOW())";

        $stmt = $conn->prepare($sql);
        $trang_thai = 'cho_xac_nhan';
        $tien_giam_gia = 0;
        $can_don_thuoc = 0;
        $hinh_anh_don_thuoc = '';
        $ghi_chu = $_POST['ghi_chu'] ?? '';
        
        // 12 tham số: i s s s d d d d i i s s
        $stmt->bind_param("isssddddiiss", 
            $user_id,                          // 1. i
            $ma_don_hang,                      // 2. s  
            $trang_thai,                       // 3. s
            $_POST['phuong_thuc_thanh_toan'],  // 4. s
            $subtotal,                         // 5. d
            $shipping,                         // 6. d
            $tien_giam_gia,                    // 7. d
            $total,                            // 8. d
            $ma_dia_chi,                       // 9. i
            $can_don_thuoc,                    // 10. i
            $hinh_anh_don_thuoc,               // 11. s
            $ghi_chu                           // 12. s
        );

        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi tạo đơn hàng: " . $stmt->error);
        }
        
        // Lấy ID đơn hàng vừa tạo (ma_don_hang auto increment)
        $ma_don_hang_id = $conn->insert_id;
        $stmt->close();

        // 3. Thêm chi tiết đơn hàng
        foreach ($cart_items as $item) {
            $sql = "INSERT INTO chi_tiet_don_hang (
                        ma_don_hang,
                        ma_san_pham,
                        ten_san_pham,
                        so_luong,
                        don_gia,
                        thanh_tien,
                        ngay_tao
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisidd",
                $ma_don_hang_id,  // Sử dụng ID số thay vì mã string
                $item['ma_san_pham'],
                $item['ten_san_pham'],
                $item['so_luong'],
                $item['gia_ban'],
                $item['thanh_tien']
            );

            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi lưu chi tiết đơn hàng: " . $stmt->error);
            }
            $stmt->close();
        }

        // 4. Xóa giỏ hàng
        $sql = "DELETE FROM gio_hang WHERE ma_nguoi_dung = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();
        $conn->autocommit(TRUE);
        
        $order_success = true;
        $success_order_id = $ma_don_hang; // Hiển thị mã string cho user

        // Reset giỏ hàng để không hiển thị nữa
        $cart_items = [];

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $conn->autocommit(TRUE);
        $error_message = $e->getMessage();
    }
}

// Function format giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.');
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
    <title>Thanh toán - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <style>
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .breadcrumb {
            margin-bottom: 30px;
            color: #666;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .breadcrumb a {
            color: #4285f4;
            text-decoration: none;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #4285f4;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4285f4;
        }

        .required {
            color: #ff4757;
        }

        .payment-methods {
            display: grid;
            gap: 15px;
        }

        .payment-option {
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-option:hover {
            border-color: #4285f4;
        }

        .payment-option.selected {
            border-color: #4285f4;
            background: #f0f2ff;
        }

        .payment-option input[type="radio"] {
            width: auto;
        }

        .payment-icon {
            font-size: 24px;
            width: 40px;
        }

        .cod-icon {
            color: #2ed573;
        }

        .card-icon {
            color: #3742fa;
        }

        .bank-icon {
            color: #ff6b6b;
        }

        .momo-icon {
            color: #d63384;
        }

        /* Order Summary */
        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            transition: none;
            display: block;
        }

        .order-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            min-height: 90px;
        }

        .item-details {
            flex: 1;
            min-width: 0;
        }

        .item-details h4 {
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }

        .item-details p {
            font-size: 12px;
            color: #666;
        }

        .item-price {
            margin-left: auto;
            font-weight: 600;
            color: #ff4757;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 18px;
            color: #333;
            padding-top: 20px;
            margin-top: 10px;
            border-top: 2px solid #f0f0f0;
        }

        .place-order-btn {
            width: 100%;
            background: linear-gradient(135deg, #4285f4, #6c5ce7);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s;
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
        }

        .security-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
            margin-top: 15px;
        }

        .security-info i {
            color: #2ed573;
        }

        .success-message,
        .error-message {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .empty-cart {
            text-align: center;
            padding: 50px;
            color: #666;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .empty-cart i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: #4285f4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 10px 5px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #3367d6;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="breadcrumb">
            <a href="index.php">Trang chủ</a> > <a href="cart.php">Giỏ hàng</a> > Thanh toán
        </div>

        <?php if ($order_success): ?>
        <div class="success-message">
            <i class="fas fa-check-circle" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
            <h3>Đặt hàng thành công!</h3>
            <p>Mã đơn hàng của bạn: <strong>
                    <?= $success_order_id ?>
                </strong></p>
            <p>Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất để xác nhận đơn hàng.</p>
            <a href="index.php" class="btn-primary">
                <i class="fas fa-home"></i> Về trang chủ
            </a>
            <a href="orders.php" class="btn-primary" style="background: #27ae60;">
                <i class="fas fa-list"></i> Xem đơn hàng
            </a>
        </div>
        <?php elseif (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Giỏ hàng trống</h3>
            <p>Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
            <a href="index.php" class="btn-primary">
                <i class="fas fa-shopping-bag"></i> Mua sắm ngay
            </a>
        </div>
        <?php else: ?>

        <?php if ($error_message): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="checkout-container">
            <!-- Form thông tin giao hàng -->
            <div class="checkout-form">
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-truck"></i>
                        Thông tin giao hàng
                    </h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Họ và tên <span class="required">*</span></label>
                            <input type="text" name="ho_ten" required
                                value="<?= htmlspecialchars($_POST['ho_ten'] ?? $user_info['ho_ten'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại <span class="required">*</span></label>
                            <input type="tel" name="so_dien_thoai" required
                                value="<?= htmlspecialchars($_POST['so_dien_thoai'] ?? $user_info['so_dien_thoai'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? $user_info['email'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Tỉnh/Thành phố <span class="required">*</span></label>
                            <select name="tinh_thanh" id="tinh_thanh" required>
                                <option value="">Chọn tỉnh/thành phố</option>
                                <option value="TP. Hồ Chí Minh" <?=($_POST['tinh_thanh'] ?? '') == 'TP. Hồ Chí Minh' ? 'selected' : ''?>>TP. Hồ Chí Minh</option>
                                <option value="Hà Nội" <?=($_POST['tinh_thanh'] ?? '') == 'Hà Nội' ? 'selected' : ''?>>Hà Nội</option>
                                <option value="Đà Nẵng" <?=($_POST['tinh_thanh'] ?? '') == 'Đà Nẵng' ? 'selected' : ''?>>Đà Nẵng</option>
                                <option value="Cần Thơ" <?=($_POST['tinh_thanh'] ?? '') == 'Cần Thơ' ? 'selected' : ''?>>Cần Thơ</option>
                                <option value="Hải Phòng" <?=($_POST['tinh_thanh'] ?? '') == 'Hải Phòng' ? 'selected' : ''?>>Hải Phòng</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quận/Huyện <span class="required">*</span></label>
                            <select name="quan_huyen" id="quan_huyen" required>
                                <option value="">Chọn quận/huyện</option>
                                <!-- Sẽ được cập nhật bởi JavaScript -->
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phường/Xã <span class="required">*</span></label>
                            <select name="phuong_xa" id="phuong_xa" required>
                                <option value="">Chọn phường/xã</option>
                                <!-- Sẽ được cập nhật bởi JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mã bưu điện</label>
                            <input type="text" name="ma_buu_dien" id="ma_buu_dien"
                                value="<?= htmlspecialchars($_POST['ma_buu_dien'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ cụ thể <span class="required">*</span></label>
                        <textarea name="dia_chi_chi_tiet" rows="3" required
                            placeholder="Số nhà, tên đường, tòa nhà..."><?= htmlspecialchars($_POST['dia_chi_chi_tiet'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú đơn hàng</label>
                        <textarea name="ghi_chu" rows="3"
                            placeholder="Ghi chú về đơn hàng..."><?= htmlspecialchars($_POST['ghi_chu'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Phương thức thanh toán -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-credit-card"></i>
                        Phương thức thanh toán
                    </h2>

                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="phuong_thuc_thanh_toan" value="tien_mat" required
                                <?=($_POST['phuong_thuc_thanh_toan'] ?? 'tien_mat' )=='tien_mat' ? 'checked' : '' ?>>
                            <i class="fas fa-money-bill-wave payment-icon cod-icon"></i>
                            <div>
                                <strong>Thanh toán khi nhận hàng (COD)</strong>
                                <p>Thanh toán bằng tiền mặt khi nhận hàng</p>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="phuong_thuc_thanh_toan" value="chuyen_khoan"
                                <?=($_POST['phuong_thuc_thanh_toan'] ?? '' )=='chuyen_khoan' ? 'checked' : '' ?>>
                            <i class="fas fa-university payment-icon bank-icon"></i>
                            <div>
                                <strong>Chuyển khoản ngân hàng</strong>
                                <p>Chuyển khoản qua Internet Banking</p>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="phuong_thuc_thanh_toan" value="the_tin_dung"
                                <?=($_POST['phuong_thuc_thanh_toan'] ?? '' )=='the_tin_dung' ? 'checked' : '' ?>>
                            <i class="fas fa-credit-card payment-icon card-icon"></i>
                            <div>
                                <strong>Thẻ tín dụng/Ghi nợ</strong>
                                <p>Visa, MasterCard, JCB, American Express</p>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="phuong_thuc_thanh_toan" value="vi_dien_tu"
                                <?=($_POST['phuong_thuc_thanh_toan'] ?? '' )=='vi_dien_tu' ? 'checked' : '' ?>>
                            <i class="fab fa-google-wallet payment-icon momo-icon"></i>
                            <div>
                                <strong>Ví điện tử</strong>
                                <p>MoMo, ZaloPay, ViettelPay</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="order-summary">
                <h2 class="summary-title">
                    <i class="fas fa-receipt"></i>
                    Đơn hàng của bạn (
                    <?= count($cart_items) ?> sản phẩm)
                </h2>

                <!-- Danh sách sản phẩm -->
                <?php foreach ($cart_items as $item): ?>
                <div class="order-item">
                    <div class="image-container">
                        <img src="<?php echo getImageUrl($item['duong_dan_hinh_anh'] ?? '', $item['ten_san_pham']); ?>"
                            alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>" class="item-image"
                            loading="lazy">
                    </div>
                    <div class="item-details">
                        <h4>
                            <?= htmlspecialchars($item['ten_san_pham']) ?>
                        </h4>
                        <p>Số lượng:
                            <?= $item['so_luong'] ?>
                        </p>
                        <p style="font-size: 12px; color: #999;">
                            Đơn giá:
                            <?= formatPrice($item['gia_ban']) ?>đ
                        </p>
                    </div>
                    <div class="item-price">
                        <?= formatPrice($item['thanh_tien']) ?>đ
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Tính toán giá -->
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span>
                        <?= formatPrice($subtotal) ?>đ
                    </span>
                </div>

                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <span>
                        <?php if ($shipping > 0): ?>
                        <?= formatPrice($shipping) ?>đ
                        <?php else: ?>
                        <span style="color: #2ed573;">Miễn phí</span>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($shipping == 0 && $subtotal >= 500000): ?>
                <div class="summary-row" style="font-size: 12px; color: #2ed573;">
                    <span>🎉 Bạn được miễn phí vận chuyển!</span>
                    <span></span>
                </div>
                <?php elseif ($subtotal > 0 && $subtotal < 500000): ?>
                <div class="summary-row" style="font-size: 12px; color: #666;">
                    <span>Mua thêm
                        <?= formatPrice(500000 - $subtotal) ?>đ để được miễn phí ship
                    </span>
                    <span></span>
                </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Tổng cộng:</span>
                    <span style="color: #ff4757; font-size: 20px;">
                        <?= formatPrice($total) ?>đ
                    </span>
                </div>

                <button type="submit" name="place_order" class="place-order-btn">
                    <i class="fas fa-shopping-bag"></i>
                    Đặt hàng ngay -
                    <?= formatPrice($total) ?>đ
                </button>

                <div class="security-info">
                    <i class="fas fa-shield-alt"></i>
                    <span>Thông tin của bạn được bảo mật 100%</span>
                </div>

                <!-- Thông tin thêm -->
                <div
                    style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 12px; color: #666;">
                    <p><i class="fas fa-info-circle"></i> <strong>Lưu ý:</strong></p>
                    <ul style="margin: 5px 0 0 20px; padding: 0;">
                        <li>Đơn hàng sẽ được xác nhận trong vòng 2-4 giờ</li>
                        <li>Giao hàng trong 1-3 ngày làm việc</li>
                        <li>Hỗ trợ đổi trả trong 7 ngày</li>
                    </ul>
                </div>
            </div>
        </form>

        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dữ liệu địa chỉ đơn giản
            const provinces = {
                "TP. Hồ Chí Minh": ["Quận 1", "Quận 2", "Quận 3", "Quận 7", "Quận Bình Thạnh", "Quận Tân Bình", "Quận Gò Vấp", "Quận Phú Nhuận", "Quận Thủ Đức"],
                "Hà Nội": ["Quận Ba Đình", "Quận Hoàn Kiếm", "Quận Hai Bà Trưng", "Quận Đống Đa", "Quận Cầu Giấy", "Quận Thanh Xuân", "Quận Hoàng Mai", "Quận Long Biên"],
                "Đà Nẵng": ["Quận Hải Châu", "Quận Thanh Khê", "Quận Sơn Trà", "Quận Ngũ Hành Sơn", "Quận Liên Chiểu", "Quận Cẩm Lệ"],
                "Cần Thơ": ["Quận Ninh Kiều", "Quận Bình Thủy", "Quận Cái Răng", "Quận Ô Môn", "Quận Thốt Nốt"],
                "Hải Phòng": ["Quận Hồng Bàng", "Quận Lê Chân", "Quận Ngô Quyền", "Quận Kiến An", "Quận Hải An"]
            };
            
            const wards = {
                // TP.HCM
                "Quận 1": ["Phường Bến Nghé", "Phường Bến Thành", "Phường Cầu Kho", "Phường Tân Định"],
                "Quận 2": ["Phường An Khánh", "Phường An Phú", "Phường Bình An", "Phường Thảo Điền"],
                "Quận 3": ["Phường 1", "Phường 2", "Phường 3", "Phường 4", "Phường 5"],
                "Quận 7": ["Phường Tân Thuận Đông", "Phường Tân Thuận Tây", "Phường Tân Kiểng", "Phường Tân Quy"],
                "Quận Bình Thạnh": ["Phường 1", "Phường 2", "Phường 3", "Phường 5", "Phường 6"],
                
                // Hà Nội
                "Quận Ba Đình": ["Phường Cống Vị", "Phường Điện Biên", "Phường Giảng Võ", "Phường Kim Mã"],
                "Quận Hoàn Kiếm": ["Phường Hàng Bạc", "Phường Hàng Bài", "Phường Hàng Đào", "Phường Tràng Tiền"],
                "Quận Hai Bà Trưng": ["Phường Bạch Đằng", "Phường Bách Khoa", "Phường Minh Khai", "Phường Nguyễn Du"],
                "Quận Đống Đa": ["Phường Cát Linh", "Phường Láng Hạ", "Phường Quang Trung", "Phường Văn Miếu"],
                
                // Đà Nẵng
                "Quận Hải Châu": ["Phường Hải Châu I", "Phường Hải Châu II", "Phường Thanh Bình", "Phường Phước Ninh"],
                "Quận Thanh Khê": ["Phường Thanh Khê Đông", "Phường Thanh Khê Tây", "Phường Xuân Hà", "Phường Chính Gián"],
                
                // Cần Thơ
                "Quận Ninh Kiều": ["Phường An Bình", "Phường An Cư", "Phường Cái Khế", "Phường Xuân Khánh"],
                
                // Hải Phòng
                "Quận Hồng Bàng": ["Phường Hoàng Văn Thụ", "Phường Hùng Vương", "Phường Sở Dầu", "Phường Trại Cau"]
            };

            const postalCodes = {
                "TP. Hồ Chí Minh": "70000",
                "Hà Nội": "10000", 
                "Đà Nẵng": "50000",
                "Cần Thơ": "94000",
                "Hải Phòng": "18000"
            };
            
            const provinceSelect = document.getElementById('tinh_thanh');
            const districtSelect = document.getElementById('quan_huyen');
            const wardSelect = document.getElementById('phuong_xa');
            const postalInput = document.getElementById('ma_buu_dien');
            
            // Lưu giá trị đã chọn
            const selectedProvince = "<?= htmlspecialchars($_POST['tinh_thanh'] ?? '') ?>";
            const selectedDistrict = "<?= htmlspecialchars($_POST['quan_huyen'] ?? '') ?>";
            const selectedWard = "<?= htmlspecialchars($_POST['phuong_xa'] ?? '') ?>";
            
            // Sự kiện khi chọn tỉnh
            provinceSelect.addEventListener('change', function() {
                const province = this.value;
                
                // Reset quận/huyện và phường/xã
                districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
                wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
                postalInput.value = '';
                
                if (province && provinces[province]) {
                    // Thêm quận/huyện
                    provinces[province].forEach(district => {
                        const option = document.createElement('option');
                        option.value = district;
                        option.textContent = district;
                        districtSelect.appendChild(option);
                    });
                    districtSelect.disabled = false;
                    
                    // Set mã bưu điện
                    if (postalCodes[province]) {
                        postalInput.value = postalCodes[province];
                    }
                } else {
                    districtSelect.disabled = true;
                }
                wardSelect.disabled = true;
            });
            
            // Sự kiện khi chọn quận
            districtSelect.addEventListener('change', function() {
                const district = this.value;
                
                // Reset phường/xã
                wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
                
                if (district) {
                    if (wards[district]) {
                        // Thêm phường/xã từ dữ liệu có sẵn
                        wards[district].forEach(ward => {
                            const option = document.createElement('option');
                            option.value = ward;
                            option.textContent = ward;
                            wardSelect.appendChild(option);
                        });
                    } else {
                        // Thêm phường/xã mặc định
                        for (let i = 1; i <= 10; i++) {
                            const option = document.createElement('option');
                            option.value = "Phường " + i;
                            option.textContent = "Phường " + i;
                            wardSelect.appendChild(option);
                        }
                    }
                    wardSelect.disabled = false;
                } else {
                    wardSelect.disabled = true;
                }
            });
            
            // Khôi phục giá trị đã chọn (nếu có lỗi validation)
            if (selectedProvince) {
                provinceSelect.value = selectedProvince;
                provinceSelect.dispatchEvent(new Event('change'));
                
                setTimeout(() => {
                    if (selectedDistrict) {
                        districtSelect.value = selectedDistrict;
                        districtSelect.dispatchEvent(new Event('change'));
                        
                        setTimeout(() => {
                            if (selectedWard) {
                                wardSelect.value = selectedWard;
                            }
                        }, 100);
                    }
                }, 100);
            }
            
            // Xử lý chọn phương thức thanh toán
            document.querySelectorAll('input[name="phuong_thuc_thanh_toan"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    document.querySelectorAll('.payment-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    this.closest('.payment-option').classList.add('selected');
                });
            });

            // Set selected cho option được chọn khi load trang
            const checkedRadio = document.querySelector('input[name="phuong_thuc_thanh_toan"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('.payment-option').classList.add('selected');
            }

            // Validation trước khi submit
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let hasError = false;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.style.borderColor = '#ff4757';
                            hasError = true;
                        } else {
                            field.style.borderColor = '#e0e6ed';
                        }
                    });

                    // Validate email
                    const emailField = form.querySelector('input[type="email"]');
                    if (emailField && emailField.value) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(emailField.value)) {
                            emailField.style.borderColor = '#ff4757';
                            hasError = true;
                        }
                    }

                    // Validate phone
                    const phoneField = form.querySelector('input[type="tel"]');
                    if (phoneField && phoneField.value) {
                        const phoneRegex = /^[0-9]{10,11}$/;
                        if (!phoneRegex.test(phoneField.value.replace(/\s/g, ''))) {
                            phoneField.style.borderColor = '#ff4757';
                            hasError = true;
                        }
                    }

                    if (hasError) {
                        e.preventDefault();
                        alert('Vui lòng kiểm tra lại thông tin đã nhập!');
                        return false;
                    }

                    // Confirm trước khi đặt hàng
                    if (!confirm('Bạn có chắc chắn muốn đặt hàng với tổng tiền <?= formatPrice($total) ?>đ?')) {
                        e.preventDefault();
                        return false;
                    }

                    // Show loading
                    const submitBtn = form.querySelector('.place-order-btn');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                    submitBtn.disabled = true;
                });
            }

            // Format phone number input
            const phoneInput = document.querySelector('input[type="tel"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function () {
                    // Remove non-digits
                    let value = this.value.replace(/\D/g, '');
                    // Limit to 11 digits
                    value = value.substring(0, 11);
                    this.value = value;
                });
            }
        });
    </script>
</body>

</html>