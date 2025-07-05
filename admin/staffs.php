<?php
include '../config/simple_session.php';
include '../config/database.php';

// Ensure session is started
ensure_session_started();

// Chỉ cho admin truy cập
if (!is_admin_logged_in() || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Xử lý tạo tài khoản nhân viên
$message = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = trim($_POST['phone']);
    $role = 'nhan_vien';
    // Kiểm tra email đã tồn tại chưa
    $check = $conn->prepare('SELECT * FROM nguoi_dung WHERE email = ?');
    if (!$check) { die('Lỗi prepare (check email): ' . $conn->error); }
    $check->bind_param('s', $email);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        $message = 'Email đã tồn tại!';
    } else {
        $stmt = $conn->prepare('INSERT INTO nguoi_dung (ho_ten, email, mat_khau_ma_hoa, so_dien_thoai, vai_tro) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) { die('Lỗi prepare (insert staff): ' . $conn->error); }
        $stmt->bind_param('sssss', $name, $email, $password, $phone, $role);
        if ($stmt->execute()) {
            $message = 'Tạo tài khoản nhân viên thành công!';
            $success = true;
        } else {
            $message = 'Lỗi khi tạo tài khoản!';
        }
        $stmt->close();
    }
    $check->close();
}

// Xử lý cập nhật thông tin nhân viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_staff'])) {
    $staff_id = intval($_POST['staff_id']);
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);
    $phone = trim($_POST['edit_phone']);
    $password = $_POST['edit_password'];
    // Kiểm tra email trùng với nhân viên khác
    $check = $conn->prepare('SELECT * FROM nguoi_dung WHERE email = ? AND ma_nguoi_dung != ?');
    if (!$check) { die('Lỗi prepare (check email edit): ' . $conn->error); }
    $check->bind_param('si', $email, $staff_id);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        $message = 'Email đã tồn tại cho nhân viên khác!';
    } else {
        if ($password) {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE nguoi_dung SET ho_ten=?, email=?, so_dien_thoai=?, mat_khau_ma_hoa=? WHERE ma_nguoi_dung=? AND vai_tro="nhan_vien"');
            if (!$stmt) { die('Lỗi prepare (update staff with pass): ' . $conn->error); }
            $stmt->bind_param('ssssi', $name, $email, $phone, $password_hashed, $staff_id);
        } else {
            $stmt = $conn->prepare('UPDATE nguoi_dung SET ho_ten=?, email=?, so_dien_thoai=? WHERE ma_nguoi_dung=? AND vai_tro="nhan_vien"');
            if (!$stmt) { die('Lỗi prepare (update staff): ' . $conn->error); }
            $stmt->bind_param('sssi', $name, $email, $phone, $staff_id);
        }
        if ($stmt->execute()) {
            $message = 'Cập nhật thông tin nhân viên thành công!';
            $success = true;
        } else {
            $message = 'Lỗi khi cập nhật thông tin!';
        }
        $stmt->close();
    }
    $check->close();
}

// Xử lý xóa nhân viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $delete_id = intval($_POST['delete_id']);
    if ($delete_id == $_SESSION['admin_id']) {
        $message = 'Bạn không thể tự xóa chính mình!';
        $success = false;
    } else {
        $stmt = $conn->prepare('DELETE FROM nguoi_dung WHERE ma_nguoi_dung = ? AND vai_tro = "nhan_vien"');
        if (!$stmt) { die('Lỗi prepare (delete staff): ' . $conn->error); }
        $stmt->bind_param('i', $delete_id);
        if ($stmt->execute()) {
            $message = 'Xóa nhân viên thành công!';
            $success = true;
        } else {
            $message = 'Lỗi khi xóa nhân viên!';
            $success = false;
        }
        $stmt->close();
    }
}

