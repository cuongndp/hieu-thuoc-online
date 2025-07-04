<?php
session_start();
include '../config/database.php';
include 'includes/permissions.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!checkPermission('products_add')) {
        $_SESSION['error_message'] = 'Bạn không có quyền thêm sản phẩm!';
        header('Location: products.php');
        exit;
    }
    $ten_san_pham = $_POST['ten_san_pham'];
    $ma_danh_muc = $_POST['ma_danh_muc'];
    $ma_nha_san_xuat = $_POST['ma_nha_san_xuat'];
    $mo_ta = $_POST['mo_ta'];
    $gia_ban = $_POST['gia_ban'];
    $gia_khuyen_mai = !empty($_POST['gia_khuyen_mai']) ? $_POST['gia_khuyen_mai'] : null;
    $so_luong_ton_kho = $_POST['so_luong_ton_kho'];
    $san_pham_noi_bat = isset($_POST['san_pham_noi_bat']) ? 1 : 0;
    $is_flash_sale = isset($_POST['is_flash_sale']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO san_pham_thuoc (ten_san_pham, ma_danh_muc, ma_nha_san_xuat, mo_ta, gia_ban, gia_khuyen_mai, so_luong_ton_kho, san_pham_noi_bat, is_flash_sale) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisdiiii", $ten_san_pham, $ma_danh_muc, $ma_nha_san_xuat, $mo_ta, $gia_ban, $gia_khuyen_mai, $so_luong_ton_kho, $san_pham_noi_bat, $is_flash_sale);
    
    if ($stmt->execute()) {
        $ma_san_pham = $conn->insert_id;
        // Xử lý upload 1 ảnh
        if (!empty($_FILES['hinh_anh']['name'][0])) {
            $img_name = basename($_FILES['hinh_anh']['name'][0]);
            $target = '../images/products/' . $img_name;
            if (move_uploaded_file($_FILES['hinh_anh']['tmp_name'][0], $target)) {
                $duong_dan = 'images/products/' . $img_name;
                $mo_ta_hinh_anh = $ten_san_pham;
                $la_hinh_chinh = 1;
                $stmt_img = $conn->prepare("INSERT INTO hinh_anh_san_pham (ma_san_pham, duong_dan_hinh_anh, mo_ta_hinh_anh, la_hinh_chinh) VALUES (?, ?, ?, ?)");
                $stmt_img->bind_param("issi", $ma_san_pham, $duong_dan, $mo_ta_hinh_anh, $la_hinh_chinh);
                $stmt_img->execute();
            }
        }
        $message = "Thêm sản phẩm thành công!";
    } else {
        $message = "Lỗi khi thêm sản phẩm!";
    }
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!checkPermission('products_delete')) {
        $_SESSION['error_message'] = 'Bạn không có quyền xóa sản phẩm!';
        header('Location: products.php');
        exit;
    }
    $ma_san_pham = $_GET['delete'];
    $stmt = $conn->prepare("UPDATE san_pham_thuoc SET trang_thai_hoat_dong = 0 WHERE ma_san_pham = ?");
    $stmt->bind_param("i", $ma_san_pham);
    
    if ($stmt->execute()) {
        $message = "Xóa sản phẩm thành công!";
    } else {
        $message = "Lỗi khi xóa sản phẩm!";
    }
}

