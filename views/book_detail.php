<section class="detail-card">
    <div class="detail-img-col">
        <img src="/<?= htmlspecialchars($book['cover_image']); ?>" alt="<?= htmlspecialchars($book['title']); ?>">
    </div>
    
    <div class="detail-info-col">
        <div>
            <p style="font-size: 13px; color: #555;">หมวดหมู่: <?= htmlspecialchars($book['category_name']); ?></p>
            <h1 style="font-size: 22px; margin: 10px 0;"><?= htmlspecialchars($book['title']); ?></h1>
            <p class="book-price" style="font-size: 20px;">฿<?= number_format($book['price'], 2); ?></p>
            
            <div style="background: #fff; padding: 10px; border: 1px solid #ccc; margin: 15px 0;">
                <p style="font-size: 14px;">
                    สถานะ: 
                    <?php if ($book['stock'] > 0): ?>
                        <span style="color: green;">มีสินค้า (คงเหลือ <?= $book['stock']; ?> เล่ม)</span>
                    <?php else: ?>
                        <span style="color: red;">สินค้าหมด</span>
                    <?php endif; ?>
                </p>
                <p style="font-size: 12px; color: #666; margin-top: 5px;">ซื้อครบ 500 บาท จัดส่งฟรี</p>
            </div>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="/cart.php?action=add&id=<?= $book['book_id']; ?>" class="btn btn-primary" style="flex: 1;">เพิ่มลงตะกร้า</a>
            <a href="/index.php" class="btn btn-outline" style="flex: 0 0 100px;">ย้อนกลับ</a>
        </div>
    </div>
</section>