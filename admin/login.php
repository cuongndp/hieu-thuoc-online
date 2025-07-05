<?php
include '../config/simple_session.php';
include '../config/database.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra nếu admin đã đăng nhập
if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $conn->prepare("SELECT ma_nguoi_dung, email, mat_khau_ma_hoa, ho_ten, vai_tro FROM nguoi_dung WHERE email = ? AND vai_tro IN ('quan_tri', 'nhan_vien', 'quan_ly')");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                if (password_verify($password, $admin['mat_khau_ma_hoa'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['ma_nguoi_dung'];
                    $_SESSION['admin_name'] = $admin['ho_ten'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['user_role'] = $admin['vai_tro']; // Lưu vai trò vào session
                    // Phân quyền cho menu/sidebar
                    if ($admin['vai_tro'] === 'quan_tri' || $admin['vai_tro'] === 'quan_ly') {
                        $_SESSION['admin_role'] = 'admin';
                    } else {
                        $_SESSION['admin_role'] = 'nhan_vien';
                    }
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error_message = "Mật khẩu không chính xác!";
                }
            } else {
                $error_message = "Email không tồn tại hoặc không có quyền admin!";
            }
        } catch (Exception $e) {
            $error_message = "Có lỗi xảy ra: " . $e->getMessage();
        }
    } else {
        $error_message = "Vui lòng nhập đầy đủ thông tin!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VitaMeds Admin - Đăng nhập</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #27ae60, #f39c12, #e74c3c);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .logo p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .admin-badge {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 38px;
            color: #7f8c8d;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .error-message {
            background: #ffe6e6;
            color: #c0392b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            font-size: 14px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #7f8c8d;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #3498db;
        }

        .security-note {
            background: #e8f5e8;
            color: #27ae60;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            text-align: center;
            margin-top: 15px;
            border-left: 4px solid #27ae60;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1><i class="fas fa-shield-alt"></i> VitaMeds</h1>
            <div class="admin-badge">
                <i class="fas fa-user-shield"></i> ADMIN PANEL
            </div>
            <p>Hệ thống quản trị website</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Admin
                </label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <i class="fas fa-user"></i>
            </div>

            <div class="form-group">
                <label for="mat_khau">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div style="margin-bottom: 20px;">
                <input type="checkbox" onclick="togglePassword()"> Hiện mật khẩu
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Đăng nhập
            </button>
        </form>

        <div class="security-note">
            <i class="fas fa-info-circle"></i> 
            Chỉ dành cho quản trị viên được ủy quyền
        </div>

        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Quay về trang chủ
            </a>
        </div>
    </div>

    <script>
        // Auto focus vào email field
        document.getElementById('email').focus();
        
        // Enter để submit form
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });

        function togglePassword() {
            var x = document.getElementById("password");
            x.type = (x.type === "password") ? "text" : "password";
        }
    </script>
</body>
</html>