<?php
include '../config/dual_session.php';
include '../config/database.php';
include 'includes/permissions.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập admin
require_admin_login();

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!checkPermission('products_add')) {
        $_SESSION['error_message'] = 'Bạn không có quyền thêm sản phẩm!';
        header('Location: products.php');
        exit;
    }
    
    // Copy chính xác từ phần UPDATE - đảm bảo đúng thứ tự
    $ten_san_pham = $_POST['ten_san_pham'];
    $ma_danh_muc = $_POST['ma_danh_muc'];
    $ma_nha_san_xuat = $_POST['ma_nha_san_xuat'];
    $mo_ta = $_POST['mo_ta'];
    $gia_ban = $_POST['gia_ban'];
    $gia_khuyen_mai = !empty($_POST['gia_khuyen_mai']) ? $_POST['gia_khuyen_mai'] : null;
    $so_luong_ton_kho = $_POST['so_luong_ton_kho'];
    $san_pham_noi_bat = isset($_POST['san_pham_noi_bat']) ? 1 : 0;
    $is_flash_sale = isset($_POST['is_flash_sale']) ? 1 : 0;
    $quy_cach_dong_goi = $_POST['quy_cach_dong_goi'];
    $can_don_thuoc = isset($_POST['can_don_thuoc']) ? 1 : 0;
    $so_lo = $_POST['so_lo'];
    $ma_vach = $_POST['ma_vach'];
    $ma_sku = $_POST['ma_sku'];
    $trong_luong = $_POST['trong_luong'];
    $dieu_kien_bao_quan = $_POST['dieu_kien_bao_quan'];
    $tac_dung_phu = $_POST['tac_dung_phu'];
    $chong_chi_dinh = $_POST['chong_chi_dinh'];
    $huong_dan_su_dung = $_POST['huong_dan_su_dung'];
    $trang_thai_hoat_dong = isset($_POST['trang_thai_hoat_dong']) ? 1 : 0;
    $ten_hoat_chat = $_POST['ten_hoat_chat'];
    $thanh_phan_hoat_chat = $_POST['thanh_phan_hoat_chat'];
    $dang_bao_che = $_POST['dang_bao_che'];
    $ham_luong = $_POST['ham_luong'];
    $gioi_han_tuoi = $_POST['gioi_han_tuoi'];
    
    // Copy chính xác từ UPDATE - chỉ bỏ WHERE clause
    $stmt = $conn->prepare("INSERT INTO san_pham_thuoc (ten_san_pham, ma_danh_muc, ma_nha_san_xuat, mo_ta, gia_ban, gia_khuyen_mai, so_luong_ton_kho, san_pham_noi_bat, is_flash_sale, quy_cach_dong_goi, can_don_thuoc, so_lo, ma_vach, ma_sku, trong_luong, dieu_kien_bao_quan, tac_dung_phu, chong_chi_dinh, huong_dan_su_dung, trang_thai_hoat_dong, ten_hoat_chat, thanh_phan_hoat_chat, dang_bao_che, ham_luong, gioi_han_tuoi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Copy chính xác type definition từ UPDATE (bỏ 'i' cuối cho ma_san_pham)
    $stmt->bind_param("siisddiiisisssssssississs", $ten_san_pham, $ma_danh_muc, $ma_nha_san_xuat, $mo_ta, $gia_ban, $gia_khuyen_mai, $so_luong_ton_kho, $san_pham_noi_bat, $is_flash_sale, $quy_cach_dong_goi, $can_don_thuoc, $so_lo, $ma_vach, $ma_sku, $trong_luong, $dieu_kien_bao_quan, $tac_dung_phu, $chong_chi_dinh, $huong_dan_su_dung, $trang_thai_hoat_dong, $ten_hoat_chat, $thanh_phan_hoat_chat, $dang_bao_che, $ham_luong, $gioi_han_tuoi);
    
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
        $_SESSION['success_message'] = "Thêm sản phẩm thành công!";
        header('Location: products.php');
        exit;
    } else {
        $_SESSION['error_message'] = "Lỗi khi thêm sản phẩm: " . $stmt->error;
        header('Location: products.php');
        exit;
    }
}

