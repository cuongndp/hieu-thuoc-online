<?php
session_start();
include 'config/database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VitaMeds - Hiệu Thuốc Trực Tuyến Uy Tín</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="banner-slider">
                <div class="main-banner">
                    <div class="banner-content">
                        <h2>Khỏe Mạnh Mỗi Ngày</h2>
                        <p>Hàng nghìn sản phẩm chính hãng với giá tốt nhất. Giao hàng nhanh chóng toàn quốc.</p>
                        <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
                            <a href="Login.php" class="cta-button">Đăng nhập ngay</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="side-banner">
                    <h3>Giảm giá lớn</h3>
                    <div class="discount">30%</div>
                    <p>Cho đơn hàng đầu tiên</p>
                </div>
            </div>

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

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <div class="section-title">
                <h2>Sản Phẩm Nổi Bật</h2>
                <p>Những sản phẩm được khách hàng tin tưởng và lựa chọn nhiều nhất</p>
            </div>
            
            <div class="products-grid">
                <div class="product-card">
                    <div class="product-badge">-15%</div>
                    <div class="product-image">
                        <img src="./images/OIP.jpg" alt="Paracetamol 500mg">
                    </div>
                    <h3>Paracetamol 500mg</h3>
                    <div class="product-price">
                        <span class="current-price">25.000đ</span>
                        <span class="old-price">30.000đ</span>
                    </div>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="product_id" value="1">
                            <input type="hidden" name="product_name" value="Paracetamol 500mg">
                            <input type="hidden" name="product_price" value="25000">
                            <button type="submit" class="add-to-cart">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="Login.php" class="add-to-cart">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </a>
                    <?php endif; ?>
                </div>

                <div class="product-card">
                    <div class="product-badge">Hot</div>
                    <div class="product-image">
                        <img src="./images/download.jpg" alt="Vitamin C 1000mg">
                    </div>
                    <h3>Vitamin C 1000mg</h3>
                    <div class="product-price">
                        <span class="current-price">120.000đ</span>
                    </div>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="product_id" value="2">
                            <input type="hidden" name="product_name" value="Vitamin C 1000mg">
                            <input type="hidden" name="product_price" value="120000">
                            <button type="submit" class="add-to-cart">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="Login.php" class="add-to-cart">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </a>
                    <?php endif; ?>
                </div>

                <div class="product-card">
                    <div class="product-badge">-20%</div>
                    <div class="product-image">
                        <img src="./images/OIP (1).jpg" alt="Amoxicillin 250mg">
                    </div>
                    <h3>Amoxicillin 250mg</h3>
                    <div class="product-price">
                        <span class="current-price">45.000đ</span>
                        <span class="old-price">56.000đ</span>
                    </div>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="product_id" value="3">
                            <input type="hidden" name="product_name" value="Amoxicillin 250mg">
                            <input type="hidden" name="product_price" value="45000">
                            <button type="submit" class="add-to-cart">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="Login.php" class="add-to-cart">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </a>
                    <?php endif; ?>
                </div>

                <div class="product-card">
                    <div class="product-image">
                        <img src="./images/OIP (2).jpg" alt="Omega-3 Fish Oil">
                    </div>
                    <h3>Omega-3 Fish Oil</h3>
                    <div class="product-price">
                        <span class="current-price">180.000đ</span>
                    </div>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="product_id" value="4">
                            <input type="hidden" name="product_name" value="Omega-3 Fish Oil">
                            <input type="hidden" name="product_price" value="180000">
                            <button type="submit" class="add-to-cart">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="Login.php" class="add-to-cart">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </a>
                    <?php endif; ?>
                </div>

                <div class="product-card">
                    <div class="product-badge">Mới</div>
                    <div class="product-image">
                        <img src="./images/OIP (3).jpg" alt="Calcium + D3">
                    </div>
                    <h3>Calcium + D3</h3>
                    <div class="product-price">
                        <span class="current-price">95.000đ</span>
                    </div>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="product_id" value="5">
                            <input type="hidden" name="product_name" value="Calcium + D3">
                            <input type="hidden" name="product_price" value="95000">
                            <button type="submit" class="add-to-cart">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="Login.php" class="add-to-cart">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </a>
                    <?php endif; ?>
                </div>

                <div class="product-card">
                    <div class="product-badge">-10%</div>
                    <div class="product-image">
                        <img src="./images/OIP (4).jpg" alt="Glucosamine 1500mg">
                    </div>
                    <h3>Glucosamine 1500mg</h3>
                    <div class="product-price">
                        <span class="current-price">320.000đ</span>
                        <span class="old-price">355.000đ</span>
                    </div>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="product_id" value="6">
                            <input type="hidden" name="product_name" value="Glucosamine 1500mg">
                            <input type="hidden" name="product_price" value="320000">
                            <button type="submit" class="add-to-cart">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="Login.php" class="add-to-cart">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </a>
                    <?php endif; ?>
                </div>
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
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
    <section class="welcome-section" style="background: #f8f9fa; padding: 40px 0;">
        <div class="container">
            <div style="text-align: center; max-width: 600px; margin: 0 auto;">
                <h3 style="color: #2c3e50; margin-bottom: 15px;">
                    <i class="fas fa-user-check" style="color: #27ae60; margin-right: 10px;"></i>
                    Xin chào <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
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
                        <div class="member-avatar">A</div>
                        <div class="member-name">Nguyễn Văn An</div>
                        <div class="member-role">Team Leader - Backend Developer</div>
                        <div class="member-id">MSSV: 21010001</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">B</div>
                        <div class="member-name">Trần Thị Bình</div>
                        <div class="member-role">Frontend Developer - UI/UX</div>
                        <div class="member-id">MSSV: 21010002</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">C</div>
                        <div class="member-name">Lê Minh Cường</div>
                        <div class="member-role">Database Administrator</div>
                        <div class="member-id">MSSV: 21010003</div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-avatar">D</div>
                        <div class="member-name">Phạm Thị Dung</div>
                        <div class="member-role">Quality Assurance - Tester</div>
                        <div class="member-id">MSSV: 21010004</div>
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