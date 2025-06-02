<?php
session_start();
include 'config/database.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý add to cart từ header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'] ?? 0;
    $product_name = $_POST['product_name'] ?? '';
    $product_price = $_POST['product_price'] ?? 0;
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => 1
        ];
    }
    
    // Redirect để tránh resubmit
    header('Location: cart.php');
    exit;
}

// Xử lý các action từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_quantity':
            $product_id = $_POST['product_id'] ?? 0;
            $quantity = max(1, intval($_POST['quantity'] ?? 1));
            
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            }
            break;
            
        case 'remove_item':
            $product_id = $_POST['product_id'] ?? 0;
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
            break;
            
        case 'clear_cart':
            $_SESSION['cart'] = [];
            break;
            
        case 'checkout':
            // Xử lý thanh toán
            $total_products = array_sum(array_column($_SESSION['cart'], 'quantity'));
            $_SESSION['checkout_message'] = "Tính năng thanh toán đang được phát triển!\n\nTổng số sản phẩm: " . $total_products;
            break;
    }
    
    // Redirect để tránh resubmit
    header('Location: cart.php');
    exit;
}

// Lấy giỏ hàng từ session
$cart = $_SESSION['cart'] ?? [];

// Lấy ảnh cho các sản phẩm trong giỏ hàng
$product_images = [];
if (!empty($cart)) {
    $product_ids = array_keys($cart);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $images_sql = "SELECT ha.ma_san_pham, ha.duong_dan_hinh_anh 
                   FROM hinh_anh_san_pham ha 
                   WHERE ha.ma_san_pham IN ($placeholders) AND ha.la_hinh_chinh = TRUE";
    
    $images_stmt = $conn->prepare($images_sql);
    $images_stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
    $images_stmt->execute();
    $images_result = $images_stmt->get_result();
    
    while ($img = $images_result->fetch_assoc()) {
        $product_images[$img['ma_san_pham']] = $img['duong_dan_hinh_anh'];
    }
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
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .cart-header {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        
        .cart-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .cart-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .cart-content {
            background: white;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cart-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 1px solid #e0e6ed;
        }
        
        .cart-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e0e6ed;
        }
        
        .product-details h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .product-details p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 120px;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #e0e6ed;
            border-radius: 4px;
            padding: 8px;
        }
        
        .price {
            font-weight: 600;
            color: #27ae60;
            font-size: 16px;
        }
        
        .remove-btn, .update-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-right: 5px;
        }
        
        .update-btn {
            background: #3498db;
        }
        
        .remove-btn:hover {
            background: #c0392b;
        }
        
        .update-btn:hover {
            background: #2980b9;
        }
        
        .cart-summary {
            background: #f8f9fa;
            padding: 30px;
            border-top: 1px solid #e0e6ed;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .summary-row.total {
            border-top: 2px solid #e0e6ed;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .cart-actions {
            padding: 20px 30px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 30px;
            color: #7f8c8d;
        }
        
        .empty-cart i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        
        .empty-cart h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        .login-prompt {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 30px;
            text-align: center;
        }
        
        .login-prompt h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .login-prompt p {
            margin: 0 0 15px 0;
            color: #856404;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 30px;
            border-radius: 5px;
            border: 1px solid;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .alert-close {
            float: right;
            font-size: 18px;
            font-weight: bold;
            line-height: 1;
            color: #000;
            text-shadow: 0 1px 0 #fff;
            opacity: 0.5;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .alert-close:hover {
            opacity: 0.75;
        }
        
        .confirm-form {
            display: inline;
        }
        
        .confirm-form button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .confirm-form button:hover {
            background: #c82333;
        }
        
        @media (max-width: 768px) {
            .cart-container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .cart-table {
                font-size: 14px;
            }
            
            .cart-table th,
            .cart-table td {
                padding: 10px 8px;
            }
            
            .product-info {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .cart-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .cart-actions .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
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
            
            <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
                <div class="login-prompt">
                    <h4><i class="fas fa-exclamation-triangle"></i> Cần đăng nhập</h4>
                    <p>Vui lòng đăng nhập để xem giỏ hàng và tiến hành thanh toán</p>
                    <a href="Login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
                    </a>
                </div>
            <?php elseif (empty($cart)): ?>
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
                                        <img src="<?php echo isset($product_images[$product_id]) ? htmlspecialchars($product_images[$product_id]) : 'https://via.placeholder.com/60x60?text=' . urlencode($item['name']); ?>" 
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
                            <button type="submit" class="btn btn-success">
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