<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ต้องล็อกอินก่อนถึงจะเข้าดูโปรไฟล์ได้
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$msg = '';
$err = '';

// บันทึกการแก้ไขข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $new_pass = trim($_POST['new_password'] ?? '');

    if (empty($fullname) || empty($phone)) {
        $err = "กรุณากรอกชื่อและเบอร์โทรศัพท์";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $err = "เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก";
    } else {
        $safe_name = mysqli_real_escape_string($conn, $fullname);
        $safe_phone = mysqli_real_escape_string($conn, $phone);
        $safe_address = mysqli_real_escape_string($conn, $address);

        $update_sql = "UPDATE users SET fullname = '$safe_name', phone = '$safe_phone', address = '$safe_address' ";

        // ถ้ามีการระบุรหัสผ่านใหม่
        if (!empty($new_pass)) {
            $safe_pass = mysqli_real_escape_string($conn, $new_pass);
            $update_sql .= ", password = '$safe_pass' ";
        }

        $update_sql .= "WHERE user_id = $user_id";

        if (mysqli_query($conn, $update_sql)) {
            $_SESSION['fullname'] = $fullname;
            $msg = "อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว";
        } else {
            $err = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
        }
    }
}

// ดึงข้อมูลปัจจุบัน
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id LIMIT 1");
$user = mysqli_fetch_assoc($user_res);

$is_logged_in = true;
$user_name = $_SESSION['fullname'];
$user_role = $_SESSION['role'];
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/profile.php';
require_once __DIR__ . '/../views/layouts/footer.php';