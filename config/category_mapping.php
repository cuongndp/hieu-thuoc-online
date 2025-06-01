<?php
// config/category_mapping.php - Mapping URL slug với database ID

// Mapping category slug với ma_danh_muc trong database
$category_mapping = [
    'thuoc-khong-ke-don'  => 1,
    'thuoc-ke-don'        => 2,
    'vitamin-khoang-chat' => 3,
    'thuc-pham-chuc-nang' => 4,
    'duoc-my-pham'        => 5,
    'thiet-bi-y-te'       => 6,
    'me-va-be'            => 7
];

// Function để lấy category ID từ slug
function get_category_id($slug) {
    global $category_mapping;
    return isset($category_mapping[$slug]) ? $category_mapping[$slug] : 1;
}

// Function để lấy category slug từ ID (reverse mapping)
function get_category_slug($id) {
    global $category_mapping;
    return array_search($id, $category_mapping) ?: 'thuoc-khong-ke-don';
}

// Danh sách tất cả categories
$all_categories = [
    1 => 'Thuốc không kê đơn',
    2 => 'Thuốc kê đơn',
    3 => 'Vitamin & Khoáng chất',
    4 => 'Thực phẩm chức năng',
    5 => 'Dược mỹ phẩm',
    6 => 'Thiết bị y tế',
    7 => 'Mẹ & bé'
];
?>