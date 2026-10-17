<div class="auth-box">
    <h2 style="text-align: center; margin-bottom: 15px;">เข้าสู่ระบบ</h2>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="/login.php" method="POST">
        <div class="form-group">
            <label>อีเมล *</label>
            <input type="email" name="email" required class="form-control">
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <label>รหัสผ่าน *</label>
                <a href="/forgot_password.php" style="font-size: 12px; text-decoration: none;">ลืมรหัสผ่าน?</a>
            </div>
            <input type="password" name="password" required class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
    </form>

    <div style="margin-top: 15px; text-align: center; font-size: 13px; border-top: 1px solid #eee; padding-top: 10px;">
        ยังไม่มีบัญชี? <a href="/register.php">สมัครสมาชิก</a>
    </div>
</div>