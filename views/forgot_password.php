<div class="auth-box">
    <h2 style="text-align: center; margin-bottom: 20px; color: #0f172a;">🔒 ลืมรหัสผ่าน / ตั้งรหัสใหม่</h2>
    <p style="text-align: center; font-size: 13px; color: #64748b; margin-bottom: 20px;">
        กรุณากรอกอีเมลและเบอร์โทรศัพท์ที่ลงทะเบียนไว้เพื่อยืนยันตัวตน
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-error">⚠️ <?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert-box alert-success">✅ <?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form action="/forgot_password.php" method="POST">
        <div class="form-group">
            <label>อีเมลบัญชีผู้ใช้ *</label>
            <input type="email" name="email" required class="form-control" placeholder="เช่น somchai@gmail.com">
        </div>

        <div class="form-group">
            <label>เบอร์โทรศัพท์ที่เคยลงทะเบียน (10 หลัก) *</label>
            <input type="tel" name="phone" required pattern="[0-9]{10}" title="กรุณากรอกตัวเลข 10 หลัก" class="form-control" placeholder="เช่น 0891112222">
        </div>

        <hr style="border: 0; border-top: 1px dashed #e2e8f0; margin: 20px 0;">

        <div class="form-group">
            <label>กำหนดรหัสผ่านใหม่ *</label>
            <input type="password" name="new_password" required minlength="4" class="form-control" placeholder="กำหนดรหัสผ่านใหม่">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label>ยืนยันรหัสผ่านใหม่อีกครั้ง *</label>
            <input type="password" name="confirm_new_password" required minlength="4" class="form-control" placeholder="พิมพ์รหัสผ่านใหม่อีกครั้ง">
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 11px 0; font-size: 15px; font-weight: bold;">
            ยืนยันการเปลี่ยนรหัสผ่าน
        </button>
    </form>

    <div style="margin-top: 20px; text-align: center; font-size: 14px; color: #64748b; border-top: 1px solid #f1f5f9; padding-top: 15px;">
        จำรหัสผ่านได้แล้ว? <a href="/login.php" style="color: #0288d1; text-decoration: none; font-weight: bold;">กลับไปเข้าสู่ระบบ</a>
    </div>
</div>