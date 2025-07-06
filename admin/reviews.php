<?php
include '../config/dual_session.php';
include '../config/database.php';
include '../config/reviews.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập admin
require_admin_login();

$success_message = '';
$error_message = '';

// Xử lý cập nhật trạng thái đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $review_id = intval($_POST['review_id']);
        $new_status = $_POST['new_status'];
        
        if (!in_array($new_status, ['cho_duyet', 'da_duyet', 'tu_choi'])) {
            throw new Exception("Trạng thái không hợp lệ");
        }
        
        // Cập nhật trạng thái
        $update_sql = "UPDATE danh_gia_san_pham SET trang_thai = ? WHERE ma_danh_gia = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $review_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Lỗi khi cập nhật trạng thái");
        }
        
        // Lấy thông tin sản phẩm để cập nhật thống kê
        $product_sql = "SELECT ma_san_pham FROM danh_gia_san_pham WHERE ma_danh_gia = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("i", $review_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        $product = $product_result->fetch_assoc();
        $product_stmt->close();
        
        if ($product) {
            update_product_rating_stats($product['ma_san_pham'], $conn);
        }
        
        $success_message = "Cập nhật trạng thái đánh giá thành công!";
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Xử lý xóa đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    try {
        $review_id = intval($_POST['review_id']);
        
        // Lấy thông tin sản phẩm trước khi xóa
        $product_sql = "SELECT ma_san_pham FROM danh_gia_san_pham WHERE ma_danh_gia = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("i", $review_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        $product = $product_result->fetch_assoc();
        $product_stmt->close();
        
        // Xóa đánh giá
        $delete_sql = "DELETE FROM danh_gia_san_pham WHERE ma_danh_gia = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $review_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Lỗi khi xóa đánh giá");
        }
        
        // Cập nhật thống kê sản phẩm
        if ($product) {
            update_product_rating_stats($product['ma_san_pham'], $conn);
        }
        
        $success_message = "Xóa đánh giá thành công!";
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Lọc và tìm kiếm
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Xây dựng query
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter) {
    $where_conditions[] = "dg.trang_thai = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($search) {
    $where_conditions[] = "(sp.ten_san_pham LIKE ? OR nd.ho_ten LIKE ? OR dg.tieu_de LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Đếm tổng số đánh giá
$count_sql = "SELECT COUNT(*) as total FROM danh_gia_san_pham dg
              JOIN san_pham_thuoc sp ON dg.ma_san_pham = sp.ma_san_pham
              JOIN nguoi_dung nd ON dg.ma_nguoi_dung = nd.ma_nguoi_dung
              $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
} else {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute();
}

$count_result = $count_stmt->get_result();
$total_reviews = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $per_page);

// Lấy danh sách đánh giá
$reviews_sql = "SELECT 
                    dg.ma_danh_gia,
                    dg.so_sao,
                    dg.tieu_de,
                    dg.noi_dung,
                    dg.trang_thai,
                    dg.ngay_tao,
                    sp.ma_san_pham,
                    sp.ten_san_pham,
                    nd.ma_nguoi_dung,
                    nd.ho_ten,
                    nd.email
                FROM danh_gia_san_pham dg
                JOIN san_pham_thuoc sp ON dg.ma_san_pham = sp.ma_san_pham
                JOIN nguoi_dung nd ON dg.ma_nguoi_dung = nd.ma_nguoi_dung
                $where_clause
                ORDER BY dg.ngay_tao DESC
                LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param($param_types, ...$params);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

$reviews = [];
while ($review = $reviews_result->fetch_assoc()) {
    $reviews[] = $review;
}

