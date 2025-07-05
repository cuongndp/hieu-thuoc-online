<?php
include 'config/dual_session.php';
include 'config/database.php';

// Biến thông báo
$message = '';
$message_type = '';
$show_register = false;

// Kiểm tra nếu đã đăng nhập thì chuyển về trang chủ
if (is_user_logged_in()) {
    header('Location: index.php');
    exit;
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        // Xử lý đăng nhập
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            $message = 'Vui lòng nhập đầy đủ thông tin';
            $message_type = 'error';
        } else {
            // Tìm user trong database
            $sql = "SELECT ma_nguoi_dung, email, mat_khau_ma_hoa, ho_ten, so_dien_thoai, gioi_tinh, vai_tro 
                    FROM nguoi_dung 
                    WHERE email = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['mat_khau_ma_hoa'])) {
                    // Đăng nhập thành công - sử dụng dual session
                    user_login($user['ma_nguoi_dung'], $user['ho_ten'], $user['email']);
                    
                    // Lưu thêm thông tin khác vào session
                    $_SESSION['user_phone'] = $user['so_dien_thoai'];
                    $_SESSION['user_gender'] = $user['gioi_tinh'];
                    $_SESSION['user_role'] = $user['vai_tro'];
                    
                    // Xử lý remember me (tạm thời lưu vào session, không dùng DB)
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expiry = time() + (30 * 24 * 60 * 60); // 30 ngày
                        
                        // Set cookie
                        setcookie('remember_token', $token, $expiry, '/');
                        $_SESSION['remember_token'] = $token;
                    }
                    
                    // Redirect về trang chủ
                    header('Location: index.php');
                    exit;
                } else {
                    $message = 'Email hoặc mật khẩu không chính xác';
                    $message_type = 'error';
                }
            } else {
                $message = 'Tài khoản không tồn tại';
                $message_type = 'error';
            }
        }
    } 
    
    elseif ($action === 'register') {
        // Xử lý đăng ký
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $gender = $_POST['gender'] ?? 'Nam';
        $birthdate = $_POST['birthdate'] ?? null;
        
        $show_register = true; // Hiển thị form đăng ký nếu có lỗi
        
        // Validation ngày sinh
        if ($birthdate && $birthdate !== '') {
            $birthdate_obj = new DateTime($birthdate);
            $today = new DateTime();
            $min_date = new DateTime('1900-01-01');
            $max_date = new DateTime();
            $max_date->modify('-13 years'); // Ít nhất 13 tuổi
            
            if ($birthdate_obj < $min_date) {
                $message = 'Ngày sinh không thể trước năm 1900';
                $message_type = 'error';
            } elseif ($birthdate_obj > $max_date) {
                $message = 'Bạn phải ít nhất 13 tuổi để đăng ký';
                $message_type = 'error';
            }
        }
        
        // Validation
        if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
            $message = 'Vui lòng nhập đầy đủ thông tin';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email không hợp lệ';
            $message_type = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Mật khẩu phải có ít nhất 6 ký tự';
            $message_type = 'error';
        } elseif ($password !== $confirm_password) {
            $message = 'Mật khẩu xác nhận không khớp';
            $message_type = 'error';
        } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
            $message = 'Số điện thoại không hợp lệ';
            $message_type = 'error';
        } else {
            // Kiểm tra email đã tồn tại
            $check_sql = "SELECT ma_nguoi_dung FROM nguoi_dung WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $message = 'Email đã được sử dụng';
                $message_type = 'error';
            } else {
                // Kiểm tra số điện thoại đã tồn tại
                $check_sql = "SELECT ma_nguoi_dung FROM nguoi_dung WHERE so_dien_thoai = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $phone);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $message = 'Số điện thoại đã được sử dụng';
                    $message_type = 'error';
                } else {
                    // Mã hóa mật khẩu
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Thêm user mới - CHỈ các cột có trong database
                    if ($birthdate && $birthdate !== '') {
                        // Có ngày sinh
                        $insert_sql = "INSERT INTO nguoi_dung (email, mat_khau_ma_hoa, ho_ten, so_dien_thoai, gioi_tinh, vai_tro, ngay_sinh) 
                                       VALUES (?, ?, ?, ?, ?, 'khach_hang', ?)";
                        $insert_stmt = $conn->prepare($insert_sql);
                        $insert_stmt->bind_param("ssssss", $email, $hashed_password, $fullname, $phone, $gender, $birthdate);
                    } else {
                        // Không có ngày sinh
                        $insert_sql = "INSERT INTO nguoi_dung (email, mat_khau_ma_hoa, ho_ten, so_dien_thoai, gioi_tinh, vai_tro) 
                                       VALUES (?, ?, ?, ?, ?, 'khach_hang')";
                        $insert_stmt = $conn->prepare($insert_sql);
                        $insert_stmt->bind_param("sssss", $email, $hashed_password, $fullname, $phone, $gender);
                    }
                    
                    if ($insert_stmt->execute()) {
                        $message = 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.';
                        $message_type = 'success';
                        $show_register = false; // Chuyển về form đăng nhập
                    } else {
                        $message = 'Có lỗi xảy ra, vui lòng thử lại. Lỗi: ' . $conn->error;
                        $message_type = 'error';
                    }
                }
            }
        }
    }
    
    elseif ($action === 'switch_to_register') {
        // Chuyển sang form đăng ký thuần PHP
        $show_register = true;
    }
    
    elseif ($action === 'switch_to_login') {
        // Chuyển sang form đăng nhập thuần PHP
        $show_register = false;
    }
}

