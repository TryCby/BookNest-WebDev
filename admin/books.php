<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ตรวจสอบสิทธิ์แอดมิน (RBAC)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../db.php';

$msg = '';
$err = '';

// 2. จัดการ Action (CRUD Operations)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE CATEGORY
    if ($action === 'create_category') {
        $category_name = trim($_POST['category_name'] ?? '');
        if (!empty($category_name)) {
            $safe_cat = mysqli_real_escape_string($conn, $category_name);
            $check_cat = mysqli_query($conn, "SELECT category_id FROM categories WHERE category_name = '$safe_cat' LIMIT 1");
            if (mysqli_num_rows($check_cat) > 0) {
                $err = "หมวดหมู่นี้มีอยู่ในระบบแล้ว";
            } else {
                $sql = "INSERT INTO categories (category_name) VALUES ('$safe_cat')";
                if (mysqli_query($conn, $sql)) {
                    $msg = "เพิ่มหมวดหมู่ '$safe_cat' เรียบร้อยแล้ว";
                } else {
                    $err = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
                }
            }
        } else {
            $err = "กรุณากรอกชื่อหมวดหมู่";
        }
    }
    // CREATE BOOK
    elseif ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 1);
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
        
        $cover_image = 'Image/Thinking/Thinking_1.jpg';

        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['cover_file']['tmp_name'];
            $file_name = $_FILES['cover_file']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed_exts)) {
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = 'book_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    $cover_image = 'uploads/' . $new_filename;
                }
            } else {
                $err = "ไฟล์รูปภาพต้องเป็นนามสกุล jpg, png หรือ webp เท่านั้น";
            }
        }

        if (empty($err)) {
            if (!empty($title) && $price > 0) {
                $safe_title = mysqli_real_escape_string($conn, $title);
                $sql = "INSERT INTO books (category_id, title, price, stock, cover_image, is_bestseller) 
                        VALUES ($category_id, '$safe_title', $price, $stock, '$cover_image', $is_bestseller)";
                if (mysqli_query($conn, $sql)) {
                    $msg = "เพิ่มหนังสือใหม่เรียบร้อยแล้ว";
                } else {
                    $err = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
                }
            } else {
                $err = "กรุณากรอกชื่อหนังสือและราคาให้ถูกต้อง";
            }
        }
    }
    // UPDATE
    elseif ($action === 'update') {
        $book_id = intval($_POST['book_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;

        if ($book_id > 0 && $price > 0) {
            $sql = "UPDATE books SET price = $price, stock = $stock, is_bestseller = $is_bestseller WHERE book_id = $book_id";
            if (mysqli_query($conn, $sql)) {
                $msg = "อัปเดตข้อมูลหนังสือรหัส #$book_id สำเร็จ";
            } else {
                $err = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
            }
        }
    }
    // DELETE
    elseif ($action === 'delete') {
        $book_id = intval($_POST['book_id'] ?? 0);
        if ($book_id > 0) {
            $sql = "DELETE FROM books WHERE book_id = $book_id";
            if (mysqli_query($conn, $sql)) {
                $msg = "ลบหนังสือรหัส #$book_id ออกจากระบบเรียบร้อย";
            } else {
                $err = "ไม่สามารถลบได้เนื่องจากมีประวัติในคำสั่งซื้อ (Foreign Key Constraint)";
            }
        }
    }
}

// 3. ดึงรายชื่อหมวดหมู่
$cats_res = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_id ASC");
$categories = [];
while ($c = mysqli_fetch_assoc($cats_res)) {
    $categories[] = $c;
}

// 4. ดึงรายการหนังสือ
$filter_cat = intval($_GET['cat'] ?? 0);
$where_sql = ($filter_cat > 0) ? "WHERE b.category_id = $filter_cat" : "";

$books_sql = "SELECT b.*, c.category_name 
              FROM books b 
              JOIN categories c ON b.category_id = c.category_id 
              $where_sql 
              ORDER BY b.book_id ASC";
$books_res = mysqli_query($conn, $books_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคลังหนังสือและหมวดหมู่ - BookNest Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<header class="admin-nav">
    <div class="admin-nav-left">
        <span class="brand-title">BookNest</span>
        <a href="/admin/dashboard.php">แดชบอร์ดสรุปผล</a>
        <a href="/admin/orders.php">จัดการคำสั่งซื้อ</a>
        <a href="/admin/books.php" class="active">จัดการหนังสือและหมวดหมู่</a>
        <a href="/admin/users.php">จัดการสมาชิก</a>
        <a href="/index.php" target="_blank">หน้าร้านค้า</a>
    </div>
    <div>
        <span class="admin-nav-user"><?= htmlspecialchars($_SESSION['fullname']); ?></span>
        <a href="/logout.php" class="admin-nav-logout">ออกจากระบบ</a>
    </div>
</header>

<main class="admin-container">
    
    <?php if (!empty($msg)): ?>
        <div class="alert-box alert-success"><?= htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="alert-box alert-error"><?= htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <!-- โซนฟอร์ม: เพิ่มหมวดหมู่ใหม่ & เพิ่มหนังสือใหม่ -->
    <div class="admin-form-grid">
        
        <!-- 1. เพิ่มหมวดหมู่ใหม่ -->
        <div class="card-panel">
            <h3>เพิ่มหมวดหมู่ใหม่</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_category">
                <div style="margin-bottom: 12px;">
                    <label class="form-label-small">ชื่อหมวดหมู่ *</label>
                    <input type="text" name="category_name" required placeholder="เช่น จิตวิทยา, เทคโนโลยี" class="form-input-admin">
                </div>
                <button type="submit" class="btn-admin-submit">+ บันทึกหมวดหมู่</button>
            </form>
        </div>

        <!-- 2. เพิ่มหนังสือใหม่ -->
        <div class="card-panel">
            <h3>เพิ่มหนังสือเล่มใหม่เข้าคลัง</h3>
            <form method="POST" enctype="multipart/form-data" class="book-form-grid">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="form-label-small">ชื่อหนังสือ *</label>
                    <input type="text" name="title" required placeholder="ชื่อหนังสือ" class="form-input-admin">
                </div>
                <div>
                    <label class="form-label-small">หมวดหมู่ *</label>
                    <select name="category_id" class="form-input-admin">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id']; ?>"><?= htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label-small">ราคา (฿) *</label>
                    <input type="number" step="0.01" name="price" required placeholder="250.00" class="form-input-admin">
                </div>
                <div>
                    <label class="form-label-small">สต็อก *</label>
                    <input type="number" name="stock" required placeholder="20" class="form-input-admin">
                </div>
                
                <div class="form-full-row">
                    <label class="form-label-small">รูปภาพหน้าปก</label>
                    <input type="file" name="cover_file" accept="image/*">
                </div>
                <div>
                    <label class="form-label-small">
                        <input type="checkbox" name="is_bestseller" value="1"> หนังสือขายดี
                    </label>
                </div>
                <div>
                    <button type="submit" class="btn-admin-success">+ เพิ่มหนังสือ</button>
                </div>
            </form>
        </div>

    </div>

    <!-- ตารางรายการหนังสือ -->
    <div class="card-panel">
        <div class="admin-page-header">
            <h3>รายการหนังสือทั้งหมดในคลัง (<?= mysqli_num_rows($books_res); ?> รายการ)</h3>
            
            <form method="GET" action="/admin/books.php" class="admin-filter-group">
                <label>เลือกหมวดหมู่:</label>
                <select name="cat" onchange="this.form.submit()" class="form-input-admin" style="min-width: 220px;">
                    <option value="0">-- แสดงหนังสือทุกหมวดหมู่ --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id']; ?>" <?= ($filter_cat == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>รหัส</th>
                    <th>รูปปก</th>
                    <th>ชื่อหนังสือ</th>
                    <th>หมวดหมู่</th>
                    <th style="width: 110px;">ราคา (฿)</th>
                    <th style="width: 100px;">สต็อก (เล่ม)</th>
                    <th style="text-align: center;">ขายดี</th>
                    <th style="text-align: center; width: 150px;">การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($b = mysqli_fetch_assoc($books_res)): ?>
                    <tr>
                        <td style="color: #94a3b8;">#<?= $b['book_id']; ?></td>
                        <td>
                            <img src="/<?= htmlspecialchars($b['cover_image']); ?>" class="table-book-img">
                        </td>
                        <td style="font-weight: 500; color: #0f172a;">
                            <?= htmlspecialchars($b['title']); ?>
                        </td>
                        <td style="color: #64748b;">
                            <?= htmlspecialchars($b['category_name']); ?>
                        </td>

                        <form method="POST">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="book_id" value="<?= $b['book_id']; ?>">
                            <td>
                                <input type="number" step="0.01" name="price" value="<?= $b['price']; ?>" required class="table-input-price">
                            </td>
                            <td>
                                <input type="number" name="stock" value="<?= $b['stock']; ?>" required class="table-input-stock <?= ($b['stock'] < 18) ? 'stock-low-warning' : ''; ?>">
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="is_bestseller" value="1" <?= ($b['is_bestseller'] == 1) ? 'checked' : ''; ?>>
                            </td>
                            <td style="text-align: center;">
                                <button type="submit" class="btn-table-edit">บันทึก</button>
                        </form>

                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('แน่ใจหรือไม่ว่าต้องการลบหนังสือเล่มนี้?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="book_id" value="<?= $b['book_id']; ?>">
                            <button type="submit" class="btn-table-del">ลบ</button>
                        </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>