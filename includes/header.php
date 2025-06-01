<?php
// includes/header.php - Header component động với PHP thuần
if (!isset($_SESSION)) {
    session_start();
}

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$user_name = $_SESSION['user_name'] ?? '';

// Xử lý add to cart nếu có
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$is_logged_in) {
        header('Location: Login.php');
        exit;
    }
    
    // Xử lý thêm vào giỏ hàng ở đây
    $product_id = $_POST['product_id'] ?? 0;
    $product_name = $_POST['product_name'] ?? '';
    $product_price = $_POST['product_price'] ?? 0;
    
    // Lưu vào session cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => 1
        ];
    }
    
    // Redirect về trang hiện tại để tránh resubmit
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Xử lý toggle user dropdown bằng PHP
$show_dropdown = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_dropdown'])) {
    $show_dropdown = $_POST['dropdown_state'] === 'closed';
}

// Tính tổng số sản phẩm trong giỏ hàng
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!-- Header -->
<header class="header">
    <div class="container">
        <div class="header-top">
            <div class="logo">
                <a href="index.php">
                    <img src="./images/medical-care-logo-illustration-vector.jpg" alt="VitaMeds Logo">
                </a>
                <span>VitaMeds</span>
            </div>
            
            <div class="search-container">
                <form method="GET" action="search.php">
                    <input type="text" class="search-box" name="q" placeholder="Nhập loại thuốc cần tìm..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="header-actions">
                <a href="cart.php" class="cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Giỏ hàng</span>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
                
                <?php if ($is_logged_in): ?>
                    <!-- User logged in -->
                    <div class="user-menu">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="toggle_dropdown" value="1">
                            <input type="hidden" name="dropdown_state" value="<?php echo $show_dropdown ? 'open' : 'closed'; ?>">
                            <button type="submit" class="user-info <?php echo $show_dropdown ? 'active' : ''; ?>">
                                <i class="fas fa-user-circle"></i>
                                <span>Xin chào, <?php echo htmlspecialchars($user_name); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </form>
                        
                        <?php if ($show_dropdown): ?>
                        <div class="user-dropdown show" id="user-dropdown">
                            <a href="profile.php"><i class="fas fa-user"></i> Thông tin cá nhân</a>
                            <a href="orders.php"><i class="fas fa-shopping-bag"></i> Đơn hàng của tôi</a>
                            <a href="change-password.php"><i class="fas fa-key"></i> Đổi mật khẩu</a>
                            <hr>
                            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- User not logged in -->
                    <a href="Login.php" class="login-btn">
                        <i class="fas fa-user"></i>
                        <span>Đăng nhập</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<?php if ($is_logged_in): ?>
<style>
/* User Menu Styles */
.user-menu {
    position: relative;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    background: #ecf0f1;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    color: inherit;
    font-family: inherit;
    font-size: inherit;
}

.user-info:hover {
    background: #d5dbdb;
}

.user-info.active {
    background: #d5dbdb;
}

.user-info i.fa-user-circle {
    font-size: 20px;
    color: #2c3e50;
}

.user-info span {
    color: #2c3e50;
    font-weight: 500;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-info i.fa-chevron-down {
    font-size: 12px;
    color: #7f8c8d;
    transition: transform 0.3s ease;
}

.user-info.active i.fa-chevron-down {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    min-width: 200px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border: 1px solid #e0e6ed;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.user-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #2c3e50;
    text-decoration: none;
    transition: background 0.3s ease;
}

.user-dropdown a:hover {
    background: #f8f9fa;
}

.user-dropdown a i {
    width: 16px;
    color: #7f8c8d;
}

.user-dropdown hr {
    margin: 5px 0;
    border: none;
    border-top: 1px solid #e0e6ed;
}

.logout-link {
    color: #e74c3c !important;
}

.logout-link:hover {
    background: #fdf2f2 !important;
}

.logout-link i {
    color: #e74c3c !important;
}

/* Responsive */
@media (max-width: 768px) {
    .user-info span {
        display: none;
    }
    
    .user-dropdown {
        right: -10px;
        min-width: 180px;
    }
}
</style>
<?php endif; ?>

<!-- Navigation Menu -->
<nav class="nav-menu">
    <div class="container">
        <div class="nav-items">
            <a href="./danh-muc.php?cat=thuoc-khong-ke-don" class="nav-item">Thuốc không kê đơn</a>
            <a href="./danh-muc.php?cat=thuoc-ke-don" class="nav-item">Thuốc kê đơn</a>
            <a href="./danh-muc.php?cat=vitamin-khoang-chat" class="nav-item">Vitamin & Khoáng chất</a>
            <a href="./danh-muc.php?cat=thuc-pham-chuc-nang" class="nav-item">Thực phẩm chức năng</a>
            <a href="./danh-muc.php?cat=duoc-my-pham" class="nav-item">Dược mỹ phẩm</a>
            <a href="./danh-muc.php?cat=thiet-bi-y-te" class="nav-item">Thiết bị y tế</a>
            <a href="./danh-muc.php?cat=me-va-be" class="nav-item">Mẹ & bé</a>
        </div>
    </div>
</nav>