// Thống kê tổng quan
$stats_sql = "SELECT 
                COUNT(*) as total_reviews,
                COUNT(CASE WHEN trang_thai = 'cho_duyet' THEN 1 END) as pending_reviews,
                COUNT(CASE WHEN trang_thai = 'da_duyet' THEN 1 END) as approved_reviews,
                COUNT(CASE WHEN trang_thai = 'tu_choi' THEN 1 END) as rejected_reviews,
                AVG(CASE WHEN trang_thai = 'da_duyet' THEN so_sao END) as avg_rating
              FROM danh_gia_san_pham";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đánh giá sản phẩm - Admin VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #f39c12;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-form select,
        .filter-form input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-form button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-form button:hover {
            background: #2980b9;
        }
        
        .reviews-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .reviews-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .reviews-table th,
        .reviews-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .reviews-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-cho_duyet {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-da_duyet {
            background: #d4edda;
            color: #155724;
        }
        
        .status-tu_choi {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-small {
            padding: 4px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-approve {
            background: #27ae60;
            color: white;
        }
        
        .btn-reject {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete {
            background: #95a5a6;
            color: white;
        }
        
        .review-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
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
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>
        
        <div class="main-content">
            <?php 
            $page_title = 'Quản lý đánh giá sản phẩm';
            $page_icon = 'fas fa-star';
            include '../includes/admin-header.php'; 
            ?>
            
            <div class="dashboard-content">

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Thống kê tổng quan -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_reviews'] ?></div>
                <div class="stat-label">Tổng số đánh giá</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending_reviews'] ?></div>
                <div class="stat-label">Chờ duyệt</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['approved_reviews'] ?></div>
                <div class="stat-label">Đã duyệt</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['rejected_reviews'] ?></div>
                <div class="stat-label">Từ chối</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></div>
                <div class="stat-label">Điểm trung bình</div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="filters">
            <form method="GET" class="filter-form">
                <select name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="cho_duyet" <?= $status_filter === 'cho_duyet' ? 'selected' : '' ?>>Chờ duyệt</option>
                    <option value="da_duyet" <?= $status_filter === 'da_duyet' ? 'selected' : '' ?>>Đã duyệt</option>
                    <option value="tu_choi" <?= $status_filter === 'tu_choi' ? 'selected' : '' ?>>Từ chối</option>
                </select>
                
                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm, người dùng..." 
                       value="<?= htmlspecialchars($search) ?>">
                
                <button type="submit">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
                
                <a href="reviews.php" class="btn-small" style="background: #6c757d; color: white;">
                    <i class="fas fa-refresh"></i> Làm mới
                </a>
            </form>
        </div>

        <!-- Bảng đánh giá -->
        <div class="reviews-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sản phẩm</th>
                        <th>Người đánh giá</th>
                        <th>Điểm</th>
                        <th>Tiêu đề</th>
                        <th>Nội dung</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                                Không có đánh giá nào
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?= $review['ma_danh_gia'] ?></td>
                                <td>
                                    <a href="../chi-tiet-san-pham.php?id=<?= $review['ma_san_pham'] ?>" target="_blank">
                                        <?= htmlspecialchars($review['ten_san_pham']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($review['ho_ten']) ?></div>
                                    <small style="color: #666;"><?= htmlspecialchars($review['email']) ?></small>
                                </td>
                                <td>
                                    <div style="color: #f39c12;">
                                        <?= format_stars($review['so_sao']) ?>
                                    </div>
                                    <small><?= $review['so_sao'] ?>/5</small>
                                </td>
                                <td><?= htmlspecialchars($review['tieu_de'] ?: '-') ?></td>
                                <td class="review-content" title="<?= htmlspecialchars($review['noi_dung']) ?>">
                                    <?= htmlspecialchars($review['noi_dung']) ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $review['trang_thai'] ?>">
                                        <?php
                                        switch ($review['trang_thai']) {
                                            case 'cho_duyet':
                                                echo 'Chờ duyệt';
                                                break;
                                            case 'da_duyet':
                                                echo 'Đã duyệt';
                                                break;
                                            case 'tu_choi':
                                                echo 'Từ chối';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($review['ngay_tao'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($review['trang_thai'] === 'cho_duyet'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="review_id" value="<?= $review['ma_danh_gia'] ?>">
                                                <input type="hidden" name="new_status" value="da_duyet">
                                                <button type="submit" name="update_status" class="btn-small btn-approve" 
                                                        onclick="return confirm('Duyệt đánh giá này?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="review_id" value="<?= $review['ma_danh_gia'] ?>">
                                                <input type="hidden" name="new_status" value="tu_choi">
                                                <button type="submit" name="update_status" class="btn-small btn-reject"
                                                        onclick="return confirm('Từ chối đánh giá này?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="review_id" value="<?= $review['ma_danh_gia'] ?>">
                                            <button type="submit" name="delete_review" class="btn-small btn-delete"
                                                    onclick="return confirm('Xóa đánh giá này? Hành động này không thể hoàn tác.')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-chevron-left"></i> Trước
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                        Sau <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
</body>
</html> 