<?php
// config/database.php - File kết nối cơ sở dữ liệu

// Thông tin kết nối database
$servername = "localhost";     // Tên server (thường là localhost)
$username = "root";            // Username MySQL (mặc định là root)
$password = "";                // Password MySQL (để trống nếu không có)
$dbname = "hieu_thuoc_online"; // Tên database (thay bằng tên DB của bạn)

// Thiết lập kết nối MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập charset UTF-8 để hiển thị tiếng Việt
$conn->set_charset("utf8");

// Thiết lập timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Function để escape string (bảo mật)
function escape_string($str) {
    global $conn;
    return $conn->real_escape_string($str);
}

// Function để format tiền tệ
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

// Function để tạo slug từ tiếng Việt
function create_slug($str) {
    // Chuyển về chữ thường
    $str = strtolower($str);
    
    // Loại bỏ dấu tiếng Việt
    $str = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $str);
    $str = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $str);
    $str = preg_replace('/[ìíịỉĩ]/u', 'i', $str);
    $str = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $str);
    $str = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $str);
    $str = preg_replace('/[ỳýỵỷỹ]/u', 'y', $str);
    $str = preg_replace('/đ/u', 'd', $str);
    
    // Loại bỏ ký tự đặc biệt
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    
    // Thay khoảng trắng bằng dấu gạch ngang
    $str = preg_replace('/[\s-]+/', '-', $str);
    
    return trim($str, '-');
}

// Optional: Kiểm tra kết nối thành công
// echo "Kết nối database thành công!";

// --- HƯỚNG DẪN TẠO BẢNG QUẢNG CÁO ĐỘNG ---
//
// CREATE TABLE quang_cao (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     tieu_de VARCHAR(255),
//     mo_ta TEXT,
//     hinh_anh VARCHAR(255),
//     link VARCHAR(255),
//     trang_thai TINYINT DEFAULT 1,
//     ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
// );
//
// Sau khi tạo bảng, thêm vài dòng dữ liệu mẫu để test banner động.
?>