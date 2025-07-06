<?php
// config/reviews.php - Hệ thống đánh giá sản phẩm VitaMeds

/**
 * Thêm đánh giá sản phẩm
 */
function add_product_review($user_id, $product_id, $order_id, $rating, $title, $content, $conn) {
    try {
        $conn->autocommit(FALSE);
        
        // Kiểm tra xem user đã đánh giá sản phẩm này trong đơn hàng này chưa
        if (has_user_reviewed_product($user_id, $product_id, $order_id, $conn)) {
            throw new Exception("Bạn đã đánh giá sản phẩm này trong đơn hàng này rồi!");
        }
        
        // Thêm đánh giá mới
        $insert_sql = "INSERT INTO danh_gia_san_pham (ma_nguoi_dung, ma_san_pham, ma_don_hang, so_sao, tieu_de, noi_dung, trang_thai) 
                      VALUES (?, ?, ?, ?, ?, ?, 'da_duyet')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiiiss", $user_id, $product_id, $order_id, $rating, $title, $content);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Lỗi khi thêm đánh giá: " . $insert_stmt->error);
        }
        
        $review_id = $conn->insert_id;
        $insert_stmt->close();
        
        // Cập nhật thống kê sản phẩm
        update_product_rating_stats($product_id, $conn);
        
        $conn->commit();
        $conn->autocommit(TRUE);
        return $review_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(TRUE);
        throw $e;
    }
}

/**
 * Cập nhật thống kê đánh giá sản phẩm
 */
function update_product_rating_stats($product_id, $conn) {
    $stats_sql = "SELECT 
                    COUNT(*) as tong_so_danh_gia,
                    AVG(so_sao) as trung_binh_sao
                  FROM danh_gia_san_pham 
                  WHERE ma_san_pham = ? AND trang_thai = 'da_duyet'";
    
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("i", $product_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();
    
    // Cập nhật bảng sản phẩm
    $update_sql = "UPDATE san_pham_thuoc SET 
                    trung_binh_sao = ?, 
                    tong_so_danh_gia = ? 
                   WHERE ma_san_pham = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("dii", $stats['trung_binh_sao'], $stats['tong_so_danh_gia'], $product_id);
    $update_stmt->execute();
    $update_stmt->close();
}

/**
 * Lấy đánh giá của sản phẩm
 */
function get_product_reviews($product_id, $conn, $limit = 10, $offset = 0) {
    $sql = "SELECT 
                dg.ma_danh_gia,
                dg.so_sao,
                dg.tieu_de,
                dg.noi_dung,
                dg.ngay_tao,
                nd.ho_ten,
                nd.ma_nguoi_dung
            FROM danh_gia_san_pham dg
            JOIN nguoi_dung nd ON dg.ma_nguoi_dung = nd.ma_nguoi_dung
            WHERE dg.ma_san_pham = ? AND dg.trang_thai = 'da_duyet'
            ORDER BY dg.ngay_tao DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $product_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    $stmt->close();
    return $reviews;
}

/**
 * Lấy thống kê đánh giá sản phẩm
 */
function get_product_rating_stats($product_id, $conn) {
    $sql = "SELECT 
                trung_binh_sao,
                tong_so_danh_gia,
                (SELECT COUNT(*) FROM danh_gia_san_pham WHERE ma_san_pham = ? AND so_sao = 5 AND trang_thai = 'da_duyet') as so_sao_5,
                (SELECT COUNT(*) FROM danh_gia_san_pham WHERE ma_san_pham = ? AND so_sao = 4 AND trang_thai = 'da_duyet') as so_sao_4,
                (SELECT COUNT(*) FROM danh_gia_san_pham WHERE ma_san_pham = ? AND so_sao = 3 AND trang_thai = 'da_duyet') as so_sao_3,
                (SELECT COUNT(*) FROM danh_gia_san_pham WHERE ma_san_pham = ? AND so_sao = 2 AND trang_thai = 'da_duyet') as so_sao_2,
                (SELECT COUNT(*) FROM danh_gia_san_pham WHERE ma_san_pham = ? AND so_sao = 1 AND trang_thai = 'da_duyet') as so_sao_1
            FROM san_pham_thuoc 
            WHERE ma_san_pham = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiii", $product_id, $product_id, $product_id, $product_id, $product_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    return $stats;
}

/**
 * Kiểm tra xem user đã mua sản phẩm chưa
 */
function has_user_purchased_product($user_id, $product_id, $conn) {
    $sql = "SELECT COUNT(*) as count 
            FROM chi_tiet_don_hang ctd
            JOIN don_hang dh ON ctd.ma_don_hang = dh.ma_don_hang
            WHERE dh.ma_nguoi_dung = ? AND ctd.ma_san_pham = ? 
            AND dh.trang_thai_don_hang IN ('da_giao', 'da_xac_nhan', 'dang_xu_ly', 'dang_giao')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Kiểm tra xem user đã đánh giá sản phẩm trong đơn hàng cụ thể chưa
 */
function has_user_reviewed_product($user_id, $product_id, $order_id, $conn) {
    $sql = "SELECT COUNT(*) as count 
            FROM danh_gia_san_pham 
            WHERE ma_nguoi_dung = ? AND ma_san_pham = ? AND ma_don_hang = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $product_id, $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] > 0;
}

/**
 * Kiểm tra xem user đã từng đánh giá sản phẩm này hay chưa (bất kể đơn hàng nào)
 */
function has_user_ever_reviewed_product($user_id, $product_id, $conn) {
    $sql = "SELECT COUNT(*) as count 
            FROM danh_gia_san_pham 
            WHERE ma_nguoi_dung = ? AND ma_san_pham = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] > 0;
}

