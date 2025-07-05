<?php
include 'config/dual_session.php';
include 'config/database.php';

// Xử lý add to cart - LƯU VÀO DATABASE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = 1;
    
    // Kiểm tra đăng nhập
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
    
    $user_id = get_user_id();
    
    // Debug
    error_log("Add to Cart - User ID: $user_id, Product ID: $product_id");
    
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
                    $_SESSION['success_message'] = "Đã cập nhật số lượng sản phẩm trong giỏ hàng!";
                    error_log("Updated quantity for product $product_id");
                } else {
                    $_SESSION['error_message'] = "Lỗi khi cập nhật sản phẩm!";
                    error_log("Failed to update product $product_id");
                }
            } else {
                // Nếu chưa có, thêm mới
                $insert_sql = "INSERT INTO gio_hang (ma_nguoi_dung, ma_san_pham, so_luong, ngay_them) VALUES (?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                
                if ($insert_stmt->execute()) {
                    $_SESSION['success_message'] = "Đã thêm sản phẩm vào giỏ hàng!";
                    error_log("Added new product $product_id to cart");
                } else {
                    $_SESSION['error_message'] = "Lỗi khi thêm sản phẩm!";
                    error_log("Failed to insert product $product_id");
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Có lỗi xảy ra: " . $e->getMessage();
            error_log("Exception in add to cart: " . $e->getMessage());
        }
    } else {
        $_SESSION['error_message'] = "Thông tin không hợp lệ!";
        error_log("Invalid data - User ID: $user_id, Product ID: $product_id");
    }
    
    // Redirect để tránh resubmit
    header('Location: index.php');
    exit;
}

// Lấy thông báo nếu có
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
if ($success_message) {
    unset($_SESSION['success_message']);
}
if ($error_message) {
    unset($_SESSION['error_message']);
}

// LẤY SẢN PHẨM NỔI BẬT TỪ CSDL
$featured_sql = "
    SELECT 
        sp.ma_san_pham,
        sp.ten_san_pham,
        sp.gia_ban,
        sp.gia_khuyen_mai,
        sp.mo_ta,
        sp.can_don_thuoc,
        sp.san_pham_noi_bat,
        nsx.ten_nha_san_xuat,
        ha.duong_dan_hinh_anh
    FROM san_pham_thuoc sp
    LEFT JOIN nha_san_xuat nsx ON sp.ma_nha_san_xuat = nsx.ma_nha_san_xuat
    LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = TRUE
    WHERE sp.san_pham_noi_bat = 1 AND sp.trang_thai_hoat_dong = 1
    ORDER BY sp.ngay_tao DESC
    LIMIT 6
";
$featured_result = $conn->query($featured_sql);
$featured_products = [];
while ($row = $featured_result->fetch_assoc()) {
    $featured_products[] = $row;
}

// LẤY QUẢNG CÁO ĐỘNG TỪ CSDL - chỉ lấy 3 quảng cáo mới nhất cho banner động
$ads_sql = "SELECT * FROM quang_cao WHERE trang_thai = 1 ORDER BY ngay_tao DESC LIMIT 3";
$ads_result = $conn->query($ads_sql);
$ads = [];
while ($row = $ads_result->fetch_assoc()) {
    $ads[] = $row;
}

// LẤY 3 QUẢNG CÁO GRID DƯỚI SLIDER (không trùng với 3 quảng cáo banner động)
$grid_ads = [];
if (!empty($ads)) {
    // Lấy tiếp 3 quảng cáo khác (bắt đầu từ quảng cáo thứ 4 trở đi)
    $grid_sql = "SELECT * FROM quang_cao WHERE trang_thai = 1 ORDER BY ngay_tao DESC LIMIT 3 OFFSET 3";
    $grid_result = $conn->query($grid_sql);
    while ($row = $grid_result->fetch_assoc()) {
        $grid_ads[] = $row;
    }
}

// LẤY MINI ADS TỪ CSDL (bỏ qua ads đầu tiên)
$mini_ads = array_slice($ads, 1, 3);

