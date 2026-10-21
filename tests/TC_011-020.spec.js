
const { test, expect, chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

// =====================================
// BookNest Configuration
// =====================================
const BASE_URL = 'http://booknest-testing.infinityfree.me';
const USERNAME = 's6704062610011@email.kmutnb.ac.th';
const PASSWORD = 'pass';


const HOME = `${BASE_URL}/index.php`;
const CART = `${BASE_URL}/cart.php`;
const CHECKOUT = `${BASE_URL}/checkout.php`;
const ORDERS = `${BASE_URL}/my-orders.php`;

// =====================================
// Shared Browser / Page
// =====================================
let browser;
let context;
let page;

test.describe.serial('BookNest - Book and Cart Tests', () => {
  test.beforeAll(async () => {
    // เปิด Browser เพียงครั้งเดียว
    browser = await chromium.launch({
      headless: true,
      slowMo: 50
    });

    // สร้าง Context และ Page เพียงครั้งเดียว
    context = await browser.newContext();
    page = await context.newPage();

    // ล็อกอินครั้งเดียวก่อนเริ่มเทสทั้งหมด
    await login(page);
  });

  test.afterAll(async () => {
    // ปิด Browser หลังเทสทั้งหมดเสร็จ
    await browser?.close();
  });

  // =====================================
  // Helpers
  // =====================================
  
async function login(page) {
  // 1. เปิดหน้าแรก
  await page.goto(HOME, {
    waitUntil: 'domcontentloaded'
  });

  // 2. ค้นหาและคลิกปุ่มเข้าสู่ระบบบนหน้าแรก
  const loginButton = page.getByRole('link', {
    name: 'เข้าสู่ระบบ',
    exact: true
  });

  if (await loginButton.isVisible().catch(() => false)) {
    await loginButton.click();

    await page.waitForLoadState('domcontentloaded').catch(() => {});
  }

  // 3. รอช่องกรอกอีเมลและรหัสผ่าน
  const emailField = page.locator(
    'input[type="email"], input[name="email"], input[name="อีเมล"]'
  ).first();

  const passwordField = page.locator(
    'input[type="password"], input[name="password"], input[name="รหัสผ่าน"]'
  ).first();

  // ถ้าไม่มีช่องรหัสผ่าน แสดงว่าอาจล็อกอินอยู่แล้ว
  if (!(await passwordField.isVisible().catch(() => false))) {
    // console.log('ไม่พบฟอร์มล็อกอิน อาจเข้าสู่ระบบอยู่แล้ว');
    return;
  }

  await expect(emailField).toBeVisible({
    timeout: 10000
  });

  // 4. กรอกข้อมูล
  await emailField.fill(USERNAME);
  await passwordField.fill(PASSWORD);

  // 5. คลิกปุ่มเข้าสู่ระบบในฟอร์ม
  await page.getByRole('button', {
    name: 'เข้าสู่ระบบ',
    exact: true
  }).click();

  // 6. รอให้เว็บไซต์ประมวลผล
  await expect(passwordField).toBeHidden({
    timeout: 15000
  });

  // console.log('เข้าสู่ระบบสำเร็จ');
}


  async function openFirstBook(page) {
    await page.goto(HOME, {
      waitUntil: 'domcontentloaded'
    });

    const bookLinks = page.getByRole('link', {
      name: 'ดูรายละเอียด',
      exact: true
    });

    await expect(bookLinks.first()).toBeVisible({
      timeout: 15000
    });

    await bookLinks.first().click();

    const heading = page.locator('h1').first();
    await expect(heading).toBeVisible({
      timeout: 15000
    });

    return heading;
  }

  async function openCart(page) {
    await page.goto(CART, {
      waitUntil: 'domcontentloaded'
    });

    await expect(page.locator('body')).not.toBeEmpty();
  }

  async function addFirstBookToCart(page) {
    await openFirstBook(page);

    const addButton = page.getByText('เพิ่มลงตะกร้า', {
      exact: true
    }).first();

    await expect(addButton).toBeVisible({
      timeout: 15000
    });

    await addButton.click();

    // รอให้เว็บประมวลผลการเพิ่มสินค้า
    await page.waitForTimeout(800);

    await openCart(page);

    await expect(
      page.locator('a[href*="action=remove"]').first(),
      'ไม่พบสินค้าในตะกร้าหลังเพิ่มสินค้า'
    ).toBeVisible({
      timeout: 15000
    });
  }

  async function clearCart(page) {
    await openCart(page);

    // ป้องกันการวนซ้ำไม่สิ้นสุด
    for (let i = 0; i < 30; i++) {
      const removeLink = page.locator(
        'a[href*="action=remove"]'
      ).first();

      if (!(await removeLink.isVisible().catch(() => false))) {
        break;
      }

      await removeLink.click();
      await page.waitForTimeout(400);
      await openCart(page);
    }

    await expect(
      page.locator('a[href*="action=remove"]')
    ).toHaveCount(0, {
      timeout: 10000
    });
  }

  // =====================================
  // TC-011: Book Detail
  // =====================================
  test('TC-011 ตรวจสอบหน้ารายละเอียดหนังสือ', async () => {
    const heading = await openFirstBook(page);

    await expect(page.locator('body')).not.toBeEmpty();
    await expect(page.locator('img:visible').first()).toBeVisible();
    await expect(heading).not.toBeEmpty();
  });

  // =====================================
  // TC-012: Add Book to Cart
  // =====================================
  test('TC-012 เพิ่มหนังสือลงตะกร้า', async () => {
    await clearCart(page);
    await addFirstBookToCart(page);

    await expect(
      page.locator('a[href*="action=remove"]').first(),
      'ไม่พบรายการสินค้าในตะกร้าหลังเพิ่มสินค้า'
    ).toBeVisible();
  });

  // =====================================
  // TC-013: Promotion Bundle
  // =====================================
  test('TC-013 ตรวจสอบชุดโปรโมชั่นและส่วนลด 10%', async () => {
    await clearCart(page);
    await page.goto(HOME, {
      waitUntil: 'domcontentloaded'
    });

    const bundleButton = page.getByText('ซื้อทั้งชุด 3 เล่ม', {
      exact: true
    }).first();

    await expect(bundleButton).toBeVisible({
      timeout: 15000
    });

    await bundleButton.scrollIntoViewIfNeeded();
    await bundleButton.click();

    await page.waitForTimeout(800);
    await openCart(page);

    const removeLinks = page.locator(
      'a[href*="action=remove"]'
    );

    await expect(
      removeLinks,
      'จำนวนสินค้าในชุดโปรโมชั่นไม่เท่ากับ 3 รายการ'
    ).toHaveCount(3, {
      timeout: 15000
    });

    const cartText = await page.locator('body').innerText();

    expect(
      /฿\s?[\d,]+(?:\.\d{2})?/.test(cartText),
      'ไม่พบราคาในหน้าตะกร้า'
    ).toBeTruthy();

    await expect(
      page.getByText(
        /ส่วนลดชุดเซ็ต.*3.*เล่ม|ส่วนลดชุดโปรโมชั่น/i
      ).first(),
      'ไม่พบข้อความส่วนลดชุดโปรโมชั่น'
    ).toBeVisible();

    await expect(
      page.getByText(/10\s*%|10%/).first(),
      'ไม่พบข้อความส่วนลด 10%'
    ).toBeVisible();
  });

  // =====================================
  // TC-014: Increase and decrease quantity
  // =====================================
  test('TC-014 เพิ่มและลดจำนวนสินค้า', async () => {
    await clearCart(page);
    await addFirstBookToCart(page);

    let increaseLink = page.locator(
      'a[href*="action=add"][href*="id="]'
    ).first();

    await expect(
      increaseLink,
      'ไม่พบลิงก์เพิ่มจำนวนสินค้า'
    ).toBeVisible();

    await increaseLink.click();
    await page.waitForTimeout(500);
    await openCart(page);

    const decreaseLink = page.locator(
      'a[href*="action=decrease"][href*="id="]'
    ).first();

    await expect(
      decreaseLink,
      'ไม่พบลิงก์ลดจำนวนสินค้า'
    ).toBeVisible();

    await decreaseLink.click();
    await page.waitForTimeout(500);
    await openCart(page);

    await expect(
      page.locator('a[href*="action=remove"]').first(),
      'ไม่พบรายการสินค้าหลังลดจำนวน'
    ).toBeVisible();
  });

  // =====================================
  // TC-015: Remove item from cart
  // =====================================
  test('TC-015 ลบรายการสินค้าออกจากตะกร้า', async () => {
    await clearCart(page);
    await addFirstBookToCart(page);

    const removeLink = page.locator(
      'a[href*="action=remove"][href*="id="]'
    ).first();

    await expect(
      removeLink,
      'ต้องมีลิงก์ลบสินค้าในหน้าตะกร้า'
    ).toBeVisible();

    page.once('dialog', dialog => dialog.accept());

    await removeLink.click();
    await page.waitForTimeout(500);
    await openCart(page);

    await expect(
      page.locator('a[href*="action=remove"]'),
      'ยังพบลิงก์ลบสินค้าหลังลบ'
    ).toHaveCount(0, {
      timeout: 10000
    });
  });

  // =====================================
  // TC-016: Checkout with slip
  // =====================================
  test('TC-016 สั่งซื้อสินค้าพร้อมแนบสลิปถูกต้อง', async () => {
    const slipPath = path.join(
      __dirname,
      'fixtures',
      'payment.jpg'
    );

    expect(
      fs.existsSync(slipPath),
      `ไม่พบไฟล์สลิป: ${slipPath}`
    ).toBeTruthy();

    await clearCart(page);
    await addFirstBookToCart(page);

    await page.goto(CHECKOUT, {
      waitUntil: 'domcontentloaded'
    });

    await expect(page.locator('body')).not.toBeEmpty();

    const slipInput = page.locator(
      'input[type="file"]'
    ).first();

    await expect(slipInput).toHaveCount(1);
    await slipInput.setInputFiles(slipPath);

    await expect(slipInput).toHaveJSProperty(
      'files.length',
      1
    );

    const confirmButton = page.getByRole('button', {
      name: /ยืนยัน.*(สั่งซื้อ|ชำระเงิน)|สั่งซื้อ|ยืนยันคำสั่งซื้อ|confirm|checkout/i
    }).first();

    await expect(confirmButton).toBeVisible();
    await confirmButton.click();

    await page.waitForTimeout(800);
    await expect(page.locator('body')).not.toBeEmpty();
  });

  // =====================================
  // TC-017: Checkout without slip
  // =====================================
  test('TC-017 สั่งซื้อโดยไม่อัปโหลดภาพสลิป', async () => {
    await clearCart(page);
    await addFirstBookToCart(page);

    await page.goto(CHECKOUT, {
      waitUntil: 'domcontentloaded'
    });

    await expect(page.locator('body')).not.toBeEmpty();

    const slipInput = page.locator(
      'input[type="file"]'
    ).first();

    await expect(slipInput).toHaveCount(1);

    const confirmButton = page.getByRole('button', {
      name: /ยืนยัน.*(สั่งซื้อ|ชำระเงิน)|สั่งซื้อ|ยืนยันคำสั่งซื้อ|confirm|checkout/i
    }).first();

    await expect(confirmButton).toBeVisible();
    await confirmButton.click();

    await page.waitForTimeout(500);

    await expect(page.locator('body')).toContainText(
      /แนบ.*สลิป|อัปโหลด.*สลิป|หลักฐาน.*ชำระเงิน|กรุณา.*ไฟล์|กรุณา.*แนบ|required|เลือกไฟล์/i
    );
  });

  // =====================================
  // TC-018: Order History
  // =====================================
  test('TC-018 ตรวจสอบประวัติการสั่งซื้อ', async () => {
    await page.goto(ORDERS, {
      waitUntil: 'domcontentloaded'
    });
  
    await expect(page.locator('body')).toContainText(
      /ประวัติการสั่งซื้อ|รายการสั่งซื้อ|คำสั่งซื้อของฉัน|order history|my orders/i
    );
  });

  
  // =====================================
  // TC-019: Admin Login
  // =====================================

  // TC-019: เข้าสู่ระบบ Admin และไปที่จัดการหลังบ้าน

  // TC-019: ออกจากระบบ User แล้วเข้าสู่ระบบ Admin
  test('TC-019 ออกจากระบบ User และเข้าสู่หน้าจัดการหลังบ้าน Admin', async () => {

    // 1. เปิดหน้าเว็บไซต์หลัก
    await page.goto(HOME, { waitUntil: 'domcontentloaded' });

    // 2. ออกจากระบบ User ก่อน
    const logoutLink = page.getByRole('link', {
      name: /ออกจากระบบ|logout|log out|sign out/i
    }).first();

    if (await logoutLink.isVisible().catch(() => false)) {
      await logoutLink.click();
      await page.waitForLoadState('domcontentloaded').catch(() => {});
      await page.waitForTimeout(500);
    }
    // 3. กลับมาหน้าเว็บไซต์หลัก
    await page.goto(HOME, { waitUntil: 'domcontentloaded' });

    // 4. กดเข้าสู่ระบบ
    const loginLink = page.getByRole('link', {
      name: 'เข้าสู่ระบบ',
      exact: true
    });

    await expect(loginLink).toBeVisible({ timeout: 15000 });
    await loginLink.click();

    // 5. กรอกบัญชี Admin
    const emailField = page.locator(
      'input[type="email"], input[name="email"], input[name="username"]'
    ).first();

    const passwordField = page.locator('input[type="password"]').first();

    await expect(emailField).toBeVisible({ timeout: 10000 });
    await emailField.fill('admin@booknest.com');
    await passwordField.fill('admin1234');

    // 6. กดเข้าสู่ระบบ
    await page.getByRole('button', {
      name: /เข้าสู่ระบบ|login|sign in/i
    }).click();

    await page.waitForLoadState('domcontentloaded').catch(() => {});
    await page.waitForTimeout(1000);


  });


  
  // TC-020: ตรวจสอบหน้า Admin Dashboard
  test('TC-020 ตรวจสอบหน้า Dashboard ผู้ดูแลระบบ', async () => {

    // // 1. ตรวจสอบว่าอยู่ในหน้า Dashboard
    // await expect(page).toHaveURL(/admin/i, { timeout: 15000 });
    // await expect(page.locator('body')).not.toBeEmpty();

    const adminLink = page.getByRole('link', {
      name: /จัดการหลังบ้าน|หลังบ้าน|admin dashboard|admin panel/i
    }).first();

    await expect(
      adminLink,
      'ไม่พบเมนูจัดการหลังบ้านหลังเข้าสู่ระบบ Admin'
    ).toBeVisible({ timeout: 15000 });

    await adminLink.click();

    console.log('TC-019: เข้าสู่เมนูจัดการหลังบ้านสำเร็จ');
  });


});
