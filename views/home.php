<!-- แบนเนอร์โปรโมชัน -->
<section class="promo-banner">
    <h1 style="margin-bottom: 8px; font-size: 24px;">Welcome to BookNest </h1>
    <p style="font-size: 15px; opacity: 0.95;">ศูนย์รวมหนังสือพัฒนาตนเอง การบริหาร และการสร้างชาติ</p>
    <div class="promo-tags">
        <span class="promo-tag-item">ส่งฟรี เมื่อซื้อครบ 500 บาท</span>
        <span class="promo-tag-item">ซื้อครบ 4 เล่มขึ้นไป ลดเพิ่ม 5%</span>
        <span class="promo-tag-item">เซ็ตผู้สนใจ 3 เล่ม ลดพิเศษ 10%</span>
    </div>
</section>

<!-- โซนที่ 1: เล่มขายดี -->
<section style="margin-bottom: 35px;">
    <h2 style="margin-bottom: 16px;">หนังสือขายดีประจำร้าน</h2>
    <div class="book-grid">
        <?php foreach ($bestsellers as $book): ?>
            <div class="book-card">
                <span class="book-badge-bestseller">BEST</span>
                <div>
                    <img src="/<?= htmlspecialchars($book['cover_image']); ?>" alt="<?= htmlspecialchars($book['title']); ?>" class="book-cover">
                    <p style="font-size: 12px; color: #64748b; margin-top: 8px;"><?= htmlspecialchars($book['category_name']); ?></p>
                    <h3 style="font-size: 15px; margin: 4px 0;"><?= htmlspecialchars($book['title']); ?></h3>
                </div>
                <div>
                    <p class="book-price">
                        ฿<?= number_format($book['price'], 2); ?>
                    </p>
                    <a href="/book-detail.php?id=<?= $book['book_id']; ?>" class="btn btn-outline">ดูรายละเอียด</a>
                    <a href="/cart.php?action=add&id=<?= $book['book_id']; ?>" class="btn btn-primary">เพิ่มลงตะกร้า</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- โซนที่ 2: ชุดหนังสือแนะนำประจำกลุ่มผู้สนใจ 3 เล่ม (ลด 10%) -->
<section style="margin-bottom: 35px;">
    <h2>ชุดหนังสือแนะนำประจำกลุ่มผู้สนใจ (ลด 10% พร้อมส่งฟรี)</h2>
    <div class="bundle-grid">
        <?php foreach ($bundles as $bun): ?>
            <div class="bundle-card">
                <div>
                    <h3 style="font-size: 15px; margin: 8px 0 4px 0;"><?= htmlspecialchars($bun['title']); ?></h3>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 10px;"><?= htmlspecialchars($bun['desc']); ?></p>
                    
                    <div style="display: flex; gap: 6px; margin-bottom: 12px;">
                        <?php foreach ($bun['books'] as $bk): ?>
                            <img src="/<?= htmlspecialchars($bk['cover_image']); ?>" title="<?= htmlspecialchars($bk['title']); ?>" style="width: 31%; height: 140px; object-fit: contain; background: #fff; border-radius: 4px; border: 1px solid #e2e8f0;">
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <div style="margin-bottom: 10px;">
                        <strong style="font-size: 17px; color: #16a34a; margin-left: 6px;">฿<?= number_format($bun['discount_price'], 2); ?></strong>
                        <span style="text-decoration: line-through; color: #94a3b8; font-size: 13px;">฿<?= number_format($bun['raw_price'], 2); ?></span>
                    </div>
                    <?php 
                        $add_bundle_query = [];
                        foreach ($bun['ids'] as $id_val) {
                            $add_bundle_query[] = "bundle_ids[]=" . $id_val;
                        }
                        $bundle_url = "/cart.php?action=add_bundle&" . implode('&', $add_bundle_query);
                    ?>
                    <a href="<?= $bundle_url; ?>" class="btn btn-primary" style="padding: 9px 0; font-size: 14px;">ซื้อทั้งชุด 3 เล่ม</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- โซนที่ 3: แคตตาล็อกหนังสือทั้งหมดในร้าน -->
<section style="margin-bottom: 40px;">
    <h2> รายการหนังสือทั้งหมดในระบบ (<?= $total_rows; ?> เล่ม)</h2>

    <!-- ตัวกรองหมวดหมู่ (แบบ Dropdown) -->
    <div style="margin: 12px 0 18px 0; display: flex; align-items: center; gap: 10px;">
        <label for="category-select" style="font-size: 14px; color: #444;">เลือกหมวดหมู่หนังสือ:</label>
        <select id="category-select" class="form-control" style="width: auto; min-width: 200px; display: inline-block; cursor: pointer;" onchange="location.href='/index.php?cat=' + this.value;">
            <option value="0" <?= ($filter_cat === 0) ? 'selected' : ''; ?>>-- แสดงทั้งหมด --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id']; ?>" <?= ($filter_cat == $cat['category_id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($cat['category_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ตารางการ์ดหนังสือ -->
    <div class="book-grid">
        <?php if (empty($catalog_books)): ?>
            <p style="color: #64748b; grid-column: 1 / -1; text-align: center; padding: 30px;">ไม่พบหนังสือในหมวดนี้</p>
        <?php else: ?>
            <?php foreach ($catalog_books as $book): ?>
                <div class="book-card">
                    <div>
                        <img src="/<?= htmlspecialchars($book['cover_image']); ?>" alt="<?= htmlspecialchars($book['title']); ?>" class="book-cover">
                        <p style="font-size: 12px; color: #64748b; margin-top: 8px;"><?= htmlspecialchars($book['category_name']); ?></p>
                        <h3 style="font-size: 15px; margin: 4px 0;"><?= htmlspecialchars($book['title']); ?></h3>
                    </div>
                    <div>
                        <p class="book-price">
                            ฿<?= number_format($book['price'], 2); ?>
                        </p>
                        <a href="/book-detail.php?id=<?= $book['book_id']; ?>" class="btn btn-outline">ดูรายละเอียด</a>
                        <a href="/cart.php?action=add&id=<?= $book['book_id']; ?>" class="btn btn-primary">เพิ่มลงตะกร้า</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ปุ่มแบ่งหน้า (Pagination) -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-bar">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="/index.php?page=<?= $p; ?><?= ($filter_cat > 0) ? '&cat=' . $filter_cat : ''; ?>" class="page-btn <?= ($page == $p) ? 'active' : ''; ?>">
                    <?= $p; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>