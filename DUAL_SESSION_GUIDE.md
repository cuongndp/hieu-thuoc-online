# 🔐 Hướng dẫn Dual Session System

## ✅ Vấn đề đã giải quyết

- ✅ **Đăng nhập cùng lúc**: User và Admin có thể đăng nhập cùng lúc trong cùng trình duyệt
- ✅ **Đăng xuất độc lập**: Đăng xuất Admin không ảnh hưởng User và ngược lại
- ✅ **Không lỗi redeclare**: Chỉ sử dụng 1 file session duy nhất
- ✅ **Giữ nguyên giỏ hàng**: Khi admin đăng xuất, giỏ hàng user vẫn còn

## 🛠️ Cách hoạt động

### File chính: `config/dual_session.php`
```php
// User session variables
$_SESSION['user_logged_in'] = true/false
$_SESSION['user_id'] = ID khách hàng
$_SESSION['user_name'] = Tên khách hàng
$_SESSION['user_email'] = Email khách hàng

// Admin session variables  
$_SESSION['admin_logged_in'] = true/false
$_SESSION['admin_id'] = ID admin
$_SESSION['admin_name'] = Tên admin
$_SESSION['admin_email'] = Email admin
$_SESSION['admin_role'] = Vai trò admin
```

### Functions có sẵn:

#### User Functions:
- `is_user_logged_in()` - Kiểm tra user đã đăng nhập chưa
- `get_user_info()` - Lấy thông tin user (array)
- `get_user_id()` - Lấy ID user
- `get_user_name()` - Lấy tên user
- `get_user_email()` - Lấy email user
- `user_login($id, $name, $email)` - Đăng nhập user
- `user_logout()` - Đăng xuất user (không ảnh hưởng admin)
- `require_user_login()` - Yêu cầu user phải đăng nhập

#### Admin Functions:
- `is_admin_logged_in()` - Kiểm tra admin đã đăng nhập chưa
- `get_admin_info()` - Lấy thông tin admin (array)
- `get_admin_id()` - Lấy ID admin
- `get_admin_name()` - Lấy tên admin
- `get_admin_email()` - Lấy email admin
- `get_admin_role()` - Lấy role admin
- `admin_login($id, $name, $email, $role)` - Đăng nhập admin
- `admin_logout()` - Đăng xuất admin (không ảnh hưởng user)
- `require_admin_login()` - Yêu cầu admin phải đăng nhập

#### Utility Functions:
- `get_current_user_type()` - Trả về 'admin', 'user', hoặc 'guest'
- `debug_session_info()` - Debug thông tin session

## 🧪 Test hệ thống

### Chạy file test:
```
http://localhost/hieu-thuoc-online/test_session_system.php
```

### Test thủ công:

1. **Mở 2 tab trình duyệt:**
   - Tab 1: `http://localhost/hieu-thuoc-online/` (trang user)
   - Tab 2: `http://localhost/hieu-thuoc-online/admin/` (trang admin)

2. **Đăng nhập user ở tab 1:**
   - Vào Login → Đăng nhập bằng tài khoản khách hàng
   - Thêm sản phẩm vào giỏ hàng

3. **Đăng nhập admin ở tab 2:**
   - Vào Admin Login → Đăng nhập bằng tài khoản admin
   - Kiểm tra trang admin hoạt động bình thường

4. **Test đăng xuất:**
   - Đăng xuất admin ở tab 2
   - Quay lại tab 1 → Refresh trang
   - ✅ User vẫn đăng nhập, giỏ hàng vẫn còn

5. **Test ngược lại:**
   - Đăng nhập lại admin ở tab 2
   - Đăng xuất user ở tab 1
   - Quay lại tab 2 → Refresh trang
   - ✅ Admin vẫn đăng nhập bình thường

## 📁 Files đã cập nhật

### User Files:
- `index.php` - Trang chủ
- `login.php` - Đăng nhập user
- `logout.php` - Đăng xuất user
- `cart.php` - Giỏ hàng
- `profile.php` - Thông tin user
- `includes/header.php` - Header chung
- Các file khác: `search.php`, `danh-muc.php`, `chi-tiet-san-pham.php`, etc.

### Admin Files:
- `admin/login.php` - Đăng nhập admin
- `admin/logout.php` - Đăng xuất admin
- `admin/dashboard.php` - Dashboard admin
- `admin/products.php` - Quản lý sản phẩm
- `admin/orders.php` - Quản lý đơn hàng
- Tất cả file admin khác

## 🔧 Cách sử dụng trong code

### Trong file user:
```php
<?php
include 'config/dual_session.php';

// Kiểm tra đăng nhập
if (is_user_logged_in()) {
    // Cách 1: Sử dụng helper functions (đơn giản)
    echo "Xin chào " . get_user_name();
    $user_id = get_user_id();
    $user_email = get_user_email();
    
    // Cách 2: Sử dụng get_user_info() (lấy array)
    $user_info = get_user_info();
    echo "Xin chào " . $user_info['name'];
}

// Yêu cầu đăng nhập
require_user_login();
?>
```

### Trong file admin:
```php
<?php
include '../config/dual_session.php';

// Kiểm tra đăng nhập admin
if (is_admin_logged_in()) {
    // Cách 1: Sử dụng helper functions (đơn giản)
    echo "Xin chào Admin " . get_admin_name();
    $admin_id = get_admin_id();
    $admin_role = get_admin_role();
    
    // Cách 2: Sử dụng get_admin_info() (lấy array)
    $admin_info = get_admin_info();
    echo "Xin chào Admin " . $admin_info['name'];
}

// Yêu cầu đăng nhập admin
require_admin_login();
?>
```

## ⚠️ Lưu ý quan trọng

1. **Chỉ sử dụng 1 file session**: `config/dual_session.php`
2. **Không sử dụng**: `session_simple.php` (đã xóa)
3. **Include đúng path**: 
   - User files: `include 'config/dual_session.php';`
   - Admin files: `include '../config/dual_session.php';`
4. **Không gọi session_start()**: File dual_session.php đã tự động xử lý

## 🎉 Kết quả

Bây giờ bạn có thể:
- ✅ Đăng nhập cùng lúc cả user và admin
- ✅ Đăng xuất một bên không ảnh hưởng bên kia
- ✅ Giữ nguyên giỏ hàng khi admin logout
- ✅ Không có lỗi redeclare functions
- ✅ Hệ thống hoạt động ổn định 