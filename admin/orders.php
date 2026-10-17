<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ตรวจสอบสิทธิ์แอดมิน (RBAC)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../db.php';

$msg = '';
$err = '';

// 2. จัดการ Action คำสั่งซื้อ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($order_id > 0) {
        if ($action === 'approve') {
            $sql = "UPDATE orders SET status = 'paid' WHERE order_id = $order_id";
            if (mysqli_query($conn, $sql)) {
                $msg = "อนุมัติคำสั่งซื้อ #$order_id (ชำระเงินแล้ว) เรียบร้อย";
            }
        } elseif ($action === 'ship') {
            $tracking = trim($_POST['tracking_no'] ?? '');
            if (!empty($tracking)) {
                $safe_track = mysqli_real_escape_string($conn, $tracking);
                $sql = "UPDATE orders SET status = 'shipped', tracking_no = '$safe_track' WHERE order_id = $order_id";
                if (mysqli_query($conn, $sql)) {
                    $msg = "บันทึกเลขพัสดุคำสั่งซื้อ #$order_id และอัปเดตสถานะเป็น 'จัดส่งแล้ว'";
                }
            } else {
                $err = "กรุณากรอกเลขพัสดุก่อนกดจัดส่ง";
            }
        } elseif ($action === 'reject') {
            $items_sql = "SELECT book_id, quantity FROM order_items WHERE order_id = $order_id";
            $items_res = mysqli_query($conn, $items_sql);
            while ($item = mysqli_fetch_assoc($items_res)) {
                $bid = $item['book_id'];
                $qty = $item['quantity'];
                mysqli_query($conn, "UPDATE books SET stock = stock + $qty WHERE book_id = $bid");
            }

            mysqli_query($conn, "UPDATE orders SET status = 'rejected' WHERE order_id = $order_id");
            $msg = "ปฏิเสธคำสั่งซื้อ #$order_id และคืนสต็อกหนังสือเข้าคลังแล้ว";
        }
    }
}

// 3. กรองคำสั่งซื้อตามสถานะ
$filter_status = $_GET['status'] ?? 'all';
$where_clause = "";
if ($filter_status !== 'all') {
    $safe_status = mysqli_real_escape_string($conn, $filter_status);
    $where_clause = "WHERE o.status = '$safe_status'";
}

// 4. Query คำสั่งซื้อทั้งหมด
$orders_sql = "SELECT o.*, u.fullname, u.phone 
               FROM orders o 
               JOIN users u ON o.user_id = u.user_id 
               $where_clause 
               ORDER BY o.order_id DESC";
$orders_res = mysqli_query($conn, $orders_sql);
$orders_list = [];

