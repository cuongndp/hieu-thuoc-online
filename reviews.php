<?php
include 'config/dual_session.php';
include 'config/database.php';

// Ensure session is started
ensure_session_started();
include 'config/reviews.php';

$product_id = intval($_GET['product_id'] ?? 0);

if (!$product_id) {
    header('Location: index.php');
    exit;
}

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
    header('Location: index.php');
    exit;
}

// Phân trang
$page = intval($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Lấy đánh giá
$reviews = get_product_reviews($product_id, $conn, $per_page, $offset);
$rating_stats = get_product_rating_stats($product_id, $conn);

// Tính tổng số trang
$total_reviews = $rating_stats['tong_so_danh_gia'] ?? 0;
$total_pages = ceil($total_reviews / $per_page);

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
    <title>Đánh giá <?= htmlspecialchars($product['ten_san_pham']) ?> - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        .reviews-page {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .product-summary {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .product-info h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .product-price {
            color: #ff4757;
            font-weight: bold;
            font-size: 18px;
        }
        
        .rating-overview {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .overview-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .overall-stats {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .average-rating {
            text-align: center;
        }
        
        .average-score {
            font-size: 48px;
            font-weight: bold;
            color: #f39c12;
            line-height: 1;
        }
        
        .average-stars {
            margin: 10px 0;
        }
        
        .total-reviews {
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
            margin-bottom: 8px;
        }
        
        .star-label {
            min-width: 60px;
            font-size: 14px;
            color: #666;
        }
        
        .bar-container {
            flex: 1;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: #f39c12;
            transition: width 0.3s ease;
        }
        
        .count {
            min-width: 40px;
            font-size: 14px;
            color: #666;
            text-align: right;
        }
        
        .reviews-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .review-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .reviewer-info {
            flex: 1;
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
        
        .review-rating {
            margin-left: 20px;
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
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #f39c12;
            color: white;
            border-color: #f39c12;
        }
        
        .pagination .current {
            background: #f39c12;
            color: white;
            border-color: #f39c12;
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
        
        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .product-summary {
                flex-direction: column;
                text-align: center;
            }
            
            .overview-header {
                flex-direction: column;
                gap: 20px;
            }
            
            .overall-stats {
                flex-direction: column;
                gap: 20px;
            }
            
            .review-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .review-rating {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="reviews-page">
            <a href="chi-tiet-san-pham.php?id=<?= $product_id ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Quay lại sản phẩm
            </a>

            <!-- Product Summary -->
            <div class="product-summary">
                <img src="<?= getImageUrl($product['duong_dan_hinh_anh'], $product['ten_san_pham']) ?>" 
                     alt="<?= htmlspecialchars($product['ten_san_pham']) ?>" 
                     class="product-image">
                <div class="product-info">
                    <h1><?= htmlspecialchars($product['ten_san_pham']) ?></h1>
                    <div class="product-price">
                        <?= formatPrice($product['gia_khuyen_mai'] ?? $product['gia_ban']) ?>đ
                    </div>
                </div>
            </div>

            <!-- Rating Overview -->
            <div class="rating-overview">
                <div class="overview-header">
                    <h2 class="overview-title">
                        <i class="fas fa-star"></i> 
                        Tất cả đánh giá (<?= $total_reviews ?>)
                    </h2>
                </div>
                
                <div class="overall-stats">
                    <div class="average-rating">
                        <div class="average-score"><?= number_format($rating_stats['trung_binh_sao'] ?? 0, 1) ?></div>
                        <div class="average-stars">
                            <?= format_stars($rating_stats['trung_binh_sao'] ?? 0) ?>
                        </div>
                        <div class="total-reviews"><?= $total_reviews ?> đánh giá</div>
                    </div>
                    
                    <div class="rating-breakdown">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="rating-bar">
                                <span class="star-label"><?= $i ?> sao</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: <?= $total_reviews > 0 ? number_format(($rating_stats["so_sao_$i"] / $total_reviews) * 100, 2) : 0 ?>%;"></div>
                                </div>
                                <span class="count"><?= $rating_stats["so_sao_$i"] ?? 0 ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
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
                                    <div class="review-date"><?= date('d/m/Y H:i', strtotime($review['ngay_tao'])) ?></div>
                                </div>
                                <div class="review-rating">
                                    <?= format_stars($review['so_sao']) ?>
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
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?product_id=<?= $product_id ?>&page=<?= $page - 1 ?>">
                            <i class="fas fa-chevron-left"></i> Trước
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?product_id=<?= $product_id ?>&page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?product_id=<?= $product_id ?>&page=<?= $page + 1 ?>">
                            Sau <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 