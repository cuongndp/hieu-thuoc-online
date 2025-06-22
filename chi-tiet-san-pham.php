<?php
session_start();
include 'config/database.php';

// Lấy product ID từ URL
$product_id = $_GET['id'] ?? 0;

// Lấy image index để hiển thị ảnh chính
$selected_image = $_GET['img'] ?? 0;

if (!$product_id) {
    header('Location: index.php');
    exit;
}

// Xử lý add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: login.php');
        exit;
    }
    
    $product_id_post = $_POST['product_id'] ?? 0;
    $product_name = $_POST['product_name'] ?? '';
    $product_price = $_POST['product_price'] ?? 0;
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id_post])) {
        $_SESSION['cart'][$product_id_post]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id_post] = [
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => $quantity
        ];
    }
    
    // Set success message
    $_SESSION['cart_message'] = "Đã thêm {$quantity} sản phẩm '{$product_name}' vào giỏ hàng!";
    
    // Redirect để tránh resubmit
    header('Location: chi-tiet-san-pham.php?id=' . $product_id . '&img=' . $selected_image);
    exit;
}

// Lấy thông tin sản phẩm chi tiết với TẤT CẢ các cột có sẵn
$product_sql = "
    SELECT 
        sp.ma_san_pham,
        sp.ten_san_pham,
        sp.ten_hoat_chat,
        sp.ma_danh_muc,
        sp.ma_nha_san_xuat,
        sp.mo_ta,
        sp.thanh_phan_hoat_chat,
        sp.dang_bao_che,
        sp.ham_luong,
        sp.quy_cach_dong_goi,
        sp.can_don_thuoc,
        sp.gia_ban,
        sp.gia_khuyen_mai,
        sp.so_luong_ton_kho,
        sp.muc_ton_kho_toi_thieu,
        sp.muc_ton_kho_toi_da,
        sp.han_su_dung,
        sp.so_lo,
        sp.ma_vach,
        sp.ma_sku,
        sp.trong_luong,
        sp.dieu_kien_bao_quan,
        sp.tac_dung_phu,
        sp.chong_chi_dinh,
        sp.huong_dan_su_dung,
        sp.gioi_han_tuoi,
        sp.trang_thai_hoat_dong,
        sp.san_pham_noi_bat,
        sp.ngay_tao,
        sp.ngay_cap_nhat,
        dm.ten_danh_muc,
        nsx.ten_nha_san_xuat,
        nsx.quoc_gia
    FROM san_pham_thuoc sp
    LEFT JOIN danh_muc_thuoc dm ON sp.ma_danh_muc = dm.ma_danh_muc
    LEFT JOIN nha_san_xuat nsx ON sp.ma_nha_san_xuat = nsx.ma_nha_san_xuat
    WHERE sp.ma_san_pham = ? AND sp.trang_thai_hoat_dong = TRUE
";

$stmt = $conn->prepare($product_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: index.php');
    exit;
}

// Lấy hình ảnh sản phẩm
$images_sql = "SELECT duong_dan_hinh_anh, la_hinh_chinh FROM hinh_anh_san_pham WHERE ma_san_pham = ? ORDER BY la_hinh_chinh DESC";
$images_stmt = $conn->prepare($images_sql);
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();

$product_images = [];
while ($image = $images_result->fetch_assoc()) {
    $product_images[] = $image;
}

// Nếu không có hình ảnh nào, tạo placeholder
if (empty($product_images)) {
    $product_images[] = [
        'duong_dan_hinh_anh' => 'https://via.placeholder.com/400x400?text=' . urlencode($product['ten_san_pham']),
        'la_hinh_chinh' => true
    ];
}

// Xác định ảnh chính để hiển thị
$main_image_index = intval($selected_image);
if ($main_image_index >= count($product_images) || $main_image_index < 0) {
    $main_image_index = 0;
}

// Lấy sản phẩm liên quan (cùng danh mục)
$related_sql = "
    SELECT 
        sp.ma_san_pham,
        sp.ten_san_pham,
        sp.gia_ban,
        sp.gia_khuyen_mai,
        ha.duong_dan_hinh_anh
    FROM san_pham_thuoc sp
    LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = TRUE
    WHERE sp.ma_danh_muc = ? 
    AND sp.ma_san_pham != ? 
    AND sp.trang_thai_hoat_dong = TRUE
    ORDER BY sp.san_pham_noi_bat DESC, RAND()
    LIMIT 4
";

$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("ii", $product['ma_danh_muc'], $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();

$related_products = [];
while ($related = $related_result->fetch_assoc()) {
    $related_products[] = $related;
}

// Lấy cart message nếu có
$cart_message = $_SESSION['cart_message'] ?? '';
if ($cart_message) {
    unset($_SESSION['cart_message']);
}

// Tính toán giá và khuyến mãi
$current_price = $product['gia_khuyen_mai'] ?: $product['gia_ban'];
$discount_percent = 0;
if ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']) {
    $discount_percent = round((($product['gia_ban'] - $product['gia_khuyen_mai']) / $product['gia_ban']) * 100);
}

