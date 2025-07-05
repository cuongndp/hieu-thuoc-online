<?php
include 'config/dual_session.php';
include 'config/database.php';

// Ensure session is started
ensure_session_started();
include 'config/reviews.php';

// Lấy product ID từ URL
$product_id = $_GET['id'] ?? 0;

// Lấy image index để hiển thị ảnh chính
$selected_image = $_GET['img'] ?? 0;

if (!$product_id) {
    header('Location: index.php');
    exit;
}

// Xử lý add to cart - LƯU VÀO DATABASE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
    
    $user_id = get_user_id();
    $product_id_post = $_POST['product_id'] ?? 0;
    $product_name = $_POST['product_name'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($product_id_post > 0 && $user_id > 0) {
        try {
            // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
            $check_sql = "SELECT so_luong FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $user_id, $product_id_post);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Nếu đã có, tăng số lượng
                $row = $result->fetch_assoc();
                $new_quantity = $row['so_luong'] + $quantity;
                
                $update_sql = "UPDATE gio_hang SET so_luong = ?, ngay_cap_nhat = NOW() WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id_post);
                $update_stmt->execute();
            } else {
                // Nếu chưa có, thêm mới
                $insert_sql = "INSERT INTO gio_hang (ma_nguoi_dung, ma_san_pham, so_luong, ngay_them) VALUES (?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iii", $user_id, $product_id_post, $quantity);
                $insert_stmt->execute();
            }
            
            // Set success message
            $_SESSION['cart_message'] = "Đã thêm {$quantity} sản phẩm '{$product_name}' vào giỏ hàng!";
        } catch (Exception $e) {
            $_SESSION['cart_message'] = "Có lỗi xảy ra khi thêm sản phẩm!";
        }
    }
    
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

// Thông báo đánh giá thành công
$review_success = isset($_GET['review_success']) && $_GET['review_success'] == 1;

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

// Lấy đánh giá sản phẩm
$reviews = get_product_reviews($product_id, $conn, 5, 0);
$rating_stats = get_product_rating_stats($product_id, $conn);

// Kiểm tra xem user đã mua và có thể đánh giá sản phẩm này không
$can_review = false;
$has_reviewed = false;
                                 if (is_user_logged_in()) {
    $user_id = get_user_id();
    $can_review = has_user_purchased_product($user_id, $product_id, $conn);
    $has_reviewed = has_user_reviewed_product($user_id, $product_id, 0, $conn);
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
        /* Product Reviews Styles */
        .product-reviews {
            background: #f8f9fa;
            padding: 40px 0;
            margin-top: 40px;
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .reviews-header h2 {
            color: #333;
            margin: 0;
        }
        
        .rating-summary {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            flex: 1;
        }
        
        .overall-rating {
            text-align: center;
            min-width: 120px;
        }
        
        .rating-score .score {
            font-size: 36px;
            font-weight: bold;
            color: #f39c12;
            display: block;
        }
        
        .rating-score .stars {
            margin: 5px 0;
        }
        
        .rating-count {
            color: #666;
            font-size: 14px;
        }
        
        .rating-breakdown {
            flex: 1;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .star-label {
            min-width: 50px;
            font-size: 14px;
            color: #666;
        }
        
        .bar-container {
            flex: 1;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: #f39c12;
            transition: width 0.3s ease;
        }
        
        .count {
            min-width: 30px;
            font-size: 12px;
            color: #666;
            text-align: right;
        }
        
        .review-btn {
            background: #27ae60;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .review-btn:hover {
            background: #229954;
        }
        
        .reviewed-badge {
            background: #f39c12;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .review-note {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .reviews-list {
            margin-top: 30px;
        }
        
        .review-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .reviewer-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .review-date {
            color: #666;
            font-size: 14px;
        }
        
        .review-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .review-content {
            color: #555;
            line-height: 1.6;
        }
        
        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .view-more-reviews {
            text-align: center;
            margin-top: 20px;
        }
        
        .view-more-btn {
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .view-more-btn:hover {
            background: #2980b9;
        }
        
        @media (max-width: 768px) {
            .reviews-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .rating-summary {
                flex-direction: column;
                gap: 20px;
            }
            
            .review-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <?php if ($review_success): ?>
        <div class="alert alert-success" style="max-width:800px;margin:20px auto 0 auto;">
            <i class="fas fa-check-circle"></i>
            Đánh giá của bạn đã được gửi thành công! Cảm ơn bạn đã chia sẻ trải nghiệm.
        </div>
    <?php endif; ?>

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
                        
                        <?php if (is_user_logged_in()): ?>
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

    <!-- Product Reviews -->
    <div class="product-reviews">
        <div class="container">
            <div class="reviews-header">
                <h2><i class="fas fa-star"></i> Đánh giá sản phẩm</h2>
                
                <!-- Rating Summary -->
                <div class="rating-summary">
                    <div class="overall-rating">
                        <div class="rating-score">
                            <span class="score"><?= number_format($rating_stats['trung_binh_sao'] ?? 0, 1) ?></span>
                            <div class="stars">
                                <?= format_stars($rating_stats['trung_binh_sao'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="rating-count">
                            <span><?= $rating_stats['tong_so_danh_gia'] ?? 0 ?> đánh giá</span>
                        </div>
                    </div>
                    
                    <!-- Rating Breakdown -->
                    <div class="rating-breakdown">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="rating-bar">
                                <span class="star-label"><?= $i ?> sao</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: <?= $rating_stats['tong_so_danh_gia'] > 0 ? ($rating_stats["so_sao_$i"] / $rating_stats['tong_so_danh_gia']) * 100 : 0 ?>%"></div>
                                </div>
                                <span class="count"><?= $rating_stats["so_sao_$i"] ?? 0 ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Review Button -->
                                    <?php if (is_user_logged_in()): ?>
                    <?php if ($can_review && !$has_reviewed): ?>
                        <a href="review.php?product_id=<?= $product_id ?>" class="review-btn">
                            <i class="fas fa-star"></i> Viết đánh giá
                        </a>
                    <?php elseif ($has_reviewed): ?>
                        <span class="reviewed-badge">
                            <i class="fas fa-check"></i> Bạn đã đánh giá sản phẩm này
                        </span>
                    <?php else: ?>
                        <span class="review-note">
                            <i class="fas fa-info-circle"></i> Mua sản phẩm để đánh giá
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="review-btn">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập để đánh giá
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Reviews List -->
            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <div class="no-reviews">
                        <i class="fas fa-star" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                        <h3>Chưa có đánh giá nào</h3>
                        <p>Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-name"><?= htmlspecialchars($review['ho_ten']) ?></div>
                                    <div class="review-date"><?= date('d/m/Y', strtotime($review['ngay_tao'])) ?></div>
                                </div>
                                <div class="review-rating">
                                    <?= format_stars($review['so_sao']) ?>
                                    <span class="review-score" style="font-weight:bold;color:#f39c12;font-size:16px;margin-left:8px;">
                                        <?= $review['so_sao'] ?>/5
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($review['tieu_de']): ?>
                                <div class="review-title"><?= htmlspecialchars($review['tieu_de']) ?></div>
                            <?php endif; ?>
                            
                            <div class="review-content">
                                <?= nl2br(htmlspecialchars($review['noi_dung'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($rating_stats['tong_so_danh_gia'] > 5): ?>
                        <div class="view-more-reviews">
                            <a href="reviews.php?product_id=<?= $product_id ?>" class="view-more-btn">
                                Xem tất cả <?= $rating_stats['tong_so_danh_gia'] ?> đánh giá
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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