// Xử lý xóa sản phẩm
if (isset($_GET['permanent_delete']) && is_numeric($_GET['permanent_delete'])) {
    if (!checkPermission('products_delete')) {
        $_SESSION['error_message'] = 'Bạn không có quyền xóa sản phẩm!';
        header('Location: products.php');
        exit;
    }
    $ma_san_pham = $_GET['permanent_delete'];
    
    // Lấy thông tin ảnh trước khi xóa
    $img_stmt = $conn->prepare("SELECT duong_dan_hinh_anh FROM hinh_anh_san_pham WHERE ma_san_pham = ?");
    $img_stmt->bind_param("i", $ma_san_pham);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    
    // Xóa các file ảnh khỏi folder
    while ($img_row = $img_result->fetch_assoc()) {
        $img_path = '../' . $img_row['duong_dan_hinh_anh'];
        if (file_exists($img_path)) {
            unlink($img_path);
        }
    }
    
    // Xóa ảnh khỏi database
    $delete_img_stmt = $conn->prepare("DELETE FROM hinh_anh_san_pham WHERE ma_san_pham = ?");
    $delete_img_stmt->bind_param("i", $ma_san_pham);
    $delete_img_stmt->execute();
    
    // Xóa sản phẩm khỏi database
    $delete_product_stmt = $conn->prepare("DELETE FROM san_pham_thuoc WHERE ma_san_pham = ?");
    $delete_product_stmt->bind_param("i", $ma_san_pham);
    
    if ($delete_product_stmt->execute()) {
        $_SESSION['success_message'] = "Xóa sản phẩm thành công!";
    } else {
        $_SESSION['error_message'] = "Lỗi khi xóa sản phẩm!";
    }
    header('Location: products.php');
    exit;
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
    $quy_cach_dong_goi = $_POST['quy_cach_dong_goi'];
    $can_don_thuoc = isset($_POST['can_don_thuoc']) ? 1 : 0;
    $so_lo = $_POST['so_lo'];
    $ma_vach = $_POST['ma_vach'];
    $ma_sku = $_POST['ma_sku'];
    $trong_luong = $_POST['trong_luong'];
    $gioi_han_tuoi = $_POST['gioi_han_tuoi'];
    $dieu_kien_bao_quan = $_POST['dieu_kien_bao_quan'];
    $tac_dung_phu = $_POST['tac_dung_phu'];
    $chong_chi_dinh = $_POST['chong_chi_dinh'];
    $huong_dan_su_dung = $_POST['huong_dan_su_dung'];
    $trang_thai_hoat_dong = isset($_POST['trang_thai_hoat_dong']) ? 1 : 0;
    $ten_hoat_chat = $_POST['ten_hoat_chat'];
    $thanh_phan_hoat_chat = $_POST['thanh_phan_hoat_chat'];
    $dang_bao_che = $_POST['dang_bao_che'];
    $ham_luong = $_POST['ham_luong'];
    $stmt = $conn->prepare("UPDATE san_pham_thuoc SET ten_san_pham=?, ma_danh_muc=?, ma_nha_san_xuat=?, mo_ta=?, gia_ban=?, gia_khuyen_mai=?, so_luong_ton_kho=?, san_pham_noi_bat=?, is_flash_sale=?, quy_cach_dong_goi=?, can_don_thuoc=?, so_lo=?, ma_vach=?, ma_sku=?, trong_luong=?, dieu_kien_bao_quan=?, tac_dung_phu=?, chong_chi_dinh=?, huong_dan_su_dung=?, trang_thai_hoat_dong=?, ten_hoat_chat=?, thanh_phan_hoat_chat=?, dang_bao_che=?, ham_luong=? WHERE ma_san_pham=?");
    $stmt->bind_param("siisddiiisisssssssississi", $ten_san_pham, $ma_danh_muc, $ma_nha_san_xuat, $mo_ta, $gia_ban, $gia_khuyen_mai, $so_luong_ton_kho, $san_pham_noi_bat, $is_flash_sale, $quy_cach_dong_goi, $can_don_thuoc, $so_lo, $ma_vach, $ma_sku, $trong_luong, $dieu_kien_bao_quan, $tac_dung_phu, $chong_chi_dinh, $huong_dan_su_dung, $trang_thai_hoat_dong, $ten_hoat_chat, $thanh_phan_hoat_chat, $dang_bao_che, $ham_luong, $ma_san_pham);
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
    $_SESSION['success_message'] = "Cập nhật sản phẩm thành công!";
    header('Location: products.php');
    exit;
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
$products_sql = "SELECT 
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
    sp.is_flash_sale,
    sp.ngay_tao,
    sp.ngay_cap_nhat,
    dm.ten_danh_muc,
    nsx.ten_nha_san_xuat
FROM san_pham_thuoc sp 
LEFT JOIN danh_muc_thuoc dm ON sp.ma_danh_muc = dm.ma_danh_muc 
LEFT JOIN nha_san_xuat nsx ON sp.ma_nha_san_xuat = nsx.ma_nha_san_xuat 
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
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>">
    <style>
        /* Product-specific styles */
        .product-stats {
            margin-bottom: 30px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-2px);
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .product-meta {
            color: #7f8c8d;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .price-current {
            font-size: 18px;
            font-weight: 700;
            color: #27ae60;
        }

        .price-original {
            color: #95a5a6;
            text-decoration: line-through;
            font-size: 14px;
        }

        .product-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge-featured {
            background: #fff3cd;
            color: #856404;
        }

        .badge-sale {
            background: #e74c3c;
            color: white;
        }

        .badge-normal {
            background: #e2e3e5;
            color: #6c757d;
        }

        .badge-active {
            background: #d1f2eb;
            color: #00695c;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .stock-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .stock-low {
            color: #e74c3c;
            font-weight: 600;
        }

        .stock-normal {
            color: #27ae60;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            min-width: auto;
        }

        .search-filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 16px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .filter-select {
            padding: 12px 16px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn-search {
            background: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-search:hover {
            background: #2980b9;
        }

        .btn-clear {
            background: #6c757d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-clear:hover {
            background: #5a6268;
        }

        .btn-add {
            background: #27ae60;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            margin-left: auto;
        }

        .btn-add:hover {
            background: #219a52;
        }

        .products-grid {
            display: grid;
            gap: 20px;
        }

        .product-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .product-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Modal improvements */
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
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #2c3e50;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
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
            font-weight: 500;
        }

        .checkbox-label input {
            margin-right: 8px;
            width: auto;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }

        .btn-submit {
            background: #27ae60;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #219a52;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input,
            .filter-select {
                min-width: 100%;
            }
            
            .btn-add {
                margin-left: 0;
                margin-top: 15px;
            }
            
            .product-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image {
                width: 80px;
                height: 80px;
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .action-buttons {
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .action-buttons .btn {
                min-width: 60px;
                white-space: nowrap;
                font-weight: 600;
            }
            
            .modal-content {
                margin: 10px;
                max-width: none;
            }
        }

        /* Enhanced Action Buttons Styling */
        .action-buttons .btn-info {
            background: #17a2b8 !important;
            border-color: #17a2b8 !important;
            color: white !important;
        }

        .action-buttons .btn-info:hover {
            background: #138496 !important;
            border-color: #117a8b !important;
        }

        .action-buttons .btn-danger {
            background: #dc3545 !important;
            border-color: #dc3545 !important;
            color: white !important;
        }

        .action-buttons .btn-danger:hover {
            background: #c82333 !important;
            border-color: #bd2130 !important;
        }

        /* Enhanced Alert Styling */
        .alert {
            position: relative;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            animation: slideInDown 0.3s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f1b2b5 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .alert i {
            font-size: 18px;
            margin-right: 5px;
        }

        .alert-close {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .alert-close:hover {
            opacity: 1;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Quản lý Sản phẩm';
            $page_icon = 'fas fa-pills';
            include '../includes/admin-header.php'; 
            ?>
            
            <div class="dashboard-content">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Product Overview Stats -->
                <div class="stats-grid">
                    <?php
                    // Tính toán thống kê sản phẩm
                    $active_products = $conn->query("SELECT COUNT(*) as count FROM san_pham_thuoc WHERE trang_thai_hoat_dong = 1")->fetch_assoc()['count'];
                    $featured_products = $conn->query("SELECT COUNT(*) as count FROM san_pham_thuoc WHERE san_pham_noi_bat = 1 AND trang_thai_hoat_dong = 1")->fetch_assoc()['count'];
                    $sale_products = $conn->query("SELECT COUNT(*) as count FROM san_pham_thuoc WHERE is_flash_sale = 1 AND trang_thai_hoat_dong = 1")->fetch_assoc()['count'];
                    $low_stock_products = $conn->query("SELECT COUNT(*) as count FROM san_pham_thuoc WHERE so_luong_ton_kho <= 10 AND trang_thai_hoat_dong = 1")->fetch_assoc()['count'];
                    ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($active_products); ?></h3>
                            <p>Sản phẩm hoạt động</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($featured_products); ?></h3>
                            <p>Sản phẩm nổi bật</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($sale_products); ?></h3>
                            <p>Sản phẩm sale</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e67e22;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($low_stock_products); ?></h3>
                            <p>Sắp hết hàng</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filters">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" class="search-input" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
                        
                        <select name="filter" class="filter-select">
                            <option value="">Tất cả sản phẩm</option>
                            <option value="low_stock" <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>>Sắp hết hàng</option>
                        </select>
                        
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        
                        <?php if ($search || $filter): ?>
                        <a href="products.php" class="btn-clear">
                            <i class="fas fa-times"></i> Xóa bộ lọc
                        </a>
                        <?php endif; ?>
                        
                        <?php if (checkPermission('products_add')): ?>
                        <button type="button" class="btn btn-primary" onclick="openModal()">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled title="Bạn không có quyền thêm sản phẩm">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </button>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Products Table -->
                <div class="product-table">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> Danh sách sản phẩm (<?php echo $total_products; ?>)</h3>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hình ảnh</th>
                                    <th>Thông tin sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Nhà sản xuất</th>
                                    <th>Giá bán</th>
                                    <th>Tồn kho</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php
                                        // Lấy hình ảnh sản phẩm
                                        $img_sql = "SELECT duong_dan_hinh_anh FROM hinh_anh_san_pham WHERE ma_san_pham = ? AND la_hinh_chinh = 1 LIMIT 1";
                                        $img_stmt = $conn->prepare($img_sql);
                                        $img_stmt->bind_param("i", $product['ma_san_pham']);
                                        $img_stmt->execute();
                                        $img_result = $img_stmt->get_result();
                                        $img_row = $img_result->fetch_assoc();
                                        $img_url = $img_row ? '../' . $img_row['duong_dan_hinh_anh'] : '../images/products/default.jpg';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($img_url); ?>" 
                                             alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>" 
                                             class="product-image"
                                             onerror="this.src='../images/products/default.jpg'">
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-name"><?php echo htmlspecialchars($product['ten_san_pham']); ?></div>
                                            <div class="product-meta">
                                                SKU: <?php echo htmlspecialchars($product['ma_sku'] ?? 'N/A'); ?>
                                            </div>
                                            <div class="product-badges">
                                                <?php if ($product['san_pham_noi_bat']): ?>
                                                    <span class="badge badge-featured">Nổi bật</span>
                                                <?php endif; ?>
                                                <?php if ($product['is_flash_sale']): ?>
                                                    <span class="badge badge-sale">SALE</span>
                                                <?php endif; ?>
                                                <?php if ($product['gia_khuyen_mai']): ?>
                                                    <span class="badge" style="background: #27ae60; color: white;">Khuyến mãi</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['ten_danh_muc']); ?></td>
                                    <td><?php echo htmlspecialchars($product['ten_nha_san_xuat']); ?></td>
                                    <td>
                                        <div class="product-price">
                                            <?php if ($product['gia_khuyen_mai']): ?>
                                                <span class="price-current"><?php echo number_format($product['gia_khuyen_mai']); ?>đ</span>
                                                <span class="price-original"><?php echo number_format($product['gia_ban']); ?>đ</span>
                                            <?php else: ?>
                                                <span class="price-current"><?php echo number_format($product['gia_ban']); ?>đ</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stock-info">
                                            <span class="<?php echo $product['so_luong_ton_kho'] <= 10 ? 'stock-low' : 'stock-normal'; ?>">
                                                <?php echo $product['so_luong_ton_kho']; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $product['trang_thai_hoat_dong'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $product['trang_thai_hoat_dong'] ? 'Hoạt động' : 'Ngừng bán'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (checkPermission('products_edit')): ?>
                                            <button class="btn btn-info btn-sm" 
                                                data-id="<?php echo $product['ma_san_pham'] ?? ''; ?>"
                                                data-ten="<?php echo htmlspecialchars($product['ten_san_pham'] ?? ''); ?>"
                                                data-danhmuc="<?php echo $product['ma_danh_muc'] ?? ''; ?>"
                                                data-nhasx="<?php echo $product['ma_nha_san_xuat'] ?? ''; ?>"
                                                data-mota="<?php echo htmlspecialchars($product['mo_ta'] ?? ''); ?>"
                                                data-giaban="<?php echo htmlspecialchars((string)($product['gia_ban'] ?? '')); ?>"
                                                data-giakm="<?php echo htmlspecialchars((string)($product['gia_khuyen_mai'] ?? '')); ?>"
                                                data-tonkho="<?php echo htmlspecialchars((string)($product['so_luong_ton_kho'] ?? '')); ?>"
                                                data-noibat="<?php echo $product['san_pham_noi_bat'] ?? 0; ?>"
                                                data-flashsale="<?php echo $product['is_flash_sale'] ?? 0; ?>"
                                                data-img="<?php echo htmlspecialchars((string)($img_row['duong_dan_hinh_anh'] ?? '')); ?>"
                                                data-quy_cach_dong_goi="<?php echo htmlspecialchars($product['quy_cach_dong_goi'] ?? ''); ?>"
                                                data-can_don_thuoc="<?php echo $product['can_don_thuoc'] ?? 0; ?>"
                                                data-so_lo="<?php echo htmlspecialchars($product['so_lo'] ?? ''); ?>"
                                                data-ma_vach="<?php echo htmlspecialchars($product['ma_vach'] ?? ''); ?>"
                                                data-ma_sku="<?php echo htmlspecialchars($product['ma_sku'] ?? ''); ?>"
                                                data-trong_luong="<?php echo htmlspecialchars((string)($product['trong_luong'] ?? '')); ?>"
                                                data-dieu_kien_bao_quan="<?php echo htmlspecialchars($product['dieu_kien_bao_quan'] ?? ''); ?>"
                                                data-tac_dung_phu="<?php echo htmlspecialchars($product['tac_dung_phu'] ?? ''); ?>"
                                                data-chong_chi_dinh="<?php echo htmlspecialchars($product['chong_chi_dinh'] ?? ''); ?>"
                                                data-huong_dan_su_dung="<?php echo htmlspecialchars($product['huong_dan_su_dung'] ?? ''); ?>"
                                                data-gioi_han_tuoi="<?php echo htmlspecialchars($product['gioi_han_tuoi'] ?? ''); ?>"
                                                data-trang_thai_hoat_dong="<?php echo $product['trang_thai_hoat_dong'] ?? 0; ?>"
                                                data-ten_hoat_chat="<?php echo htmlspecialchars($product['ten_hoat_chat'] ?? ''); ?>"
                                                data-thanh_phan_hoat_chat="<?php echo htmlspecialchars($product['thanh_phan_hoat_chat'] ?? ''); ?>"
                                                data-dang_bao_che="<?php echo htmlspecialchars($product['dang_bao_che'] ?? ''); ?>"
                                                data-ham_luong="<?php echo htmlspecialchars($product['ham_luong'] ?? ''); ?>"
                                                onclick="openEditModal(this)">
                                                <i class="fas fa-edit"></i> Sửa
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (checkPermission('products_delete')): ?>
                                            <button class="btn btn-danger btn-sm" 
                                                data-product-id="<?php echo $product['ma_san_pham']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['ten_san_pham']); ?>"
                                                onclick="deleteProduct(this.dataset.productId, this.dataset.productName)">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>" class="page-link">
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
                        <label>Quy cách đóng gói</label>
                        <input type="text" name="quy_cach_dong_goi">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="can_don_thuoc" value="1">
                            Cần đơn thuốc
                        </label>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Số lô</label>
                            <input type="text" name="so_lo">
                        </div>
                        <div class="form-group">
                            <label>Mã vạch</label>
                            <input type="text" name="ma_vach">
                        </div>
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" name="ma_sku">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Trọng lượng (g)</label>
                            <input type="number" name="trong_luong" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Giới hạn tuổi</label>
                            <input type="text" name="gioi_han_tuoi">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Điều kiện bảo quản</label>
                        <input type="text" name="dieu_kien_bao_quan">
                    </div>
                    <div class="form-group">
                        <label>Tác dụng phụ</label>
                        <textarea name="tac_dung_phu" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Chống chỉ định</label>
                        <textarea name="chong_chi_dinh" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Hướng dẫn sử dụng</label>
                        <textarea name="huong_dan_su_dung" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="trang_thai_hoat_dong" value="1" checked>
                            Đang hoạt động
                        </label>
                    </div>
                    <div class="form-row">
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
                    </div>
                    <div class="form-group">
                        <label>Tên hoạt chất</label>
                        <input type="text" name="ten_hoat_chat">
                    </div>
                    <div class="form-group">
                        <label>Thành phần hoạt chất</label>
                        <input type="text" name="thanh_phan_hoat_chat">
                    </div>
                    <div class="form-group">
                        <label>Dạng bào chế</label>
                        <input type="text" name="dang_bao_che">
                    </div>
                    <div class="form-group">
                        <label>Hàm lượng</label>
                        <input type="text" name="ham_luong">
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
                        <label>Quy cách đóng gói</label>
                        <input type="text" name="quy_cach_dong_goi" id="edit_quy_cach_dong_goi">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="can_don_thuoc" id="edit_can_don_thuoc" value="1">
                            Cần đơn thuốc
                        </label>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Số lô</label>
                            <input type="text" name="so_lo" id="edit_so_lo">
                        </div>
                        <div class="form-group">
                            <label>Mã vạch</label>
                            <input type="text" name="ma_vach" id="edit_ma_vach">
                        </div>
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" name="ma_sku" id="edit_ma_sku">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Trọng lượng (g)</label>
                            <input type="number" name="trong_luong" id="edit_trong_luong" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Giới hạn tuổi</label>
                            <input type="text" name="gioi_han_tuoi" id="edit_gioi_han_tuoi">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Điều kiện bảo quản</label>
                        <input type="text" name="dieu_kien_bao_quan" id="edit_dieu_kien_bao_quan">
                    </div>
                    <div class="form-group">
                        <label>Tác dụng phụ</label>
                        <textarea name="tac_dung_phu" id="edit_tac_dung_phu" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Chống chỉ định</label>
                        <textarea name="chong_chi_dinh" id="edit_chong_chi_dinh" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Hướng dẫn sử dụng</label>
                        <textarea name="huong_dan_su_dung" id="edit_huong_dan_su_dung" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="trang_thai_hoat_dong" id="edit_trang_thai_hoat_dong" value="1">
                            Đang hoạt động
                        </label>
                    </div>
                    <div class="form-row">
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
                    </div>
                    <div class="form-group">
                        <label>Tên hoạt chất</label>
                        <input type="text" name="ten_hoat_chat" id="edit_ten_hoat_chat">
                    </div>
                    <div class="form-group">
                        <label>Thành phần hoạt chất</label>
                        <input type="text" name="thanh_phan_hoat_chat" id="edit_thanh_phan_hoat_chat">
                    </div>
                    <div class="form-group">
                        <label>Dạng bào chế</label>
                        <input type="text" name="dang_bao_che" id="edit_dang_bao_che">
                    </div>
                    <div class="form-group">
                        <label>Hàm lượng</label>
                        <input type="text" name="ham_luong" id="edit_ham_luong">
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
    </div>

    <script src="js/admin.js"></script>
    <script>
        function openModal() {
            console.log('Opening add modal...');
            const modal = document.getElementById('addModal');
            if (modal) {
                modal.style.display = 'flex';
            } else {
                console.error('Modal addModal not found');
            }
        }

        function closeModal() {
            console.log('Closing add modal...');
            const modal = document.getElementById('addModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Hàm xóa sản phẩm
        function deleteProduct(id, name) {
            console.log('Delete product called:', id, name);
            if (confirm(`Bạn có chắc chắn muốn xóa sản phẩm "${name}"?\n\nHành động này không thể hoàn tác!`)) {
                window.location.href = `?permanent_delete=${id}`;
            }
        }

        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        }

        // Tự động ẩn thông báo sau 5 giây
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                }
            });
        }, 5000);

        // Enhanced alert interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add click to dismiss functionality
            document.querySelectorAll('.alert').forEach(alert => {
                alert.addEventListener('click', function() {
                    this.style.opacity = '0';
                    this.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        if (this.parentNode) {
                            this.remove();
                        }
                    }, 300);
                });
            });
        });

        function openEditModal(btn) {
            // Debug: In ra console để kiểm tra dữ liệu
            console.log('Debug data attributes:');
            console.log('ten_hoat_chat:', btn.getAttribute('data-ten_hoat_chat'));
            console.log('thanh_phan_hoat_chat:', btn.getAttribute('data-thanh_phan_hoat_chat'));
            console.log('dang_bao_che:', btn.getAttribute('data-dang_bao_che'));
            console.log('ham_luong:', btn.getAttribute('data-ham_luong'));
            console.log('quy_cach_dong_goi:', btn.getAttribute('data-quy_cach_dong_goi'));
            
            // Debug: In ra tất cả attributes
            console.log('All attributes:', btn.attributes);
            for (let i = 0; i < btn.attributes.length; i++) {
                let attr = btn.attributes[i];
                if (attr.name.startsWith('data-')) {
                    console.log(attr.name + ':', attr.value);
                }
            }
            
            document.getElementById('editModal').style.display = 'flex';
            
            // Populate form fields
            const fields = [
                {id: 'edit_ma_san_pham', attr: 'data-id'},
                {id: 'edit_ten_san_pham', attr: 'data-ten'},
                {id: 'edit_ma_danh_muc', attr: 'data-danhmuc'},
                {id: 'edit_ma_nha_san_xuat', attr: 'data-nhasx'},
                {id: 'edit_mo_ta', attr: 'data-mota'},
                {id: 'edit_gia_ban', attr: 'data-giaban'},
                {id: 'edit_gia_khuyen_mai', attr: 'data-giakm'},
                {id: 'edit_so_luong_ton_kho', attr: 'data-tonkho'},
                {id: 'edit_quy_cach_dong_goi', attr: 'data-quy_cach_dong_goi'},
                {id: 'edit_so_lo', attr: 'data-so_lo'},
                {id: 'edit_ma_vach', attr: 'data-ma_vach'},
                {id: 'edit_ma_sku', attr: 'data-ma_sku'},
                {id: 'edit_trong_luong', attr: 'data-trong_luong'},
                {id: 'edit_dieu_kien_bao_quan', attr: 'data-dieu_kien_bao_quan'},
                {id: 'edit_tac_dung_phu', attr: 'data-tac_dung_phu'},
                {id: 'edit_chong_chi_dinh', attr: 'data-chong_chi_dinh'},
                {id: 'edit_huong_dan_su_dung', attr: 'data-huong_dan_su_dung'},
                {id: 'edit_gioi_han_tuoi', attr: 'data-gioi_han_tuoi'},
                {id: 'edit_ten_hoat_chat', attr: 'data-ten_hoat_chat'},
                {id: 'edit_thanh_phan_hoat_chat', attr: 'data-thanh_phan_hoat_chat'},
                {id: 'edit_dang_bao_che', attr: 'data-dang_bao_che'},
                {id: 'edit_ham_luong', attr: 'data-ham_luong'}
            ];
            
            fields.forEach(field => {
                const element = document.getElementById(field.id);
                const value = btn.getAttribute(field.attr) || '';
                if (element) {
                    element.value = value;
                    console.log(`Set ${field.id} = "${value}"`);
                } else {
                    console.error(`Element not found: ${field.id}`);
                }
            });
            
            // Handle checkboxes
            const checkboxes = [
                {id: 'edit_san_pham_noi_bat', attr: 'data-noibat'},
                {id: 'edit_is_flash_sale', attr: 'data-flashsale'},
                {id: 'edit_can_don_thuoc', attr: 'data-can_don_thuoc'},
                {id: 'edit_trang_thai_hoat_dong', attr: 'data-trang_thai_hoat_dong'}
            ];
            
            checkboxes.forEach(checkbox => {
                const element = document.getElementById(checkbox.id);
                if (element) {
                    element.checked = btn.getAttribute(checkbox.attr) == '1';
                    console.log(`Set ${checkbox.id} = ${element.checked}`);
                } else {
                    console.error(`Checkbox not found: ${checkbox.id}`);
                }
            });
            
            // Handle image
            var img = btn.getAttribute('data-img');
            var imgTag = document.getElementById('edit_current_img');
            if (img && imgTag) {
                imgTag.src = '../' + img;
                imgTag.style.display = 'inline-block';
            } else if (imgTag) {
                imgTag.style.display = 'none';
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Xử lý logic loại trừ giữa "Sản phẩm nổi bật" và "Flash Sale"
        function handleExclusiveCheckbox(clickedId, otherId) {
            const clicked = document.getElementById(clickedId);
            const other = document.getElementById(otherId);
            
            if (clicked.checked) {
                other.checked = false;
            }
        }

        // Thêm event listeners cho form thêm sản phẩm
        document.addEventListener('DOMContentLoaded', function() {
            // Form thêm sản phẩm
            const addNoiBat = document.querySelector('input[name="san_pham_noi_bat"]');
            const addFlashSale = document.querySelector('input[name="is_flash_sale"]');
            
            if (addNoiBat && addFlashSale) {
                addNoiBat.addEventListener('change', function() {
                    if (this.checked) {
                        addFlashSale.checked = false;
                    }
                });
                
                addFlashSale.addEventListener('change', function() {
                    if (this.checked) {
                        addNoiBat.checked = false;
                    }
                });
            }

            // Form sửa sản phẩm
            const editNoiBat = document.getElementById('edit_san_pham_noi_bat');
            const editFlashSale = document.getElementById('edit_is_flash_sale');
            
            if (editNoiBat && editFlashSale) {
                editNoiBat.addEventListener('change', function() {
                    if (this.checked) {
                        editFlashSale.checked = false;
                    }
                });
                
                editFlashSale.addEventListener('change', function() {
                    if (this.checked) {
                        editNoiBat.checked = false;
                    }
                });
            }
        });
    </script>
</body>
</html>