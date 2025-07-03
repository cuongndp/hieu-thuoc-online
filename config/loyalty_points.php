<?php
// config/loyalty_points.php - Hệ thống tích điểm VitaMeds

/**
 * Tính điểm tích lũy cho đơn hàng
 * Quy tắc: 1 điểm cho mỗi 10,000đ, đơn hàng tối thiểu 50,000đ
 */
function calculate_loyalty_points($order_total) {
    $min_order_value = 50000; // Đơn hàng tối thiểu 50,000đ
    $points_rate = 10000; // 1 điểm cho mỗi 10,000đ
    $max_points_per_order = 100; // Tối đa 100 điểm/đơn hàng
    
    if ($order_total < $min_order_value) {
        return 0;
    }
    
    $points = floor($order_total / $points_rate);
    return min($points, $max_points_per_order);
}

/**
 * Thêm điểm tích lũy cho người dùng
 */
function add_loyalty_points($user_id, $points, $order_id, $description, $conn) {
    try {
        $conn->autocommit(FALSE);
        
        // Lấy điểm hiện tại
        $current_sql = "SELECT diem_tich_luy FROM nguoi_dung WHERE ma_nguoi_dung = ?";
        $current_stmt = $conn->prepare($current_sql);
        $current_stmt->bind_param("i", $user_id);
        $current_stmt->execute();
        $current_points = $current_stmt->get_result()->fetch_assoc()['diem_tich_luy'] ?? 0;
        
        $new_points = $current_points + $points;
        
        // Cập nhật điểm cho user
        $update_sql = "UPDATE nguoi_dung SET diem_tich_luy = ? WHERE ma_nguoi_dung = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_points, $user_id);
        $update_stmt->execute();
        
        // Lưu lịch sử
        $history_sql = "INSERT INTO lich_su_diem_tich_luy (ma_nguoi_dung, loai_giao_dich, so_diem, diem_truoc_giao_dich, diem_sau_giao_dich, ma_don_hang, mo_ta) VALUES (?, 'tich_diem', ?, ?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("iiiiss", $user_id, $points, $current_points, $new_points, $order_id, $description);
        $history_stmt->execute();
        
        $conn->commit();
        $conn->autocommit(TRUE);
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(TRUE);
        error_log("Lỗi tích điểm: " . $e->getMessage());
        return false;
    }
}

/**
 * Lấy điểm tích lũy của user
 */
function get_user_loyalty_points($user_id, $conn) {
    $sql = "SELECT diem_tich_luy FROM nguoi_dung WHERE ma_nguoi_dung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc()['diem_tich_luy'] : 0;
}

/**
 * Lấy lịch sử tích điểm
 */
function get_loyalty_history($user_id, $conn, $limit = 10) {
    $sql = "SELECT * FROM lich_su_diem_tich_luy WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    return $history;
}

/**
 * Sử dụng điểm để giảm giá
 */
function use_loyalty_points($user_id, $points_to_use, $order_id, $description, $conn) {
    try {
        $conn->autocommit(FALSE);
        
        // Lấy điểm hiện tại
        $current_sql = "SELECT diem_tich_luy FROM nguoi_dung WHERE ma_nguoi_dung = ?";
        $current_stmt = $conn->prepare($current_sql);
        $current_stmt->bind_param("i", $user_id);
        $current_stmt->execute();
        $current_points = $current_stmt->get_result()->fetch_assoc()['diem_tich_luy'] ?? 0;
        
        // Kiểm tra đủ điểm không
        if ($current_points < $points_to_use) {
            throw new Exception("Không đủ điểm tích lũy");
        }
        
        $new_points = $current_points - $points_to_use;
        
        // Cập nhật điểm cho user
        $update_sql = "UPDATE nguoi_dung SET diem_tich_luy = ? WHERE ma_nguoi_dung = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_points, $user_id);
        $update_stmt->execute();
        
        // Lưu lịch sử sử dụng điểm
        $history_sql = "INSERT INTO lich_su_diem_tich_luy (ma_nguoi_dung, loai_giao_dich, so_diem, diem_truoc_giao_dich, diem_sau_giao_dich, ma_don_hang, mo_ta) VALUES (?, 'su_dung_diem', ?, ?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_sql);
        $negative_points = -$points_to_use;
        $history_stmt->bind_param("iiiiss", $user_id, $negative_points, $current_points, $new_points, $order_id, $description);
        $history_stmt->execute();
        
        $conn->commit();
        $conn->autocommit(TRUE);
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(TRUE);
        error_log("Lỗi sử dụng điểm: " . $e->getMessage());
        return false;
    }
}

/**
 * Tính số tiền giảm giá từ điểm
 * 1000 điểm = 1,000đ
 */
function points_to_discount($points) {
    $exchange_rate = 1; // 1 điểm = 1đ
    return $points * $exchange_rate;
}

/**
 * Tính số điểm cần để được giảm X đồng
 */
function discount_to_points($discount_amount) {
    $exchange_rate = 1; // 1đ = 1 điểm
    return $discount_amount * $exchange_rate;
}

/**
 * Kiểm tra điểm có đủ để sử dụng không
 */
function can_use_points($user_id, $points_needed, $conn) {
    $current_points = get_user_loyalty_points($user_id, $conn);
    return $current_points >= $points_needed;
}

/**
 * Format hiển thị điểm
 */
function format_loyalty_points($points) {
    return number_format($points, 0, ',', '.') . ' điểm';
}
?> 