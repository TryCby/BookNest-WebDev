<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ล้าง Session ข้อมูลผู้ใช้ แต่ยังคงตะกร้าสินค้าไว้
unset($_SESSION['user_id']);
unset($_SESSION['email']);
unset($_SESSION['fullname']);
unset($_SESSION['role']);

header('Location: /index.php');
exit;