// Xử lý cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    if (!checkPermission('products_edit')) {
        $_SESSION['error_message'] = 'Bạn không có quyền sửa sản phẩm!';
        header('Location: products.php');
        exit;
    }
    $ma_san_pham = $_POST['ma_san_pham'];
    $ten_san_pham = $_POST['ten_san_pham'];
    $ma_danh_muc = $_POST['ma_danh_muc'];
    $ma_nha_san_xuat = $_POST['ma_nha_san_xuat'];
    $mo_ta = $_POST['mo_ta'];
    $gia_ban = $_POST['gia_ban'];
    $gia_khuyen_mai = !empty($_POST['gia_khuyen_mai']) ? $_POST['gia_khuyen_mai'] : null;
    $so_luong_ton_kho = $_POST['so_luong_ton_kho'];
    $san_pham_noi_bat = isset($_POST['san_pham_noi_bat']) ? 1 : 0;
    $is_flash_sale = isset($_POST['is_flash_sale']) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE san_pham_thuoc SET ten_san_pham=?, ma_danh_muc=?, ma_nha_san_xuat=?, mo_ta=?, gia_ban=?, gia_khuyen_mai=?, so_luong_ton_kho=?, san_pham_noi_bat=?, is_flash_sale=? WHERE ma_san_pham=?");
    $stmt->bind_param("siisdiiiii", $ten_san_pham, $ma_danh_muc, $ma_nha_san_xuat, $mo_ta, $gia_ban, $gia_khuyen_mai, $so_luong_ton_kho, $san_pham_noi_bat, $is_flash_sale, $ma_san_pham);
    $stmt->execute();
    // Lấy thông tin ảnh cũ
    $res = $conn->query("SELECT * FROM hinh_anh_san_pham WHERE ma_san_pham=".(int)$ma_san_pham." AND la_hinh_chinh=1 LIMIT 1");
    $old_img = $res->fetch_assoc();
    if (!empty($_FILES['hinh_anh']['name'][0])) {
        // Xóa file ảnh cũ
        if ($old_img && file_exists('../'.$old_img['duong_dan_hinh_anh'])) {
            unlink('../'.$old_img['duong_dan_hinh_anh']);
        }
        // Xóa bản ghi ảnh cũ
        if ($old_img) {
            $conn->query("DELETE FROM hinh_anh_san_pham WHERE ma_hinh_anh=".(int)$old_img['ma_hinh_anh']);
        }
        // Lưu file ảnh mới với tên giống ảnh cũ (nếu có), hoặc tên mới nếu chưa từng có ảnh
        $new_name = $old_img ? basename($old_img['duong_dan_hinh_anh']) : basename($_FILES['hinh_anh']['name'][0]);
        $target = '../images/products/' . $new_name;
        move_uploaded_file($_FILES['hinh_anh']['tmp_name'][0], $target);
        $duong_dan = 'images/products/' . $new_name;
        $mo_ta_hinh_anh = $ten_san_pham;
        $la_hinh_chinh = 1;
        $stmt_img = $conn->prepare("INSERT INTO hinh_anh_san_pham (ma_san_pham, duong_dan_hinh_anh, mo_ta_hinh_anh, la_hinh_chinh) VALUES (?, ?, ?, ?)");
        $stmt_img->bind_param("issi", $ma_san_pham, $duong_dan, $mo_ta_hinh_anh, $la_hinh_chinh);
        $stmt_img->execute();
    }
    $message = "Cập nhật sản phẩm thành công!";
}

// Lấy danh sách sản phẩm
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_clauses = [];
$params = [];
$types = "";

if ($search) {
    $where_clauses[] = "sp.ten_san_pham LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}
if ($filter === 'low_stock') {
    $where_clauses[] = "sp.so_luong_ton_kho <= sp.muc_ton_kho_toi_thieu AND sp.trang_thai_hoat_dong = 1";
}
$where_clause = count($where_clauses) ? ("WHERE " . implode(" AND ", $where_clauses)) : "";

// Đếm tổng sản phẩm
$count_sql = "SELECT COUNT(*) as total FROM san_pham_thuoc sp 
              JOIN danh_muc_thuoc dm ON sp.ma_danh_muc = dm.ma_danh_muc 
              JOIN nha_san_xuat nsx ON sp.ma_nha_san_xuat = nsx.ma_nha_san_xuat 
              $where_clause";

if ($search) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_products = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_products = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_products / $per_page);

// Lấy danh sách sản phẩm
$products_sql = "SELECT sp.*, dm.ten_danh_muc, nsx.ten_nha_san_xuat
                FROM san_pham_thuoc sp 
                JOIN danh_muc_thuoc dm ON sp.ma_danh_muc = dm.ma_danh_muc 
                JOIN nha_san_xuat nsx ON sp.ma_nha_san_xuat = nsx.ma_nha_san_xuat 
                $where_clause
                ORDER BY sp.ngay_tao DESC 
                LIMIT $per_page OFFSET $offset";

if ($search) {
    $products_stmt = $conn->prepare($products_sql);
    $products_stmt->bind_param($types, ...$params);
    $products_stmt->execute();
    $products = $products_stmt->get_result();
} else {
    $products = $conn->query($products_sql);
}