// LẤY QUẢNG CÁO NỔI BẬT DƯỚI SLIDER (ads thứ 2 nếu có)
$below_slider_ad = isset($ads[1]) ? $ads[1] : null;

// LẤY THỜI GIAN GIẢM GIÁ SỐC TỪ BẢNG GIAM_GIA_SOC
$flash_time = $conn->query("SELECT * FROM giam_gia_soc WHERE thoi_gian_bat_dau <= NOW() AND thoi_gian_ket_thuc >= NOW() ORDER BY id DESC LIMIT 1")->fetch_assoc();
$in_flash_sale = false;
$phan_tram_giam_flash = 0;
if ($flash_time) {
    $in_flash_sale = true;
    $phan_tram_giam_flash = (int)$flash_time['phan_tram_giam'];
}

// LẤY SẢN PHẨM GIẢM GIÁ SỐC (chỉ khi đang trong thời gian giảm giá sốc)
$flash_sale_products = [];
if ($in_flash_sale) {
    $sql_flash_sale = "
        SELECT sp.*, ha.duong_dan_hinh_anh
        FROM san_pham_thuoc sp
        LEFT JOIN hinh_anh_san_pham ha ON sp.ma_san_pham = ha.ma_san_pham AND ha.la_hinh_chinh = 1
        WHERE sp.is_flash_sale = 1
          AND sp.trang_thai_hoat_dong = 1
        ORDER BY sp.ngay_tao DESC
        LIMIT 6
    ";
    $result_flash_sale = $conn->query($sql_flash_sale);
    while ($row = $result_flash_sale->fetch_assoc()) {
        $flash_sale_products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VitaMeds - Hiệu Thuốc Trực Tuyến Uy Tín</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/header.css">
    
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section (Banner động) -->
    <section class="hero-section">
        <div class="container">
            <?php if (!empty($ads)): ?>
            <div class="ads-slider" id="adsSlider">
                <?php foreach ($ads as $i => $ad): ?>
                <div class="ads-slide<?php if ($i === 0) echo ' active'; ?>">
                    <div class="ads-img">
                        <img src="<?php echo htmlspecialchars($ad['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($ad['tieu_de']); ?>">
                    </div>
                    <div class="ads-content">
                        <h2><?php echo htmlspecialchars($ad['tieu_de']); ?></h2>
                        <p><?php echo htmlspecialchars($ad['mo_ta']); ?></p>
                        <?php if (!empty($ad['link'])): ?>
                        <a href="<?php echo htmlspecialchars($ad['link']); ?>" class="ads-btn" target="_blank">Xem chi tiết</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <button class="ads-prev" onclick="plusAdsSlide(-1)">&#10094;</button>
                <button class="ads-next" onclick="plusAdsSlide(1)">&#10095;</button>
            </div>
            <script>
            let adsSlideIndex = 0;
            showAdsSlide(adsSlideIndex);
            function plusAdsSlide(n) { showAdsSlide(adsSlideIndex += n); }
            function showAdsSlide(n) {
                let slides = document.querySelectorAll('#adsSlider .ads-slide');
                if (n >= slides.length) adsSlideIndex = 0;
                if (n < 0) adsSlideIndex = slides.length - 1;
                slides.forEach((slide, i) => {
                    slide.style.display = (i === adsSlideIndex) ? 'block' : 'none';
                });
            }
            // Auto slide
            setInterval(() => { plusAdsSlide(1); }, 5000);
            </script>
            <style>
            .ads-slider { position: relative; overflow: hidden; border-radius: 18px; margin-bottom: 40px; }
            .ads-slide { display: none; position: relative; background: #fff; }
            .ads-slide.active, .ads-slide:first-child { display: block; }
            .ads-img img { width: 100%; height: 320px; object-fit: cover; border-radius: 18px 18px 0 0; }
            .ads-content { position: absolute; left: 40px; top: 40px; background: rgba(0,0,0,0.45); color: #fff; padding: 30px 40px; border-radius: 12px; max-width: 50%; }
            .ads-content h2 { font-size: 2.2rem; margin-bottom: 10px; }
            .ads-content p { font-size: 1.1rem; margin-bottom: 18px; }
            .ads-btn { background: #ff6b6b; color: #fff; padding: 12px 28px; border-radius: 25px; text-decoration: none; font-weight: bold; transition: background 0.2s; }
            .ads-btn:hover { background: #e74c3c; }
            .ads-prev, .ads-next { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.3); color: #fff; border: none; font-size: 2rem; padding: 8px 16px; border-radius: 50%; cursor: pointer; z-index: 2; }
            .ads-prev { left: 10px; }
            .ads-next { right: 10px; }
            @media (max-width: 768px) {
                .ads-content { position: static; max-width: 100%; padding: 15px; border-radius: 0 0 12px 12px; }
                .ads-img img { height: 180px; }
            }
            </style>
            <?php else: ?>
            <!-- Nếu không có quảng cáo thì giữ banner cũ -->
            <div class="banner-slider">
                <div class="main-banner">
                    <div class="banner-content">
                        <h2>Khỏe Mạnh Mỗi Ngày</h2>
                        <p>Hàng nghìn sản phẩm chính hãng với giá tốt nhất. Giao hàng nhanh chóng toàn quốc.</p>
                        <?php if (!is_user_logged_in()): ?>
                            <!-- <a href="login.php" class="cta-button">Đăng nhập ngay</a> -->
                        <?php endif; ?>
                    </div>
                </div>
                <div class="side-banner">
                    <h3>Giảm giá lớn</h3>
                    <div class="discount">30%</div>
                    <p>Cho đơn hàng đầu tiên</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Trust Badges -->
            <div class="trust-badges">
                <div class="trust-item">
                    <i class="fas fa-certificate"></i>
                    <h4>Chính hãng 100%</h4>
                    <p>Cam kết thuốc chính hãng, có nguồn gốc xuất xứ rõ ràng</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-shipping-fast"></i>
                    <h4>Giao hàng nhanh</h4>
                    <p>Giao hàng trong 2-4 giờ tại TP.HCM và 1-2 ngày toàn quốc</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-user-md"></i>
                    <h4>Tư vấn dược sĩ</h4>
                    <p>Đội ngũ dược sĩ tư vấn 24/7, hỗ trợ khách hàng mọi lúc</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Thanh toán an toàn</h4>
                    <p>Bảo mật thông tin thanh toán với công nghệ mã hóa SSL</p>
                </div>
            </div>
        </div>
    </section>

<!-- Quảng cáo grid nổi bật dưới slider -->
<?php if (!empty($grid_ads)): ?>
<section class="below-slider-ads-grid-section">
    <div class="container">
        <div class="below-slider-ads-grid">
            <?php if (count($grid_ads) === 3): ?>
                <div class="below-slider-ads-main ads-fade-in">
                    <div class="below-slider-ads-img-wrap">
                        <img src="<?php echo htmlspecialchars($grid_ads[0]['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($grid_ads[0]['tieu_de']); ?>">
                    </div>
                    <div class="below-slider-ads-content">
                        <h2><?php echo htmlspecialchars($grid_ads[0]['tieu_de']); ?></h2>
                        <p><?php echo htmlspecialchars($grid_ads[0]['mo_ta']); ?></p>
                    </div>
                </div>
                <div class="below-slider-ads-side">
                    <div class="below-slider-ads-side-item ads-fade-in">
                        <div class="below-slider-ads-img-wrap">
                            <img src="<?php echo htmlspecialchars($grid_ads[1]['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($grid_ads[1]['tieu_de']); ?>">
                        </div>
                        <div class="below-slider-ads-side-content">
                            <h3><?php echo htmlspecialchars($grid_ads[1]['tieu_de']); ?></h3>
                            <p><?php echo htmlspecialchars($grid_ads[1]['mo_ta']); ?></p>
                        </div>
                    </div>
                    <div class="below-slider-ads-side-item ads-fade-in">
                        <div class="below-slider-ads-img-wrap">
                            <img src="<?php echo htmlspecialchars($grid_ads[2]['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($grid_ads[2]['tieu_de']); ?>">
                        </div>
                        <div class="below-slider-ads-side-content">
                            <h3><?php echo htmlspecialchars($grid_ads[2]['tieu_de']); ?></h3>
                            <p><?php echo htmlspecialchars($grid_ads[2]['mo_ta']); ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif (count($grid_ads) === 2): ?>
                <div class="below-slider-ads-main ads-fade-in">
                    <div class="below-slider-ads-img-wrap">
                        <img src="<?php echo htmlspecialchars($grid_ads[0]['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($grid_ads[0]['tieu_de']); ?>">
                    </div>
                    <div class="below-slider-ads-content">
                        <h2><?php echo htmlspecialchars($grid_ads[0]['tieu_de']); ?></h2>
                        <p><?php echo htmlspecialchars($grid_ads[0]['mo_ta']); ?></p>
                    </div>
                </div>
                <div class="below-slider-ads-side">
                    <div class="below-slider-ads-side-item ads-fade-in">
                        <div class="below-slider-ads-img-wrap">
                            <img src="<?php echo htmlspecialchars($grid_ads[1]['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($grid_ads[1]['tieu_de']); ?>">
                        </div>
                        <div class="below-slider-ads-side-content">
                            <h3><?php echo htmlspecialchars($grid_ads[1]['tieu_de']); ?></h3>
                            <p><?php echo htmlspecialchars($grid_ads[1]['mo_ta']); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="below-slider-ads-main ads-fade-in" style="width:100%">
                    <div class="below-slider-ads-img-wrap">
                        <img src="<?php echo htmlspecialchars($grid_ads[0]['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($grid_ads[0]['tieu_de']); ?>">
                    </div>
                    <div class="below-slider-ads-content">
                        <h2><?php echo htmlspecialchars($grid_ads[0]['tieu_de']); ?></h2>
                        <p><?php echo htmlspecialchars($grid_ads[0]['mo_ta']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<style>
.below-slider-ads-grid-section { margin: 30px 0 10px 0; }
.below-slider-ads-grid { display: flex; gap: 24px; }
.below-slider-ads-main { flex: 2; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(33,150,243,0.10); overflow: hidden; position: relative; display: flex; flex-direction: column; transition: box-shadow 0.3s, transform 0.3s; }
.below-slider-ads-main:hover { box-shadow: 0 8px 32px rgba(33,150,243,0.18); transform: translateY(-4px) scale(1.03); z-index: 2; }
.below-slider-ads-img-wrap { overflow: hidden; border-radius: 18px 18px 0 0; }
.below-slider-ads-main img { width: 100%; height: 220px; object-fit: cover; border-radius: 18px 18px 0 0; transition: transform 0.4s cubic-bezier(.4,2,.6,1); }
.below-slider-ads-main:hover img { transform: scale(1.08) rotate(-1deg); }
.below-slider-ads-content { padding: 22px 28px; }
.below-slider-ads-content h2 { font-size: 1.5rem; color: #1976d2; margin-bottom: 8px; font-weight: bold; }
.below-slider-ads-content p { font-size: 1.05rem; color: #333; margin-bottom: 12px; }
.below-slider-ads-btn { background: #ff6b6b; color: #fff; padding: 8px 22px; border-radius: 22px; text-decoration: none; font-weight: 500; font-size: 1rem; transition: background 0.2s, box-shadow 0.2s; box-shadow: 0 2px 8px rgba(255,107,107,0.10); }
.below-slider-ads-btn:hover { background: #e74c3c; box-shadow: 0 4px 16px rgba(255,107,107,0.18); }
.below-slider-ads-side { flex: 1; display: flex; flex-direction: column; gap: 18px; }
.below-slider-ads-side-item { background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(33,150,243,0.10); overflow: hidden; display: flex; flex-direction: column; transition: box-shadow 0.3s, transform 0.3s; }
.below-slider-ads-side-item:hover { box-shadow: 0 8px 32px rgba(33,150,243,0.18); transform: translateY(-2px) scale(1.04); z-index: 2; }
.below-slider-ads-side-item .below-slider-ads-img-wrap { border-radius: 18px 18px 0 0; }
.below-slider-ads-side-item img { width: 100%; height: 100px; object-fit: cover; border-radius: 18px 18px 0 0; transition: transform 0.4s cubic-bezier(.4,2,.6,1); }
.below-slider-ads-side-item:hover img { transform: scale(1.08) rotate(1deg); }
.below-slider-ads-side-content { padding: 14px 18px; }
.below-slider-ads-side-content h3 { font-size: 1.08rem; color: #1976d2; margin-bottom: 6px; font-weight: bold; }
.below-slider-ads-side-content p { font-size: 0.98rem; color: #333; margin-bottom: 8px; }
/* Fade-in animation */
.ads-fade-in { opacity: 0; animation: adsFadeIn 1.1s ease forwards; }
.ads-fade-in:nth-child(1) { animation-delay: 0.1s; }
.ads-fade-in:nth-child(2) { animation-delay: 0.3s; }
.ads-fade-in:nth-child(3) { animation-delay: 0.5s; }
@keyframes adsFadeIn { from { opacity: 0; transform: translateY(30px) scale(0.98); } to { opacity: 1; transform: none; } }
@media (max-width: 1100px) { .below-slider-ads-grid { flex-direction: column; } .below-slider-ads-main, .below-slider-ads-side { width: 100%; } }
@media (max-width: 700px) { .below-slider-ads-main img { height: 140px; } .below-slider-ads-side-item img { height: 70px; } .below-slider-ads-content, .below-slider-ads-side-content { padding: 10px 6px; } }
</style>
<?php endif; ?>


    <?php if ($in_flash_sale && !empty($flash_sale_products)): ?>
    <section class="flash-sale-section">
        <div class="container">
            <div class="flash-sale-title">
                <span class="flash-icon">⚡</span> SẢN PHẨM GIẢM GIÁ SỐC
                <span class="flash-sale-badge-global">-<?php echo $phan_tram_giam_flash; ?>%</span>
            </div>
            <div class="flash-sale-products-grid">
                <?php foreach ($flash_sale_products as $product): 
                    $gia_goc = $product['gia_ban'];
                    $gia_sau_giam = $gia_goc * (1 - $phan_tram_giam_flash / 100);
                    $img_src = !empty($product['duong_dan_hinh_anh']) ? htmlspecialchars($product['duong_dan_hinh_anh']) : 'https://via.placeholder.com/160x160/fffbe7/cccccc?text=No+Image';
                ?>
                <div class="flash-sale-product-card">
                    <div class="flash-sale-product-img">
                        <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                    </div>
                    <div class="flash-sale-product-info">
                        <h3><?php echo htmlspecialchars($product['ten_san_pham']); ?></h3>
                        <div class="flash-sale-product-price">
                            <span class="flash-sale-current-price"><?php echo number_format($gia_sau_giam, 0, ',', '.'); ?>đ</span>
                            <span class="flash-sale-old-price"><?php echo number_format($gia_goc, 0, ',', '.'); ?>đ</span>
                        </div>
                        <div class="flash-sale-actions">
                            <?php if (is_user_logged_in()): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="add_to_cart" value="1">
                                    <input type="hidden" name="product_id" value="<?php echo $product['ma_san_pham']; ?>">
                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                                    <input type="hidden" name="product_price" value="<?php echo $gia_sau_giam; ?>">
                                    <button type="submit" class="flash-sale-add-to-cart">
                                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="flash-sale-add-to-cart" style="text-decoration: none;">
                                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                </a>
                            <?php endif; ?>
                            <a href="chi-tiet-san-pham.php?id=<?php echo $product['ma_san_pham']; ?>" class="flash-sale-view-detail" title="Xem chi tiết sản phẩm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <style>
    .flash-sale-section {
        margin: 40px 0 32px 0;
        background: linear-gradient(90deg, #fffbe7 0%, #ffe0b2 100%);
        border-radius: 18px;
        box-shadow: 0 4px 32px rgba(255,152,0,0.18);
        padding: 32px 0;
        border: 3px solid #ff9800;
        position: relative;
    }
    .flash-sale-title {
        font-size: 2.2rem;
        font-weight: bold;
        color: #e53935;
        text-align: center;
        margin-bottom: 24px;
        letter-spacing: 1px;
        position: relative;
        text-shadow: 0 2px 8px #fffbe7, 0 1px 0 #fff;
    }
    .flash-icon {
        font-size: 2.4rem;
        vertical-align: middle;
        margin-right: 8px;
        color: #ff9800;
        filter: drop-shadow(0 2px 6px #fffbe7);
    }
    .flash-sale-badge-global {
        display: inline-block;
        background: #e53935;
        color: #fff;
        font-weight: bold;
        border-radius: 10px;
        padding: 8px 22px;
        font-size: 1.3rem;
        margin-left: 18px;
        box-shadow: 0 2px 12px rgba(229,57,53,0.18);
        letter-spacing: 1px;
        vertical-align: middle;
        border: 2px solid #fffbe7;
    }
    .flash-sale-products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 32px;
        justify-content: center;
    }
    .flash-sale-product-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(255,152,0,0.18);
        padding: 22px 18px 18px 18px;
        position: relative;
        border: 2.5px solid #ff9800;
        display: flex;
        flex-direction: column;
        align-items: center;
        transition: box-shadow 0.2s, transform 0.2s, border 0.2s;
        min-height: 370px;
        overflow: hidden;
    }
    .flash-sale-product-card:hover {
        box-shadow: 0 12px 36px rgba(255,152,0,0.28);
        transform: translateY(-6px) scale(1.04);
        z-index: 2;
        border: 2.5px solid #e53935;
    }
    .flash-sale-product-img {
        width: 160px;
        height: 160px;
        background: #fffbe7;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
    }
    .flash-sale-product-img img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 12px;
        background: #fffbe7;
    }
    .flash-sale-product-info {
        text-align: center;
        margin-top: 6px;
        width: 100%;
    }
    .flash-sale-product-info h3 {
        font-size: 1.18rem;
        color: #222;
        font-weight: bold;
        margin-bottom: 8px;
        min-height: 48px;
    }
    .flash-sale-product-price {
        margin: 8px 0 18px 0;
    }
    .flash-sale-current-price {
        color: #e53935;
        font-weight: bold;
        font-size: 1.35rem;
        letter-spacing: 1px;
    }
    .flash-sale-old-price {
        color: #888;
        text-decoration: line-through;
        margin-left: 10px;
        font-size: 1.08rem;
    }
    .flash-sale-actions {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        margin-top: 8px;
    }
    .flash-sale-add-to-cart {
        background: linear-gradient(90deg, #ff9800 0%, #e53935 100%);
        color: #fff;
        border: none;
        border-radius: 24px;
        padding: 10px 28px;
        font-size: 1.08rem;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 2px 12px rgba(255,152,0,0.13);
        transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .flash-sale-add-to-cart:hover {
        background: linear-gradient(90deg, #e53935 0%, #ff9800 100%);
        box-shadow: 0 4px 18px rgba(255,152,0,0.22);
        transform: scale(1.04);
    }
    .flash-sale-view-detail {
        background: #fffbe7;
        color: #e53935;
        border-radius: 50%;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 2px 8px rgba(255,152,0,0.10);
        transition: background 0.2s, color 0.2s, transform 0.2s;
        margin-left: 0;
        text-decoration: none;
    }
    .flash-sale-view-detail:hover {
        background: #e53935;
        color: #fff;
        transform: scale(1.12);
    }
    @media (max-width: 900px) {
        .flash-sale-products-grid { grid-template-columns: 1fr; gap: 18px; }
        .flash-sale-product-card { min-height: 0; }
    }
    </style>
    <?php endif; ?>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <div class="section-title">
                <h2>Sản Phẩm Nổi Bật</h2>
                <p>Những sản phẩm được khách hàng tin tưởng và lựa chọn nhiều nhất</p>
            </div>
            
            <div class="products-grid">
                <?php if (empty($featured_products)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px;">
                        <h3>Không có sản phẩm nổi bật</h3>
                        <p>Hiện tại chưa có sản phẩm nổi bật nào.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_products as $product): ?>
                        <div class="product-card" data-product-id="<?php echo $product['ma_san_pham']; ?>">
                            <?php 
                            // Badge
                            if ($product['can_don_thuoc']) {
                                echo '<div class="product-badge prescription">Kê đơn</div>';
                            } elseif ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']) {
                                $discount = round((($product['gia_ban'] - $product['gia_khuyen_mai']) / $product['gia_ban']) * 100);
                                echo '<div class="product-badge">Giảm ' . $discount . '%</div>';
                            } elseif ($product['san_pham_noi_bat']) {
                                echo '<div class="product-badge">Nổi bật</div>';
                            }
                            ?>
                            <div class="product-image">
                                <img src="<?php echo $product['duong_dan_hinh_anh'] ?: 'https://via.placeholder.com/150x150?text=' . urlencode($product['ten_san_pham']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                            </div>
                            <h3><?php echo htmlspecialchars($product['ten_san_pham']); ?></h3>
                            <div class="product-manufacturer"><?php echo htmlspecialchars($product['ten_nha_san_xuat'] ?: 'Không rõ'); ?></div>
                            <div class="product-price">
                                <span class="current-price">
                                    <?php echo number_format($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban'] ? $product['gia_khuyen_mai'] : $product['gia_ban'], 0, ',', '.'); ?>đ
                                </span>
                                <?php if ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']): ?>
                                    <span class="old-price"><?php echo number_format($product['gia_ban'], 0, ',', '.'); ?>đ</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <?php if (is_user_logged_in()): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $product['ma_san_pham']; ?>">
                                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
                                        <input type="hidden" name="product_price" value="<?php echo $product['gia_khuyen_mai'] ?: $product['gia_ban']; ?>">
                                        <button type="submit" class="add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php" class="add-to-cart" style="text-decoration: none;">
                                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                    </a>
                                <?php endif; ?>
                                <a href="chi-tiet-san-pham.php?id=<?php echo $product['ma_san_pham']; ?>" class="quick-view" title="Xem chi tiết sản phẩm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    

    <!-- Categories -->
    <section class="categories-section">
        <div class="container">
            <div class="section-title">
                <h2>Danh Mục Sản Phẩm</h2>
                <p>Tìm kiếm sản phẩm theo từng danh mục chuyên biệt</p>
            </div>
            
            <div class="categories-grid">
                <a href="danh-muc.php?cat=thuoc-khong-ke-don" class="category-card">
                    <i class="fas fa-heart"></i>
                    <h3>Tim Mạch</h3>
                    <p>120+ sản phẩm</p>
                </a>
                
                <a href="danh-muc.php?cat=vitamin-khoang-chat" class="category-card">
                    <i class="fas fa-pills"></i>
                    <h3>Vitamin & Khoáng chất</h3>
                    <p>85+ sản phẩm</p>
                </a>
                
                <a href="danh-muc.php?cat=thuc-pham-chuc-nang" class="category-card">
                    <i class="fas fa-leaf"></i>
                    <h3>Thực phẩm chức năng</h3>
                    <p>95+ sản phẩm</p>
                </a>
                
                <a href="danh-muc.php?cat=duoc-my-pham" class="category-card">
                    <i class="fas fa-spa"></i>
                    <h3>Dược mỹ phẩm</h3>
                    <p>110+ sản phẩm</p>
                </a>
                
                <a href="danh-muc.php?cat=thiet-bi-y-te" class="category-card">
                    <i class="fas fa-stethoscope"></i>
                    <h3>Thiết bị y tế</h3>
                    <p>75+ sản phẩm</p>
                </a>
                
                <a href="danh-muc.php?cat=me-va-be" class="category-card">
                    <i class="fas fa-baby"></i>
                    <h3>Mẹ & bé</h3>
                    <p>65+ sản phẩm</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Welcome message for logged in users -->
    <?php if (is_user_logged_in()): ?>
    <section class="welcome-section" style="background: #f8f9fa; padding: 40px 0;">
        <div class="container">
            <div style="text-align: center; max-width: 600px; margin: 0 auto;">
                <h3 style="color: #2c3e50; margin-bottom: 15px;">
                    <i class="fas fa-user-check" style="color: #27ae60; margin-right: 10px;"></i>
                    Xin chào <?php echo htmlspecialchars(get_user_name()); ?>!
                </h3>
                <p style="color: #7f8c8d; font-size: 16px;">
                    Cảm ơn bạn đã tin tưởng VitaMeds. Hãy khám phá các sản phẩm chăm sóc sức khỏe chất lượng của chúng tôi.
                </p>
                <div style="margin-top: 20px;">
                    <a href="danh-muc.php" style="background: #3498db; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-right: 10px;">
                        <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                    </a>
                    <a href="profile.php" style="background: #95a5a6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
                        <i class="fas fa-user-cog"></i> Quản lý tài khoản
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <!-- Team Members Section -->
            <div class="team-section">
                <div class="section-title" style="margin: 0 0 40px 0;">
                    <h2 style="color: #ecf0f1;">Thành Viên Nhóm</h2>
                    <p style="color: #bdc3c7;">Đội ngũ phát triển website VitaMeds</p>
                </div>
                
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-avatar">
                            <img src="images/anhthe.jpg" alt="Lê Hải Bằng" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        </div>
                        <div class="member-name">Lê Hải Bằng</div>
                        <!-- <div class="member-role">Team Leader - Backend Developer</div> -->
                        <div class="member-id">MSSV: 054205001811</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">
                            <img src="images/download1.png" alt="Nguyễn Văn Phong" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        </div>
                        <div class="member-name">Nguyễn Văn Phong</div>
                        <!-- <div class="member-role">Frontend Developer - UI/UX</div> -->
                        <div class="member-id">MSSV: 052205013518</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">
                            <img src="images/download.png" alt="Nguyễn Đăng Phúc Cường" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        </div>
                        <div class="member-name">Nguyễn Đăng Phúc Cường</div>
                        <!-- <div class="member-role">Database Administrator</div> -->
                        <div class="member-id">MSSV: 054205000736</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">
                            <img src="images/hinh2.jpg" alt="Lý Khánh Đăng" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        </div>
                        <div class="member-name">Lý Khánh Đăng</div>
                        <!-- <div class="member-role">Quality Assurance - Tester</div> -->
                        <div class="member-id">MSSV: 083205014004</div>
                    </div>
                </div>
            </div>

            <div class="footer-content">
                <div class="footer-section">
                    <h3>VitaMeds</h3>
                    <p>Đồ án môn học: Lập trình Web<br>
                    Trường: Đại học Giao thông vận tải</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fas fa-envelope"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Thông Tin Dự Án</h3>
                    <ul>
                        <li><a href="#">Mô tả dự án</a></li>
                        <li><a href="#">Tài liệu kỹ thuật</a></li>
                        <li><a href="#">Database Schema</a></li>
                        <li><a href="#">API Documentation</a></li>
                        <li><a href="#">Source Code</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Công Nghệ Sử Dụng</h3>
                    <ul>
                        <li>Frontend: HTML5, CSS3, JavaScript</li>
                        <li>Backend: PHP, MySQL</li>
                        <li>Framework: Bootstrap</li>
                        <li>Tools: VSCode, phpMyAdmin</li>
                        <li>Version Control: Git, GitHub</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Liên Hệ Nhóm</h3>
                    <p><i class="fas fa-envelope"></i> vitameds.team@student.uit.edu.vn</p>
                    <p><i class="fas fa-phone"></i> (+84) 123-456-789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Đại học Giao thông vận tải</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>