<?php
include 'config/simple_session.php';
include 'config/database.php';
include 'config/reviews.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập
require_user_login();

$user_id = $_SESSION['user_id'] ?? 0;
$success_message = '';
$error_message = '';

// Xử lý đánh giá sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    try {
        $product_id = intval($_POST['product_id']);
        $order_id = intval($_POST['order_id']);
        $rating = intval($_POST['rating']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        // Validation
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Vui lòng chọn số sao từ 1-5");
        }
        
        if (empty($title) || strlen($title) < 5) {
            throw new Exception("Tiêu đề phải có ít nhất 5 ký tự");
        }
        
        if (empty($content) || strlen($content) < 10) {
            throw new Exception("Nội dung đánh giá phải có ít nhất 10 ký tự");
        }
        
        // Kiểm tra xem user đã mua sản phẩm chưa
        if (!has_user_purchased_product($user_id, $product_id, $conn)) {
            throw new Exception("Bạn chưa mua sản phẩm này!");
        }
        
        // Kiểm tra xem đã đánh giá chưa
        if (has_user_reviewed_product($user_id, $product_id, $order_id, $conn)) {
            throw new Exception("Bạn đã đánh giá sản phẩm này trong đơn hàng này rồi!");
        }
        
        // Thêm đánh giá
        $review_id = add_product_review($user_id, $product_id, $order_id, $rating, $title, $content, $conn);
        
        $success_message = "Đánh giá của bạn đã được gửi thành công! Cảm ơn bạn đã chia sẻ trải nghiệm.";
        $redirect_url = 'review.php?order_id=' . $order_id . '&review_success=1';
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Lấy thông tin sản phẩm cần đánh giá
$product_id = intval($_GET['product_id'] ?? 0);
$order_id = intval($_GET['order_id'] ?? 0);

if ($product_id > 0) {
    // Lấy thông tin sản phẩm
    $product_sql = "SELECT 
                        sp.ma_san_pham,
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
    $product = $product_result->fetch_assoc();
    $product_stmt->close();
    
    if (!$product) {
        $error_message = "Không tìm thấy sản phẩm!";
    } else {
        // Kiểm tra xem user đã mua sản phẩm này chưa
        if (!has_user_purchased_product($user_id, $product_id, $conn)) {
            $error_message = "Bạn chưa mua sản phẩm này!";
        }
        // Kiểm tra xem đã đánh giá chưa (theo từng đơn hàng)
        if (empty($success_message) && has_user_reviewed_product($user_id, $product_id, $order_id, $conn)) {
            $error_message = "Bạn đã đánh giá sản phẩm này trong đơn hàng này rồi!";
        }
    }
} else if ($order_id > 0) {
    // Nếu có order_id mà không có product_id, chỉ lấy sản phẩm chưa đánh giá của đơn hàng đó
    $products_to_review = get_products_in_order_not_reviewed($user_id, $order_id, $conn);
} else {
    // Không có order_id, lấy toàn bộ sản phẩm chưa đánh giá của tất cả đơn hàng
    $products_to_review = get_purchased_products_not_reviewed($user_id, $conn);
}

// Function format giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.');
}

