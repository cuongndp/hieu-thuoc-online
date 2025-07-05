<?php
include 'config/dual_session.php';
include 'config/database.php';

echo "<h2>🧪 Test Dual Session System</h2>";

// Test 1: Kiểm tra session ban đầu
echo "<h3>📋 Test 1: Session ban đầu</h3>";
echo "User logged in: " . (is_user_logged_in() ? '✅ YES' : '❌ NO') . "<br>";
echo "Admin logged in: " . (is_admin_logged_in() ? '✅ YES' : '❌ NO') . "<br>";

// Test 2: Đăng nhập user
echo "<h3>👤 Test 2: Đăng nhập User</h3>";
user_login(1, 'Khách hàng Test', 'customer@test.com');
echo "User logged in: " . (is_user_logged_in() ? '✅ YES' : '❌ NO') . "<br>";
echo "Admin logged in: " . (is_admin_logged_in() ? '✅ YES' : '❌ NO') . "<br>";
$user_info = get_user_info();
echo "User info: " . print_r($user_info, true) . "<br>";
echo "Helper functions - User ID: " . get_user_id() . ", Name: " . get_user_name() . ", Email: " . get_user_email() . "<br>";

// Test 3: Đăng nhập admin (không ảnh hưởng user)
echo "<h3>👨‍💼 Test 3: Đăng nhập Admin</h3>";
admin_login(1, 'Admin Test', 'admin@test.com', 'admin');
echo "User logged in: " . (is_user_logged_in() ? '✅ YES' : '❌ NO') . "<br>";
echo "Admin logged in: " . (is_admin_logged_in() ? '✅ YES' : '❌ NO') . "<br>";
$admin_info = get_admin_info();
echo "Admin info: " . print_r($admin_info, true) . "<br>";
echo "Helper functions - Admin ID: " . get_admin_id() . ", Name: " . get_admin_name() . ", Email: " . get_admin_email() . ", Role: " . get_admin_role() . "<br>";

// Test 4: Đăng xuất admin (không ảnh hưởng user)
echo "<h3>🚪 Test 4: Đăng xuất Admin</h3>";
admin_logout();
echo "User logged in: " . (is_user_logged_in() ? '✅ YES' : '❌ NO') . "<br>";
echo "Admin logged in: " . (is_admin_logged_in() ? '✅ YES' : '❌ NO') . "<br>";

// Test 5: Đăng xuất user
echo "<h3>🚪 Test 5: Đăng xuất User</h3>";
user_logout();
echo "User logged in: " . (is_user_logged_in() ? '✅ YES' : '❌ NO') . "<br>";
echo "Admin logged in: " . (is_admin_logged_in() ? '✅ YES' : '❌ NO') . "<br>";

// Test 6: Kiểm tra session variables
echo "<h3>🔍 Test 6: Debug Session Variables</h3>";
debug_session_info();

echo "<h3>✅ Test hoàn thành!</h3>";
echo "<p><strong>Kết quả mong đợi:</strong></p>";
echo "<ul>";
echo "<li>✅ User và Admin có thể đăng nhập cùng lúc</li>";
echo "<li>✅ Đăng xuất Admin không ảnh hưởng User</li>";
echo "<li>✅ Đăng xuất User không ảnh hưởng Admin</li>";
echo "<li>✅ Không có lỗi redeclare functions</li>";
echo "</ul>";
?> 