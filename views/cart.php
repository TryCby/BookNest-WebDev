<h2 style="margin-bottom: 20px;">ตะกร้าสินค้าของคุณ</h2>

<?php if (empty($cart_items)): ?>
    <div class="auth-box" style="text-align: center; max-width: 600px;">
        <p style="font-size: 18px; color: #64748b; margin-bottom: 15px;">ยังไม่มีหนังสือในตะกร้าของคุณ</p>
        <a href="/index.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 10px 24px;">เลือกซื้อหนังสือ</a>
    </div>
<?php else: ?>
    <div class="two-col-layout">
        <!-- รายการตารางหนังสือ -->
        <div class="cart-table-panel">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>หนังสือ</th>
                        <th style="text-align: center;">ราคา</th>
                        <th style="text-align: center;">จำนวน</th>
                        <th style="text-align: right;">รวม</th>
                        <th style="text-align: center;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td style="display: flex; align-items: center; gap: 12px;">
                                <img src="/<?= htmlspecialchars($item['cover_image']); ?>" style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px;">
                                <div>
                                    <strong style="font-size: 14px;"><?= htmlspecialchars($item['title']); ?></strong>
                                    <p style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($item['category_name']); ?></p>
                                </div>
                            </td>
                            <td style="text-align: center; font-size: 14px;">฿<?= number_format($item['price'], 2); ?></td>
                            <td style="text-align: center;">
                                <a href="/cart.php?action=decrease&id=<?= $item['book_id']; ?>" style="padding: 2px 8px; background: #e2e8f0; border-radius: 4px; text-decoration: none; color: #000;">-</a>
                                <span style="margin: 0 8px; font-weight: bold;"><?= $item['qty']; ?></span>
                                <a href="/cart.php?action=add&id=<?= $item['book_id']; ?>" style="padding: 2px 8px; background: #e2e8f0; border-radius: 4px; text-decoration: none; color: #000;">+</a>
                            </td>
                            <td style="text-align: right; font-weight: bold; font-size: 14px;">฿<?= number_format($item['line_total'], 2); ?></td>
                            <td style="text-align: center;">
                                <a href="/cart.php?action=remove&id=<?= $item['book_id']; ?>" style="color: #dc2626; text-decoration: none; font-size: 13px;">ลบ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- กล่องสรุปการคำนวณเงินและโปรโมชัน -->
        <div class="summary-card">
            <h3 style="margin-bottom: 16px; font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">สรุปคำสั่งซื้อ</h3>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
                <span style="color: #64748b;">รวมราคาสินค้า (<?= $total_quantity; ?> เล่ม)</span>
                <span>฿<?= number_format($subtotal, 2); ?></span>
            </div>

            <?php if ($bundle_discount > 0): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #16a34a;">
                    <span>ส่วนลดชุดเซ็ต 3 เล่ม (10%)</span>
                    <span>-฿<?= number_format($bundle_discount, 2); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($qty_discount > 0): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #16a34a;">
                    <span>ส่วนลดซื้อครบ 4 เล่ม (5%)</span>
                    <span>-฿<?= number_format($qty_discount, 2); ?></span>
                </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
                <span style="color: #64748b;">ค่าจัดส่ง</span>
                <span>
                    <?php if ($shipping_fee === 0.00): ?>
                        <strong style="color: #16a34a;">ฟรี</strong>
                    <?php else: ?>
                        ฿<?= number_format($shipping_fee, 2); ?>
                    <?php endif; ?>
                </span>
            </div>

            <?php if ($shipping_fee > 0): ?>
                <p style="font-size: 12px; color: #f59e0b; margin-bottom: 12px;">
                    ซื้อเพิ่มอีก ฿<?= number_format(500 - $price_after_discount, 2); ?> เพื่อรับสิทธิ์ส่งฟรี!
                </p>
            <?php endif; ?>

            <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 15px 0;">

            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 18px; font-weight: bold;">
                <span>ยอดสุทธิ</span>
                <span style="color: #0288d1;">฿<?= number_format($net_total, 2); ?></span>
            </div>

            <a href="/checkout.php" class="btn btn-primary" style="padding: 12px 0; font-size: 16px;">
                ดำเนินการสั่งซื้อ
            </a>
            <a href="/index.php" class="btn btn-outline" style="margin-top: 8px;">เลือกดูหนังสือเพิ่ม</a>
        </div>
    </div>
<?php endif; ?>