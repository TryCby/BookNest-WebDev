// catalog-cart.spec.js : TC-007 to TC-035 (catalog, cart, checkout, admin, responsive, security)
// Admin pages (TC-019..026, 033) use GUESSED selectors: views in /admin were not seen. Tune ADMIN block if needed.
// Verified from views: cart.php (+/-/remove links, summary texts) and checkout.php (field names, slip input).
// NOT verified (index/detail views not seen): book link pattern, search box, category links, add-to-cart buttons.
// Tune those via the CONFIG block or environment variables if a test fails on a locator, not on a result.
const { test, expect } = require('@playwright/test');

const CONFIG = {
  customer: { email: 'somchai@gmail.com', password: 'pass1234' },
  bookLink: process.env.BOOK_LINK || 'a[href*="book-detail.php?id="]',
  searchInput: process.env.SEARCH_INPUT || 'input[name="q"], input[name="search"], input[name="keyword"], input[type="search"]',
  categoryLink: process.env.CATEGORY_LINK || 'a[href*="category"]',
  expectedBooks: 25,
};

// 1x1 PNG (valid image) for slip upload
const PNG = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  'base64'
);

const money = (t) => parseFloat(String(t).replace(/[^0-9.]/g, ''));
const submit = (page) => page.locator('form button[type="submit"], form input[type="submit"]').first();
const addBtn = (page) =>
  page.getByRole('button', { name: /เพิ่มลงตะกร้า/ }).or(page.getByRole('link', { name: /เพิ่มลงตะกร้า/ })).first();
const addSetBtn = (page) =>
  page.getByRole('link', { name: /ซื้อทั้งชุด 3 เล่ม/ }).first();

async function login(page) {
  await page.goto('/login.php');
  await page.fill('input[name="email"]', CONFIG.customer.email);
  await page.fill('input[name="password"]', CONFIG.customer.password);
  await submit(page).click();
  await page.waitForURL(/index\.php|\/$/);
}

// The index page has 3 zones (bestsellers, bundles, catalog). Only the LAST .book-grid is the filtered, paged catalog.
const catalog = (page) => page.locator('.book-grid').last();

async function bookHrefs(page, qs = '') {
  await page.goto('/index.php' + qs);
  return catalog(page).locator(CONFIG.bookLink).evaluateAll((as) => [...new Set(as.map((a) => a.getAttribute('href')))]);
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

test.describe('Group B: Catalog (guest)', () => {
  test('TC-007 list all books', async ({ page }) => {
    await page.goto('/index.php');
    const heading = await page.getByRole('heading', { name: /รายการหนังสือทั้งหมดในระบบ/ }).innerText();
    const total = Number(heading.match(/\((\d+)\s*เล่ม\)/)[1]);
    expect(total).toBe(CONFIG.expectedBooks);
    const pages = await page.locator('.pagination-bar a.page-btn').count();
    const seen = new Set();
    for (let p = 1; p <= Math.max(pages, 1); p++) {
      for (const h of await bookHrefs(page, `?page=${p}`)) seen.add(h);
    }
    console.log(`TC-007 heading total=${total}, pages=${pages}, distinct books=${seen.size}`);
    expect(seen.size).toBe(total);
    await expect(page.locator('.book-cover').first()).toBeVisible();
    await expect(page.locator('.book-price').first()).toContainText('฿');
  });

  test('TC-008 promotion sets show 10% discount', async ({ page }) => {
    await page.goto('/index.php');
    await expect(page.getByText(/10\s*%/).first()).toBeVisible();
  });

  test('TC-009 filter by category', async ({ page }) => {
    await page.goto('/index.php');
    const select = page.locator('#category-select');
    const options = await select.locator('option').evaluateAll((os) => os.map((o) => ({ value: o.value, label: o.textContent.trim() })));
    const wanted = process.env.CATEGORY_NAME;
    const opt = wanted ? options.find((o) => o.label.includes(wanted)) : options.find((o) => o.value !== '0');
    console.log('TC-009 options:', options.map((o) => o.label).join(' | '), '-> selecting:', opt && opt.label);
    expect(opt, 'category not found in dropdown').toBeTruthy();

    await Promise.all([page.waitForURL(/cat=/), select.selectOption(opt.value)]);

    const cards = catalog(page).locator('.book-card');
    const n = await cards.count();
    expect(n, 'no books shown for this category').toBeGreaterThan(0);
    // first <p> in each card is the category name
    const cats = await cards.evaluateAll((cs) => cs.map((c) => c.querySelector('p').textContent.trim()));
    console.log(`TC-009 shown=${n}, url=${page.url()}`);
    for (const c of cats) expect(c).toBe(opt.label);
  });

  test('TC-010 search by keyword', async ({ page }) => {
    await page.goto('/index.php');
    const keyword = process.env.SEARCH_KEYWORD || (await page.locator('.book-card h3').first().innerText()).trim();
    const box = page.locator(CONFIG.searchInput).first();
    expect(await box.count(), 'no search box found (index view has none; check views/layouts/header.php)').toBeGreaterThan(0);
    await box.fill(keyword);
    await box.press('Enter');
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('body')).toContainText(keyword);
  });

  test('TC-011 book detail page', async ({ page }) => {
    const hrefs = await bookHrefs(page);
    await page.goto(hrefs[0]);
    await expect(page.locator('img').first()).toBeVisible();
    await expect(page.locator('h1, h2').first()).not.toBeEmpty();
    await expect(page.locator('body')).toContainText('฿');
    await expect(page.locator('body')).toContainText(/สต็อก|คงเหลือ|เล่ม/);
  });
});

