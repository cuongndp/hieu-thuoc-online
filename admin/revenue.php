<?php
include '../config/dual_session.php';
include '../config/database.php';

// Ensure session is started
ensure_session_started();

// Kiểm tra đăng nhập admin
require_admin_login();

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Lấy doanh thu và đơn hàng theo tháng/năm
$sql = "SELECT dh.*, nd.ho_ten FROM don_hang dh JOIN nguoi_dung nd ON dh.ma_nguoi_dung = nd.ma_nguoi_dung WHERE dh.trang_thai_thanh_toan = 'da_thanh_toan' AND dh.trang_thai_don_hang = 'da_giao' AND MONTH(dh.ngay_tao) = $month AND YEAR(dh.ngay_tao) = $year ORDER BY dh.ngay_tao ASC";
$orders = $conn->query($sql);

$total_revenue = 0;
$total_orders = 0;
$order_list = [];
$chart_data = [];

if ($orders) {
    $total_orders = $orders->num_rows;
    while($row = $orders->fetch_assoc()) {
        $total_revenue += $row['tong_tien_thanh_toan'];
        $order_list[] = $row;
        $day = date('d', strtotime($row['ngay_tao']));
        if (!isset($chart_data[$day])) $chart_data[$day] = 0;
        $chart_data[$day] += $row['tong_tien_thanh_toan'];
    }
}

// Chuẩn bị dữ liệu cho biểu đồ
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$labels = [];
$values = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $key = sprintf('%02d', $d);
    $labels[] = $key;
    $values[] = isset($chart_data[$key]) ? $chart_data[$key] : 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê doanh thu</title>
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
        .chart-section { 
            background: white; 
            border-radius: 12px; 
            padding: 30px 20px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); 
        }
        .chart-title { 
            font-size: 1.2em; 
            color: #2980b9; 
            margin-bottom: 20px; 
            font-weight: 600; 
        }
        .customer-name { 
            font-weight: 600; 
            color: #2980b9; 
        }
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-form label {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-form select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .filter-form button {
            padding: 8px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-form button:hover {
            background: #2980b9;
        }
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-form label {
                justify-content: space-between;
            }
            .filter-form select,
            .filter-form button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar-admin.php'; ?>
        
        <div class="main-content">
            <?php 
            $page_title = 'Thống kê doanh thu';
            $page_icon = 'fas fa-chart-line';
            include '../includes/admin-header.php'; 
            ?>
            
            <div class="dashboard-content">
                <form method="GET" class="filter-form">
                    <label>Tháng:
                        <select name="month">
                            <?php for($m=1;$m<=12;$m++): ?>
                                <option value="<?php echo $m; ?>" <?php if($m==$month) echo 'selected'; ?>><?php echo $m; ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>Năm:
                        <select name="year">
                            <?php for($y=date('Y')-3;$y<=date('Y');$y++): ?>
                                <option value="<?php echo $y; ?>" <?php if($y==$year) echo 'selected'; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <button type="submit">Xem</button>
                </form>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($total_revenue); ?>đ</h3>
                            <p>Tổng doanh thu tháng <?php echo $month; ?>/<?php echo $year; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_orders; ?></h3>
                            <p>Số đơn đã giao & thanh toán</p>
                        </div>
                    </div>
                </div>

                <div class="chart-section">
                    <div class="chart-title">
                        <i class="fas fa-chart-bar"></i> Biểu đồ doanh thu theo ngày
                    </div>
                    <canvas id="revenueChart" height="80"></canvas>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Ngày tạo</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thanh toán</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($order_list)): ?>
                                <?php foreach($order_list as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['so_don_hang']); ?></td>
                                    <td><span class="customer-name"><?php echo htmlspecialchars($order['ho_ten']); ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                                    <td><?php echo number_format($order['tong_tien_thanh_toan']); ?>đ</td>
                                    <td><span class="status-badge status-da_giao">Đã giao</span></td>
                                    <td><span class="status-badge status-da_thanh_toan">Đã thanh toán</span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;color:#888;">Không có đơn hàng nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart');
            if (ctx) {
                const chartData = {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Doanh thu (đ)',
                        data: <?php echo json_encode($values); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.6)',
                        borderColor: 'rgba(41, 128, 185, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                        maxBarThickness: 28
                    }]
                };
                
                const chartOptions = {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: '#ecf0f1' } }
                    }
                };
                
                new Chart(ctx, {
                    type: 'bar',
                    data: chartData,
                    options: chartOptions
                });
            }
        });
    </script>
</body>
</html> 