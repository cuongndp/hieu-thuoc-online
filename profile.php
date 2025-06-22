<?php
session_start();
include 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';
$active_tab = $_GET['tab'] ?? 'profile';

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Cập nhật thông tin cá nhân
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = $_POST['gender'] ?? 'Nam';
        $birthdate = $_POST['birthdate'] ?? null;
        
        // Validation
        if (empty($fullname) || empty($email) || empty($phone)) {
            $message = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email không hợp lệ';
            $message_type = 'error';
        } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
            $message = 'Số điện thoại không hợp lệ';
            $message_type = 'error';
        } else {
            // Kiểm tra email đã tồn tại (ngoại trừ user hiện tại)
            $check_sql = "SELECT ma_nguoi_dung FROM nguoi_dung WHERE email = ? AND ma_nguoi_dung != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $message = 'Email đã được sử dụng bởi tài khoản khác';
                $message_type = 'error';
            } else {
                // Kiểm tra số điện thoại đã tồn tại (ngoại trừ user hiện tại)
                $check_sql = "SELECT ma_nguoi_dung FROM nguoi_dung WHERE so_dien_thoai = ? AND ma_nguoi_dung != ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("si", $phone, $user_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $message = 'Số điện thoại đã được sử dụng bởi tài khoản khác';
                    $message_type = 'error';
                } else {
                    // Cập nhật thông tin
                    if ($birthdate && $birthdate !== '') {
                        $update_sql = "UPDATE nguoi_dung SET ho_ten = ?, email = ?, so_dien_thoai = ?, gioi_tinh = ?, ngay_sinh = ? WHERE ma_nguoi_dung = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("sssssi", $fullname, $email, $phone, $gender, $birthdate, $user_id);
                    } else {
                        $update_sql = "UPDATE nguoi_dung SET ho_ten = ?, email = ?, so_dien_thoai = ?, gioi_tinh = ? WHERE ma_nguoi_dung = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("ssssi", $fullname, $email, $phone, $gender, $user_id);
                    }
                    
                    if ($update_stmt->execute()) {
                        // Cập nhật session
                        $_SESSION['user_name'] = $fullname;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_phone'] = $phone;
                        $_SESSION['user_gender'] = $gender;
                        
                        $message = 'Cập nhật thông tin thành công!';
                        $message_type = 'success';
                    } else {
                        $message = 'Có lỗi xảy ra, vui lòng thử lại';
                        $message_type = 'error';
                    }
                }
            }
        }
    }
    
    elseif ($action === 'change_password') {
        // Đổi mật khẩu
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = 'Vui lòng nhập đầy đủ thông tin';
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'Mật khẩu mới phải có ít nhất 6 ký tự';
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Mật khẩu xác nhận không khớp';
            $message_type = 'error';
        } else {
            // Kiểm tra mật khẩu hiện tại
            $check_sql = "SELECT mat_khau_ma_hoa FROM nguoi_dung WHERE ma_nguoi_dung = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!password_verify($current_password, $user['mat_khau_ma_hoa'])) {
                $message = 'Mật khẩu hiện tại không chính xác';
                $message_type = 'error';
            } else {
                // Cập nhật mật khẩu mới
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE nguoi_dung SET mat_khau_ma_hoa = ? WHERE ma_nguoi_dung = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $message = 'Đổi mật khẩu thành công!';
                    $message_type = 'success';
                    $active_tab = 'security';
                } else {
                    $message = 'Có lỗi xảy ra, vui lòng thử lại';
                    $message_type = 'error';
                }
            }
        }
        $active_tab = 'security';
    }
}

// Lấy thông tin user từ database
$user_sql = "SELECT * FROM nguoi_dung WHERE ma_nguoi_dung = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();

// Thống kê cơ bản - chỉ dùng thông tin từ session và giỏ hàng
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'total_spent' => 0,
    'cart_items' => 0
];

// Đếm số sản phẩm trong giỏ hàng
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $stats['cart_items'] = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Tính ngày tham gia
// Tính ngày tham gia - kiểm tra các cột có thể có
$join_date_field = '';
if (isset($user_info['ngay_tao'])) {
    $join_date_field = $user_info['ngay_tao'];
} elseif (isset($user_info['created_at'])) {
    $join_date_field = $user_info['created_at'];
} elseif (isset($user_info['date_created'])) {
    $join_date_field = $user_info['date_created'];
}

