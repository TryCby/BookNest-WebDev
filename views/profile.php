<div class="auth-box" style="max-width: 600px;">
    <h2 style="margin-bottom: 20px; color: #0f172a;">ข้อมูลบัญชีผู้ใช้ (Profile)</h2>

    <?php if (!empty($msg)): ?>
        <div class="alert-box alert-success"><?= htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="alert-box alert-error"><?= htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>อีเมล (ไม่สามารถเปลี่ยนได้)</label>
            <input type="text" value="<?= htmlspecialchars($user['email']); ?>" disabled class="form-control" style="background: #f8fafc; color: #64748b;">
        </div>

        <div class="form-group">
            <label>ชื่อ - นามสกุล *</label>
            <input type="text" name="fullname" required value="<?= htmlspecialchars($user['fullname']); ?>" class="form-control">
        </div>

        <div class="form-group">
            <label>เบอร์โทรศัพท์ *</label>
            <input type="tel" name="phone" required pattern="[0-9]{10}" title="กรุณากรอกตัวเลข 10 หลัก" value="<?= htmlspecialchars($user['phone']); ?>" class="form-control">
        </div>

        <div class="form-group">
            <label>ที่อยู่จัดส่งพัสดุเริ่มต้น</label>
            <textarea name="address" rows="3" class="form-control"><?= htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label>เปลี่ยนรหัสผ่านใหม่</label>
            <input type="password" name="new_password" minlength="4" placeholder="กรอกรหัสผ่านใหม่" class="form-control">
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 11px 0; font-size: 15px; font-weight: bold;">
                บันทึกการแก้ไข
            </button>
            <a href="/index.php" class="btn btn-outline" style="flex: 0 0 100px; padding: 11px 0;">กลับหน้าแรก</a>
        </div>
    </form>
</div>