test.describe('Group C: Cart (customer)', () => {
  test('TC-012 add single book to cart', async ({ page }) => {
    await login(page);
    await addFirstBook(page);
    await expect(page.locator('.cart-table tbody tr')).toHaveCount(1);
  });

  test('TC-013 add promotion set to cart', async ({ page }) => {
    await login(page);
    await page.goto('/index.php');
    await addSetBtn(page).click();
    await page.goto('/cart.php');
    expect(await page.locator('.cart-table tbody tr').count()).toBeGreaterThanOrEqual(3);
    await expect(page.getByText(/ส่วนลดชุดเซ็ต 3 เล่ม \(10%\)/)).toBeVisible();
  });

  test('TC-014 increase/decrease quantity', async ({ page }) => {
    await login(page);
    await addFirstBook(page);
    const row = page.locator('.cart-table tbody tr').first();
    const price = money(await row.locator('td').nth(1).innerText());
    expect(await row.locator('td').nth(2).locator('span').innerText()).toBe('1');

    await row.locator('a[href*="action=add"]').click();
    const row2 = page.locator('.cart-table tbody tr').first();
    expect(await row2.locator('td').nth(2).locator('span').innerText()).toBe('2');
    expect(money(await row2.locator('td').nth(3).innerText())).toBeCloseTo(price * 2, 2);

    await row2.locator('a[href*="action=decrease"]').click();
    const row3 = page.locator('.cart-table tbody tr').first();
    expect(await row3.locator('td').nth(2).locator('span').innerText()).toBe('1');
  });

  test('TC-015 remove item from cart', async ({ page }) => {
    await login(page);
    await addFirstBook(page);
    await page.locator('a[href*="action=remove"]').first().click();
    await expect(page.getByText('ยังไม่มีหนังสือในตะกร้าของคุณ')).toBeVisible();
  });

  test('TC-035 session and cart persist after reload and reopening tab', async ({ page, context }) => {
    await login(page);
    await addFirstBook(page);
    await page.reload();
    await expect(page.locator('.cart-table tbody tr')).toHaveCount(1);
    await page.close(); // close the tab; cookies stay in the same browser context
    const reopened = await context.newPage();
    await reopened.goto('/cart.php');
    await expect(reopened.locator('.cart-table tbody tr')).toHaveCount(1);
    await reopened.goto('/login.php'); // already logged in -> controller redirects to index
    await expect(reopened).toHaveURL(/index\.php|\/$/);
  });
});

test.describe.serial('Group C: Checkout and orders', () => {
  test('TC-016 checkout with valid slip', async ({ page }) => {
    await login(page);
    await addFirstBook(page);
    await page.goto('/checkout.php');
    await fillShipping(page);
    await page.setInputFiles('input[name="slip_image"]', { name: 'slip.png', mimeType: 'image/png', buffer: PNG });
    await submit(page).click();
    await expect(page.getByText('ทางร้านได้รับสลิปแล้ว')).toBeVisible();
    await page.goto('/cart.php');
    await expect(page.getByText('ยังไม่มีหนังสือในตะกร้าของคุณ')).toBeVisible(); // cart cleared
  });

  test('TC-017 checkout without slip (server-side check)', async ({ page }) => {
    await login(page);
    await addFirstBook(page);
    await page.goto('/checkout.php');
    await fillShipping(page);
    // browser would block via required; bypass it to test the server
    await page.locator('form').first().evaluate((f) => (f.noValidate = true));
    await page.locator('input[name="slip_image"]').evaluate((el) => el.removeAttribute('required'));
    await submit(page).click();
    await expect(page.locator('.alert-error')).toBeVisible();
    await expect(page.getByText('ทางร้านได้รับสลิปแล้ว')).toHaveCount(0);
  });

  test('TC-018 order history shows pending order', async ({ page }) => {
    await login(page);
    await page.goto('/my-orders.php');
    await expect(page.locator('body')).toContainText('รอตรวจสอบ');
  });
});