// Lấy danh mục và nhà sản xuất
$categories = $conn->query("SELECT * FROM danh_muc_thuoc WHERE trang_thai_hoat_dong = 1 ORDER BY ten_danh_muc");
$manufacturers = $conn->query("SELECT * FROM nha_san_xuat WHERE trang_thai_hoat_dong = 1 ORDER BY ten_nha_san_xuat");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - VitaMeds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #2c3e50;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            color: #ecf0f1;
            margin-bottom: 5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .search-form {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-form input {
            width: 300px;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            margin-right: 10px;
        }

        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ecf0f1;
        }

        .card-body {
            padding: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-active {
            color: #27ae60;
            font-weight: 600;
        }

        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-featured {
            background: #fff3cd;
            color: #856404;
        }

        .badge-normal {
            background: #e2e3e5;
            color: #6c757d;
        }

        .price-sale {
            color: #e74c3c;
            font-weight: 600;
        }

        .price-normal {
            color: #2c3e50;
            font-weight: 600;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #d1f2eb;
            color: #00695c;
            border-left: 4px solid #27ae60;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .page-link {
            padding: 8px 12px;
            color: #3498db;
            text-decoration: none;
            border: 1px solid #ecf0f1;
            border-radius: 4px;
        }

        .page-link:hover,
        .page-link.active {
            background: #3498db;
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .checkbox-label input {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-pills"></i> Quản lý Sản phẩm</h1>
                <?php if (checkPermission('products_add')): ?>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Thêm sản phẩm
                </button>
                <?php endif; ?>
            </div>

            <?php if (isset($message)): ?>
            <div class="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Search Form -->
            <div class="search-form">
                <form method="GET">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                    <?php if ($search): ?>
                    <a href="products.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Xóa tìm kiếm
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Products Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Danh sách sản phẩm (<?php echo $total_products; ?>)</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Nhà sản xuất</th>
                                <th>Giá bán</th>
                                <th>Tồn kho</th>
                                <th>Nổi bật</th>
                                <th>Trạng thái</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['ten_san_pham']); ?></strong>
                                    <?php if ($product['gia_khuyen_mai']): ?>
                                        <br><small style="color: #27ae60;">Có khuyến mãi</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['ten_danh_muc']); ?></td>
                                <td><?php echo htmlspecialchars($product['ten_nha_san_xuat']); ?></td>
                                <td>
                                    <?php if ($product['gia_khuyen_mai']): ?>
                                        <span class="price-sale"><?php echo number_format($product['gia_khuyen_mai']); ?>đ</span>
                                        <br><del style="color: #7f8c8d;"><?php echo number_format($product['gia_ban']); ?>đ</del>
                                    <?php else: ?>
                                        <span class="price-normal"><?php echo number_format($product['gia_ban']); ?>đ</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['so_luong_ton_kho']; ?></td>
                                <td>
                                    <?php if ($product['san_pham_noi_bat']): ?>
                                        <span class="badge badge-featured">Nổi bật</span>
                                    <?php else: ?>
                                        <span class="badge badge-normal">Thường</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?php echo $product['trang_thai_hoat_dong'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['trang_thai_hoat_dong'] ? 'Hoạt động' : 'Không hoạt động'; ?>
                                    </span>
                                </td>
                                <?php if (checkPermission('products_edit')): ?>
                                <td>
                                    <?php
                                    $img_sql = "SELECT duong_dan_hinh_anh FROM hinh_anh_san_pham WHERE ma_san_pham = ? AND la_hinh_chinh = 1 LIMIT 1";
                                    $img_stmt = $conn->prepare($img_sql);
                                    $img_stmt->bind_param("i", $product['ma_san_pham']);
                                    $img_stmt->execute();
                                    $img_result = $img_stmt->get_result();
                                    $img_row = $img_result->fetch_assoc();
                                    $img_url = $img_row ? $img_row['duong_dan_hinh_anh'] : '';
                                    ?>
                                    <button class="btn btn-primary btn-edit" 
                                        data-id="<?php echo $product['ma_san_pham']; ?>"
                                        data-ten="<?php echo htmlspecialchars($product['ten_san_pham']); ?>"
                                        data-danhmuc="<?php echo $product['ma_danh_muc']; ?>"
                                        data-nhasx="<?php echo $product['ma_nha_san_xuat']; ?>"
                                        data-mota="<?php echo htmlspecialchars($product['mo_ta']); ?>"
                                        data-giaban="<?php echo $product['gia_ban']; ?>"
                                        data-giakm="<?php echo $product['gia_khuyen_mai']; ?>"
                                        data-tonkho="<?php echo $product['so_luong_ton_kho']; ?>"
                                        data-noibat="<?php echo $product['san_pham_noi_bat']; ?>"
                                        data-flashsale="<?php echo $product['is_flash_sale']; ?>"
                                        data-img="<?php echo htmlspecialchars($img_url); ?>"
                                        onclick="openEditModal(this)">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>
                                </td>
                                <?php endif; ?>
                                <?php if (checkPermission('products_delete')): ?>
                                <td>
                                    <a href="?delete=<?php echo $product['ma_san_pham']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Thêm sản phẩm mới</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_product" value="1">
                    
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input type="text" name="ten_san_pham" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Danh mục *</label>
                            <select name="ma_danh_muc" required>
                                <option value="">Chọn danh mục</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['ma_danh_muc']; ?>">
                                    <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Nhà sản xuất *</label>
                            <select name="ma_nha_san_xuat" required>
                                <option value="">Chọn nhà sản xuất</option>
                                <?php while ($mfg = $manufacturers->fetch_assoc()): ?>
                                <option value="<?php echo $mfg['ma_nha_san_xuat']; ?>">
                                    <?php echo htmlspecialchars($mfg['ten_nha_san_xuat']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Giá bán *</label>
                            <input type="number" name="gia_ban" min="0" step="1000" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Giá khuyến mãi</label>
                            <input type="number" name="gia_khuyen_mai" min="0" step="1000">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Số lượng tồn kho *</label>
                        <input type="number" name="so_luong_ton_kho" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mô tả sản phẩm</label>
                        <textarea name="mo_ta" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="san_pham_noi_bat" value="1">
                            Sản phẩm nổi bật
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_flash_sale" value="1">
                            Flash Sale
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>Hình ảnh sản phẩm *</label>
                        <input type="file" name="hinh_anh[]" accept="image/*" required>
                        <small>Chỉ chọn 1 ảnh, ảnh này sẽ là ảnh chính</small>
                    </div>
                    
                    <div style="text-align: right; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                        <button type="button" onclick="closeModal()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 6px; margin-right: 10px; cursor: pointer;">
                            Hủy
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Thêm sản phẩm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Cập nhật sản phẩm</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_product" value="1">
                    <input type="hidden" name="ma_san_pham" id="edit_ma_san_pham">
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input type="text" name="ten_san_pham" id="edit_ten_san_pham" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Danh mục *</label>
                            <select name="ma_danh_muc" id="edit_ma_danh_muc" required>
                                <option value="">Chọn danh mục</option>
                                <?php $categories2 = $conn->query("SELECT * FROM danh_muc_thuoc WHERE trang_thai_hoat_dong = 1 ORDER BY ten_danh_muc");
                                while ($cat = $categories2->fetch_assoc()): ?>
                                <option value="<?php echo $cat['ma_danh_muc']; ?>"><?php echo htmlspecialchars($cat['ten_danh_muc']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nhà sản xuất *</label>
                            <select name="ma_nha_san_xuat" id="edit_ma_nha_san_xuat" required>
                                <option value="">Chọn nhà sản xuất</option>
                                <?php $manufacturers2 = $conn->query("SELECT * FROM nha_san_xuat WHERE trang_thai_hoat_dong = 1 ORDER BY ten_nha_san_xuat");
                                while ($mfg = $manufacturers2->fetch_assoc()): ?>
                                <option value="<?php echo $mfg['ma_nha_san_xuat']; ?>"><?php echo htmlspecialchars($mfg['ten_nha_san_xuat']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Giá bán *</label>
                            <input type="number" name="gia_ban" id="edit_gia_ban" min="0" step="1000" required>
                        </div>
                        <div class="form-group">
                            <label>Giá khuyến mãi</label>
                            <input type="number" name="gia_khuyen_mai" id="edit_gia_khuyen_mai" min="0" step="1000">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Số lượng tồn kho *</label>
                        <input type="number" name="so_luong_ton_kho" id="edit_so_luong_ton_kho" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Mô tả sản phẩm</label>
                        <textarea name="mo_ta" id="edit_mo_ta" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="san_pham_noi_bat" id="edit_san_pham_noi_bat" value="1">
                            Sản phẩm nổi bật
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_flash_sale" id="edit_is_flash_sale" value="1">
                            Flash Sale
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Hình ảnh hiện tại</label><br>
                        <img id="edit_current_img" src="" style="width:80px;height:80px;object-fit:cover;display:none;">
                    </div>
                    <div class="form-group">
                        <label>Thay thế ảnh mới</label>
                        <input type="file" name="hinh_anh[]" accept="image/*">
                        <small>Nếu chọn ảnh mới, ảnh cũ sẽ bị thay thế.</small>
                    </div>
                    <div style="text-align: right; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                        <button type="button" onclick="closeEditModal()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 6px; margin-right: 10px; cursor: pointer;">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Tự động ẩn thông báo sau 5 giây
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);

        function openEditModal(btn) {
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('edit_ma_san_pham').value = btn.getAttribute('data-id');
            document.getElementById('edit_ten_san_pham').value = btn.getAttribute('data-ten');
            document.getElementById('edit_ma_danh_muc').value = btn.getAttribute('data-danhmuc');
            document.getElementById('edit_ma_nha_san_xuat').value = btn.getAttribute('data-nhasx');
            document.getElementById('edit_mo_ta').value = btn.getAttribute('data-mota');
            document.getElementById('edit_gia_ban').value = btn.getAttribute('data-giaban');
            document.getElementById('edit_gia_khuyen_mai').value = btn.getAttribute('data-giakm');
            document.getElementById('edit_so_luong_ton_kho').value = btn.getAttribute('data-tonkho');
            document.getElementById('edit_san_pham_noi_bat').checked = btn.getAttribute('data-noibat') == '1';
            document.getElementById('edit_is_flash_sale').checked = btn.getAttribute('data-flashsale') == '1';
            var img = btn.getAttribute('data-img');
            var imgTag = document.getElementById('edit_current_img');
            if (img) {
                imgTag.src = '../' + img;
                imgTag.style.display = 'inline-block';
            } else {
                imgTag.style.display = 'none';
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>