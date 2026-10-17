<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// 1. จัดการ Action ต่างๆ
if ($action === 'add' && $book_id > 0) {
    if (isset($_SESSION['cart'][$book_id])) {
        $_SESSION['cart'][$book_id] += 1;
    } else {
        $_SESSION['cart'][$book_id] = 1;
    }

    $total_count = array_sum($_SESSION['cart']);

    // ถ้าส่งมาแบบ AJAX ให้ส่ง Response เป็น JSON กลับไปทันที (ไม่โหลดหน้าใหม่)
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มหนังสือลงในตะกร้าเรียบร้อยแล้ว',
            'cart_count' => $total_count
        ]);
        exit;
    }

    header('Location: /cart.php');
    exit;
}

// เพิ่มแบบชุดเซ็ต 3 เล่มพร้อมกัน
if ($action === 'add_bundle' && isset($_GET['bundle_ids']) && is_array($_GET['bundle_ids'])) {
    foreach ($_GET['bundle_ids'] as $bid) {
        $bid = intval($bid);
        if ($bid > 0) {
            $_SESSION['cart'][$bid] = ($_SESSION['cart'][$bid] ?? 0) + 1;
        }
    }

    $total_count = array_sum($_SESSION['cart']);
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มชุดหนังสือ 3 เล่มลงตะกร้าเรียบร้อยแล้ว',
            'cart_count' => $total_count
        ]);
        exit;
    }

    header('Location: /cart.php');
    exit;
}

// ลดจำนวนสินค้า
if ($action === 'decrease' && $book_id > 0) {
    if (isset($_SESSION['cart'][$book_id])) {
        $_SESSION['cart'][$book_id] -= 1;
        if ($_SESSION['cart'][$book_id] <= 0) {
            unset($_SESSION['cart'][$book_id]);
        }
    }
    header('Location: /cart.php');
    exit;
}

// ลบสินค้าออกจากตะกร้า
if ($action === 'remove' && $book_id > 0) {
    unset($_SESSION['cart'][$book_id]);
    header('Location: /cart.php');
    exit;
}

// 2. ดึงข้อมูลหนังสือที่อยู่ในตะกร้าออกมาคำนวณ
$cart_items = [];
$subtotal = 0.00;
$total_quantity = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $sql = "SELECT b.*, c.category_name 
            FROM books b 
            JOIN categories c ON b.category_id = c.category_id 
            WHERE b.book_id IN ($ids)";
    $res = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($res)) {
        $bid = $row['book_id'];
        $qty = $_SESSION['cart'][$bid];
        $line_total = $row['price'] * $qty;

        $subtotal += $line_total;
        $total_quantity += $qty;

        $row['qty'] = $qty;
        $row['line_total'] = $line_total;
        $cart_items[] = $row;
    }
}

// 3. กฎ Promotion Engine 3 สเต็ป
$curated_sets = [
    [4, 10, 17],
    [2, 9, 18],
    [5, 11, 15],
    [6, 16, 20],
    [21, 22, 23]
];

$bundle_discount = 0.00;
$in_cart_ids = array_keys($_SESSION['cart']);

foreach ($curated_sets as $set) {
    if (count(array_intersect($set, $in_cart_ids)) === 3) {
        $set_ids = implode(',', $set);
        $set_sql = "SELECT SUM(price) as set_price FROM books WHERE book_id IN ($set_ids)";
        $set_res = mysqli_query($conn, $set_sql);
        $set_data = mysqli_fetch_assoc($set_res);
        $bundle_discount += ($set_data['set_price'] * 0.10);
    }
}

$qty_discount = ($total_quantity >= 4) ? ($subtotal * 0.05) : 0.00;
$total_discount = $bundle_discount + $qty_discount;
$price_after_discount = max(0, $subtotal - $total_discount);
$shipping_fee = ($total_quantity > 0) ? (($price_after_discount >= 500) ? 0.00 : 50.00) : 0.00;
$net_total = $price_after_discount + $shipping_fee;

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['fullname'] : '';
$user_role = $is_logged_in ? $_SESSION['role'] : '';
$cart_count = $total_quantity;

require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/cart.php';
require_once __DIR__ . '/../views/layouts/footer.php';