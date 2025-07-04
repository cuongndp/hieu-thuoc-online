<?php
session_start();
include '../config/database.php';
include 'includes/permissions.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Kiểm tra quyền xem danh sách admin
requirePermission('admin_users_view');

$message = '';
$error_message = '';

// Xử lý thêm admin mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' && checkPermission('admin_users_add')) {
        $ho_ten = $_POST['ho_ten'] ?? '';
        $email = $_POST['email'] ?? '';
        $mat_khau = $_POST['mat_khau'] ?? '';
        $vai_tro = $_POST['vai_tro'] ?? '';
        
        if (!empty($ho_ten) && !empty($email) && !empty($mat_khau) && !empty($vai_tro)) {
            // Kiểm tra email đã tồn tại chưa
            $check_sql = "SELECT ma_nguoi_dung FROM nguoi_dung WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows === 0) {
                $mat_khau_hash = password_hash($mat_khau, PASSWORD_DEFAULT);
                
                $insert_sql = "INSERT INTO nguoi_dung (ho_ten, email, mat_khau_ma_hoa, vai_tro, trang_thai_hoat_dong) VALUES (?, ?, ?, ?, 1)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ssss", $ho_ten, $email, $mat_khau_hash, $vai_tro);
                
                if ($insert_stmt->execute()) {
                    $message = "Thêm admin thành công!";
                } else {
                    $error_message = "Có lỗi xảy ra khi thêm admin!";
                }
            } else {
                $error_message = "Email đã tồn tại!";
            }
        } else {
            $error_message = "Vui lòng nhập đầy đủ thông tin!";
        }
    }
    
    // Xử lý xóa admin
    if ($_POST['action'] === 'delete' && checkPermission('admin_users_delete')) {
        $ma_nguoi_dung = $_POST['ma_nguoi_dung'] ?? '';
        
        if (!empty($ma_nguoi_dung) && $ma_nguoi_dung != $_SESSION['admin_id']) {
            $delete_sql = "UPDATE nguoi_dung SET trang_thai_hoat_dong = 0 WHERE ma_nguoi_dung = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $ma_nguoi_dung);
            
            if ($delete_stmt->execute()) {
                $message = "Xóa admin thành công!";
            } else {
                $error_message = "Có lỗi xảy ra khi xóa admin!";
            }
        } else {
            $error_message = "Không thể xóa chính mình!";
        }
    }
}

// Lấy danh sách admin
$admin_sql = "SELECT 
    ma_nguoi_dung,
    ho_ten,
    email,
    vai_tro,
    ngay_tao,
    trang_thai_hoat_dong
    FROM nguoi_dung 
    WHERE vai_tro IN ('quan_tri', 'nhan_vien', 'quan_ly')
    ORDER BY ngay_tao DESC";
$admin_result = $conn->query($admin_sql);

// Danh sách vai trò
$roles = [
    'quan_tri' => 'Quản Trị',
    'nhan_vien' => 'Nhân Viên',
    'quan_ly' => 'Quản Lý'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Admin - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }
        
        .admin-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-form {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .admin-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn-add {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-primary {
            background: #3498db;
            color: white;
        }
        
        .badge-success {
            background: #27ae60;
            color: white;
        }
        
        .badge-warning {
            background: #f39c12;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>VitaMeds</h2>
                <p>Quản Trị Hệ Thống</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li <?php echo showMenuIf('products_view'); ?>><a href="products.php"><i class="fas fa-pills"></i> Sản Phẩm</a></li>
                <li <?php echo showMenuIf('orders_view'); ?>><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn Hàng</a></li>
                <li <?php echo showMenuIf('customers_view'); ?>><a href="customers.php"><i class="fas fa-users"></i> Khách Hàng</a></li>
                <li <?php echo showMenuIf('reviews_view'); ?>><a href="reviews.php"><i class="fas fa-star"></i> Đánh Giá</a></li>
                <li <?php echo showMenuIf('admin_users_view'); ?>><a href="admin_users.php" class="active"><i class="fas fa-user-shield"></i> Quản Lý Admin</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-user-shield"></i> Quản Lý Admin</h1>
                <div class="user-info">
                    <span>Xin chào, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="admin-grid">
                <!-- Danh sách Admin -->
                <div class="admin-list">
                    <h2><i class="fas fa-list"></i> Danh Sách Admin</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Họ Tên</th>
                                <th>Email</th>
                                <th>Vai Trò</th>
                                <th>Ngày Tạo</th>
                                <th>Trạng Thái</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($admin = $admin_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['ho_ten']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <?php 
                                        $role_class = '';
                                        switch($admin['vai_tro']) {
                                            case 'quan_tri':
                                                $role_class = 'badge-primary';
                                                break;
                                            case 'quan_ly':
                                                $role_class = 'badge-success';
                                                break;
                                            case 'nhan_vien':
                                                $role_class = 'badge-warning';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $role_class; ?>">
                                            <?php echo getRoleName($admin['vai_tro']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($admin['ngay_tao'])); ?></td>
                                    <td>
                                        <?php if ($admin['trang_thai_hoat_dong']): ?>
                                            <span class="status-active">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="status-inactive">Không hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (checkPermission('admin_users_edit') && $admin['ma_nguoi_dung'] != $_SESSION['admin_id']): ?>
                                            <button class="btn-action btn-edit" onclick="editAdmin(<?php echo $admin['ma_nguoi_dung']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (checkPermission('admin_users_delete') && $admin['ma_nguoi_dung'] != $_SESSION['admin_id']): ?>
                                            <button class="btn-action btn-delete" onclick="deleteAdmin(<?php echo $admin['ma_nguoi_dung']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Form thêm Admin -->
                <?php if (checkPermission('admin_users_add')): ?>
                <div class="admin-form">
                    <h3><i class="fas fa-plus"></i> Thêm Admin Mới</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="ho_ten">Họ Tên:</label>
                            <input type="text" id="ho_ten" name="ho_ten" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mat_khau">Mật Khẩu:</label>
                            <input type="password" id="mat_khau" name="mat_khau" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="vai_tro">Vai Trò:</label>
                            <select id="vai_tro" name="vai_tro" required>
                                <option value="">Chọn vai trò</option>
                                <?php foreach ($roles as $key => $name): ?>
                                    <option value="<?php echo $key; ?>">
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-add">
                            <i class="fas fa-plus"></i> Thêm Admin
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function deleteAdmin(adminId) {
            if (confirm('Bạn có chắc chắn muốn xóa admin này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="ma_nguoi_dung" value="${adminId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editAdmin(adminId) {
            // Có thể mở modal hoặc chuyển đến trang edit
            alert('Chức năng chỉnh sửa sẽ được phát triển sau!');
        }
    </script>
</body>
</html> 