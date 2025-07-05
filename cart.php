<?php
include 'config/dual_session.php';
include 'config/database.php';

// Kiểm tra đăng nhập
require_user_login();

$user_id = get_user_id();

// Xử lý add to cart từ header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = 1;
    
    if ($product_id > 0) {
        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        $check_sql = "SELECT so_luong FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Nếu đã có, tăng số lượng
            $row = $result->fetch_assoc();
            $new_quantity = $row['so_luong'] + $quantity;
            
            $update_sql = "UPDATE gio_hang SET so_luong = ?, ngay_cap_nhat = NOW() WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
            $update_stmt->execute();
        } else {
            // Nếu chưa có, thêm mới
            $insert_sql = "INSERT INTO gio_hang (ma_nguoi_dung, ma_san_pham, so_luong, ngay_them) VALUES (?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
            $insert_stmt->execute();
        }
    }
    
    // Redirect để tránh resubmit
    header('Location: cart.php');
    exit;
}

// Xử lý các action từ form giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_quantity':
            $product_id = $_POST['product_id'] ?? 0;
            $quantity = max(1, intval($_POST['quantity'] ?? 1));
            
            if ($product_id > 0) {
                $update_sql = "UPDATE gio_hang SET so_luong = ?, ngay_cap_nhat = NOW() WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $quantity, $user_id, $product_id);
                $update_stmt->execute();
            }
            break;
            
        case 'remove_item':
            $product_id = $_POST['product_id'] ?? 0;
            if ($product_id > 0) {
                $delete_sql = "DELETE FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("ii", $user_id, $product_id);
                $delete_stmt->execute();
            }
            break;
            
        case 'clear_cart':
            $clear_sql = "DELETE FROM gio_hang WHERE ma_nguoi_dung = ?";
            $clear_stmt = $conn->prepare($clear_sql);
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            break;
            
        case 'checkout':
            header('Location: thanh-toan.php');
            exit;
            break;
    }
    
    // Redirect để tránh resubmit
    header('Location: cart.php');
    exit;
}

// Debug thông tin
echo "<!-- Debug: User ID = " . $user_id . " -->";

// Lấy giỏ hàng từ database
$cart_sql = "SELECT ma_san_pham, so_luong FROM gio_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_them DESC";

$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

echo "<!-- Debug: Số sản phẩm trong giỏ = " . $cart_result->num_rows . " -->";

// Định nghĩa thông tin sản phẩm mẫu
$product_info = [
    1 => ['name' => 'Paracetamol 500mg', 'price' => 25000],
    2 => ['name' => 'Vitamin C 1000mg', 'price' => 120000],
    3 => ['name' => 'Amoxicillin 250mg', 'price' => 45000],
    4 => ['name' => 'Omega-3 Fish Oil', 'price' => 180000],
    5 => ['name' => 'Calcium + D3', 'price' => 95000],
    6 => ['name' => 'Glucosamine 1500mg', 'price' => 320000],
];

$cart = [];

// Lấy danh sách sản phẩm trong giỏ hàng với thông tin chi tiết từ database
while ($row = $cart_result->fetch_assoc()) {
    $product_id = $row['ma_san_pham'];
    $quantity = $row['so_luong'];
    
    echo "<!-- Debug: Sản phẩm ID = " . $product_id . ", Số lượng = " . $quantity . " -->";
    
    // Lấy thông tin chi tiết sản phẩm từ database
    $product_detail_sql = "
        SELECT 
            sp.ten_san_pham,
            sp.gia_ban,
            sp.gia_khuyen_mai,
            ha.duong_dan_hinh_anh
        FROM san_pham_thuoc sp
        LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = TRUE
        WHERE sp.ma_san_pham = ?
    ";
    
    $product_detail_stmt = $conn->prepare($product_detail_sql);
    $product_detail_stmt->bind_param("i", $product_id);
    $product_detail_stmt->execute();
    $product_detail_result = $product_detail_stmt->get_result();
    $product_detail = $product_detail_result->fetch_assoc();
    
    if ($product_detail) {
        // Sử dụng dữ liệu thực từ database
        $product_name = $product_detail['ten_san_pham'];
        $product_price = $product_detail['gia_khuyen_mai'] ?: $product_detail['gia_ban'];
        $product_image = $product_detail['duong_dan_hinh_anh'] ?: "https://via.placeholder.com/60x60?text=" . urlencode($product_name);
    } else {
        // Fallback về dữ liệu mẫu nếu không tìm thấy trong database
        $product_name = isset($product_info[$product_id]) ? $product_info[$product_id]['name'] : "Sản phẩm " . $product_id;
        $product_price = isset($product_info[$product_id]) ? $product_info[$product_id]['price'] : 100000;
        $product_image = "https://via.placeholder.com/60x60?text=" . urlencode($product_name);
    }
    
    $cart[$product_id] = [
        'name' => $product_name,
        'price' => $product_price,
        'quantity' => $quantity,
        'image' => $product_image
    ];
    
    echo "<!-- Debug: Đã thêm vào cart - " . $product_name . " x " . $quantity . " -->";
}

echo "<!-- Debug: Tổng số items trong cart array = " . count($cart) . " -->";
if (!empty($cart)) {
    echo "<!-- Debug: Danh sách product IDs: " . implode(', ', array_keys($cart)) . " -->";
}

// Tính tổng tiền
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping_fee = 0; // Miễn phí ship
$discount = 0;     // Chưa có giảm giá
$total = $subtotal + $shipping_fee - $discount;

// Lấy thông báo checkout nếu có
$checkout_message = $_SESSION['checkout_message'] ?? '';
if ($checkout_message) {
    unset($_SESSION['checkout_message']); // Xóa message sau khi hiển thị
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/cart.css">
    <style>
        
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Giỏ hàng</h1>
            <p>Xem lại các sản phẩm trước khi thanh toán</p>
        </div>
        
        <div class="cart-content">
            <?php if ($checkout_message): ?>
                <div class="alert alert-info">
                    <button type="button" class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
                    <?php echo nl2br(htmlspecialchars($checkout_message)); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($cart)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Giỏ hàng trống</h3>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                    </a>
                </div>
            <?php else: ?>
                <!-- Cart Items Table -->
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $product_id => $item): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="<?php echo $item['image'] ? htmlspecialchars($item['image']) : 'https://via.placeholder.com/60x60?text=' . urlencode($item['name']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                                        <div class="product-details">
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <p>Mã SP: <?php echo $product_id; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                                <td>
                                    <form method="POST" class="quantity-control">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <input type="number" name="quantity" class="quantity-input" 
                                               value="<?php echo $item['quantity']; ?>" min="1">
                                        <button type="submit" class="update-btn">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="price"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</td>
                                <td>
                                    <form method="POST" class="confirm-form">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <button type="submit" class="remove-btn" 
                                                onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span><?php echo $shipping_fee === 0 ? 'Miễn phí' : number_format($shipping_fee, 0, ',', '.') . 'đ'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Giảm giá:</span>
                        <span><?php echo number_format($discount, 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="summary-row total">
                        <span>Tổng cộng:</span>
                        <span><?php echo number_format($total, 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
                
                <!-- Cart Actions -->
                <div class="cart-actions">
                    <div>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                        <form method="POST" class="confirm-form">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="btn btn-secondary"
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ giỏ hàng?')">
                                <i class="fas fa-trash"></i> Xóa giỏ hàng
                            </button>
                        </form>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="btn btn-success" >
                                <i class="fas fa-credit-card"></i> Tiến hành thanh toán
                            </button>
                        </form>
                    </div>
                </div>
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
            </div>
        </div>
    </footer>
</body>
</html>