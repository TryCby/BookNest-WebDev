
const { test, expect } = require('@playwright/test');

const CUSTOMER = { email: 'somchai@gmail.com', password: 'pass1234' };
const XSS = "<script>alert('XSS')</script>";

const submit = (page) => page.locator('form button[type="submit"], form input[type="submit"]').first();
const catalog = (page) => page.locator('.book-grid').last(); // last grid = paged catalog (first grid = bestsellers)

// ---- print the system's warning / success message to the terminal after each submit ----
const KNOWN_MESSAGES = [
  'กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน', 'รหัสผ่านไม่ถูกต้อง', 'ไม่พบอีเมลนี้ในระบบ',
  'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน', 'รูปแบบอีเมลไม่ถูกต้อง', 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน',
  'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลักเท่านั้น', 'อีเมลนี้ถูกใช้งานแล้ว', 'สมัครสมาชิกสำเร็จ',
  'ทางร้านได้รับสลิปแล้ว',
];

async function showMessages(page) {
  try {
    const boxes = page
      .locator('.alert-box, .alert, .alert-error, .alert-success, .alert-danger, [role="alert"]')
      .filter({ visible: true });
    let texts = (await boxes.allInnerTexts()).map((t) => t.replace(/\s+/g, ' ').trim()).filter(Boolean);
    if (!texts.length) {
      const body = await page.locator('body').innerText();
      texts = KNOWN_MESSAGES.filter((m) => body.includes(m));
    }
    for (const t of [...new Set(texts)]) console.log(`   [system message] ${t}`);
  } catch (e) { /* page may be navigating */ }
}

async function submitAndLog(page) {
  await submit(page).click();
  await page.waitForLoadState('domcontentloaded');
  await showMessages(page);
}

// ---- shared steps ----
async function login(page, email = CUSTOMER.email, password = CUSTOMER.password) {
  await page.goto('/login.php');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await submitAndLog(page);
  await page.waitForURL(/index\.php|\/$/);
}

async function addFirstBook(page) {
  await page.goto('/index.php');
  await catalog(page).getByRole('link', { name: 'เพิ่มลงตะกร้า' }).first().click(); // GET /cart.php?action=add&id=..
  await page.goto('/cart.php');
}

async function fillShipping(page) {
  const set = async (sel, val) => {
    const el = page.locator(sel);
    if (!(await el.inputValue())) await el.fill(val);
  };
  await set('input[name="fullname"]', 'ทดสอบ ระบบ');
  await set('input[name="phone"]', '0812345678');
  await set('textarea[name="address"]', '123 ถนนทดสอบ กรุงเทพฯ');
}

// ---------------------------------------------------------------------------
test.describe('Group F: Security', () => {
  test('TC-031 password fields are masked', async ({ page }) => {
    for (const path of ['/login.php', '/register.php']) {
      await page.goto(path);
      const pw = page.locator('input[name="password"]');
      await pw.fill('secret123');
      await expect(pw).toHaveAttribute('type', 'password');
      console.log(`   [check] ${path}: password field type = password`);
    }
    await expect(page.locator('input[name="confirm_password"]')).toHaveAttribute('type', 'password'); // register page
  });

  test('TC-032 guest cannot open admin dashboard', async ({ page }) => {
    await page.goto('/admin/dashboard.php');
    console.log(`   [redirect] final URL: ${page.url()}`);
    await expect(page).not.toHaveURL(/dashboard\.php/);
    await expect(page.locator('body')).not.toContainText('ยอดขาย');
  });

  test('TC-033 customer cannot open admin dashboard', async ({ page }) => {
    await login(page);
    await page.goto('/admin/dashboard.php');
    console.log(`   [redirect] final URL: ${page.url()}`);
    await expect(page).not.toHaveURL(/dashboard\.php/);
    await expect(page.locator('body')).not.toContainText('ยอดขาย');
  });

  // TC-034: dummy files only (plain text with a misleading extension). No real scripts.
  // If the server ACCEPTS a file the test fails = real finding. Then delete the uploaded file from the host's uploads folder.
  for (const f of [
    { name: 'fake.php', mimeType: 'application/x-php', body: 'dummy text, not code' },
    { name: 'doc.pdf', mimeType: 'application/pdf', body: 'dummy text, not a pdf' },
    { name: 'run.exe', mimeType: 'application/octet-stream', body: 'dummy text, not a program' },
  ]) {
    test(`TC-033 non-image upload is rejected (${f.name})`, async ({ page }) => {
      await login(page);
      await addFirstBook(page);
      await page.goto('/checkout.php');
      await fillShipping(page);
      await page.setInputFiles('input[name="slip_image"]', { name: f.name, mimeType: f.mimeType, buffer: Buffer.from(f.body) });
      await submitAndLog(page);

      const accepted = await page.getByText('ทางร้านได้รับสลิปแล้ว').count();
      if (accepted) console.log(`   [FINDING] server accepted ${f.name}: delete it from the uploads folder`);
      expect(accepted, `server accepted ${f.name}`).toBe(0);
      await expect(page.locator('.alert-error')).toBeVisible();
      const msg = await page.locator('.alert-error').first().innerText();
      expect.soft(msg, 'message should tell the user to upload .jpg/.jpeg/.png only').toMatch(/jpg|jpeg|png|รูป|ภาพ/i);
    });
  }
});

// ---------------------------------------------------------------------------
test.describe('Group G: Reliability and session', () => {
  test('TC-034 login and cart survive reload and reopening the tab', async ({ page, context }) => {
    await login(page);
    await addFirstBook(page);
    const rows = page.locator('.cart-table tbody tr');
    await expect(rows).toHaveCount(1);
    console.log(`   [cart] items before reload: ${await rows.count()}`);

    await page.reload();
    await expect(rows).toHaveCount(1);
    console.log(`   [cart] items after reload: ${await rows.count()}`);

    await page.close(); // close the tab; the session cookie stays in the same browser context
    const reopened = await context.newPage();
    await reopened.goto('/cart.php');
    const rows2 = reopened.locator('.cart-table tbody tr');
    await expect(rows2).toHaveCount(1);
    console.log(`   [cart] items after reopening tab: ${await rows2.count()}`);

    await reopened.goto('/login.php'); // still logged in -> controller redirects away from the login page
    await expect(reopened).toHaveURL(/index\.php|\/$/);
    console.log('   [session] still logged in after reopening the tab');
  });
});