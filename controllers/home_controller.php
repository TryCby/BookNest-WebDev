<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['fullname'] : '';
$user_role = $is_logged_in ? $_SESSION['role'] : '';

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}

// 1. ดึงหมวดหมู่ทั้งหมดสำหรับทำแถบ Filter
$cat_sql = "SELECT * FROM categories ORDER BY category_id ASC";
$cat_res = mysqli_query($conn, $cat_sql);
$categories = [];
while ($row = mysqli_fetch_assoc($cat_res)) {
    $categories[] = $row;
}

// 2. ดึงหนังสือขายดี 5 เล่มเด่น (is_bestseller = 1)
$best_sql = "SELECT b.*, c.category_name 
             FROM books b 
             JOIN categories c ON b.category_id = c.category_id 
             WHERE b.is_bestseller = 1 
             ORDER BY b.book_id ASC LIMIT 5";
$best_res = mysqli_query($conn, $best_sql);
$bestsellers = [];
while ($row = mysqli_fetch_assoc($best_res)) {
    $bestsellers[] = $row;
}

// 3. เตรียมข้อมูลชุดเซ็ต 3 เล่มตามกลุ่มผู้สนใจ (ลด 10%)
$bundles_data = [
    [
        'title' => 'ชุดที่ 1: กลุ่มนักเรียน / นักศึกษา',
        'desc' => 'ฝึกกระบวนการคิด ค้นคว้าข้อมูล และเป้าหมายชีวิต',
        'ids' => [4, 10, 17]
    ],
    [
        'title' => 'ชุดที่ 2: กลุ่มคนทำงาน / มนุษย์เงินเดือน',
        'desc' => 'ทักษะการวิเคราะห์ สื่อสารเจรจา และสมดุลชีวิตงาน',
        'ids' => [2, 9, 18]
    ],
    [
        'title' => 'ชุดที่ 3: กลุ่มผู้บริหาร / เจ้าของกิจการ',
        'desc' => 'การคิดเชิงกลยุทธ์ ภาวะผู้นำ และขับเคลื่อนองค์กร',
        'ids' => [5, 11, 15]
    ],
    [
        'title' => 'ชุดที่ 4: กลุ่มครอบครัว / พัฒนาตนเอง',
        'desc' => 'ความสัมพันธ์ การใช้คำพูด และพลังใจดำเนินชีวิต',
        'ids' => [6, 16, 20]
    ],
    [
        'title' => 'ชุดที่ 5: กลุ่มนักพัฒนาสังคม / ข้าราชการ',
        'desc' => 'การพัฒนาทรัพยากรมนุษย์ ชุมชน และขับเคลื่อนสังคม',
        'ids' => [21, 22, 23]
    ]
];

$bundles = [];
foreach ($bundles_data as $b_item) {
    $in_ids = implode(',', $b_item['ids']);
    $b_sql = "SELECT * FROM books WHERE book_id IN ($in_ids)";
    $b_res = mysqli_query($conn, $b_sql);
    $b_books = [];
    $raw_price = 0;
    while ($bk = mysqli_fetch_assoc($b_res)) {
        $b_books[] = $bk;
        $raw_price += $bk['price'];
    }
    $discount_price = $raw_price * 0.90; // ลด 10%
    $bundles[] = [
        'title' => $b_item['title'],
        'desc' => $b_item['desc'],
        'books' => $b_books,
        'raw_price' => $raw_price,
        'discount_price' => $discount_price,
        'ids' => $b_item['ids']
    ];
}

// 4. ระบบค้นหา กรองหมวดหมู่ และตัดแบ่งหน้า (Pagination หน้าละ 6 เล่ม)
$filter_cat = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$search_q = isset($_GET['q']) ? trim($_GET['q']) : '';

$where_clauses = [];
if ($filter_cat > 0) {
    $where_clauses[] = "b.category_id = $filter_cat";
}
if (!empty($search_q)) {
    $safe_q = mysqli_real_escape_string($conn, $search_q);
    $where_clauses[] = "b.title LIKE '%$safe_q%'";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

$limit = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as total FROM books b $where_sql";
$count_res = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $limit);

$catalog_sql = "SELECT b.*, c.category_name 
                FROM books b 
                JOIN categories c ON b.category_id = c.category_id 
                $where_sql 
                ORDER BY b.book_id ASC 
                LIMIT $limit OFFSET $offset";
$catalog_res = mysqli_query($conn, $catalog_sql);
$catalog_books = [];
while ($row = mysqli_fetch_assoc($catalog_res)) {
    $catalog_books[] = $row;
}

require_once __DIR__ . '/../views/layouts/header.php';
require_once __DIR__ . '/../views/home.php';
require_once __DIR__ . '/../views/layouts/footer.php';