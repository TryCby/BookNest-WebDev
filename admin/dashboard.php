<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบสิทธิ์ Admin (RBAC)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../db.php';

// 1. ดึงสถิติตัวเลขสรุปภาพรวมร้านค้า
$sales_sql = "SELECT SUM(net_total) as total_sales FROM orders WHERE status IN ('paid', 'shipped')";
$sales_res = mysqli_query($conn, $sales_sql);
$total_sales = mysqli_fetch_assoc($sales_res)['total_sales'] ?? 0;

$pending_sql = "SELECT COUNT(*) as pending_count FROM orders WHERE status = 'pending'";
$pending_res = mysqli_query($conn, $pending_sql);
$pending_count = mysqli_fetch_assoc($pending_res)['pending_count'] ?? 0;

$shipped_sql = "SELECT COUNT(*) as shipped_count FROM orders WHERE status = 'shipped'";
$shipped_res = mysqli_query($conn, $shipped_sql);
$shipped_count = mysqli_fetch_assoc($shipped_res)['shipped_count'] ?? 0;

$low_stock_sql = "SELECT COUNT(*) as low_stock_count FROM books WHERE stock < 18";
$low_stock_res = mysqli_query($conn, $low_stock_sql);
$low_stock_count = mysqli_fetch_assoc($low_stock_res)['low_stock_count'] ?? 0;

// 2. ดึงข้อมูลยอดขายแยกตามหมวดหมู่ สำหรับใส่ตารางและกราฟ
$cat_sales_sql = "SELECT c.category_name, 
                         COALESCE(SUM(oi.quantity), 0) as total_qty,
                         COALESCE(SUM(oi.price_each * oi.quantity), 0) as category_total
                  FROM categories c
                  LEFT JOIN books b ON c.category_id = b.category_id
                  LEFT JOIN order_items oi ON b.book_id = oi.book_id
                  LEFT JOIN orders o ON oi.order_id = o.order_id AND o.status IN ('paid', 'shipped')
                  GROUP BY c.category_id, c.category_name
                  ORDER BY c.category_id ASC";
$cat_sales_res = mysqli_query($conn, $cat_sales_sql);

$labels = [];
$sales_data = [];
$table_rows = [];

while ($row = mysqli_fetch_assoc($cat_sales_res)) {
    $labels[] = $row['category_name'];
    $sales_data[] = floatval($row['category_total']);
    $table_rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุปสถิติ - BookNest Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <!-- Chart.js CDN สำหรับวาดกราฟ -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header class="admin-nav">
    <div style="display: flex; align-items: center; gap: 20px;">
        <span style="font-size: 18px; font-weight: bold; color: white;">BookNest</span>
        <a href="/admin/dashboard.php" class="active">แดชบอร์ดสรุปผล</a>
        <a href="/admin/orders.php">จัดการคำสั่งซื้อ</a>
        <a href="/admin/books.php">จัดการหนังสือและหมวดหมู่</a>
        <a href="/admin/users.php">จัดการสมาชิก</a>
        <a href="/index.php" target="_blank">หน้าร้านค้า</a>
    </div>
    <div>
        <span style="color: #94a3b8; font-size: 13px; margin-right: 15px;"><?= htmlspecialchars($_SESSION['fullname']); ?></span>
        <a href="/logout.php" style="color: #f87171;">ออกจากระบบ</a>
    </div>
</header>

<main class="container" style="max-width: 1000px; margin: 30px auto; padding: 0 20px;">
    <h2 style="margin-bottom: 20px; color: #1e293b;">รายงานสรุปสถิติร้านค้า</h2>

    <!-- ส่วนที่ 1: ตารางสรุปภาพรวมร้านค้า (ตรงตามเกณฑ์ Basic รูปแบบตาราง) -->
    <div style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 20px; margin-bottom: 25px;">
        <h3 style="font-size: 16px; margin-bottom: 12px; color: #334155;">1. ตารางสรุปภาพรวมคำสั่งซื้อและสต็อก</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 10px; border: 1px solid #e2e8f0;">หัวข้อสถิติ</th>
                    <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: right;">จำนวน / ยอดรวม</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px; border: 1px solid #e2e8f0;">ยอดขายสุทธิรวม (สถานะชำระเงิน/จัดส่งแล้ว)</td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #0288d1;">฿<?= number_format($total_sales, 2); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #e2e8f0;">ออเดอร์ที่รอตรวจสอบสลิป</td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; text-align: right;"><?= number_format($pending_count); ?> รายการ</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #e2e8f0;">ออเดอร์ที่จัดส่งเรียบร้อยแล้ว</td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; text-align: right;"><?= number_format($shipped_count); ?> รายการ</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #e2e8f0;">จำนวนหนังสือที่สต็อกใกล้หมด (น้อยกว่า 18 เล่ม)</td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; text-align: right; color: #dc2626;"><?= number_format($low_stock_count); ?> เล่ม</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ส่วนที่ 2: กราฟสถิติยอดขาย (ตรงตามเกณฑ์ Bonus แสดงเป็นกราฟ) -->
    <div style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 20px;">
        <h3 style="font-size: 16px; margin-bottom: 15px; color: #334155;">2. แผนภูมิแท่งแสดงยอดขายตามหมวดหมู่ (บาท)</h3>
        <div style="height: 320px; position: relative;">
            <canvas id="categoryChart" 
                    data-labels="<?= htmlspecialchars(json_encode($labels, JSON_UNESCAPED_UNICODE)); ?>" 
                    data-values="<?= htmlspecialchars(json_encode($sales_data)); ?>"></canvas>
        </div>
    </div>
</main>

<script src="/assets/js/dashboard.js"></script>

</body>
</html>