test.describe('Group F: XSS and upload validation', () => {
  test('TC-031 XSS in search box is escaped', async ({ page }) => {
    const dialogs = [];
    page.on('dialog', async (d) => { dialogs.push(d.message()); await d.dismiss(); });
    await page.goto('/index.php');
    const box = page.locator(CONFIG.searchInput).first();
    expect(await box.count(), 'no search box found (check views/layouts/header.php)').toBeGreaterThan(0);
    await box.fill("<script>alert('XSS')</script>");
    await box.press('Enter');
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1500);
    expect(dialogs).toEqual([]);
  });

  // Uses harmless dummy files only. If the server ACCEPTS them, the test fails = real finding.
  // After a failure, delete any uploaded file in the uploads folder via InfinityFree File Manager.
  for (const f of [
    { name: 'fake.php', mimeType: 'application/x-php', body: 'dummy text, not code' },
    { name: 'doc.pdf', mimeType: 'application/pdf', body: 'dummy text, not a pdf' },
  ]) {
    test(`TC-034 reject non-image upload (${f.name})`, async ({ page }) => {
      await login(page);
      await addFirstBook(page);
      await page.goto('/checkout.php');
      await fillShipping(page);
      await page.setInputFiles('input[name="slip_image"]', {
        name: f.name, mimeType: f.mimeType, buffer: Buffer.from(f.body),
      });
      await submit(page).click();
      await expect(page.getByText('ทางร้านได้รับสลิปแล้ว')).toHaveCount(0);
      await expect(page.locator('.alert-error')).toBeVisible();
    });
  }
});

// ---------------------------------------------------------------------------
// Group D: Admin (TC-019 to TC-026) and TC-033 access control
// ---------------------------------------------------------------------------
const ADMIN = {
  email: 'admin@booknest.com',
  password: 'admin1234',
  emailInput: 'input[name="email"], input[name="username"]',
  newTitle: `คู่มือเขียนโปรแกรม PHP ${Date.now()}`, // unique so we only touch our own book
};

async function adminLogin(page) {
  await page.goto('/admin/login.php');
  await page.fill(ADMIN.emailInput, ADMIN.email);
  await page.fill('input[name="password"]', ADMIN.password);
  await submit(page).click();
  await page.waitForURL(/dashboard/);
}

