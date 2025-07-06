<?php
include '../config/dual_session.php';
include '../config/database.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập admin
require_admin_login();

$message = '';

// Xử lý cập nhật trạng thái khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ma_nguoi_dung = $_POST['ma_nguoi_dung'];
    $trang_thai = $_POST['trang_thai'] == '1' ? 1 : 0;
    
    // Kiểm tra xem cột trang_thai_hoat_dong có tồn tại không
    $check_column = $conn->query("SHOW COLUMNS FROM nguoi_dung LIKE 'trang_thai_hoat_dong'");
    if ($check_column->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE nguoi_dung SET trang_thai_hoat_dong = ? WHERE ma_nguoi_dung = ? AND vai_tro = 'khach_hang'");
        $stmt->bind_param("ii", $trang_thai, $ma_nguoi_dung);
        
        if ($stmt->execute()) {
            $message = "Cập nhật trạng thái khách hàng thành công!";
        } else {
            $message = "Lỗi khi cập nhật trạng thái!";
        }
    } else {
        $message = "Chức năng này cần cập nhật database!";
    }
}

// Lấy thông tin tìm kiếm
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Xây dựng query tìm kiếm
$where_clause = "WHERE nd.vai_tro = 'khach_hang'";
$params = [];
$types = "";

if ($search) {
    $where_clause .= " AND (nd.ho_ten LIKE ? OR nd.email LIKE ? OR nd.so_dien_thoai LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

// Đếm tổng khách hàng
$count_sql = "SELECT COUNT(*) as total FROM nguoi_dung nd $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_customers = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_customers = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_customers / $per_page);

// Lấy danh sách khách hàng
$customers_sql = "
    SELECT nd.*, 
           COUNT(DISTINCT dh.ma_don_hang) as tong_don_hang,
           COALESCE(SUM(CASE WHEN dh.trang_thai_thanh_toan = 'da_thanh_toan' THEN dh.tong_tien_thanh_toan ELSE 0 END), 0) as tong_chi_tieu,
           MAX(dh.ngay_tao) as don_hang_gan_nhat
    FROM nguoi_dung nd 
    LEFT JOIN don_hang dh ON nd.ma_nguoi_dung = dh.ma_nguoi_dung 
    $where_clause
    GROUP BY nd.ma_nguoi_dung 
    ORDER BY nd.ma_nguoi_dung DESC 
    LIMIT $per_page OFFSET $offset
";

if (!empty($params)) {
    $customers_stmt = $conn->prepare($customers_sql);
    $customers_stmt->bind_param($types, ...$params);
    $customers_stmt->execute();
    $customers = $customers_stmt->get_result();
} else {
    $customers = $conn->query($customers_sql);
}

// Thống kê
$total_stats = $conn->query("SELECT COUNT(*) as total FROM nguoi_dung WHERE vai_tro = 'khach_hang'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Khách hàng - VitaMeds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>">
    <style>
        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .admin-wrapper.sidebar-open .sidebar {
                transform: translateX(0);
            }
        }
    </style>
    <style>
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stats-card h3 {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .stats-card p {
            font-size: 16px;
            color: #7f8c8d;
        }

        .search-form {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-form input {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-form input {
                min-width: auto;
                width: 100%;
            }
            
            .stats-card {
                padding: 20px;
            }
            
            .stats-card h3 {
                font-size: 36px;
            }
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #3498db, #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .customer-details h4 {
            color: #2c3e50;
            margin-bottom: 2px;
            font-size: 14px;
        }

        .customer-details small {
            color: #7f8c8d;
            font-size: 12px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #d1f2eb;
            color: #00695c;
            border-left: 4px solid #27ae60;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .page-link {
            padding: 8px 12px;
            color: #3498db;
            text-decoration: none;
            border: 1px solid #ecf0f1;
            border-radius: 4px;
        }

        .page-link:hover,
        .page-link.active {
            background: #3498db;
            color: white;
        }

        @media (max-width: 768px) {
            .stats-card {
                padding: 20px;
            }
            
            .stats-card h3 {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Quản lý Khách hàng';
            $page_icon = 'fas fa-users';
            include '../includes/admin-header.php'; 
            ?>
            
            <div class="dashboard-content">

            <?php if ($message): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-card">
                <h3><?php echo number_format($total_stats); ?></h3>
                <p>Tổng số khách hàng trong hệ thống</p>
            </div>

            <!-- Search Form -->
            <div class="search-form">
                <form method="GET">
                    <input type="text" name="search" placeholder="Tìm kiếm theo tên, email, số điện thoại..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                    
                    <?php if ($search): ?>
                    <a href="customers.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Xóa tìm kiếm
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Customers Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Danh sách khách hàng (<?php echo $total_customers; ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if ($customers && $customers->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Khách hàng</th>
                                <th>Thông tin liên hệ</th>
                                <th>Thống kê mua hàng</th>
                                <th>Hoạt động gần nhất</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($customer = $customers->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            <?php echo strtoupper(substr($customer['ho_ten'], 0, 1)); ?>
                                        </div>
                                        <div class="customer-details">
                                            <h4><?php echo htmlspecialchars($customer['ho_ten']); ?></h4>
                                            <small>ID: <?php echo $customer['ma_nguoi_dung']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($customer['email']); ?></strong><br>
                                        <small>SĐT: <?php echo htmlspecialchars($customer['so_dien_thoai'] ?? 'Chưa cập nhật'); ?></small><br>
                                        <small>Sinh: <?php echo $customer['ngay_sinh'] ? date('d/m/Y', strtotime($customer['ngay_sinh'])) : 'Chưa cập nhật'; ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo $customer['tong_don_hang']; ?></strong> đơn hàng<br>
                                        <span style="color: #27ae60; font-weight: bold;">
                                            <?php echo number_format($customer['tong_chi_tieu']); ?>đ
                                        </span> tổng chi tiêu
                                    </div>
                                </td>
                                <td>
                                    <?php if ($customer['don_hang_gan_nhat']): ?>
                                        <small>
                                            <?php echo date('d/m/Y H:i', strtotime($customer['don_hang_gan_nhat'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <small style="color: #7f8c8d;">Chưa có đơn hàng</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="customer_detail.php?id=<?php echo $customer['ma_nguoi_dung']; ?>" class="btn btn-info" target="_blank">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                &laquo; Trước
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                               class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                Sau &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <h3>Không tìm thấy khách hàng nào</h3>
                        <p>Thử thay đổi từ khóa tìm kiếm</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>
    </div>

    <script>
        // Simple responsive toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const adminWrapper = document.querySelector('.admin-wrapper');
            
            if (sidebarToggle && adminWrapper) {
                sidebarToggle.addEventListener('click', function() {
                    adminWrapper.classList.toggle('sidebar-open');
                });
            }
        });
    </script>
    <script>
        // Auto hide alert
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    </script>
</body>
</html>