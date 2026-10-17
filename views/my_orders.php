<h2 style="margin-bottom: 20px;">ประวัติการสั่งซื้อของฉัน</h2>

<?php if (empty($orders)): ?>
    <div class="auth-box" style="text-align: center; max-width: 600px;">
        <p style="color: #64748b;">คุณยังไม่มีประวัติการสั่งซื้อหนังสือ</p>
        <a href="/index.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 8px 20px; margin-top: 15px;">ไปหน้าร้านค้า</a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($orders as $ord): ?>
            <div class="cart-table-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 14px;">
                    <div>
                        <strong style="font-size: 16px;">คำสั่งซื้อ #<?= $ord['order_id']; ?></strong>
                        <span style="font-size: 13px; color: #64748b; margin-left: 10px;">วันที่: <?= $ord['created_at']; ?></span>
                    </div>
                    <div>
                        <?php 
                            $status = $ord['status'];
                            $badge_color = '#fef3c7'; $text_color = '#b45309'; $status_th = 'รอตรวจสอบสลิป';

                            if ($status === 'paid') {
                                $badge_color = '#dcfce7'; $text_color = '#166534'; $status_th = 'ชำระเงินแล้ว';
                            } elseif ($status === 'shipped') {
                                $badge_color = '#e0f2fe'; $text_color = '#0369a1'; $status_th = 'จัดส่งสินค้าแล้ว';
                            } elseif ($status === 'rejected') {
                                $badge_color = '#fee2e2'; $text_color = '#991b1b'; $status_th = 'สลิปไม่ถูกต้อง';
                            }
                        ?>
                        <span style="background: <?= $badge_color; ?>; color: <?= $text_color; ?>; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                            ● <?= $status_th; ?>
                        </span>
                    </div>
                </div>

                <!-- รายการหนังสือในบิล -->
                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px;">
                    <?php foreach ($ord['items'] as $item): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 14px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="/<?= htmlspecialchars($item['cover_image']); ?>" style="width: 35px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <span><?= htmlspecialchars($item['title']); ?> (x<?= $item['quantity']; ?>)</span>
                            </div>
                            <span style="font-weight: 500;">฿<?= number_format($item['price_each'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="background: #f8fafc; padding: 12px 16px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 14px;">
                    <div>
                        <?php if (!empty($ord['tracking_no'])): ?>
                            <span style="color: #0369a1; font-weight: bold;">📦 เลขพัสดุ: <?= htmlspecialchars($ord['tracking_no']); ?></span>
                        <?php else: ?>
                            <span style="color: #64748b;">รอดำเนินการตรวจสอบและแจ้งเลขพัสดุ</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span>ยอดชำระสุทธิ: </span>
                        <strong style="color: #0288d1; font-size: 16px;">฿<?= number_format($ord['net_total'], 2); ?></strong>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>