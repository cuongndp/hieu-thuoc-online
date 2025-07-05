<?php
// includes/header.php - Header component với database
include_once __DIR__ . '/../config/dual_session.php';
include_once __DIR__ . '/../config/database.php';

// Kiểm tra trạng thái đăng nhập
$is_logged_in = is_user_logged_in();
$user_name = get_user_name();
$user_id = get_user_id();

// Xử lý add to cart - LƯU VÀO DATABASE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$is_logged_in) {
        header('Location: login.php');
        exit;
    }
    
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = 1;
    
    if ($product_id > 0 && $user_id > 0) {
        try {
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
            
            $_SESSION['success_message'] = "Đã thêm sản phẩm vào giỏ hàng!";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Có lỗi xảy ra: " . $e->getMessage();
        }
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

// Tính tổng số sản phẩm trong giỏ hàng từ DATABASE
$cart_count = 0;
if ($is_logged_in && $user_id > 0) {
    try {
        $cart_count_sql = "SELECT SUM(so_luong) as total_items FROM gio_hang WHERE ma_nguoi_dung = ?";
        $cart_count_stmt = $conn->prepare($cart_count_sql);
        $cart_count_stmt->bind_param("i", $user_id);
        $cart_count_stmt->execute();
        $cart_count_result = $cart_count_stmt->get_result();
        $cart_count_row = $cart_count_result->fetch_assoc();
        $cart_count = $cart_count_row['total_items'] ?? 0;
    } catch (Exception $e) {
        $cart_count = 0;
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
                            <a href="don-hang.php"><i class="fas fa-shopping-bag"></i> Đơn hàng của tôi</a>
                            <a href="profile.php?tab=security"><i class="fas fa-key"></i> Đổi mật khẩu</a>
                            <hr>
                            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- User not logged in -->
                    <a href="login.php" class="login-btn">
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