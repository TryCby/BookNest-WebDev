<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ถ้าไม่มีสินค้าในตะกร้า ให้ดีดกลับไปหน้าตะกร้า
if (empty($_SESSION['cart'])) {
    header('Location: /cart.php');
    exit;
}

// 1. ดึงข้อมูลสินค้าในตะกร้ามาคำนวณยอดรวม
$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$sql = "SELECT * FROM books WHERE book_id IN ($ids)";
$res = mysqli_query($conn, $sql);

$subtotal = 0.00;
$total_quantity = 0;
$cart_books = [];

while ($row = mysqli_fetch_assoc($res)) {
    $bid = $row['book_id'];
    $qty = $_SESSION['cart'][$bid];
    $subtotal += ($row['price'] * $qty);
    $total_quantity += $qty;
    $cart_books[$bid] = [
        'title' => $row['title'],
        'price' => $row['price'],
        'qty' => $qty,
        'stock' => $row['stock']
    ];
}

// 2. คำนวณโปรโมชัน
// เซ็ต 3 เล่มลด 10%
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
        $set_res = mysqli_query($conn, "SELECT SUM(price) as set_price FROM books WHERE book_id IN ($set_ids)");
        $set_data = mysqli_fetch_assoc($set_res);
        $bundle_discount += ($set_data['set_price'] * 0.10);
    }
}

// ซื้อครบ 4 เล่มลด 5%
$qty_discount = ($total_quantity >= 4) ? ($subtotal * 0.05) : 0.00;
$total_discount = $bundle_discount + $qty_discount;
$price_after_discount = max(0, $subtotal - $total_discount);

// ส่งฟรี 500 บาทขึ้นไป
$shipping_fee = ($price_after_discount >= 500) ? 0.00 : 50.00;
$net_total = $price_after_discount + $shipping_fee;

// ดึงข้อมูลสมาชิกมาเตรียม Auto-fill ในฟอร์มหากเข้าสู่ระบบแล้ว
$default_name = '';
$default_phone = '';
$default_address = '';

if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $user_q = mysqli_query($conn, "SELECT fullname, phone, address FROM users WHERE user_id = $uid LIMIT 1");
    if ($u_data = mysqli_fetch_assoc($user_q)) {
        $default_name = $u_data['fullname'];
        $default_phone = $u_data['phone'];
        $default_address = $u_data['address'];
    }
}

$error_msg = '';
$success_msg = '';

// 3. ประมวลผลเมื่อกดปุ่มยืนยันสั่งซื้อ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($fullname) || empty($phone) || empty($address)) {
        $error_msg = 'กรุณากรอกข้อมูลที่อยู่จัดส่งให้ครบถ้วน';
    } elseif (!isset($_FILES['slip_image']) || $_FILES['slip_image']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = 'กรุณาแนบไฟล์สลิปหลักฐานการโอนเงิน';
    } else {
        $file_tmp = $_FILES['slip_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($file_ext, $allowed_exts)) {
            $error_msg = 'รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP) เท่านั้น';
        } else {
            $new_file_name = 'slip_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            $upload_path = __DIR__ . '/../uploads/slips/' . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 2;

                // บันทึกลงตาราง orders
                $insert_order = "INSERT INTO orders (user_id, subtotal, discount_amount, shipping_fee, net_total, slip_image, status) 
                                 VALUES ($user_id, $subtotal, $total_discount, $shipping_fee, $net_total, '$new_file_name', 'pending')";
                
                if (mysqli_query($conn, $insert_order)) {
                    $order_id = mysqli_insert_id($conn);

                    // บันทึกลง order_items และตัดยอด stock ใน books
                    foreach ($cart_books as $bid => $item) {
                        $qty = $item['qty'];
                        $price = $item['price'];

                        $insert_item = "INSERT INTO order_items (order_id, book_id, quantity, price_each) 
                                        VALUES ($order_id, $bid, $qty, $price)";
                        mysqli_query($conn, $insert_item);

                        $update_stock = "UPDATE books SET stock = stock - $qty WHERE book_id = $bid";
                        mysqli_query($conn, $update_stock);
                    }

                    // ล้างตะกร้าสินค้าใน Session
                    unset($_SESSION['cart']);
                    $success_msg = "สั่งซื้อสินค้าสำเร็จ! รหัสคำสั่งซื้อของคุณคือ #$order_id";
                } else {
                    $error_msg = 'เกิดข้อผิดพลาดในการบันทึก: ' . mysqli_error($conn);
                }
            } else {
                $error_msg = 'ไม่สามารถบันทึกไฟล์สลิปได้ ตรวจสอบโฟลเดอร์ uploads/slips';
            }
        }
    }
}

// ตัวแปรสำหรับ Header
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['fullname'] : '';
$user_role = $is_logged_in ? $_SESSION['role'] : '';
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $q) {
        $cart_count += $q;
    }
}

require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/checkout.php';
require_once __DIR__ . '/../views/layouts/footer.php';