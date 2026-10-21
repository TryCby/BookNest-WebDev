const { test, expect } = require('@playwright/test');

// TC-021 ถึง TC-030 สำหรับ BookNest
// ใช้ baseURL จาก playwright.config.js
// หากต้องใช้บัญชีทดสอบ ให้กำหนด TEST_CUSTOMER_EMAIL และ TEST_CUSTOMER_PASSWORD
const CUSTOMER_EMAIL = process.env.TEST_CUSTOMER_EMAIL || 'somchai@gmail.com';
const CUSTOMER_PASSWORD = process.env.TEST_CUSTOMER_PASSWORD || 'pass1234';

async function openFirstBook(page) {
  await page.goto('/index.php');
  const detailLink = page.locator('a[href*="book-detail.php?id="]').first();
  await expect(detailLink, 'ไม่พบลิงก์ดูรายละเอียดหนังสือ').toBeVisible();
  await detailLink.click();
  await expect(page).toHaveURL(/book-detail\.php\?id=/);
}

async function loginAsCustomer(page) {
  await page.goto('/login.php');
  await page.locator('input[name="email"]').fill(CUSTOMER_EMAIL);
  await page.locator('input[name="password"]').fill(CUSTOMER_PASSWORD);
  await page.getByRole('button', { name: 'เข้าสู่ระบบ', exact: true }).click();
  await expect(page).toHaveURL(/index\.php|\/$/);
}

test.describe('BookNest - Additional Test Cases TC-021 to TC-030', () => {
  test('TC-021', async ({ page }) => {
    await page.goto('/index.php?page=2');
    await expect(page).toHaveURL(/page=2/);
    const cards = page.locator('.book-grid').last().locator('.book-card');
    await expect(cards.first()).toBeVisible();
    expect(await cards.count()).toBeGreaterThan(0);
  });

  test('TC-022 เลือกแสดงหนังสือทุกหมวดหมู่ได้', async ({ page }) => {
    await page.goto('/index.php?cat=1');
    const categorySelect = page.locator('#category-select');
    await expect(categorySelect).toBeVisible();
    await categorySelect.selectOption('0');
    await expect(page).toHaveURL(/cat=0/);
    await expect(page.getByRole('heading', { name: /รายการหนังสือทั้งหมดในระบบ/ })).toBeVisible();
    await expect(page.locator('.book-grid').last().locator('.book-card').first()).toBeVisible();
  });

  test('TC-023 ภาพปกหนังสือรายการแรกโหลดได้จริง', async ({ page }) => {
    await page.goto('/index.php');
    const cover = page.locator('.book-grid').last().locator('.book-cover').first();
    await expect(cover).toBeVisible();
    await expect.poll(async () => cover.evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
  });

  test('TC-024 หน้ารายละเอียดหนังสือแสดงชื่อและราคา', async ({ page }) => {
    await openFirstBook(page);
    await expect(page.locator('h1').first()).toBeVisible();
    await expect(page.locator('body')).toContainText(/฿\s?[\d,]+(?:\.\d{2})?/);
  });

  test('TC-025 ผู้เยี่ยมชมที่ยังไม่เข้าสู่ระบบถูกจำกัดการเข้าหน้าโปรไฟล์', async ({ page }) => {
    await page.goto('/profile.php');
    await expect(page).toHaveURL(/login\.php|index\.php|\/$/);
    await expect(page.locator('input[name="new_password"]')).toHaveCount(0);
  });

  test('TC-026 เปิดหน้า Checkout เมื่อตะกร้าว่างต้องกลับไปหน้าตะกร้า', async ({ page }) => {
    // ใช้ browser context ใหม่ของแต่ละ test ทำให้ไม่มี session ตะกร้าจาก test อื่น
    await page.goto('/checkout.php');
    await expect(page).toHaveURL(/cart\.php/);
  });

  test('TC-027 สมัครสมาชิกด้วยรหัสผ่านยืนยันไม่ตรงกันต้องแสดงข้อผิดพลาด', async ({ page }) => {
    await page.goto('/register.php');
    await page.locator('input[name="fullname"]').fill('ทดสอบรหัสผ่านไม่ตรง');
    await page.locator('input[name="email"]').fill(`tc027_${Date.now()}@booknest-test.com`);
    await page.locator('input[name="phone"]').fill('0812345678');
    await page.locator('input[name="password"]').fill('pass1234');
    await page.locator('input[name="confirm_password"]').fill('different123');
    await page.getByRole('button', { name: 'ยืนยันการสมัครสมาชิก' }).click();
    await expect(page.getByText('รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน')).toBeVisible();
    await expect(page).toHaveURL(/register\.php/);
  });

  test('TC-028 ฟอร์มสมัครสมาชิกไม่ยอมรับเบอร์โทรศัพท์ที่ไม่ครบ 10 หลัก', async ({ page }) => {
    await page.goto('/register.php');
    const phone = page.locator('input[name="phone"]');
    await phone.fill('081234567'); // 9 หลัก
    await expect(phone).toHaveJSProperty('validity.valid', false);
    await expect(phone).toHaveAttribute('pattern', '[0-9]{10}');
  });

  test('TC-029 อีเมลในหน้าโปรไฟล์ไม่สามารถแก้ไขได้', async ({ page }) => {
    await loginAsCustomer(page);
    await page.goto('/profile.php');
    const emailField = page.locator('input[disabled]').first();
    await expect(emailField).toBeVisible();
    await expect(emailField).toHaveAttribute('disabled', '');
    await expect(emailField).toHaveValue(CUSTOMER_EMAIL);
  });

  test('TC-030 ผู้เยี่ยมชมไม่สามารถเปิดหน้า Admin Dashboard ได้โดยตรง', async ({ page }) => {
    await page.goto('/admin/dashboard.php');
    await expect(page).toHaveURL(/index\.php|\/$/);
    await expect(page.getByRole('heading', { name: 'รายงานสรุปสถิติร้านค้า' })).toHaveCount(0);
  });
});