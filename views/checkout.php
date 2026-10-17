<h2 style="margin-bottom: 15px;">สั่งซื้อและชำระเงิน</h2>

<?php if (!empty($success_msg)): ?>
    <div class="alert-box alert-success" style="text-align: center;">
        <h3><?= htmlspecialchars($success_msg); ?></h3>
        <p style="margin: 10px 0;">ทางร้านได้รับสลิปแล้ว กำลังรอผู้ดูแลตรวจสอบ</p>
        <a href="/index.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 6px 15px;">กลับหน้าแรก</a>
    </div>
<?php else: ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-box alert-error"><?= htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form action="/checkout.php" method="POST" enctype="multipart/form-data" class="two-col-layout">
        <!-- ฟอร์มข้อมูลจัดส่ง (คงเกณฑ์ข้อ 3 Validation ไว้ครบ) -->
        <div class="cart-table-panel">
            <h3 style="margin-bottom: 12px; border-bottom: 1px solid #ddd; padding-bottom: 6px;">1. ข้อมูลจัดส่งสินค้า</h3>
            
            <div class="form-group">
                <label>ชื่อ-นามสกุล *</label>
                <input type="text" name="fullname" required value="<?= htmlspecialchars($default_name ?? ''); ?>" class="form-control">
            </div>

            <div class="form-group">
                <label>เบอร์โทรศัพท์ (10 หลัก) *</label>
                <input type="tel" name="phone" required pattern="[0-9]{10}" title="กรอกเบอร์โทร 10 หลัก" value="<?= htmlspecialchars($default_phone ?? ''); ?>" class="form-control">
            </div>

            <div class="form-group">
                <label>ที่อยู่จัดส่ง *</label>
                <textarea name="address" rows="3" required class="form-control"><?= htmlspecialchars($default_address ?? ''); ?></textarea>
            </div>
        </div>

        <!-- ชำระเงิน -->
        <div class="summary-card">
            <h3 style="margin-bottom: 12px; border-bottom: 1px solid #ddd; padding-bottom: 6px;">2. ชำระเงิน</h3>
            
            <div style="background: #fff; border: 1px solid #ccc; padding: 12px; text-align: center; margin-bottom: 12px;">
                <p style="font-size: 13px;">โอนเงินเข้าบัญชี</p>
                <p style="font-weight: bold; margin: 4px 0;">ธนาคาร xxx เลขที่ xxx-x-xxxx-x</p>
                <p style="font-size: 16px; color: #0288d1; font-weight: bold;">ยอดโอน: ฿<?= number_format($net_total, 2); ?></p>
            </div>

            <div class="form-group">
                <label>แนบไฟล์สลิป *</label>
                <input type="file" name="slip_image" accept="image/*" required class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">ยืนยันการสั่งซื้อ</button>
            <a href="/cart.php" class="btn btn-outline">ย้อนกลับ</a>
        </div>
    </form>
<?php endif; ?>