test.describe.serial('Group D: Admin back office', () => {
  test('TC-019 admin login', async ({ page }) => {
    await adminLogin(page);
    await expect(page).toHaveURL(/admin\/dashboard\.php/);
  });

  test('TC-020 dashboard statistics', async ({ page }) => {
    await adminLogin(page);
    await page.goto('/admin/dashboard.php');
    await expect(page.locator('body')).toContainText(/ยอดขาย/);
    await expect(page.locator('body')).toContainText(/คำสั่งซื้อ/);
    await expect(page.locator('body')).toContainText(/สมาชิก|ผู้ใช้/);
    await expect(page.locator('canvas, svg').first()).toBeVisible(); // chart
  });

  test('TC-021 view order and slip image', async ({ page }) => {
    await adminLogin(page);
    await page.goto('/admin/orders.php');
    const detail = page.locator('a[href*="order"]:not([href$="orders.php"])').first();
    if (await detail.count()) await detail.click();
    const slip = page.locator('img[src*="slip"], img[src*="upload"]').first();
    await expect(slip).toBeVisible();
    expect(await slip.evaluate((i) => i.naturalWidth)).toBeGreaterThan(0); // image really loaded
  });

  test('TC-022 update order status', async ({ page }) => {
    await adminLogin(page);
    await page.goto('/admin/orders.php');
    const detail = page.locator('a[href*="order"]:not([href$="orders.php"])').first();
    if (await detail.count()) await detail.click();
    await page.locator('select[name="status"]').first().selectOption({ label: 'ชำระเงินแล้ว' });
    await submit(page).click();
    await expect(page.getByText('ชำระเงินแล้ว').first()).toBeVisible();
  });

  test('TC-023 add new book', async ({ page }) => {
    await adminLogin(page);
    await page.goto('/admin/books.php');
    const add = page.getByRole('link', { name: /เพิ่มหนังสือ/ }).or(page.getByRole('button', { name: /เพิ่มหนังสือ/ })).first();
    await add.click();
    await page.fill('input[name="title"]', ADMIN.newTitle);
    const author = page.locator('input[name="author"]');
    if (await author.count()) await author.fill('ผู้เขียนทดสอบ');
    await page.fill('input[name="price"]', '350');
    await page.fill('input[name="stock"]', '10');
    await page.setInputFiles('input[type="file"]', { name: 'cover.png', mimeType: 'image/png', buffer: PNG });
    await submit(page).click();
    await page.goto('/index.php');
    await expect(page.locator('body')).toContainText(ADMIN.newTitle);
  });

  test('TC-024 edit book price and stock', async ({ page }) => {
    await adminLogin(page);
    await page.goto('/admin/books.php');
    const row = page.locator('tr', { hasText: ADMIN.newTitle });
    await row.getByRole('link', { name: /แก้ไข/ }).or(row.getByRole('button', { name: /แก้ไข/ })).first().click();
    await page.fill('input[name="price"]', '400');
    await page.fill('input[name="stock"]', '5');
    await submit(page).click();
    await page.goto('/admin/books.php');
    const updated = page.locator('tr', { hasText: ADMIN.newTitle });
    await expect(updated).toContainText('400');
    await expect(updated).toContainText('5');
  });

  test('TC-025 delete the book we added (never the original 25)', async ({ page }) => {
    await adminLogin(page);
    await page.goto('/admin/books.php');
    page.once('dialog', (d) => d.accept()); // confirmation popup
    const row = page.locator('tr', { hasText: ADMIN.newTitle });
    await row.getByRole('link', { name: /ลบ/ }).or(row.getByRole('button', { name: /ลบ/ })).first().click();
    await page.goto('/index.php');
    await expect(page.locator('body')).not.toContainText(ADMIN.newTitle);
  });

  test('TC-026 manage users and roles', async ({ page }) => {
    await adminLogin(page);
    await page.goto('/admin/users.php');
    await expect(page.locator('body')).toContainText(CONFIG.customer.email);
    const row = page.locator('tr', { hasText: CONFIG.customer.email });
    const role = row.locator('select').first();
    await expect(role).toBeVisible();
    try {
      await role.selectOption({ label: 'admin' }).catch(() => role.selectOption('admin'));
      await row.locator('button, input[type="submit"]').first().click();
      await page.goto('/admin/users.php');
      await expect(page.locator('tr', { hasText: CONFIG.customer.email }).locator('select').first()).toHaveValue('admin');
    } finally {
      // always restore the customer role
      await page.goto('/admin/users.php');
      const r = page.locator('tr', { hasText: CONFIG.customer.email });
      await r.locator('select').first().selectOption('customer');
      await r.locator('button, input[type="submit"]').first().click();
    }
  });
});

test.describe('Group F: Access control', () => {
  test('TC-033 guest cannot open admin dashboard', async ({ page }) => {
    await page.goto('/admin/dashboard.php');
    await expect(page).toHaveURL(/login/);
  });

  test('TC-033 customer cannot open admin dashboard', async ({ page }) => {
    await login(page);
    await page.goto('/admin/dashboard.php');
    await expect(page).not.toHaveURL(/admin\/dashboard\.php/);
  });
});

// ---------------------------------------------------------------------------
// Group E: Performance and responsive (TC-027 to TC-029)
// Lighthouse (report table 8) is run separately in Chrome DevTools or: npx lighthouse <url> --view
// ---------------------------------------------------------------------------
const noHorizontalScroll = (page) =>
  page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1);

test.describe('Group E: Performance and responsive', () => {
  test('TC-027 home page loads within 3 seconds', async ({ page }) => {
    const t0 = Date.now();
    await page.goto('/index.php', { waitUntil: 'load' }); // DOM + images
    const ms = Date.now() - t0;
    console.log(`TC-027 load time: ${ms} ms`);
    expect(ms).toBeLessThanOrEqual(3000);
  });

  test('TC-028 mobile 375px layout', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/index.php');
    expect(await noHorizontalScroll(page)).toBe(true);
    const burger = page.locator('.hamburger, .menu-toggle, .navbar-toggler, .nav-toggle, button[aria-label*="menu" i]');
    expect.soft(await burger.count(), 'hamburger menu not found (check selector)').toBeGreaterThan(0);
    // single column: first two book cards share (almost) the same x position
    const xs = await page.$$eval('.book-card', (cs) => cs.slice(0, 4).map((c) => Math.round(c.getBoundingClientRect().x)));
    expect.soft(new Set(xs).size).toBeLessThanOrEqual(2);
  });

  for (const width of [768, 1280]) {
    test(`TC-029 layout at ${width}px`, async ({ page }) => {
      await page.setViewportSize({ width, height: 900 });
      await page.goto('/index.php');
      expect(await noHorizontalScroll(page)).toBe(true);
      const xs = await page.$$eval('.book-card', (cs) => cs.slice(0, 6).map((c) => Math.round(c.getBoundingClientRect().x)));
      expect(new Set(xs).size).toBeGreaterThan(1); // more than one column = grid
    });
  }
});