// Xử lý stock status
$stock = intval($product['so_luong_ton_kho'] ?? 0);
$stock_status = 'out';
$stock_class = 'stock-out';
$stock_text = 'Hết hàng';
$stock_icon = 'fa-times-circle';

if ($stock > 10) {
    $stock_status = 'available';
    $stock_class = 'stock-available';
    $stock_text = "Còn hàng ({$stock} sản phẩm)";
    $stock_icon = 'fa-check-circle';
} elseif ($stock > 0) {
    $stock_status = 'low';
    $stock_class = 'stock-low';
    $stock_text = "Sắp hết hàng (còn {$stock} sản phẩm)";
    $stock_icon = 'fa-exclamation-triangle';
}

// Xử lý hạn sử dụng
$expiry_date = '';
if ($product['han_su_dung']) {
    $expiry_date = date('d/m/Y', strtotime($product['han_su_dung']));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['ten_san_pham']); ?> - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/chi-tiet-san-pham.css">
    <style>
        
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="product-detail-container">
        <!-- Success Message -->
        <?php if ($cart_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($cart_message); ?>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <ul class="breadcrumb-list">
                <li><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><i class="fas fa-chevron-right"></i></li>
                <li><a href="danh-muc.php?cat=thuoc-khong-ke-don"><?php echo htmlspecialchars($product['ten_danh_muc']); ?></a></li>
                <li><i class="fas fa-chevron-right"></i></li>
                <li class="current"><?php echo htmlspecialchars($product['ten_san_pham']); ?></li>
            </ul>
        </div>

        <!-- Product Main Info -->
        <div class="product-main">
            <!-- Product Images -->
            <div class="product-images">
                <div class="main-image">
                    <img src="<?php echo htmlspecialchars($product_images[$main_image_index]['duong_dan_hinh_anh']); ?>" 
                         alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                </div>
                
                <?php if (count($product_images) > 1): ?>
                <div class="thumbnail-list">
                    <?php foreach ($product_images as $index => $image): ?>
                        <div class="thumbnail <?php echo $index === $main_image_index ? 'active' : ''; ?>">
                            <a href="chi-tiet-san-pham.php?id=<?php echo $product_id; ?>&img=<?php echo $index; ?>">
                                <img src="<?php echo htmlspecialchars($image['duong_dan_hinh_anh']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['ten_san_pham']); ?></h1>
                
                <!-- Badges -->
                <div class="product-badges">
                    <?php if ($product['can_don_thuoc']): ?>
                        <span class="badge badge-prescription">Thuốc kê đơn</span>
                    <?php endif; ?>
                    
                    <?php if ($discount_percent > 0): ?>
                        <span class="badge badge-discount">Giảm <?php echo $discount_percent; ?>%</span>
                    <?php endif; ?>
                    
                    <?php if ($product['san_pham_noi_bat']): ?>
                        <span class="badge badge-featured">Sản phẩm nổi bật</span>
                    <?php endif; ?>
                </div>

                <!-- Price -->
                <div class="product-price">
                    <span class="current-price"><?php echo number_format($current_price, 0, ',', '.'); ?>đ</span>
                    <?php if ($discount_percent > 0): ?>
                        <span class="old-price"><?php echo number_format($product['gia_ban'], 0, ',', '.'); ?>đ</span>
                    <?php endif; ?>
                </div>

                <!-- Product Meta -->
                <div class="product-meta">
                    <div class="meta-item">
                        <span class="meta-label">Thương hiệu:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['ten_nha_san_xuat'] ?: 'Không rõ'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Xuất xứ:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['quoc_gia'] ?: 'Không rõ'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Danh mục:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['ten_danh_muc']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Mã sản phẩm:</span>
                        <span class="meta-value">SP<?php echo str_pad($product['ma_san_pham'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <?php if ($product['ma_sku']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Mã SKU:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['ma_sku']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($product['ham_luong']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Hàm lượng:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['ham_luong']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($product['quy_cach_dong_goi']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Quy cách:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['quy_cach_dong_goi']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($expiry_date): ?>
                    <div class="meta-item">
                        <span class="meta-label">Hạn sử dụng:</span>
                        <span class="meta-value"><?php echo $expiry_date; ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Stock Status -->
                <div class="stock-status">
                    <i class="fas <?php echo $stock_icon; ?> <?php echo $stock_class; ?>"></i>
                    <span class="<?php echo $stock_class; ?>"><?php echo $stock_text; ?></span>
                </div>

                <!-- Add to Cart Section -->
                <?php if ($stock > 0): ?>
                <div class="add-to-cart-section">
                    <form method="POST">
                        <input type="hidden" name="add_to_cart" value="1">
                        <input type="hidden" name="product_id" value="<?php echo $product['ma_san_pham']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $current_price; ?>">
                        
                        <div class="quantity-selector">
                            <span class="quantity-label">Số lượng:</span>
                            <div class="quantity-controls">
                                <input type="number" name="quantity" class="quantity-input" value="1" min="1" max="<?php echo $stock; ?>">
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                            <button type="submit" class="add-to-cart-btn">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ hàng
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="add-to-cart-btn" style="text-decoration: none;">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập để mua hàng
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <?php else: ?>
                <div class="add-to-cart-section">
                    <button class="add-to-cart-btn" disabled>
                        <i class="fas fa-times"></i> Hết hàng
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details -->
        <div class="product-details">
            <div class="detail-section">
                <h3>Mô tả sản phẩm</h3>
                <p><?php echo nl2br(htmlspecialchars($product['mo_ta'] ?: 'Thông tin mô tả sản phẩm đang được cập nhật.')); ?></p>
            </div>
            
            <?php if ($product['thanh_phan_hoat_chat']): ?>
            <div class="detail-section">
                <h3>Thành phần hoạt chất</h3>
                <p><?php echo nl2br(htmlspecialchars($product['thanh_phan_hoat_chat'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($product['ten_hoat_chat']): ?>
            <div class="detail-section">
                <h3>Hoạt chất chính</h3>
                <p><?php echo htmlspecialchars($product['ten_hoat_chat']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($product['dang_bao_che']): ?>
            <div class="detail-section">
                <h3>Dạng bào chế</h3>
                <p><?php echo htmlspecialchars($product['dang_bao_che']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($product['huong_dan_su_dung']): ?>
            <div class="detail-section">
                <h3>Hướng dẫn sử dụng</h3>
                <p><?php echo nl2br(htmlspecialchars($product['huong_dan_su_dung'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($product['dieu_kien_bao_quan']): ?>
            <div class="detail-section">
                <h3>Điều kiện bảo quản</h3>
                <p><?php echo nl2br(htmlspecialchars($product['dieu_kien_bao_quan'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($product['tac_dung_phu']): ?>
            <div class="detail-section">
                <h3>Tác dụng phụ</h3>
                <p><?php echo nl2br(htmlspecialchars($product['tac_dung_phu'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($product['chong_chi_dinh']): ?>
            <div class="detail-section">
                <h3>Chống chỉ định</h3>
                <p><?php echo nl2br(htmlspecialchars($product['chong_chi_dinh'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($product['gioi_han_tuoi']): ?>
            <div class="detail-section">
                <h3>Giới hạn tuổi</h3>
                <p><?php echo htmlspecialchars($product['gioi_han_tuoi']); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Cảnh báo thuốc kê đơn -->
            <?php if ($product['can_don_thuoc']): ?>
            <div class="warning-box">
                <h4>
                    <i class="fas fa-exclamation-triangle"></i> 
                    Lưu ý quan trọng - Thuốc kê đơn
                </h4>
                <p>
                    Sản phẩm này chỉ được bán theo đơn thuốc của bác sĩ. Vui lòng tham khảo ý kiến bác sĩ hoặc dược sĩ trước khi sử dụng. 
                    Không tự ý sử dụng mà không có chỉ định của bác sĩ.
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Thông tin bổ sung -->
            <div class="detail-section">
                <h3>Thông tin bổ sung</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <?php if ($product['so_lo']): ?>
                    <p><strong>Số lô:</strong> <?php echo htmlspecialchars($product['so_lo']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($product['ma_vach']): ?>
                    <p><strong>Mã vạch:</strong> <?php echo htmlspecialchars($product['ma_vach']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($product['trong_luong']): ?>
                    <p><strong>Trọng lượng:</strong> <?php echo number_format($product['trong_luong'], 2); ?>g</p>
                    <?php endif; ?>
                    
                    <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y', strtotime($product['ngay_tao'])); ?></p>
                    
                    <?php if ($product['ngay_cap_nhat']): ?>
                    <p><strong>Cập nhật cuối:</strong> <?php echo date('d/m/Y', strtotime($product['ngay_cap_nhat'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <div class="related-products">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Sản phẩm liên quan</h2>
            <div class="related-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="related-item">
                        <img src="<?php echo $related['duong_dan_hinh_anh'] ?: 'https://via.placeholder.com/120x120?text=' . urlencode($related['ten_san_pham']); ?>" 
                             alt="<?php echo htmlspecialchars($related['ten_san_pham']); ?>">
                        <h4><?php echo htmlspecialchars($related['ten_san_pham']); ?></h4>
                        <div class="price"><?php echo number_format($related['gia_khuyen_mai'] ?: $related['gia_ban'], 0, ',', '.'); ?>đ</div>
                        <a href="chi-tiet-san-pham.php?id=<?php echo $related['ma_san_pham']; ?>" class="btn">Xem chi tiết</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Back to category link -->
    <div>
        <a href="danh-muc.php?cat=thuoc-khong-ke-don" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại danh mục sản phẩm
        </a>
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