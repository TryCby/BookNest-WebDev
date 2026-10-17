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

// 2. จัดการสลับบทบาท (Role)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_uid = intval($_POST['user_id'] ?? 0);
    $new_role = $_POST['role'] ?? 'customer';

    if ($target_uid > 0 && in_array($new_role, ['customer', 'admin'])) {
        if ($target_uid === intval($_SESSION['user_id'])) {
            $err = "ไม่สามารถเปลี่ยนบทบาทของบัญชีที่กำลังใช้งานอยู่ได้";
        } else {
            $sql = "UPDATE users SET role = '$new_role' WHERE user_id = $target_uid";
            if (mysqli_query($conn, $sql)) {
                $msg = "อัปเดตบทบาทผู้ใช้งานรหัส #$target_uid เป็น '$new_role' เรียบร้อยแล้ว";
            } else {
                $err = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
            }
        }
    }
}

// 3. ดึงรายชื่อผู้ใช้
$users_res = mysqli_query($conn, "SELECT * FROM users ORDER BY user_id ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิก - BookNest Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<header class="admin-nav">
    <div class="admin-nav-left">
        <span class="brand-title">BookNest </span>
        <a href="/admin/dashboard.php">แดชบอร์ดสรุปผล</a>
        <a href="/admin/orders.php">จัดการคำสั่งซื้อ</a>
        <a href="/admin/books.php">จัดการหนังสือและหมวดหมู่</a>
        <a href="/admin/users.php" class="active">จัดการสมาชิก</a>
        <a href="/index.php" target="_blank">ไปหน้าร้านค้า</a>
    </div>
    <div>
        <span class="admin-nav-user">แอดมิน: <?= htmlspecialchars($_SESSION['fullname']); ?></span>
        <a href="/logout.php" class="admin-nav-logout">ออกจากระบบ</a>
    </div>
</header>

<main class="admin-container">
    <h2 style="margin-bottom: 20px;">รายชื่อสมาชิกและจัดการสิทธิ์ผู้ใช้งาน</h2>

    <?php if (!empty($msg)): ?>
        <div class="alert-box alert-success"><?= htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="alert-box alert-error"><?= htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <div class="card-panel">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อ - นามสกุล</th>
                    <th>อีเมล</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th>ที่อยู่เริ่มต้น</th>
                    <th style="text-align: center;">บทบาท (Role)</th>
                    <th style="text-align: center;">การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = mysqli_fetch_assoc($users_res)): ?>
                    <tr>
                        <td style="color: #94a3b8;">#<?= $u['user_id']; ?></td>
                        <td style="font-weight: 500; color: #0f172a;">
                            <?= htmlspecialchars($u['fullname']); ?>
                            <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                <span class="badge badge-self">คุณ</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: #475569;"><?= htmlspecialchars($u['email']); ?></td>
                        <td style="color: #475569;"><?= htmlspecialchars($u['phone']); ?></td>
                        <td style="color: #64748b; max-width: 250px;"><?= htmlspecialchars($u['address'] ?? '-'); ?></td>
                        <td style="text-align: center;">
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge badge-admin">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-customer">Customer</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id']; ?>">
                                    <input type="hidden" name="role" value="<?= ($u['role'] === 'admin') ? 'customer' : 'admin'; ?>">
                                    <button type="submit" class="btn-table-toggle">
                                        สลับเป็น <?= ($u['role'] === 'admin') ? 'Customer' : 'Admin'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #cbd5e1;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>