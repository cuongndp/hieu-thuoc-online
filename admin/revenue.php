<?php
session_start();
include '../config/database.php';
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #2c3e50; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%); color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h3 { color: #ecf0f1; margin-bottom: 5px; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu a { display: flex; align-items: center; padding: 15px 20px; color: #ecf0f1; text-decoration: none; transition: all 0.3s ease; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.1); }
        .sidebar-menu li.active a, .sidebar-menu a.active { background: linear-gradient(90deg, #3498db, #2980b9); }
        .sidebar-menu a i { width: 20px; margin-right: 10px; }
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        .page-header { background: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { color: #2c3e50; font-size: 28px; }
        .user-info { text-align: right; }
        .user-info span { font-weight: 600; color: #2c3e50; }
        .user-info small { color: #7f8c8d; display: block; }
        .filter-form { display: flex; gap: 15px; align-items: center; margin-bottom: 30px; }
        .filter-form select, .filter-form button { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(90deg, #f8f9fa, #e3f6fc); border-radius: 12px; padding: 25px; display: flex; align-items: center; gap: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: white; background: linear-gradient(45deg, #27ae60, #2ecc71); }
        .stat-info h3 { font-size: 28px; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .stat-info p { color: #7f8c8d; font-size: 14px; }
        .chart-section { background: #f8f9fa; border-radius: 12px; padding: 30px 20px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .chart-title { font-size: 1.2em; color: #2980b9; margin-bottom: 10px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        th, td { padding: 12px 8px; border-bottom: 1px solid #ecf0f1; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        tr:last-child td { border-bottom: none; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-da_giao { background: #d1f2eb; color: #00695c; }
        .status-da_thanh_toan { background: #d1f2eb; color: #00695c; }
        .customer-name { font-weight: 600; color: #2980b9; }
        @media (max-width: 700px) { .main-content { margin-left: 0; padding: 10px; } .stats-grid { grid-template-columns: 1fr; } }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; transition: all 0.3s ease; }
        .logout-btn:hover { background: #c0392b; }
    </style>
</head>
<body>
<?php include '../includes/sidebar-admin.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> Thống kê doanh thu</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
            <small>Administrator</small>
            <div style="margin-top: 10px;">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>
    </div>
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
        <button type="submit" class="btn btn-primary">Xem</button>
    </form>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($total_revenue); ?>đ</h3>
                <p>Tổng doanh thu tháng <?php echo $month; ?>/<?php echo $year; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(45deg,#3498db,#2980b9);"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_orders; ?></h3>
                <p>Số đơn đã giao & thanh toán</p>
            </div>
        </div>
    </div>
    <div class="chart-section">
        <div class="chart-title"><i class="fas fa-chart-bar"></i> Biểu đồ doanh thu theo ngày</div>
        <canvas id="revenueChart" height="80"></canvas>
    </div>
    <table>
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
            <?php if (!empty($order_list)): foreach($order_list as $order): ?>
            <tr>
                <td><?php echo htmlspecialchars($order['so_don_hang']); ?></td>
                <td><span class="customer-name"><?php echo htmlspecialchars($order['ho_ten']); ?></span></td>
                <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                <td><?php echo number_format($order['tong_tien_thanh_toan']); ?>đ</td>
                <td><span class="status-badge status-da_giao">Đã giao</span></td>
                <td><span class="status-badge status-da_thanh_toan">Đã thanh toán</span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center;color:#888;">Không có đơn hàng nào</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
const data = {
    labels: <?php echo json_encode($labels); ?>,
    datasets: [{
        label: 'Doanh thu (đ)',
        data: <?php echo json_encode($values); ?>,
        backgroundColor: <?php echo (array_sum($values) > 0 && count(array_filter($values)) === 1) ? "'rgba(231, 76, 60, 0.8)'" : "'rgba(52, 152, 219, 0.6)'"; ?>,
        borderColor: 'rgba(41, 128, 185, 1)',
        borderWidth: 2,
        borderRadius: 6,
        maxBarThickness: 28
    }]
};
const options = {
    responsive: true,
    plugins: {
        legend: { display: false },
        tooltip: { enabled: true },
        datalabels: {
            display: true,
            color: '#222',
            anchor: 'end',
            align: 'top',
            formatter: function(value) { return value > 0 ? value.toLocaleString() + 'đ' : ''; }
        },
        title: {
            display: <?php echo array_sum($values) == 0 ? 'true' : 'false'; ?>,
            text: 'Không có dữ liệu doanh thu trong tháng này',
            color: '#e74c3c',
            font: { size: 18 }
        }
    },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, grid: { color: '#ecf0f1' } }
    }
};
// Nếu có nhiều đơn, thêm plugin datalabels
if (typeof ChartDataLabels !== 'undefined') {
    options.plugins.datalabels = data.datasets[0].data.some(v => v > 0) ? options.plugins.datalabels : false;
}
const revenueChart = new Chart(ctx, { type: 'bar', data, options });
</script>
</body>
</html> 