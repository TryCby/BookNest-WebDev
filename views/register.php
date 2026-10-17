<div class="auth-box" style="max-width: 520px;">
    <h2 style="text-align: center; margin-bottom: 24px; color: #0f172a;">สมัครสมาชิก BookNest</h2>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert-box alert-success"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form action="/register.php" method="POST">
        <div class="form-group">
            <label>ชื่อ - นามสกุลจริง *</label>
            <input type="text" name="fullname" required class="form-control">
        </div>

        <div class="form-group">
            <label>อีเมล (Email) *</label>
            <input type="email" name="email" required class="form-control">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;" class="form-group">
            <div>
                <label>กำหนดรหัสผ่าน *</label>
                <input type="password" name="password" required minlength="4" class="form-control" placeholder="อย่างน้อย 4 ตัว">
            </div>
            <div>
                <label>ยืนยันรหัสผ่าน *</label>
                <input type="password" name="confirm_password" required minlength="4" class="form-control" placeholder="พิมพ์ซ้ำอีกครั้ง">
            </div>
        </div>

        <div class="form-group">
            <label>เบอร์โทรศัพท์ *</label>
            <input type="tel" name="phone" required pattern="[0-9]{10}" title="กรุณากรอกตัวเลข 10 หลัก เช่น 0812345678" class="form-control">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label>ที่อยู่จัดส่งพัสดุ</label>
            <textarea name="address" rows="3" class="form-control" placeholder="บ้านเลขที่, ถนน, แขวง, เขต, จังหวัด"></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 11px 0; font-size: 15px; font-weight: bold;">ยืนยันการสมัครสมาชิก</button>
    </form>

    <div style="margin-top: 18px; text-align: center; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9; padding-top: 14px;">
        มีบัญชีอยู่แล้ว? <a href="/login.php" style="color: #0288d1; text-decoration: none; font-weight: bold;">เข้าสู่ระบบที่นี่</a>
    </div>
</div>