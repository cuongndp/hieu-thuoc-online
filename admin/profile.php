<?php
include '../config/dual_session.php';
include '../config/database.php';

// Ensure session is started
ensure_session_started();

// Chỉ cho phép nhân viên đăng nhập
if (!is_admin_logged_in() || ($_SESSION['admin_role'] ?? '') !== 'nhan_vien') {
    header('Location: dashboard.php');
    exit;
}

$staff_id = $_SESSION['admin_id'];
$message = '';
$success = false;

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    if (empty($name) || empty($phone)) {
        $message = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $message = 'Số điện thoại không hợp lệ.';
    } else {
        // Kiểm tra số điện thoại trùng với nhân viên khác
        $check = $conn->prepare('SELECT ma_nguoi_dung FROM nguoi_dung WHERE so_dien_thoai = ? AND ma_nguoi_dung != ? AND vai_tro = "nhan_vien"');
        if (!$check) { die('Lỗi prepare (check phone): ' . $conn->error); }
        $check->bind_param('si', $phone, $staff_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = 'Số điện thoại đã được sử dụng bởi nhân viên khác!';
        } else {
            $stmt = $conn->prepare('UPDATE nguoi_dung SET ho_ten=?, so_dien_thoai=? WHERE ma_nguoi_dung=? AND vai_tro="nhan_vien"');
            if (!$stmt) { die('Lỗi prepare (update staff profile): ' . $conn->error); }
            $stmt->bind_param('ssi', $name, $phone, $staff_id);
            if ($stmt->execute()) {
                $message = 'Cập nhật thông tin thành công!';
                $success = true;
            } else {
                $message = 'Lỗi khi cập nhật thông tin!';
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Kiểm tra có dấu tiếng Việt
    if (preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴÈÉẸẺẼÊỀẾỆỂỄÌÍỊỈĨÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠÙÚỤỦŨƯỪỨỰỬỮỲÝỴỶỸĐ]/u', $new_password) || preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴÈÉẸẺẼÊỀẾỆỂỄÌÍỊỈĨÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠÙÚỤỦŨƯỪỨỰỬỮỲÝỴỶỸĐ]/u', $confirm_password)) {
        $message = 'Mật khẩu không được chứa ký tự có dấu tiếng Việt!';
    } elseif (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (strlen($new_password) < 6) {
        $message = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Mật khẩu xác nhận không khớp.';
    } else {
        $check = $conn->prepare('SELECT mat_khau_ma_hoa FROM nguoi_dung WHERE ma_nguoi_dung = ? AND vai_tro = "nhan_vien"');
        if (!$check) { die('Lỗi prepare (check pass): ' . $conn->error); }
        $check->bind_param('i', $staff_id);
        $check->execute();
        $result = $check->get_result();
        $user = $result->fetch_assoc();
        if (!$user || !password_verify($current_password, $user['mat_khau_ma_hoa'])) {
            $message = 'Mật khẩu hiện tại không chính xác!';
        } elseif (password_verify($new_password, $user['mat_khau_ma_hoa'])) {
            $message = 'Mật khẩu mới không được trùng với mật khẩu hiện tại!';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE nguoi_dung SET mat_khau_ma_hoa=? WHERE ma_nguoi_dung=? AND vai_tro="nhan_vien"');
            if (!$stmt) { die('Lỗi prepare (update pass): ' . $conn->error); }
            $stmt->bind_param('si', $hashed_password, $staff_id);
            if ($stmt->execute()) {
                $message = 'Đổi mật khẩu thành công!';
                $success = true;
            } else {
                $message = 'Lỗi khi đổi mật khẩu!';
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Lấy thông tin nhân viên
$staff = $conn->query("SELECT * FROM nguoi_dung WHERE ma_nguoi_dung = $staff_id AND vai_tro = 'nhan_vien'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - VitaMeds Nhân viên</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .profile-staff-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px #e0e6ed;
            padding: 40px 32px 32px 32px;
        }
        .profile-staff-container h2 { text-align: center; color: #2c3e50; margin-bottom: 24px; }
        .profile-staff-container form { margin-bottom: 32px; }
        .profile-staff-container label { font-weight: 600; color: #34495e; }
        .profile-staff-container input { width: 100%; padding: 12px; margin-bottom: 18px; border-radius: 7px; border: 1px solid #dbe2ef; font-size: 15px; }
        .profile-staff-container button { background: #3498db; color: #fff; border: none; padding: 12px 28px; border-radius: 7px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.2s; width: 100%; margin-top: 8px; }
        .profile-staff-container button:hover { background: #217dbb; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 7px; margin-bottom: 18px; border: 1px solid #c3e6cb; text-align: center; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 7px; margin-bottom: 18px; border: 1px solid #f5c6cb; text-align: center; }
        @media (max-width: 900px) {
            .profile-staff-container { max-width: 100%; padding: 20px 6px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar-admin.php'; ?>
    <div class="main-content">
        <div class="profile-staff-container">
            <h2>Thông tin cá nhân nhân viên</h2>
            <?php if ($message): ?>
                <div class="<?php echo $success ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="update_profile" value="1">
                <label for="name">Họ tên</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($staff['ho_ten']); ?>">
                <label for="email">Email (không thể đổi)</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" disabled>
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone" required value="<?php echo htmlspecialchars($staff['so_dien_thoai']); ?>">
                <button type="submit"><i class="fas fa-save"></i> Cập nhật thông tin</button>
            </form>
            <form method="post" autocomplete="off">
                <input type="hidden" name="change_password" value="1">
                <label for="current_password">Mật khẩu hiện tại</label>
                <input type="password" id="current_password" name="current_password" required>
                <label for="new_password">Mật khẩu mới</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <button type="submit"><i class="fas fa-key"></i> Đổi mật khẩu</button>
            </form>
        </div>
    </div>
</body>
</html> 