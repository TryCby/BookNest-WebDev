<?php
// เริ่มต้น Session เพื่อตรวจเช็กสถานะผู้ใช้และจำนวนสินค้าในตะกร้า
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['fullname'] : '';
$user_role = $is_logged_in ? $_SESSION['role'] : '';

// คำนวณจำนวนเล่มรวมในตะกร้าสำหรับแสดงบนแถบ Navbar
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}

// 1. รับค่า id ของหนังสือจาก URL เช่น book-detail.php?id=1
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. Query ดึงข้อมูลหนังสือเล่มนั้น ร่วมกับชื่อหมวดหมู่
$sql = "SELECT b.*, c.category_name 
        FROM books b 
        JOIN categories c ON b.category_id = c.category_id 
        WHERE b.book_id = $book_id 
        LIMIT 1";
$result = mysqli_query($conn, $sql);
$book = mysqli_fetch_assoc($result);

// หากไม่พบ ID หนังสือในระบบ ให้แจ้งเตือนและมีปุ่มกลับหน้าแรก
if (!$book) {
    die("<div style='text-align:center; padding: 50px; font-family: sans-serif;'>
            <h2>ไม่พบข้อมูลหนังสือเล่มนี้ในระบบ</h2>
            <p><a href='/index.php'>กลับสู่หน้าแรก</a></p>
         </div>");
}

// 3. เรียกชิ้นส่วนหน้าตาเว็บ (View) มาร้อยเรียงแสดงผล
require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/book_detail.php';
require_once __DIR__ . '/../views/layouts/footer.php';