// Function lấy URL hình ảnh
function getImageUrl($image_path, $product_name = 'Product') {
    if (empty($image_path)) {
        return 'https://via.placeholder.com/100x100?text=' . urlencode($product_name);
    }
    
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
    <title>Đánh giá sản phẩm - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        .review-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .product-details h3 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .product-price {
            color: #ff4757;
            font-weight: bold;
            font-size: 18px;
        }
        
        .review-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e6ed;
        }
        
        .rating-section {
            margin-bottom: 20px;
        }
        
        .rating-stars {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .rating-stars input[type="radio"] {
            display: none;
        }
        
        .rating-stars label {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input[type="radio"]:checked ~ label {
            color: #f39c12;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .submit-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .submit-btn:hover {
            background: #229954;
        }
        
        .products-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            padding: 15px;
            background: white;
            transition: transform 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="breadcrumb">
            <a href="index.php">Trang chủ</a> > <a href="don-hang.php">Đơn hàng</a> > Đánh giá sản phẩm
        </div>

        <div class="review-container">
            <a href="don-hang.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Quay lại đơn hàng
            </a>

            <h1><i class="fas fa-star"></i> Đánh giá sản phẩm</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = "<?= $redirect_url ?>";
                    }, 2500);
                </script>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($product) && $product): ?>
                <!-- Form đánh giá sản phẩm cụ thể -->
                <div class="product-info">
                    <img src="<?= getImageUrl($product['duong_dan_hinh_anh'], $product['ten_san_pham']) ?>" 
                         alt="<?= htmlspecialchars($product['ten_san_pham']) ?>" 
                         class="product-image">
                    <div class="product-details">
                        <h3><?= htmlspecialchars($product['ten_san_pham']) ?></h3>
                        <div class="product-price">
                            <?= formatPrice($product['gia_khuyen_mai'] ?? $product['gia_ban']) ?>đ
                        </div>
                    </div>
                </div>

                <form method="POST" class="review-form">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    
                    <div class="rating-section">
                        <label>Đánh giá của bạn:</label>
                        <div class="rating-stars">
                            <input type="radio" name="rating" value="5" id="star5">
                            <label for="star5"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                        <small style="color: #666;">Chọn số sao từ 1-5</small>
                    </div>

                    <div class="form-group">
                        <label for="title">Tiêu đề đánh giá:</label>
                        <input type="text" id="title" name="title" 
                               placeholder="Ví dụ: Thuốc rất hiệu quả, giá cả hợp lý" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="content">Nội dung đánh giá:</label>
                        <textarea id="content" name="content" 
                                  placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..." required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="submit_review" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Gửi đánh giá
                    </button>
                </form>

            <?php elseif (isset($products_to_review)): ?>
                <!-- Danh sách sản phẩm cần đánh giá -->
                <h2>Sản phẩm cần đánh giá</h2>
                
                <?php if (empty($products_to_review)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-star" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                        <h3>Không có sản phẩm nào cần đánh giá</h3>
                        <p>Bạn đã đánh giá tất cả sản phẩm đã mua hoặc chưa có đơn hàng nào.</p>
                        <a href="index.php" class="submit-btn" style="text-decoration: none; display: inline-block;">
                            <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="products-list">
                        <?php foreach ($products_to_review as $product): ?>
                            <div class="product-card">
                                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                                    <img src="<?= getImageUrl($product['duong_dan_hinh_anh'], $product['ten_san_pham']) ?>" 
                                         alt="<?= htmlspecialchars($product['ten_san_pham']) ?>" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                    <div>
                                        <h4 style="margin: 0 0 5px 0;"><?= htmlspecialchars($product['ten_san_pham']) ?></h4>
                                        <div style="color: #ff4757; font-weight: bold;">
                                            <?= formatPrice($product['gia_ban']) ?>đ
                                        </div>
                                    </div>
                                </div>
                                <a href="review.php?product_id=<?= $product['ma_san_pham'] ?>&order_id=<?= $product['ma_don_hang'] ?>" 
                                   class="submit-btn" style="text-decoration: none; display: block; text-align: center;">
                                    <i class="fas fa-star"></i> Đánh giá ngay
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Xử lý hiển thị sao khi hover
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.rating-stars label');
            
            stars.forEach((star, index) => {
                star.addEventListener('mouseenter', function() {
                    // Reset tất cả sao
                    stars.forEach(s => s.style.color = '#ddd');
                    
                    // Tô màu sao từ vị trí hiện tại trở về trước
                    for (let i = stars.length - 1; i >= index; i--) {
                        stars[i].style.color = '#f39c12';
                    }
                });
            });
            
            // Reset khi rời chuột
            document.querySelector('.rating-stars').addEventListener('mouseleave', function() {
                const checkedStar = document.querySelector('.rating-stars input[type="radio"]:checked');
                if (!checkedStar) {
                    stars.forEach(star => star.style.color = '#ddd');
                }
            });
        });
    </script>
</body>
</html> 