/**
 * Format hiển thị sao
 */
function format_stars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star" style="color: #f39c12;"></i>';
        } else {
            $stars .= '<i class="far fa-star" style="color: #ddd;"></i>';
        }
    }
    return $stars;
}

/**
 * Format hiển thị sao với số điểm
 */
function format_stars_with_rating($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star" style="color: #f39c12;"></i>';
        } else {
            $stars .= '<i class="far fa-star" style="color: #ddd;"></i>';
        }
    }
    return $stars . ' <span style="color: #666; font-size: 12px;">(' . number_format($rating, 1) . ')</span>';
}

/**
 * Lấy danh sách sản phẩm đã mua chưa đánh giá
 */
function get_purchased_products_not_reviewed($user_id, $conn) {
    $sql = "SELECT DISTINCT 
                sp.ma_san_pham,
                sp.ten_san_pham,
                sp.gia_ban,
                ha.duong_dan_hinh_anh,
                dh.ma_don_hang,
                dh.so_don_hang
            FROM chi_tiet_don_hang ctd
            JOIN don_hang dh ON ctd.ma_don_hang = dh.ma_don_hang
            JOIN san_pham_thuoc sp ON ctd.ma_san_pham = sp.ma_san_pham
            LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = 1
            WHERE dh.ma_nguoi_dung = ? 
            AND dh.trang_thai_don_hang IN ('da_giao', 'da_xac_nhan', 'dang_xu_ly', 'dang_giao')
            AND NOT EXISTS (
                SELECT 1 FROM danh_gia_san_pham dg 
                WHERE dg.ma_nguoi_dung = ? AND dg.ma_san_pham = sp.ma_san_pham AND dg.ma_don_hang = dh.ma_don_hang
            )
            ORDER BY dh.ngay_tao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
    return $products;
}

// Lấy danh sách sản phẩm trong 1 đơn hàng mà user chưa đánh giá (theo từng đơn hàng)
function get_products_in_order_not_reviewed($user_id, $order_id, $conn) {
    $sql = "SELECT 
                sp.ma_san_pham,
                sp.ten_san_pham,
                sp.gia_ban,
                ha.duong_dan_hinh_anh,
                dh.ma_don_hang,
                dh.so_don_hang
            FROM chi_tiet_don_hang ctd
            JOIN don_hang dh ON ctd.ma_don_hang = dh.ma_don_hang
            JOIN san_pham_thuoc sp ON ctd.ma_san_pham = sp.ma_san_pham
            LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = 1
            WHERE dh.ma_nguoi_dung = ? 
            AND dh.ma_don_hang = ?
            AND dh.trang_thai_don_hang IN ('da_giao', 'da_xac_nhan', 'dang_xu_ly', 'dang_giao')
            AND NOT EXISTS (
                SELECT 1 FROM danh_gia_san_pham dg 
                WHERE dg.ma_nguoi_dung = ? AND dg.ma_san_pham = sp.ma_san_pham AND dg.ma_don_hang = dh.ma_don_hang
            )
            ORDER BY ctd.ma_chi_tiet ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
    return $products;
}
?> 