while ($ord = mysqli_fetch_assoc($orders_res)) {
    $oid = $ord['order_id'];
    $items_sql = "SELECT oi.*, b.title, b.cover_image 
                  FROM order_items oi 
                  JOIN books b ON oi.book_id = b.book_id 
                  WHERE oi.order_id = $oid";
    $items_res = mysqli_query($conn, $items_sql);
    $items = [];
    while ($it = mysqli_fetch_assoc($items_res)) {
        $items[] = $it;
    }
    $ord['items'] = $items;
    $orders_list[] = $ord;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - BookNest Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<header class="admin-nav">
    <div class="admin-nav-left">
        <span class="brand-title">BookNest</span>
        <a href="/admin/dashboard.php">แดชบอร์ดสรุปผล</a>
        <a href="/admin/orders.php" class="active">จัดการคำสั่งซื้อ</a>
        <a href="/admin/books.php">จัดการหนังสือและหมวดหมู่</a>
        <a href="/admin/users.php">จัดการสมาชิก</a>
        <a href="/index.php" target="_blank">หน้าร้านค้า</a>
    </div>
    <div>
        <span class="admin-nav-user"><?= htmlspecialchars($_SESSION['fullname']); ?></span>
        <a href="/logout.php" class="admin-nav-logout">ออกจากระบบ</a>
    </div>
</header>

<main class="admin-container">
    <div class="admin-page-header">
        <h2>รายการคำสั่งซื้อและตรวจสลิปโอนเงิน</h2>
        <div class="admin-filter-group">
            <a href="/admin/orders.php?status=all" class="filter-btn <?= ($filter_status === 'all') ? 'active' : ''; ?>">ทั้งหมด</a>
            <a href="/admin/orders.php?status=pending" class="filter-btn <?= ($filter_status === 'pending') ? 'active' : ''; ?>">รอตรวจสอบสลิป</a>
            <a href="/admin/orders.php?status=paid" class="filter-btn <?= ($filter_status === 'paid') ? 'active' : ''; ?>">ชำระแล้ว</a>
            <a href="/admin/orders.php?status=shipped" class="filter-btn <?= ($filter_status === 'shipped') ? 'active' : ''; ?>">จัดส่งแล้ว</a>
            <a href="/admin/orders.php?status=rejected" class="filter-btn <?= ($filter_status === 'rejected') ? 'active' : ''; ?>">ปฏิเสธ</a>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert-box alert-success"><?= htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="alert-box alert-error"><?= htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <?php if (empty($orders_list)): ?>
        <div class="card-panel" style="text-align: center; color: #64748b; padding: 40px;">
            ไม่พบรายการคำสั่งซื้อในหมวดนี้
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <?php foreach ($orders_list as $ord): ?>
                <div class="card-panel">
                    <div class="admin-page-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 14px;">
                        <div>
                            <strong style="font-size: 16px; color: #0f172a;">คำสั่งซื้อ #<?= $ord['order_id']; ?></strong>
                            <span style="font-size: 14px; color: #475569; margin-left: 12px;">ลูกค้า: <strong><?= htmlspecialchars($ord['fullname']); ?></strong> (<?= htmlspecialchars($ord['phone']); ?>)</span>
                            <span style="font-size: 13px; color: #94a3b8; margin-left: 12px;">วันที่: <?= $ord['created_at']; ?></span>
                        </div>
                        <div>
                            <?php 
                                $status = $ord['status'];
                                if ($status === 'pending') {
                                    echo '<span class="badge badge-pending">⏳ รอตรวจสอบสลิป</span>';
                                } elseif ($status === 'paid') {
                                    echo '<span class="badge badge-paid">✅ ชำระเงินแล้ว</span>';
                                } elseif ($status === 'shipped') {
                                    echo '<span class="badge badge-shipped">🚚 จัดส่งแล้ว</span>';
                                } elseif ($status === 'rejected') {
                                    echo '<span class="badge badge-rejected">❌ ถูกปฏิเสธ (คืนสต็อกแล้ว)</span>';
                                }
                            ?>
                        </div>
                    </div>

                    <div class="order-card-grid">
                        <div>
                            <p style="font-size: 13px; font-weight: bold; color: #64748b; margin-bottom: 8px;">รายการสินค้าที่สั่งซื้อ:</p>
                            <?php foreach ($ord['items'] as $item): ?>
                                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                                    <span>• <?= htmlspecialchars($item['title']); ?> (x<?= $item['quantity']; ?>)</span>
                                    <span>฿<?= number_format($item['price_each'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e2e8f0; font-size: 14px; font-weight: bold;">
                                ยอดชำระสุทธิ: <span style="color: #0288d1;">฿<?= number_format($ord['net_total'], 2); ?></span>
                            </div>
                        </div>

                        <div class="order-slip-panel">
                            <div>
                                <span style="font-size: 13px; font-weight: bold; color: #475569;">หลักฐานการโอนเงิน (สลิป):</span>
                                <div style="margin: 8px 0;">
                                    <?php if (!empty($ord['slip_image'])): ?>
                                        <a href="/uploads/slips/<?= htmlspecialchars($ord['slip_image']); ?>" target="_blank" class="slip-link-btn">
                                            กดคลิกเพื่อเปิดดูรูปสลิป
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #94a3b8;">ไม่มีไฟล์สลิป</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                                <?php if ($ord['status'] === 'pending'): ?>
                                    <div style="display: flex; gap: 8px;">
                                        <form method="POST" style="flex: 1;">
                                            <input type="hidden" name="order_id" value="<?= $ord['order_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn-admin-success" style="width: 100%;">อนุมัติ (Paid)</button>
                                        </form>
                                        <form method="POST" style="flex: 1;" onsubmit="return confirm('ยืนยันปฏิเสธออเดอร์นี้? ระบบจะคืนสต็อกหนังสือเข้าคลังทันที');">
                                            <input type="hidden" name="order_id" value="<?= $ord['order_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn-admin-danger" style="width: 100%;">ปฏิเสธ (คืนสต็อก)</button>
                                        </form>
                                    </div>
                                <?php elseif ($ord['status'] === 'paid'): ?>
                                    <form method="POST" style="display: flex; gap: 6px;">
                                        <input type="hidden" name="order_id" value="<?= $ord['order_id']; ?>">
                                        <input type="hidden" name="action" value="ship">
                                        <input type="text" name="tracking_no" required placeholder="ใส่เลขพัสดุ THxxxx" class="form-input-admin" style="flex: 1;">
                                        <button type="submit" class="btn-admin-submit" style="width: auto; padding: 6px 12px;">จัดส่งสินค้า</button>
                                    </form>
                                <?php elseif ($ord['status'] === 'shipped'): ?>
                                    <div style="font-size: 13px; color: #0369a1;">
                                        เลขพัสดุ: <strong><?= htmlspecialchars($ord['tracking_no']); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>