<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookNest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="navbar">
    <a href="/index.php" class="brand">BookNest</a>
    <ul class="nav-links">
        <li><a href="/index.php">หน้าแรก</a></li>
        <li><a href="/cart.php">ตะกร้าสินค้า (<span id="nav-cart-count"><?= $cart_count; ?></span>)</a></li>
        
        <?php if ($is_logged_in): ?>
            <li><a href="/my-orders.php">รายการสั่งซื้อ</a></li>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="/admin/dashboard.php" style="color: #fef08a; font-weight: bold;">[จัดการหลังบ้าน]</a></li>
            <?php endif; ?>
            <li><a href="/profile.php"><?= htmlspecialchars($user_name); ?></a></li>
            <li><a href="/logout.php">ออกจากระบบ</a></li>
        <?php else: ?>
            <li><a href="/login.php">เข้าสู่ระบบ</a></li>
            <li><a href="/register.php">สมัครสมาชิก</a></li>
        <?php endif; ?>
    </ul>
</header>

<main class="container">