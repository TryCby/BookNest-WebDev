const {test, expect} = require('@playwright/test')


const CUSTOMER = {email: 'somchai@gmail.com', password: 'pass1234', name:'สมชาย สุขสบาย'}

const submit = (page) => page.locator('form button[type="submit"], form input[type="submit"]').first()


async function login(page, email, password){
    await page.goto('/login.php')
    await page.locator('input[name="email"]').fill(email)
    await page.locator('input[name="password"]').fill(password)
    await submit(page).click()
}


test.describe('Group A: Auth & Profile ', ()=>{
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



})

test.describe('Group F: Auth security', ()=>{
    test('TC-030', async({page}) =>{
        await page.goto('/login.php')

        await page.locator('form').first().evaluate((f)=>(f.noValidate = true))
        await page.fill('input[name="email"]', "' OR '1'='1")
        await page.fill('input[name="password"]', "' OR '1'='1")
        await submit(page).click()
        await expect(page).toHaveURL(/login\.php/);
        await expect(page.locator('body')).not.toContainText(/sql syntax|mysqli|warning:|fatal error/i)
        await expect(page.getByText('ไม่พบอีเมลนี้ในระบบ')).toBeVisible();
    })
    
    test('TC-032', async({page})=>{
       for (const path of ['/login.php', '/register.php']) {
          await page.goto(path);
          await expect(page.locator('input[name="password"]')).toHaveAttribute('type', 'password');
       }
    })

})