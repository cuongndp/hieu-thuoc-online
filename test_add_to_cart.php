<?php
include 'config/dual_session.php';
include 'config/database.php';

echo "<h2>🛒 Test Add to Cart System</h2>";

// Test 1: Kiểm tra session hiện tại
echo "<h3>📋 Test 1: Kiểm tra session hiện tại</h3>";
echo "User logged in: " . (is_user_logged_in() ? '✅ YES' : '❌ NO') . "<br>";
if (is_user_logged_in()) {
    echo "User ID: " . get_user_id() . "<br>";
    echo "User Name: " . get_user_name() . "<br>";
    echo "User Email: " . get_user_email() . "<br>";
} else {
    echo "❌ Cần đăng nhập để test add to cart<br>";
    echo "<a href='login.php'>Đăng nhập ngay</a><br>";
}

// Test 2: Kiểm tra cấu trúc database
echo "<h3>🗄️ Test 2: Kiểm tra cấu trúc database</h3>";
try {
    // Kiểm tra bảng gio_hang
    $check_table = $conn->query("SHOW TABLES LIKE 'gio_hang'");
    if ($check_table->num_rows > 0) {
        echo "✅ Bảng 'gio_hang' tồn tại<br>";
        
        // Kiểm tra cấu trúc bảng
        $structure = $conn->query("DESCRIBE gio_hang");
        echo "<strong>Cấu trúc bảng gio_hang:</strong><br>";
        while ($row = $structure->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
        }
    } else {
        echo "❌ Bảng 'gio_hang' không tồn tại<br>";
    }
    
    // Kiểm tra bảng san_pham_thuoc
    $check_products = $conn->query("SHOW TABLES LIKE 'san_pham_thuoc'");
    if ($check_products->num_rows > 0) {
        echo "✅ Bảng 'san_pham_thuoc' tồn tại<br>";
        
        // Đếm số sản phẩm
        $count_products = $conn->query("SELECT COUNT(*) as total FROM san_pham_thuoc WHERE trang_thai_hoat_dong = 1");
        $count_row = $count_products->fetch_assoc();
        echo "📦 Có " . $count_row['total'] . " sản phẩm hoạt động<br>";
    } else {
        echo "❌ Bảng 'san_pham_thuoc' không tồn tại<br>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "<br>";
}

// Test 3: Kiểm tra giỏ hàng hiện tại
if (is_user_logged_in()) {
    echo "<h3>🛒 Test 3: Kiểm tra giỏ hàng hiện tại</h3>";
    $user_id = get_user_id();
    
    try {
        $cart_sql = "SELECT COUNT(*) as total_items, SUM(so_luong) as total_quantity FROM gio_hang WHERE ma_nguoi_dung = ?";
        $cart_stmt = $conn->prepare($cart_sql);
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        $cart_data = $cart_result->fetch_assoc();
        
        echo "🛒 Số loại sản phẩm trong giỏ: " . ($cart_data['total_items'] ?? 0) . "<br>";
        echo "📦 Tổng số lượng sản phẩm: " . ($cart_data['total_quantity'] ?? 0) . "<br>";
        
        // Hiển thị chi tiết giỏ hàng
        $detail_sql = "
            SELECT gh.ma_san_pham, gh.so_luong, sp.ten_san_pham, sp.gia_ban 
            FROM gio_hang gh 
            LEFT JOIN san_pham_thuoc sp ON gh.ma_san_pham = sp.ma_san_pham 
            WHERE gh.ma_nguoi_dung = ?
        ";
        $detail_stmt = $conn->prepare($detail_sql);
        $detail_stmt->bind_param("i", $user_id);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->get_result();
        
        if ($detail_result->num_rows > 0) {
            echo "<strong>Chi tiết giỏ hàng:</strong><br>";
            while ($item = $detail_result->fetch_assoc()) {
                echo "- Sản phẩm #" . $item['ma_san_pham'] . ": " . 
                     htmlspecialchars($item['ten_san_pham'] ?? 'Không tên') . 
                     " (SL: " . $item['so_luong'] . ")<br>";
            }
        } else {
            echo "🛒 Giỏ hàng trống<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Lỗi khi kiểm tra giỏ hàng: " . $e->getMessage() . "<br>";
    }
}

// Test 4: Test form add to cart
if (is_user_logged_in()) {
    echo "<h3>🧪 Test 4: Test form add to cart</h3>";
    
    // Lấy một sản phẩm mẫu để test
    try {
        $sample_product = $conn->query("SELECT ma_san_pham, ten_san_pham, gia_ban FROM san_pham_thuoc WHERE trang_thai_hoat_dong = 1 LIMIT 1")->fetch_assoc();
        
        if ($sample_product) {
            echo "<strong>Sản phẩm test:</strong> " . htmlspecialchars($sample_product['ten_san_pham']) . "<br>";
            echo "<strong>Giá:</strong> " . number_format($sample_product['gia_ban'], 0, ',', '.') . "đ<br>";
            
            echo "<form method='POST' style='margin: 10px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
            echo "<input type='hidden' name='add_to_cart' value='1'>";
            echo "<input type='hidden' name='product_id' value='" . $sample_product['ma_san_pham'] . "'>";
            echo "<input type='hidden' name='product_name' value='" . htmlspecialchars($sample_product['ten_san_pham']) . "'>";
            echo "<input type='hidden' name='product_price' value='" . $sample_product['gia_ban'] . "'>";
            echo "<button type='submit' style='background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "<i class='fas fa-cart-plus'></i> Test thêm vào giỏ hàng";
            echo "</button>";
            echo "</form>";
        } else {
            echo "❌ Không tìm thấy sản phẩm nào để test<br>";
        }
    } catch (Exception $e) {
        echo "❌ Lỗi khi lấy sản phẩm test: " . $e->getMessage() . "<br>";
    }
}

// Xử lý add to cart test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    echo "<h3>🎯 Kết quả test add to cart</h3>";
    
    if (!is_user_logged_in()) {
        echo "❌ User chưa đăng nhập<br>";
    } else {
        $user_id = get_user_id();
        $product_id = $_POST['product_id'] ?? 0;
        $quantity = 1;
        
        echo "🔍 Debug info:<br>";
        echo "- User ID: " . $user_id . "<br>";
        echo "- Product ID: " . $product_id . "<br>";
        echo "- Quantity: " . $quantity . "<br>";
        
        if ($product_id > 0 && $user_id > 0) {
            try {
                // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
                $check_sql = "SELECT so_luong FROM gio_hang WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $user_id, $product_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Nếu đã có, tăng số lượng
                    $row = $result->fetch_assoc();
                    $new_quantity = $row['so_luong'] + $quantity;
                    
                    $update_sql = "UPDATE gio_hang SET so_luong = ?, ngay_cap_nhat = NOW() WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
                    
                    if ($update_stmt->execute()) {
                        echo "✅ Đã cập nhật số lượng sản phẩm từ " . $row['so_luong'] . " thành " . $new_quantity . "<br>";
                    } else {
                        echo "❌ Lỗi khi cập nhật: " . $conn->error . "<br>";
                    }
                } else {
                    // Nếu chưa có, thêm mới
                    $insert_sql = "INSERT INTO gio_hang (ma_nguoi_dung, ma_san_pham, so_luong, ngay_them) VALUES (?, ?, ?, NOW())";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                    
                    if ($insert_stmt->execute()) {
                        echo "✅ Đã thêm sản phẩm mới vào giỏ hàng<br>";
                    } else {
                        echo "❌ Lỗi khi thêm: " . $conn->error . "<br>";
                    }
                }
            } catch (Exception $e) {
                echo "❌ Exception: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Thông tin không hợp lệ (User ID: $user_id, Product ID: $product_id)<br>";
        }
    }
    
    echo "<br><a href='test_add_to_cart.php'>🔄 Refresh để test lại</a><br>";
    echo "<a href='cart.php'>🛒 Xem giỏ hàng</a><br>";
}

echo "<h3>🔗 Links hữu ích</h3>";
echo "<a href='index.php'>🏠 Trang chủ</a> | ";
echo "<a href='cart.php'>🛒 Giỏ hàng</a> | ";
echo "<a href='login.php'>🔐 Đăng nhập</a> | ";
echo "<a href='test_session_system.php'>🧪 Test Session</a>";
?> 