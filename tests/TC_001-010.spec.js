const {test, expect} = require('@playwright/test')


const CUSTOMER = {email: 'somchai@gmail.com', password: 'pass1234', name:'สมชาย สุขสบาย'}

const submit = (page) => page.locator('form button[type="submit"], form input[type="submit"]').first()
const catalog = (page) => page.locator('.book-grid').last();

const CONFIG = {
  customer: { email: 'somchai@gmail.com', password: 'pass1234' },
  bookLink: process.env.BOOK_LINK || 'a[href*="book-detail.php?id="]',
  searchInput: process.env.SEARCH_INPUT || 'input[name="q"], input[name="search"], input[name="keyword"], input[type="search"]',
  categoryLink: process.env.CATEGORY_LINK || 'a[href*="category"]',
  expectedBooks: 25,
};

async function bookHrefs(page, qs = '') {
  await page.goto('/index.php' + qs);
  return catalog(page).locator(CONFIG.bookLink).evaluateAll((as) => [...new Set(as.map((a) => a.getAttribute('href')))]);
}



async function login(page, email, password){
    await page.goto('/login.php')
    await page.locator('input[name="email"]').fill(email)
    await page.locator('input[name="password"]').fill(password)
    await submit(page).click()
}


test.describe('TC 001-010 ', ()=>{
    test('TC-001', async({page})=> {
        const email = `user_${Date.now()}@booknest.com`;
        await page.goto('/register.php')
        await page.fill('input[name="fullname"]', 'ทดสอบ ระบบ')
        await page.fill('input[name="email"]', email)
        await page.fill('input[name="phone"]','0812345678' )
        await page.fill('input[name="password"]', 'pass1234')
        await page.fill('input[name="confirm_password"]', 'pass1234')

        const address = page.locator('[name="address"]')
        if(await address.count()) await address.fill('123 ถนนทดสอบ กรุงเทพ')
        
        await submit(page).click()
        await expect(page.getByText('สมัครสมาชิกสำเร็จ')).toBeVisible()
        await page.waitForURL(/login\.php/, { timeout: 15_000 })
    })

    test('TC-002', async({page}) =>{
        await page.goto('/register.php')
        await page.fill('input[name="fullname"]', 'ทดสอบ ระบบ')
        await page.fill('input[name="email"]', CUSTOMER.email)
        await page.fill('input[name="phone"]','0812345678' )
        await page.fill('input[name="password"]', 'pass1234')
        await page.fill('input[name="confirm_password"]', 'pass1234')
        await submit(page).click()
        await expect(page.getByText('อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น')).toBeVisible()
    })
    
    test('TC-003', async ({ page }) => {
      await login(page, CUSTOMER.email, CUSTOMER.password);
      await expect(page).toHaveURL(/index\.php|\/$/);
    await expect(page.getByText(CUSTOMER.name).first()).toBeVisible();
    });

  test('TC-004', async ({ page }) => {
    await login(page, CUSTOMER.email, 'wrong-password');
    await expect(page.getByText('รหัสผ่านไม่ถูกต้อง')).toBeVisible();
    await expect(page).toHaveURL(/login\.php/);
  });

  test('TC-005', async ({ page }) => {
    await login(page, CUSTOMER.email, CUSTOMER.password);
    await page.getByRole('link', { name: /ออกจากระบบ/ }).first().click();
    // after logout the session is gone: protected page should not show the profile form
    await page.goto('/profile.php');
    await expect(page).toHaveURL(/login\.php|index\.php|\/$/);
    await expect(page.getByText(CUSTOMER.name)).toHaveCount(0);
  });

  test('TC-006', async ({ page }) => {
    await login(page, CUSTOMER.email, CUSTOMER.password);
    await page.goto('/profile.php');
    const newPhone = '0898765432';
    const newAddress = `ที่อยู่ทดสอบ ${Date.now()}`;
    await page.fill('input[name="phone"]', newPhone);
    await page.fill('[name="address"]', newAddress);
    await submit(page).click();
    await page.goto('/profile.php');
    await expect(page.locator('input[name="phone"]')).toHaveValue(newPhone);
    await expect(page.locator('[name="address"]')).toHaveValue(newAddress);
  });
  
  test('TC-007 list all books', async({page})=>{
  
       await page.goto('/index.php')
       const heading = await page.getByRole('heading',{
          name:/รายการหนังสือทั้งหมดในระบบ/
       })
       .innerText()
  
        const total = Number(
          heading.match(/\((\d+)\s*เล่ม\)/)[1]
        )
  
    
        expect(total).toBe(CONFIG.expectedBooks)
        const pages = await page.locator('.pagination-bar a.page-btn').count()  
        const seen = new Set()
        for(let p=1; p<=Math.max(pages,1);p++){
          for(const h of await bookHrefs(page, `?page=${p}`)){
              seen.add(h)
          }
           
        }
        console.log(`TC-007 heading total=${total}, pages=${pages}, distinct books=${seen.size}`);
        expect(seen.size).toBe(total)
        await expect(page.locator('.book-cover').first()).toBeVisible()
        await expect(page.locator('.book-price').first()).toContainText('฿')
  
    })
    test('TC-008', async({page})=>{
        await page.goto('/index.php')
    
        const discount_prices = page.locator('.bundle-card strong')
        const raw_prices = page.locator('.bundle-card span')
        const count = await discount_prices.count()
    
        console.log("count",count)
    
        let check = false
        
        for(let i=0;i<count;i++){
            const raw_priceText = await raw_prices.nth(i).innerText()
            const discount_priceText = await discount_prices.nth(i).innerText()
    
            const raw_price = Number(raw_priceText.replace(/[฿,]/g, ''))
            const discount_price = Number(discount_priceText.replace(/[฿,]/g,''))
            
            console.log("raw_price",raw_price)
            console.log("discount_price",discount_price)
            
            if(raw_price-(raw_price*0.1) === discount_price){
                check = true
            }
            else{
                check = false
                console.log("fail_raw_price",raw_price)
                console.log("fail_discount_price",discount_price)
                break
            }
    
    
        }
        expect(check).toBe(true)
        await expect(page.getByText(/10\s*%/).first()).toBeVisible()
    
    
      })
    
    
    
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
    

  test('TC-010 reset password',async({page})=>{
    const stamp = Date.now()
    const user = {name: 'ทดสอบ รีเซ็ต', email: `reset_${stamp}@booknest.com`,phone:'0811111111',
      oldPw:'oldpass123', newPw:'newpass456'
    }

    await test.step('create a throwaway account', async()=>{
      await page.goto('/register.php')
      await page.goto('/register.php');
      await page.fill('input[name="fullname"]', user.name);
      await page.fill('input[name="email"]', user.email);
      await page.fill('input[name="phone"]', user.phone);
      await page.fill('input[name="password"]', user.oldPw);
      await page.fill('input[name="confirm_password"]', user.oldPw);
      await submit(page).click();
      await expect(page.getByText('สมัครสมาชิกสำเร็จ')).toBeVisible();

    })

     const reset = async (phone, pw, confirm) => {
      await page.goto('/forgot_password.php');
      await page.fill('input[name="email"]', user.email);
      await page.fill('input[name="phone"]', phone);
      await page.fill('input[name="new_password"]', pw);
      await page.fill('input[name="confirm_new_password"]', confirm);
      await submit(page).click();
    };

    await test.step('wrong phone is rejected', async () => {
      await reset('0899999999', user.newPw, user.newPw);
      await expect(page.getByText('ข้อมูลอีเมลหรือเบอร์โทรศัพท์ไม่ตรงกับในระบบ')).toBeVisible();
    });
 
    await test.step('mismatched confirmation is rejected', async () => {
      await reset(user.phone, user.newPw, 'something-else');
      await expect(page.getByText('รหัสผ่านใหม่และการยืนยันไม่ตรงกัน')).toBeVisible();
    });
 
    await test.step('correct email + phone resets the password', async () => {
      await reset(user.phone, user.newPw, user.newPw);
      await expect(page.getByText('เปลี่ยนรหัสผ่านสำเร็จ')).toBeVisible();
      await page.waitForURL(/login\.php/, { timeout: 15_000 }); // controller redirects after 2 s
    });
 
    await test.step('old password no longer works', async () => {
      await login(page, user.email, user.oldPw);
      await expect(page.getByText('รหัสผ่านไม่ถูกต้อง')).toBeVisible();
    });
 
    await test.step('new password logs in', async () => {
      await login(page, user.email, user.newPw);
      await expect(page).toHaveURL(/index\.php|\/$/);
      await expect(page.getByText(user.name).first()).toBeVisible();
    });
 })


})
