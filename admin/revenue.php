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
    $labels[] = sprintf('%02d', $d);
    $values[] = isset($chart_data[$d]) ? $chart_data[$d] : 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thống kê doanh thu</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #2c3e50; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 32px; }
        h1 { font-size: 2rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-form { display: flex; gap: 15px; align-items: center; margin-bottom: 30px; }
        .filter-form select, .filter-form button { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(90deg, #f8f9fa, #e3f6fc); border-radius: 12px; padding: 25px; display: flex; align-items: center; gap: 20px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); }
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
        @media (max-width: 700px) { .container { padding: 10px; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-chart-line"></i> Thống kê doanh thu</h1>
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
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'bar',
    data: {
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
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
        },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: '#ecf0f1' } }
        }
    }
});
</script>
</body>
</html> 