<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ถ้าเข้าสู่ระบบอยู่แล้ว ให้ส่งกลับหน้าแรกทันที
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$error = '';
$success = '';

// จัดการเมื่อมีการกดส่งฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth_action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error = 'กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน';
        } else {
            $safe_email = mysqli_real_escape_string($conn, $email);
            $sql = "SELECT * FROM users WHERE email = '$safe_email' LIMIT 1";
            $res = mysqli_query($conn, $sql);

            if ($row = mysqli_fetch_assoc($res)) {
                if ($password === $row['password']) {
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['fullname'] = $row['fullname'];
                    $_SESSION['role'] = $row['role'];

                    header('Location: /index.php');
                    exit;
                } else {
                    $error = 'รหัสผ่านไม่ถูกต้อง';
                }
            } else {
                $error = 'ไม่พบอีเมลนี้ในระบบ';
            }
        }
    } elseif ($auth_action === 'register') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($fullname) || empty($email) || empty($password) || empty($phone)) {
            $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'รูปแบบอีเมลไม่ถูกต้อง';
        } elseif ($password !== $confirm_password) {
            $error = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error = 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลักเท่านั้น';
        } else {
            $safe_email = mysqli_real_escape_string($conn, $email);
            $check_sql = "SELECT user_id FROM users WHERE email = '$safe_email' LIMIT 1";
            $check_res = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_res) > 0) {
                $error = 'อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น';
            } else {
                $safe_name = mysqli_real_escape_string($conn, $fullname);
                $safe_phone = mysqli_real_escape_string($conn, $phone);
                $safe_address = mysqli_real_escape_string($conn, $address);
                $safe_pass = mysqli_real_escape_string($conn, $password);

                $ins_sql = "INSERT INTO users (fullname, email, password, phone, address, role) 
                            VALUES ('$safe_name', '$safe_email', '$safe_pass', '$safe_phone', '$safe_address', 'customer')";
                
                if (mysqli_query($conn, $ins_sql)) {
                    $success = 'สมัครสมาชิกสำเร็จ! กำลังพาไปหน้าเข้าสู่ระบบ...';
                    header('Refresh: 2; url=/login.php');
                } else {
                    $error = 'เกิดข้อผิดพลาดในการบันทึก: ' . mysqli_error($conn);
                }
            }
        }
    } elseif ($auth_action === 'forgot_password') {
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_new_password = trim($_POST['confirm_new_password'] ?? '');

        if (empty($email) || empty($phone) || empty($new_password)) {
            $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } elseif ($new_password !== $confirm_new_password) {
            $error = 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน';
        } else {
            $safe_email = mysqli_real_escape_string($conn, $email);
            $safe_phone = mysqli_real_escape_string($conn, $phone);

            // ตรวจสอบว่ามีผู้ใช้อีเมลและเบอร์โทรนี้คู่กันจริงไหม
            $check_user = "SELECT user_id FROM users WHERE email = '$safe_email' AND phone = '$safe_phone' LIMIT 1";
            $res = mysqli_query($conn, $check_user);

            if (mysqli_num_rows($res) > 0) {
                $safe_pass = mysqli_real_escape_string($conn, $new_password);
                $update_sql = "UPDATE users SET password = '$safe_pass' WHERE email = '$safe_email'";
                
                if (mysqli_query($conn, $update_sql)) {
                    $success = 'เปลี่ยนรหัสผ่านสำเร็จ! กำลังพาไปหน้าเข้าสู่ระบบ...';
                    header('Refresh: 2; url=/login.php');
                } else {
                    $error = 'เกิดข้อผิดพลาดในการอัปเดต: ' . mysqli_error($conn);
                }
            } else {
                $error = 'ข้อมูลอีเมลหรือเบอร์โทรศัพท์ไม่ตรงกับในระบบ';
            }
        }
    }
}

// ตัวแปรสำหรับ Header Navbar
$is_logged_in = false;
$user_name = '';
$user_role = '';
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $q) {
        $cart_count += $q;
    }
}

require_once __DIR__ . '/../views/layouts/header.php';
if ($auth_action === 'login') {
    require_once __DIR__ . '/../views/login.php';
} elseif ($auth_action === 'register') {
    require_once __DIR__ . '/../views/register.php';
} elseif ($auth_action === 'forgot_password') {
    require_once __DIR__ . '/../views/forgot_password.php';
}
require_once __DIR__ . '/../views/layouts/footer.php';