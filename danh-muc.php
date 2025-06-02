<?php
session_start();
include 'config/database.php';
include 'config/category_mapping.php';

// Xử lý add to cart trước khi hiển thị gì cả
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: Login.php');
        exit;
    }
    
    $product_id = $_POST['product_id'] ?? 0;
    $product_name = $_POST['product_name'] ?? '';
    $product_price = $_POST['product_price'] ?? 0;
    
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
    
    // Redirect để tránh resubmit
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Lấy category từ URL
$category_slug = $_GET['cat'] ?? 'thuoc-khong-ke-don';
$category_id = get_category_id($category_slug);

// Lấy thông tin danh mục
$category_sql = "SELECT * FROM danh_muc_thuoc WHERE ma_danh_muc = ? AND trang_thai_hoat_dong = TRUE";
$category_stmt = $conn->prepare($category_sql);
$category_stmt->bind_param("i", $category_id);
$category_stmt->execute();
$category_result = $category_stmt->get_result();
$category_info = $category_result->fetch_assoc();

// Nếu không tìm thấy danh mục
if (!$category_info) {
    $category_info = [
        'ten_danh_muc' => 'Danh mục sản phẩm',
        'mo_ta' => 'Khám phá các sản phẩm chất lượng cao'
    ];
}

// Lấy các tham số filter và sort
$sort_by = $_GET['sort'] ?? 'default';
$price_filter = $_GET['price'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';
$view_type = $_GET['view'] ?? 'grid';

// Tạo query lấy sản phẩm với filter
$products_sql = "
    SELECT 
        sp.ma_san_pham,
        sp.ten_san_pham,
        sp.gia_ban,
        sp.gia_khuyen_mai,
        sp.mo_ta,
        sp.can_don_thuoc,
        sp.san_pham_noi_bat,
        sp.ngay_tao,
        nsx.ten_nha_san_xuat,
        ha.duong_dan_hinh_anh
    FROM san_pham_thuoc sp
    LEFT JOIN nha_san_xuat nsx ON sp.ma_nha_san_xuat = nsx.ma_nha_san_xuat
    LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = TRUE
    WHERE sp.ma_danh_muc = ? AND sp.trang_thai_hoat_dong = TRUE
";

// Thêm filter theo giá
if ($price_filter !== 'all') {
    if ($price_filter === '500000+') {
        $products_sql .= " AND (COALESCE(sp.gia_khuyen_mai, sp.gia_ban) >= 500000)";
    } else {
        $price_parts = explode('-', $price_filter);
        if (count($price_parts) === 2) {
            $min_price = intval($price_parts[0]);
            $max_price = intval($price_parts[1]);
            $products_sql .= " AND (COALESCE(sp.gia_khuyen_mai, sp.gia_ban) BETWEEN $min_price AND $max_price)";
        }
    }
}

// Thêm filter theo thương hiệu
if ($brand_filter !== 'all') {
    $products_sql .= " AND LOWER(nsx.ten_nha_san_xuat) LIKE '%$brand_filter%'";
}

// Thêm ORDER BY
switch ($sort_by) {
    case 'name-asc':
        $products_sql .= " ORDER BY sp.ten_san_pham ASC";
        break;
    case 'name-desc':
        $products_sql .= " ORDER BY sp.ten_san_pham DESC";
        break;
    case 'price-asc':
        $products_sql .= " ORDER BY COALESCE(sp.gia_khuyen_mai, sp.gia_ban) ASC";
        break;
    case 'price-desc':
        $products_sql .= " ORDER BY COALESCE(sp.gia_khuyen_mai, sp.gia_ban) DESC";
        break;
    case 'newest':
        $products_sql .= " ORDER BY sp.ngay_tao DESC";
        break;
    default:
        $products_sql .= " ORDER BY sp.san_pham_noi_bat DESC, sp.ngay_tao DESC";
        break;
}

$products_stmt = $conn->prepare($products_sql);
$products_stmt->bind_param("i", $category_id);
$products_stmt->execute();
$products_result = $products_stmt->get_result();

$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

// Debug: Hiển thị số sản phẩm tìm được
echo "<!-- Debug: Category ID = $category_id, Found " . count($products) . " products -->";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_info['ten_danh_muc']); ?> - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/danh-muc.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <ul class="breadcrumb-list">
                <li><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><i class="fas fa-chevron-right"></i></li>
                <li class="current"><?php echo htmlspecialchars($category_info['ten_danh_muc']); ?></li>
            </ul>
        </div>
    </div>

    <!-- Category Header -->
    <section class="category-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($category_info['ten_danh_muc']); ?></h1>
            <p><?php echo htmlspecialchars($category_info['mo_ta']); ?></p>
            <div class="category-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($products); ?></span>
                    <span class="stat-label">Sản phẩm</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">10+</span>
                    <span class="stat-label">Thương hiệu</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">5★</span>
                    <span class="stat-label">Đánh giá</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <div class="filter-container">
                <form method="GET" class="filter-left">
                    <input type="hidden" name="cat" value="<?php echo htmlspecialchars($category_slug); ?>">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_type); ?>">
                    
                    <div class="filter-group">
                        <label>Sắp xếp:</label>
                        <select class="filter-select" name="sort" onchange="this.form.submit()">
                            <option value="default" <?php echo $sort_by === 'default' ? 'selected' : ''; ?>>Mặc định</option>
                            <option value="name-asc" <?php echo $sort_by === 'name-asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                            <option value="name-desc" <?php echo $sort_by === 'name-desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                            <option value="price-asc" <?php echo $sort_by === 'price-asc' ? 'selected' : ''; ?>>Giá thấp - cao</option>
                            <option value="price-desc" <?php echo $sort_by === 'price-desc' ? 'selected' : ''; ?>>Giá cao - thấp</option>
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Giá:</label>
                        <select class="filter-select" name="price" onchange="this.form.submit()">
                            <option value="all" <?php echo $price_filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                            <option value="0-50000" <?php echo $price_filter === '0-50000' ? 'selected' : ''; ?>>Dưới 50.000đ</option>
                            <option value="50000-200000" <?php echo $price_filter === '50000-200000' ? 'selected' : ''; ?>>50.000đ - 200.000đ</option>
                            <option value="200000-500000" <?php echo $price_filter === '200000-500000' ? 'selected' : ''; ?>>200.000đ - 500.000đ</option>
                            <option value="500000+" <?php echo $price_filter === '500000+' ? 'selected' : ''; ?>>Trên 500.000đ</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Thương hiệu:</label>
                        <select class="filter-select" name="brand" onchange="this.form.submit()">
                            <option value="all" <?php echo $brand_filter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                            <option value="teva" <?php echo $brand_filter === 'teva' ? 'selected' : ''; ?>>Teva</option>
                            <option value="sanofi" <?php echo $brand_filter === 'sanofi' ? 'selected' : ''; ?>>Sanofi</option>
                            <option value="pfizer" <?php echo $brand_filter === 'pfizer' ? 'selected' : ''; ?>>Pfizer</option>
                            <option value="hau-giang" <?php echo $brand_filter === 'hau-giang' ? 'selected' : ''; ?>>Hậu Giang</option>
                        </select>
                    </div>
                </form>
                
                <div class="view-toggle">
                    <form method="GET" style="display: inline;">
                        <input type="hidden" name="cat" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($price_filter); ?>">
                        <input type="hidden" name="brand" value="<?php echo htmlspecialchars($brand_filter); ?>">
                        <input type="hidden" name="view" value="grid">
                        <button type="submit" class="view-btn <?php echo $view_type === 'grid' ? 'active' : ''; ?>">
                            <i class="fas fa-th"></i>
                        </button>
                    </form>
                    <form method="GET" style="display: inline;">
                        <input type="hidden" name="cat" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($price_filter); ?>">
                        <input type="hidden" name="brand" value="<?php echo htmlspecialchars($brand_filter); ?>">
                        <input type="hidden" name="view" value="list">
                        <button type="submit" class="view-btn <?php echo $view_type === 'list' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <!-- Grid View -->
            <?php if ($view_type === 'grid'): ?>
            <div class="products-grid active" id="products-grid">
                <?php if (empty($products)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px;">
                        <h3>Không có sản phẩm nào</h3>
                        <p>Danh mục này hiện tại chưa có sản phẩm phù hợp với bộ lọc đã chọn.</p>
                        <a href="?cat=<?php echo $category_slug; ?>" style="color: #3498db; text-decoration: underline;">Xóa bộ lọc</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-product-id="<?php echo $product['ma_san_pham']; ?>">
                            <?php 
                            // Tính badge
                            if ($product['can_don_thuoc']) {
                                echo '<div class="product-badge prescription">Kê đơn</div>';
                            } elseif ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']) {
                                $discount = round((($product['gia_ban'] - $product['gia_khuyen_mai']) / $product['gia_ban']) * 100);
                                echo '<div class="product-badge">Giảm ' . $discount . '%</div>';
                            } elseif ($product['san_pham_noi_bat']) {
                                echo '<div class="product-badge">Nổi bật</div>';
                            }
                            ?>
                            
                            <div class="product-image">
                                <img src="<?php echo $product['duong_dan_hinh_anh'] ?: 'https://via.placeholder.com/150x150?text=' . urlencode($product['ten_san_pham']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                            </div>
                            
                            <h3><?php echo htmlspecialchars($product['ten_san_pham']); ?></h3>
                            <div class="product-manufacturer"><?php echo htmlspecialchars($product['ten_nha_san_xuat'] ?: 'Không rõ'); ?></div>
                            
                            <div class="product-price">
                                <span class="current-price">
                                    <?php echo number_format($product['gia_khuyen_mai'] ?: $product['gia_ban'], 0, ',', '.'); ?>đ
                                </span>
                                <?php if ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']): ?>
                                    <span class="old-price"><?php echo number_format($product['gia_ban'], 0, ',', '.'); ?>đ</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $product['ma_san_pham']; ?>">
                                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                                        <input type="hidden" name="product_price" value="<?php echo $product['gia_khuyen_mai'] ?: $product['gia_ban']; ?>">
                                        <button type="submit" class="add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="Login.php" class="add-to-cart">
                                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                    </a>
                                <?php endif; ?>
                                <a href="chi-tiet-san-pham.php?id=<?php echo $product['ma_san_pham']; ?>" class="quick-view" title="Xem chi tiết sản phẩm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- List View -->
            <?php if ($view_type === 'list'): ?>
            <div class="products-list active" id="products-list">
                <?php if (empty($products)): ?>
                    <div style="text-align: center; padding: 60px;">
                        <h3>Không có sản phẩm nào</h3>
                        <p>Danh mục này hiện tại chưa có sản phẩm phù hợp với bộ lọc đã chọn.</p>
                        <a href="?cat=<?php echo $category_slug; ?>" style="color: #3498db; text-decoration: underline;">Xóa bộ lọc</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-list-item">
                            <div class="list-product-image">
                                <img src="<?php echo $product['duong_dan_hinh_anh'] ?: 'https://via.placeholder.com/150x150?text=' . urlencode($product['ten_san_pham']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                            </div>
                            <div class="list-product-info">
                                <h3>
                                    <a href="product-detail.php?id=<?php echo $product['ma_san_pham']; ?>" style="text-decoration: none; color: inherit;">
                                        <?php echo htmlspecialchars($product['ten_san_pham']); ?>
                                    </a>
                                    <?php 
                                    if ($product['can_don_thuoc']) {
                                        echo '<span class="product-badge prescription">Kê đơn</span>';
                                    } elseif ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']) {
                                        $discount = round((($product['gia_ban'] - $product['gia_khuyen_mai']) / $product['gia_ban']) * 100);
                                        echo '<span class="product-badge">Giảm ' . $discount . '%</span>';
                                    } elseif ($product['san_pham_noi_bat']) {
                                        echo '<span class="product-badge">Nổi bật</span>';
                                    }
                                    ?>
                                </h3>
                                <div class="product-manufacturer"><?php echo htmlspecialchars($product['ten_nha_san_xuat'] ?: 'Không rõ'); ?></div>
                                <div class="list-product-description"><?php echo htmlspecialchars(substr($product['mo_ta'] ?: 'Sản phẩm chất lượng cao, đảm bảo an toàn cho sức khỏe', 0, 100)); ?>...</div>
                                <div class="list-product-actions">
                                    <div class="product-price">
                                        <span class="current-price">
                                            <?php echo number_format($product['gia_khuyen_mai'] ?: $product['gia_ban'], 0, ',', '.'); ?>đ
                                        </span>
                                        <?php if ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']): ?>
                                            <span class="old-price"><?php echo number_format($product['gia_ban'], 0, ',', '.'); ?>đ</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="add_to_cart" value="1">
                                                <input type="hidden" name="product_id" value="<?php echo $product['ma_san_pham']; ?>">
                                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                                                <input type="hidden" name="product_price" value="<?php echo $product['gia_khuyen_mai'] ?: $product['gia_ban']; ?>">
                                                <button type="submit" class="add-to-cart">
                                                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="Login.php" class="add-to-cart">
                                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                            </a>
                                        <?php endif; ?>
                                        <a href="product-detail.php?id=<?php echo $product['ma_san_pham']; ?>" class="btn btn-primary" style="text-decoration: none;">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if (count($products) > 0): ?>
            <div class="pagination" id="pagination">
                <a href="#" class="page-btn disabled" id="prev-btn">
                    <i class="fas fa-chevron-left"></i> Trước
                </a>
                <a href="#" class="page-btn active">1</a>
                <?php if (count($products) > 12): ?>
                <a href="#" class="page-btn">2</a>
                <a href="#" class="page-btn">...</a>
                <?php endif; ?>
                <a href="#" class="page-btn" id="next-btn">
                    Sau <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <!-- <footer class="footer">
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
    </footer> -->

      <footer class="footer">
        <div class="container">
            <!-- Team Members Section -->
            <div class="team-section">
                <div class="section-title" style="margin: 0 0 40px 0;">
                    <h2 style="color: #ecf0f1;">Thành Viên Nhóm</h2>
                    <p style="color: #bdc3c7;">Đội ngũ phát triển website VitaMeds</p>
                </div>
                
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-avatar">A</div>
                        <div class="member-name">Nguyễn Văn An</div>
                        <div class="member-role">Team Leader - Backend Developer</div>
                        <div class="member-id">MSSV: 21010001</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">B</div>
                        <div class="member-name">Trần Thị Bình</div>
                        <div class="member-role">Frontend Developer - UI/UX</div>
                        <div class="member-id">MSSV: 21010002</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">C</div>
                        <div class="member-name">Lê Minh Cường</div>
                        <div class="member-role">Database Administrator</div>
                        <div class="member-id">MSSV: 21010003</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">D</div>
                        <div class="member-name">Phạm Thị Dung</div>
                        <div class="member-role">Quality Assurance - Tester</div>
                        <div class="member-id">MSSV: 21010004</div>
                    </div>
                </div>
            </div>

            <div class="footer-content">
                <div class="footer-section">
                    <h3>VitaMeds</h3>
                    <p>Đồ án môn học: Lập trình Web<br>
                    Trường: Đại học Giao thông vận tải</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fas fa-envelope"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Thông Tin Dự Án</h3>
                    <ul>
                        <li><a href="#">Mô tả dự án</a></li>
                        <li><a href="#">Tài liệu kỹ thuật</a></li>
                        <li><a href="#">Database Schema</a></li>
                        <li><a href="#">API Documentation</a></li>
                        <li><a href="#">Source Code</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Công Nghệ Sử Dụng</h3>
                    <ul>
                        <li>Frontend: HTML5, CSS3, JavaScript</li>
                        <li>Backend: PHP, MySQL</li>
                        <li>Framework: Bootstrap</li>
                        <li>Tools: VSCode, phpMyAdmin</li>
                        <li>Version Control: Git, GitHub</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Liên Hệ Nhóm</h3>
                    <p><i class="fas fa-envelope"></i> vitameds.team@student.uit.edu.vn</p>
                    <p><i class="fas fa-phone"></i> (+84) 123-456-789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Đại học Giao thông vận tải</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // JavaScript CHỈ cho UI - KHÔNG có backend logic
        
        // Set active navigation
        document.addEventListener('DOMContentLoaded', function() {
            const currentCategory = '<?php echo $category_slug; ?>';
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
                if (item.href.includes('cat=' + currentCategory)) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>