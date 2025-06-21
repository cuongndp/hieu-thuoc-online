<?php
session_start();
include 'config/database.php';

// Xử lý add to cart - FIXED VERSION (giống trang danh mục)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = 1;
    
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: Login.php');
        exit;
    }
    
    $user_id = $_SESSION['user_id'] ?? 0;
    
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
                
                if ($update_stmt->execute()) {
                    $_SESSION['success_message'] = "Đã cập nhật số lượng sản phẩm trong giỏ hàng!";
                    error_log("Updated quantity for product $product_id");
                } else {
                    $_SESSION['error_message'] = "Lỗi khi cập nhật sản phẩm!";
                    error_log("Failed to update product $product_id");
                }
            } else {
                // Nếu chưa có, thêm mới
                $insert_sql = "INSERT INTO gio_hang (ma_nguoi_dung, ma_san_pham, so_luong, ngay_them) VALUES (?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                
                if ($insert_stmt->execute()) {
                    $_SESSION['success_message'] = "Đã thêm sản phẩm vào giỏ hàng!";
                    error_log("Added new product $product_id to cart");
                } else {
                    $_SESSION['error_message'] = "Lỗi khi thêm sản phẩm!";
                    error_log("Failed to insert product $product_id");
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Có lỗi xảy ra: " . $e->getMessage();
            error_log("Exception in add to cart: " . $e->getMessage());
        }
    } else {
        $_SESSION['error_message'] = "Thông tin không hợp lệ!";
        error_log("Invalid data - User ID: $user_id, Product ID: $product_id");
    }
    
    // Redirect để tránh resubmit
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Lấy tham số tìm kiếm
$search_query = trim($_GET['q'] ?? '');
$category_filter = $_GET['cat'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'relevance';
$price_filter = $_GET['price'] ?? 'all';
$page = intval($_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Thống kê tìm kiếm
$total_products = 0;
$search_results = [];

if (!empty($search_query)) {
    // Tạo query tìm kiếm với LIKE
    $search_sql = "
        SELECT 
            sp.ma_san_pham,
            sp.ten_san_pham,
            sp.gia_ban,
            sp.gia_khuyen_mai,
            sp.mo_ta,
            sp.can_don_thuoc,
            sp.san_pham_noi_bat,
            sp.ten_hoat_chat,
            sp.ham_luong,
            dm.ten_danh_muc,
            nsx.ten_nha_san_xuat,
            ha.duong_dan_hinh_anh
        FROM san_pham_thuoc sp
        LEFT JOIN danh_muc_thuoc dm ON sp.ma_danh_muc = dm.ma_danh_muc
        LEFT JOIN nha_san_xuat nsx ON sp.ma_nha_san_xuat = nsx.ma_nha_san_xuat
        LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = TRUE
        WHERE sp.trang_thai_hoat_dong = TRUE
        AND (
            sp.ten_san_pham LIKE ? 
            OR sp.mo_ta LIKE ?
            OR sp.ten_hoat_chat LIKE ?
            OR nsx.ten_nha_san_xuat LIKE ?
        )
    ";
    
    // Thêm filter theo danh mục
    if ($category_filter !== 'all') {
        $search_sql .= " AND sp.ma_danh_muc = ?";
    }
    
    // Thêm filter theo giá
    if ($price_filter !== 'all') {
        if ($price_filter === '500000+') {
            $search_sql .= " AND (COALESCE(sp.gia_khuyen_mai, sp.gia_ban) >= 500000)";
        } else {
            $price_parts = explode('-', $price_filter);
            if (count($price_parts) === 2) {
                $min_price = intval($price_parts[0]);
                $max_price = intval($price_parts[1]);
                $search_sql .= " AND (COALESCE(sp.gia_khuyen_mai, sp.gia_ban) BETWEEN $min_price AND $max_price)";
            }
        }
    }
    
    // Thêm ORDER BY
    switch ($sort_by) {
        case 'name-asc':
            $search_sql .= " ORDER BY sp.ten_san_pham ASC";
            break;
        case 'name-desc':
            $search_sql .= " ORDER BY sp.ten_san_pham DESC";
            break;
        case 'price-asc':
            $search_sql .= " ORDER BY COALESCE(sp.gia_khuyen_mai, sp.gia_ban) ASC";
            break;
        case 'price-desc':
            $search_sql .= " ORDER BY COALESCE(sp.gia_khuyen_mai, sp.gia_ban) DESC";
            break;
        case 'newest':
            $search_sql .= " ORDER BY sp.ngay_tao DESC";
            break;
        default: // relevance
            $search_sql .= " ORDER BY sp.san_pham_noi_bat DESC, sp.ten_san_pham ASC";
            break;
    }
    
    // Thêm LIMIT cho pagination
    $search_sql .= " LIMIT ? OFFSET ?";
    
    // Chuẩn bị tham số
    $search_param = "%{$search_query}%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $param_types = "ssss";
    
    if ($category_filter !== 'all') {
        $params[] = intval($category_filter);
        $param_types .= "i";
    }
    
    $params[] = $per_page;
    $params[] = $offset;
    $param_types .= "ii";
    
    // Thực hiện query
    $search_stmt = $conn->prepare($search_sql);
    $search_stmt->bind_param($param_types, ...$params);
    $search_stmt->execute();
    $search_result = $search_stmt->get_result();
    
    while ($row = $search_result->fetch_assoc()) {
        $search_results[] = $row;
    }
    
    // Đếm tổng số kết quả (cho pagination)
    $count_sql = str_replace("SELECT sp.ma_san_pham, sp.ten_san_pham, sp.gia_ban, sp.gia_khuyen_mai, sp.mo_ta, sp.can_don_thuoc, sp.san_pham_noi_bat, sp.ten_hoat_chat, sp.ham_luong, dm.ten_danh_muc, nsx.ten_nha_san_xuat, ha.duong_dan_hinh_anh", "SELECT COUNT(*)", $search_sql);
    $count_sql = preg_replace('/ORDER BY.*LIMIT.*OFFSET.*$/', '', $count_sql);
    
    $count_params = array_slice($params, 0, -2); // Bỏ LIMIT và OFFSET
    $count_types = substr($param_types, 0, -2);
    
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($count_types, ...$count_params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_row();
    $total_products = $count_row ? $count_row[0] : 0;
}

// Lấy danh sách danh mục cho filter
$categories_sql = "SELECT ma_danh_muc, ten_danh_muc FROM danh_muc_thuoc WHERE trang_thai_hoat_dong = TRUE ORDER BY ten_danh_muc";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
}

// Tính pagination
$total_pages = ceil($total_products / $per_page);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($search_query) ? 'Tìm kiếm: ' . htmlspecialchars($search_query) : 'Tìm kiếm sản phẩm'; ?> - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/search.css">
    
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Search Header -->
    <div class="search-header">
        <div class="container">
            <h1>Tìm kiếm sản phẩm</h1>
            <div class="search-box-large">
                <form method="GET" action="search.php">
                    <input type="text" name="q" placeholder="Nhập tên thuốc, hoạt chất, thương hiệu..." 
                           value="<?php echo htmlspecialchars($search_query); ?>" required>
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="search-container">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($search_query)): ?>
            <!-- Search Results Info -->
            <div class="search-results-info">
                <h2>Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($search_query); ?>"</h2>
                <p>Tìm thấy <?php echo $total_products; ?> sản phẩm</p>
            </div>

            <!-- Search Filters -->
            <div class="search-filters">
                <form method="GET" action="search.php">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label>Danh mục:</label>
                            <select name="cat" class="filter-select">
                                <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['ma_danh_muc']; ?>" 
                                            <?php echo $category_filter == $cat['ma_danh_muc'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Sắp xếp:</label>
                            <select name="sort" class="filter-select">
                                <option value="relevance" <?php echo $sort_by === 'relevance' ? 'selected' : ''; ?>>Liên quan nhất</option>
                                <option value="name-asc" <?php echo $sort_by === 'name-asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                                <option value="name-desc" <?php echo $sort_by === 'name-desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                                <option value="price-asc" <?php echo $sort_by === 'price-asc' ? 'selected' : ''; ?>>Giá thấp - cao</option>
                                <option value="price-desc" <?php echo $sort_by === 'price-desc' ? 'selected' : ''; ?>>Giá cao - thấp</option>
                                <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Khoảng giá:</label>
                            <select name="price" class="filter-select">
                                <option value="all" <?php echo $price_filter === 'all' ? 'selected' : ''; ?>>Tất cả giá</option>
                                <option value="0-50000" <?php echo $price_filter === '0-50000' ? 'selected' : ''; ?>>Dưới 50.000đ</option>
                                <option value="50000-200000" <?php echo $price_filter === '50000-200000' ? 'selected' : ''; ?>>50.000đ - 200.000đ</option>
                                <option value="200000-500000" <?php echo $price_filter === '200000-500000' ? 'selected' : ''; ?>>200.000đ - 500.000đ</option>
                                <option value="500000+" <?php echo $price_filter === '500000+' ? 'selected' : ''; ?>>Trên 500.000đ</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <button type="submit" class="filter-btn">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Search Results -->
            <?php if (!empty($search_results)): ?>
                <div class="search-results">
                    <?php foreach ($search_results as $product): ?>
                        <div class="product-card">
                            <?php 
                            // Tính badge
                            if ($product['can_don_thuoc']) {
                                echo '<div class="product-badge prescription">Kê đơn</div>';
                            } elseif ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']) {
                                $discount = round((($product['gia_ban'] - $product['gia_khuyen_mai']) / $product['gia_ban']) * 100);
                                echo '<div class="product-badge">-' . $discount . '%</div>';
                            } elseif ($product['san_pham_noi_bat']) {
                                echo '<div class="product-badge featured">Nổi bật</div>';
                            }
                            ?>
                            
                            <div class="product-image">
                                <img src="<?php echo $product['duong_dan_hinh_anh'] ?: 'https://via.placeholder.com/150x150?text=' . urlencode($product['ten_san_pham']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                            </div>
                            
                            <div class="product-category"><?php echo htmlspecialchars($product['ten_danh_muc']); ?></div>
                            
                            <h3>
                                <a href="chi-tiet-san-pham.php?id=<?php echo $product['ma_san_pham']; ?>">
                                    <?php echo htmlspecialchars($product['ten_san_pham']); ?>
                                </a>
                            </h3>
                            
                            <div class="product-manufacturer"><?php echo htmlspecialchars($product['ten_nha_san_xuat'] ?: 'Không rõ'); ?></div>
                            
                            <?php if ($product['ten_hoat_chat'] || $product['ham_luong']): ?>
                            <div class="product-manufacturer">
                                <?php echo htmlspecialchars($product['ten_hoat_chat']); ?>
                                <?php if ($product['ham_luong']): ?>
                                    - <?php echo htmlspecialchars($product['ham_luong']); ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
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
                                <a href="chi-tiet-san-pham.php?id=<?php echo $product['ma_san_pham']; ?>" class="view-detail" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?q=<?php echo urlencode($search_query); ?>&cat=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&price=<?php echo urlencode($price_filter); ?>&page=<?php echo $page - 1; ?>" class="page-btn">
                            <i class="fas fa-chevron-left"></i> Trước
                        </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?q=<?php echo urlencode($search_query); ?>&cat=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&price=<?php echo urlencode($price_filter); ?>&page=<?php echo $i; ?>" 
                           class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?q=<?php echo urlencode($search_query); ?>&cat=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&price=<?php echo urlencode($price_filter); ?>&page=<?php echo $page + 1; ?>" class="page-btn">
                            Sau <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Results -->
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Không tìm thấy sản phẩm</h3>
                    <p>Không tìm thấy sản phẩm nào phù hợp với từ khóa "<?php echo htmlspecialchars($search_query); ?>"</p>
                    <p>Vui lòng thử lại với từ khóa khác hoặc kiểm tra chính tả.</p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Search Suggestions -->
            <div class="search-suggestions">
                <h3>Gợi ý tìm kiếm phổ biến:</h3>
                <div class="suggestions-list">
                    <a href="search.php?q=paracetamol" class="suggestion-tag">Paracetamol</a>
                    <a href="search.php?q=vitamin c" class="suggestion-tag">Vitamin C</a>
                    <a href="search.php?q=amoxicillin" class="suggestion-tag">Amoxicillin</a>
                    <a href="search.php?q=omega 3" class="suggestion-tag">Omega 3</a>
                    <a href="search.php?q=calcium" class="suggestion-tag">Calcium</a>
                    <a href="search.php?q=thuốc cảm cúm" class="suggestion-tag">Thuốc cảm cúm</a>
                    <a href="search.php?q=thuốc đau đầu" class="suggestion-tag">Thuốc đau đầu</a>
                    <a href="search.php?q=thuốc dạ dày" class="suggestion-tag">Thuốc dạ dày</a>
                </div>
            </div>

            <!-- Popular Categories -->
            <div class="search-suggestions">
                <h3>Danh mục phổ biến:</h3>
                <div class="suggestions-list">
                    <a href="danh-muc.php?cat=thuoc-khong-ke-don" class="suggestion-tag">Thuốc không kê đơn</a>
                    <a href="danh-muc.php?cat=vitamin-khoang-chat" class="suggestion-tag">Vitamin & Khoáng chất</a>
                    <a href="danh-muc.php?cat=thuc-pham-chuc-nang" class="suggestion-tag">Thực phẩm chức năng</a>
                    <a href="danh-muc.php?cat=duoc-my-pham" class="suggestion-tag">Dược mỹ phẩm</a>
                    <a href="danh-muc.php?cat=thiet-bi-y-te" class="suggestion-tag">Thiết bị y tế</a>
                    <a href="danh-muc.php?cat=me-va-be" class="suggestion-tag">Mẹ & bé</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
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
                        <div class="member-avatar">B</div>
                        <div class="member-name">Lê Hải Bằng</div>
                        <div class="member-id">MSSV: 21010001</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">P</div>
                        <div class="member-name">nguyễn Văn Phong</div>
                        <div class="member-id">MSSV: 21010002</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">C</div>
                        <div class="member-name">Nguyễn Đăng Phúc Cường</div>
                        <div class="member-id">MSSV: 21010003</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">D</div>
                        <div class="member-name">Lý Khánh Đăng</div>
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
        // JavaScript cho UI interaction
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alert messages sau 5 giây
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>