if ($join_date_field) {
    $join_date = new DateTime($join_date_field);
    $now = new DateTime();
    $days_member = $now->diff($join_date)->days;
} else {
    $days_member = 0; // Mặc định nếu không có ngày tạo
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/profile.css">
    
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="profile-container">
        <!-- Sidebar -->
        <div class="profile-sidebar">
            <div class="user-avatar">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($user_info['ho_ten'], 0, 1)); ?>
                </div>
                <div class="user-name"><?php echo htmlspecialchars($user_info['ho_ten']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($user_info['email']); ?></div>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="profile.php?tab=profile" class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i>
                        Thông tin cá nhân
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php?tab=security" class="nav-link <?php echo $active_tab === 'security' ? 'active' : ''; ?>">
                        <i class="fas fa-shield-alt"></i>
                        Bảo mật
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cart.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Giỏ hàng
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link" style="color: #e74c3c;">
                        <i class="fas fa-sign-out-alt"></i>
                        Đăng xuất
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="profile-content">
            <!-- Header -->
            <div class="content-header">
                <h1>
                    <?php if ($active_tab === 'profile'): ?>
                        Thông tin cá nhân
                    <?php elseif ($active_tab === 'security'): ?>
                        Cài đặt bảo mật
                    <?php endif; ?>
                </h1>
            </div>

            <!-- Body -->
            <div class="content-body">
                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Tab -->
                <div id="profile" class="tab-content <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
                    <!-- Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $stats['cart_items']; ?></span>
                            <div class="stat-label">Sản phẩm trong giỏ</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $days_member; ?></span>
                            <div class="stat-label">Ngày thành viên</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo ucfirst($user_info['vai_tro'] ?? 'Khách hàng'); ?></span>
                            <div class="stat-label">Vai trò</div>
                        </div>
                    </div>

                    <!-- Profile Form -->
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="fullname">Họ và tên <span class="required">*</span></label>
                                <input type="text" id="fullname" name="fullname" required 
                                       value="<?php echo htmlspecialchars($user_info['ho_ten']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($user_info['email']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="phone">Số điện thoại <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" required pattern="[0-9]{10,11}"
                                       value="<?php echo htmlspecialchars($user_info['so_dien_thoai']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="gender">Giới tính</label>
                                <select id="gender" name="gender">
                                    <option value="Nam" <?php echo $user_info['gioi_tinh'] === 'Nam' ? 'selected' : ''; ?>>Nam</option>
                                    <option value="Nữ" <?php echo $user_info['gioi_tinh'] === 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                                    <option value="Khác" <?php echo $user_info['gioi_tinh'] === 'Khác' ? 'selected' : ''; ?>>Khác</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="birthdate">Ngày sinh</label>
                                <input type="date" id="birthdate" name="birthdate" 
                                       value="<?php echo $user_info['ngay_sinh']; ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i>
                            Cập nhật thông tin
                        </button>
                    </form>
                </div>

                <!-- Security Tab -->
                <div id="security" class="tab-content <?php echo $active_tab === 'security' ? 'active' : ''; ?>">
                    <div class="security-info">
                        <h4>
                            <i class="fas fa-info-circle"></i>
                            Bảo mật tài khoản
                        </h4>
                        <p>
                            Để bảo vệ tài khoản của bạn, vui lòng sử dụng mật khẩu mạnh bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt. 
                            Không chia sẻ thông tin đăng nhập với người khác.
                        </p>
                    </div>

                    <!-- Change Password Form -->
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Mật khẩu hiện tại <span class="required">*</span></label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới <span class="required">*</span></label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                            <div id="password-strength" class="password-strength"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu mới <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn">
                            <i class="fas fa-key"></i>
                            Đổi mật khẩu
                        </button>
                    </form>

                    <!-- Account Info -->
                    <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e0e0e0;">
                        <h3>Thông tin tài khoản</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Ngày tạo tài khoản:</label>
                                <input type="text" value="<?php echo date('d/m/Y H:i', strtotime($user_info['ngay_tao'])); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Vai trò:</label>
                                <input type="text" value="<?php echo ucfirst($user_info['vai_tro'] ?? 'Khách hàng'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>ID tài khoản:</label>
                                <input type="text" value="<?php echo str_pad($user_info['ma_nguoi_dung'], 6, '0', STR_PAD_LEFT); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
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
    </footer>

    <script>
        // JavaScript CHỈ cho UI - kiểm tra độ mạnh mật khẩu
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                case 2:
                    feedback = '<span class="strength-weak">Yếu</span>';
                    break;
                case 3:
                case 4:
                    feedback = '<span class="strength-medium">Trung bình</span>';
                    break;
                case 5:
                    feedback = '<span class="strength-strong">Mạnh</span>';
                    break;
            }
            
            return feedback;
        }
        
        // Event listener cho password strength
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const strengthDiv = document.getElementById('password-strength');
            
            if (newPasswordInput && strengthDiv) {
                newPasswordInput.addEventListener('input', function() {
                    const strength = checkPasswordStrength(this.value);
                    strengthDiv.innerHTML = strength ? 'Độ mạnh: ' + strength : '';
                });
            }
            
            // Kiểm tra khớp mật khẩu
            const confirmPasswordInput = document.getElementById('confirm_password');
            if (newPasswordInput && confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    if (this.value && this.value !== newPasswordInput.value) {
                        this.setCustomValidity('Mật khẩu xác nhận không khớp');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>