// Lấy danh sách nhân viên
$staffs = $conn->query("SELECT * FROM nguoi_dung WHERE vai_tro = 'nhan_vien' ORDER BY ho_ten ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nhân viên - VitaMeds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .staff-container { max-width: 1000px; margin: 0 auto; }
        .staff-form-card { background: #fff; padding: 32px 28px; border-radius: 14px; box-shadow: 0 2px 12px #e0e6ed; max-width: 420px; margin-bottom: 36px; margin-top: 20px; }
        .staff-form-card h3 { margin-bottom: 18px; color: #2c3e50; }
        .staff-form-card input { width: 100%; padding: 12px; margin-bottom: 18px; border-radius: 7px; border: 1px solid #dbe2ef; font-size: 15px; }
        .staff-form-card button { background: #3498db; color: #fff; border: none; padding: 12px 28px; border-radius: 7px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.2s; }
        .staff-form-card button:hover { background: #217dbb; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 7px; margin-bottom: 18px; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 7px; margin-bottom: 18px; border: 1px solid #f5c6cb; }
        .staff-table-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px #e0e6ed; padding: 24px; margin-bottom: 30px; overflow-x: auto; }
        .staff-table { width: 100%; border-collapse: collapse; font-size: 15px; }
        .staff-table th, .staff-table td { padding: 14px 10px; border-bottom: 1px solid #f0f0f0; text-align: left; }
        .staff-table th { background: #f8f9fa; color: #2c3e50; font-weight: 700; }
        .staff-table tr:last-child td { border-bottom: none; }
        .staff-actions { display: flex; gap: 10px; }
        .btn-edit { background: #f39c12; color: #fff; border: none; border-radius: 5px; padding: 7px 16px; cursor: pointer; font-size: 14px; transition: background 0.2s; }
        .btn-edit:hover { background: #d35400; }
        .btn-delete { background: #e74c3c; color: #fff; border: none; border-radius: 5px; padding: 7px 16px; cursor: pointer; font-size: 14px; transition: background 0.2s; }
        .btn-delete:hover { background: #c0392b; }
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.25); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; border-radius: 18px; padding: 38px 32px 32px 32px; min-width: 340px; max-width: 95vw; box-shadow: 0 8px 32px #b2bec3; position: relative; margin: 0 auto; }
        .modal-content h3 { margin-bottom: 18px; color: #2c3e50; text-align: center; }
        .close-modal { position: absolute; top: 14px; right: 18px; font-size: 28px; color: #e74c3c; cursor: pointer; font-weight: bold; transition: color 0.2s; }
        .close-modal:hover { color: #c0392b; }
        .modal-content form input { width: 100%; padding: 13px; margin-bottom: 18px; border-radius: 7px; border: 1px solid #dbe2ef; font-size: 15px; }
        .modal-content form button { background: #27ae60; color: #fff; border: none; padding: 12px 28px; border-radius: 7px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.2s; width: 100%; margin-top: 8px; }
        .modal-content form button:hover { background: #219150; }
        .modal-alert { background: #f8d7da; color: #721c24; padding: 10px 18px; border-radius: 7px; margin-bottom: 16px; border: 1px solid #f5c6cb; text-align: center; }
        .modal-alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        @media (max-width: 700px) {
            .staff-table th, .staff-table td { padding: 10px 4px; font-size: 13px; }
            .staff-form-card { padding: 18px 8px; }
            .staff-table-wrap { padding: 10px; }
            .modal-content { padding: 16px 6px; min-width: 0; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>
        <div class="main-content staff-container">
            <div class="page-header">
                <h1><i class="fas fa-user-tie"></i> Quản lý nhân viên</h1>
            </div>
            <div class="staff-form-card">
                <h3>Tạo tài khoản nhân viên</h3>
                <?php if ($message): ?>
                    <div class="<?php echo $success ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <input type="text" name="name" placeholder="Họ tên" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Mật khẩu" required>
                    <input type="text" name="phone" placeholder="Số điện thoại" required>
                    <button type="submit" name="create_staff"><i class="fas fa-plus"></i> Tạo tài khoản</button>
                </form>
            </div>
            <div class="staff-table-wrap">
                <h3 style="margin-bottom: 18px; color: #2c3e50;">Danh sách nhân viên</h3>
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($staff = $staffs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($staff['ho_ten']); ?></td>
                                <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                <td><?php echo htmlspecialchars($staff['so_dien_thoai']); ?></td>
                                <td class="staff-actions">
                                    <button class="btn-edit" type="button"
                                        data-id="<?php echo (int)$staff['ma_nguoi_dung']; ?>"
                                        data-name="<?php echo htmlspecialchars($staff['ho_ten'], ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($staff['email'], ENT_QUOTES); ?>"
                                        data-phone="<?php echo htmlspecialchars($staff['so_dien_thoai'], ENT_QUOTES); ?>"
                                        onclick="openEditModalByData(this)"><i class='fas fa-edit'></i> Sửa</button>
                                    <button class="btn-delete" type="submit" form="delete-staff-<?php echo $staff['ma_nguoi_dung']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa nhân viên này không?');"><i class="fas fa-trash"></i> Xóa</button>
                                </td>
                            </tr>
                            <form id="delete-staff-<?php echo $staff['ma_nguoi_dung']; ?>" method="post" style="display:none;">
                                <input type="hidden" name="delete_staff" value="1">
                                <input type="hidden" name="delete_id" value="<?php echo $staff['ma_nguoi_dung']; ?>">
                            </form>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <!-- Modal Sửa nhân viên -->
            <div class="modal" id="editModal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeEditModal()">&times;</span>
                    <h3>Chỉnh sửa nhân viên</h3>
                    <?php if (isset($_POST['edit_staff'])): ?>
                        <div class="modal-alert <?php echo $success ? 'success' : ''; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="staff_id" id="edit_staff_id">
                        <input type="text" name="edit_name" id="edit_name" placeholder="Họ tên" required>
                        <input type="email" name="edit_email" id="edit_email" placeholder="Email" required>
                        <input type="text" name="edit_phone" id="edit_phone" placeholder="Số điện thoại" required>
                        <input type="password" name="edit_password" id="edit_password" placeholder="Mật khẩu mới (bỏ trống nếu không đổi)">
                        <button type="submit" name="edit_staff"><i class="fas fa-save"></i> Lưu thay đổi</button>
                    </form>
                </div>
            </div>
            <script>
                function openEditModalByData(btn) {
                    document.getElementById('edit_staff_id').value = btn.getAttribute('data-id');
                    document.getElementById('edit_name').value = btn.getAttribute('data-name');
                    document.getElementById('edit_email').value = btn.getAttribute('data-email');
                    document.getElementById('edit_phone').value = btn.getAttribute('data-phone');
                    document.getElementById('edit_password').value = '';
                    document.getElementById('editModal').classList.add('active');
                }
                function closeEditModal() {
                    document.getElementById('editModal').classList.remove('active');
                }
                // Clear form tạo tài khoản sau khi tạo thành công
                window.onload = function() {
                    <?php if ($success && isset($_POST['create_staff'])): ?>
                        var form = document.querySelector('.staff-form-card form');
                        if (form) form.reset();
                    <?php endif; ?>
                }
            </script>
        </div>
    </div>
</body>
</html> 