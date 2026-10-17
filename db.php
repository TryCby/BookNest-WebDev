<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "booknest_db";

$conn = mysqli_connect($host, $user, $pass, $db_name);

if (!$conn) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>