// Đảm bảo biến $show_register luôn có giá trị boolean
$show_register = isset($show_register) ? (bool)$show_register : false;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập & Đăng Ký - VitaMeds</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/header.css">
</head>
<body>
    <div class="auth-container">
        <!-- Back to home button -->
        <a href="index.php" class="back-home">
            <i class="fas fa-home"></i>
            Về trang chủ
        </a>

        <!-- Left side - Auth Info -->
        <div class="auth-info">
            <div class="logo-container">
                <div class="logo-img">
                    <a href="index.php"><img src="./images/medical-care-logo-illustration-vector.jpg" alt="VitaMeds Logo"></a>
                </div>
                <div class="logo-text">VitaMeds</div>
            </div>
            
            <div class="info-content">
                <h2 id="info-title"><?php echo $show_register ? 'Xin chào!' : 'Chào mừng trở lại!'; ?></h2>
                <p id="info-description">
                    <?php echo $show_register ? 'Đăng ký tài khoản để trải nghiệm dịch vụ tuyệt vời của chúng tôi.' : 'Đăng nhập để tiếp tục mua sắm và quản lý đơn hàng của bạn.'; ?>
                </p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="<?php echo $show_register ? 'switch_to_login' : 'switch_to_register'; ?>">
                    <button type="submit" class="switch-btn">
                        <?php echo $show_register ? 'Đăng nhập' : 'Đăng ký ngay'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right side - Auth Forms -->
        <div class="auth-form-container">
            <!-- Login Form -->
            <div class="auth-form <?php echo !$show_register ? 'active' : ''; ?>" id="login-form">
                <div class="form-title">
                    <h2>Đăng Nhập</h2>
                    <p>Nhập thông tin để truy cập tài khoản</p>
                </div>

                <?php if ($message && !$show_register): ?>
                    <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>" style="display: block;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" name="email" required value="<?php echo isset($_POST['email']) && !$show_register ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <i class="fas fa-envelope"></i>
                    </div>

                    <div class="form-group">
                        <label for="login-password">Mật khẩu</label>
                        <input type="password" id="login-password" name="password" required>
                        <i class="fas fa-eye-slash" onclick="togglePassword('login-password', this)"></i>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" <?php echo isset($_POST['remember']) && !$show_register ? 'checked' : ''; ?>>
                            Ghi nhớ đăng nhập
                        </label>
                        <a href="#" class="forgot-password">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="submit-btn">
                        Đăng Nhập
                    </button>
                </form>

                <div class="auth-switch">
                    Chưa có tài khoản? 
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="switch_to_register">
                        <button type="submit" style="background: none; border: none; color: #3498db; cursor: pointer; text-decoration: underline;">
                            Đăng ký ngay
                        </button>
                    </form>
                </div>
            </div>

            <!-- Register Form -->
            <div class="auth-form <?php echo $show_register ? 'active' : ''; ?>" id="register-form">
                <div class="form-title">
                    <h2>Đăng Ký</h2>
                    <p>Tạo tài khoản mới để bắt đầu mua sắm</p>
                </div>

                <?php if ($message && $show_register): ?>
                    <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>" style="display: block;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="register-fullname">Họ và tên</label>
                        <input type="text" id="register-fullname" name="fullname" required value="<?php echo isset($_POST['fullname']) && $show_register ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                        <i class="fas fa-user"></i>
                    </div>

                    <div class="form-group">
                        <label for="register-email">Email</label>
                        <input type="email" id="register-email" name="email" required value="<?php echo isset($_POST['email']) && $show_register ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <i class="fas fa-envelope"></i>
                    </div>

                    <div class="form-group">
                        <label for="register-phone">Số điện thoại</label>
                        <input type="tel" id="register-phone" name="phone" required pattern="[0-9]{10,11}" value="<?php echo isset($_POST['phone']) && $show_register ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        <i class="fas fa-phone"></i>
                    </div>

                    <div class="form-group">
                        <label for="register-gender">Giới tính</label>
                        <select id="register-gender" name="gender" required>
                            <option value="Nam" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Nam' && $show_register) ? 'selected' : ''; ?>>Nam</option>
                            <option value="Nữ" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Nữ' && $show_register) ? 'selected' : ''; ?>>Nữ</option>
                            <option value="Khác" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Khác' && $show_register) ? 'selected' : ''; ?>>Khác</option>
                        </select>
                        <i class="fas fa-venus-mars"></i>
                    </div>

                    <div class="form-group">
                        <label for="register-birthdate">Ngày sinh (tùy chọn)</label>
                        <input type="date" id="register-birthdate" name="birthdate" 
                               value="<?php echo isset($_POST['birthdate']) && $show_register ? htmlspecialchars($_POST['birthdate']) : ''; ?>"
                               min="1900-01-01" 
                               max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>"
                               onchange="validateBirthdate(this)">
                        <small class="form-text">Ngày sinh phải từ năm 1900 và bạn phải ít nhất 13 tuổi</small>
                        <!-- <i class="fas fa-birthday-cake"></i> -->
                    </div>

                    <div class="form-group">
                        <label for="register-password">Mật khẩu</label>
                        <input type="password" id="register-password" name="password" required minlength="6">
                        <i class="fas fa-eye-slash" onclick="togglePassword('register-password', this)"></i>
                    </div>

                    <div class="form-group">
                        <label for="register-confirm-password">Xác nhận mật khẩu</label>
                        <input type="password" id="register-confirm-password" name="confirm_password" required>
                        <i class="fas fa-eye-slash" onclick="togglePassword('register-confirm-password', this)"></i>
                    </div>

                    <button type="submit" class="submit-btn">
                        Đăng Ký
                    </button>
                </form>

                <div class="auth-switch">
                    Đã có tài khoản? 
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="switch_to_login">
                        <button type="submit" style="background: none; border: none; color: #3498db; cursor: pointer; text-decoration: underline;">
                            Đăng nhập ngay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript CHỈ cho UI - không có logic backend
        function validateBirthdate(input) {
            const selectedDate = new Date(input.value);
            const today = new Date();
            const minDate = new Date('1900-01-01');
            const maxDate = new Date();
            maxDate.setFullYear(today.getFullYear() - 13); // Ít nhất 13 tuổi
            
            if (selectedDate < minDate) {
                input.setCustomValidity('Ngày sinh không thể trước năm 1900');
                return false;
            } else if (selectedDate > maxDate) {
                input.setCustomValidity('Bạn phải ít nhất 13 tuổi để đăng ký');
                return false;
            } else {
                input.setCustomValidity('');
                return true;
            }
        }
        
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>
</html>