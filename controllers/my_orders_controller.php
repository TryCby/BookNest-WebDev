<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบ user_id (ถ้าล็อกอินใช้ ID ตัวเอง ถ้ายังไม่ล็อกอินให้ดึงออเดอร์ของ Somchai user_id = 2 มาดู)
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 2;

// ดึงประวัติคำสั่งซื้อของลูกค้ารายนี้ เรียงจากล่าสุดไปเก่าสุด
$sql_orders = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_id DESC";
$res_orders = mysqli_query($conn, $sql_orders);
$orders = [];

while ($order = mysqli_fetch_assoc($res_orders)) {
    $oid = $order['order_id'];
    
    // ดึงรายการหนังสือในแต่ละออเดอร์
    $sql_items = "SELECT oi.*, b.title, b.cover_image 
                  FROM order_items oi 
                  JOIN books b ON oi.book_id = b.book_id 
                  WHERE oi.order_id = $oid";
    $res_items = mysqli_query($conn, $sql_items);
    $items = [];
    while ($item = mysqli_fetch_assoc($res_items)) {
        $items[] = $item;
    }
    
    $order['items'] = $items;
    $orders[] = $order;
}

// ตัวแปรสำหรับ Header
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['fullname'] : 'สมชาย สุขสบาย (จำลอง)';
$user_role = $is_logged_in ? $_SESSION['role'] : 'customer';
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $q) {
        $cart_count += $q;
    }
}

require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/my_orders.php';
require_once __DIR__ . '/../views/layouts/footer.php';