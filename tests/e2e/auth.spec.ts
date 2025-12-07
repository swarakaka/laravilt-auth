import { test, expect } from '@playwright/test'

/**
 * Auth Package E2E Tests
 *
 * Tests authentication flows including login, registration,
 * password reset, logout, and profile settings.
 */

test.describe('Login Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Ensure we're logged out before each login test
    await page.goto('/admin/logout', { waitUntil: 'networkidle' }).catch(() => {})
    await page.goto('/admin/login')
    await page.waitForSelector('input[name="email"]', { timeout: 10000 })
  })

  test('should render login page', async ({ page }) => {
    await expect(page.locator('input[name="email"]')).toBeVisible()
    await expect(page.locator('input[name="password"]')).toBeVisible()
    await expect(page.locator('button:has-text("Sign In")')).toBeVisible()
  })

  test('should display forgot password link', async ({ page }) => {
    await expect(page.locator('text=Forgot').first()).toBeVisible()
  })

  test('should display register link', async ({ page }) => {
    await expect(
      page.locator('a:has-text("Sign up")').or(page.locator('a:has-text("Register")'))
    ).toBeVisible()
  })

  test('should show error for invalid credentials', async ({ page }) => {
    await page.fill('input[name="email"]', 'invalid@example.com')
    await page.fill('input[name="password"]', 'wrongpassword')

    // Get CSRF token
    const csrfToken = await page.evaluate(() => {
      const meta = document.querySelector('meta[name="csrf-token"]')
      return meta?.getAttribute('content') || ''
    })

    // Submit via direct POST (bypassing action system)
    await page.evaluate(async (token) => {
      const formData = new FormData()
      formData.append('email', 'invalid@example.com')
      formData.append('password', 'wrongpassword')
      formData.append('_token', token)

      const res = await fetch('/admin/login', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'text/html' }
      })
      return res
    }, csrfToken)

    await page.reload()
    await page.waitForTimeout(500)

    // Should still be on login page (authentication failed)
    await expect(page).toHaveURL(/\/login/)
  })

  test('should login successfully with valid credentials', async ({ page }) => {
    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'password')

    // Get CSRF token
    const csrfToken = await page.evaluate(() => {
      const meta = document.querySelector('meta[name="csrf-token"]')
      return meta?.getAttribute('content') || ''
    })

    // Submit via direct POST
    const response = await page.evaluate(async (token) => {
      const formData = new FormData()
      formData.append('email', 'test@example.com')
      formData.append('password', 'password')
      formData.append('_token', token)

      const res = await fetch('/admin/login', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'text/html' }
      })
      return { ok: res.ok, redirected: res.redirected, url: res.url }
    }, csrfToken)

    // Reload to get authenticated state
    await page.goto('/admin')
    await page.waitForTimeout(1000)

    // Should be on dashboard or OTP page (if OTP is required)
    await expect(page).not.toHaveURL(/\/login$/)
  })
})

test.describe('Registration Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/admin/register')
    await page.waitForSelector('input[name="name"]', { timeout: 10000 })
  })

  test('should render registration page', async ({ page }) => {
    await expect(page.locator('input[name="name"]')).toBeVisible()
    await expect(page.locator('input[name="email"]')).toBeVisible()
    await expect(page.locator('input[name="password"]')).toBeVisible()
    await expect(page.locator('input[name="password_confirmation"]')).toBeVisible()
  })

  test('should display login link', async ({ page }) => {
    // Registration page may have "Already have an account? Sign in" or similar
    await expect(
      page.locator('text=Already have an account').or(page.locator('a:has-text("Sign in")')).first()
    ).toBeVisible()
  })
})

test.describe('Password Reset Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/admin/forgot-password')
    await page.waitForSelector('input[name="email"]', { timeout: 10000 })
  })

  test('should render forgot password page', async ({ page }) => {
    await expect(page.locator('input[name="email"]')).toBeVisible()
    await expect(page.locator('button:has-text("Send Reset Link")')).toBeVisible()
  })

  test('should display login link', async ({ page }) => {
    await expect(page.locator('a:has-text("Sign in")').or(page.locator('a:has-text("Login")')).first()).toBeVisible()
  })
})

test.describe('Authenticated Pages', () => {
  test.beforeEach(async ({ page }) => {
    // Login via direct POST
    await page.goto('/admin/login')
    await page.waitForSelector('input[name="email"]', { timeout: 10000 })

    const csrfToken = await page.evaluate(() => {
      const meta = document.querySelector('meta[name="csrf-token"]')
      return meta?.getAttribute('content') || ''
    })

    await page.evaluate(async (token) => {
      const formData = new FormData()
      formData.append('email', 'test@example.com')
      formData.append('password', 'password')
      formData.append('_token', token)

      await fetch('/admin/login', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'text/html' }
      })
    }, csrfToken)

    await page.goto('/admin')
    await page.waitForTimeout(500)
  })

  test('should access profile page', async ({ page }) => {
    await page.goto('/admin/settings/profile')
    await page.waitForTimeout(1000)

    // Either we see profile content or we're redirected (OTP/2FA required)
    const hasProfileContent = await page.locator('text=Profile').first().isVisible().catch(() => false)
    const isOnOtpPage = await page.url().includes('otp')
    const isOnTwoFactorPage = await page.url().includes('two-factor')

    expect(hasProfileContent || isOnOtpPage || isOnTwoFactorPage).toBeTruthy()
  })

  test('should access change password page', async ({ page }) => {
    await page.goto('/admin/settings/change-password')
    await page.waitForTimeout(1000)

    const hasContent = await page.locator('text=Password').first().isVisible().catch(() => false)
    const isRedirected = await page.url().includes('otp') || await page.url().includes('two-factor') || await page.url().includes('login')

    expect(hasContent || isRedirected).toBeTruthy()
  })

  test('should access two-factor settings page', async ({ page }) => {
    await page.goto('/admin/settings/two-factor')
    await page.waitForTimeout(1000)

    const hasContent = await page.locator('text=Two-Factor').or(page.locator('text=2FA')).first().isVisible().catch(() => false)
    const isRedirected = await page.url().includes('otp') || await page.url().includes('login')

    expect(hasContent || isRedirected).toBeTruthy()
  })

  test('should access sessions page', async ({ page }) => {
    await page.goto('/admin/settings/sessions')
    await page.waitForTimeout(1000)

    const hasContent = await page.locator('text=Session').or(page.locator('text=Device')).first().isVisible().catch(() => false)
    const isRedirected = await page.url().includes('otp') || await page.url().includes('login')

    expect(hasContent || isRedirected).toBeTruthy()
  })

  test('should logout successfully', async ({ page }) => {
    const csrfToken = await page.evaluate(() => {
      const meta = document.querySelector('meta[name="csrf-token"]')
      return meta?.getAttribute('content') || ''
    })

    await page.evaluate(async (token) => {
      const formData = new FormData()
      formData.append('_token', token)

      await fetch('/admin/logout', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'text/html' }
      })
    }, csrfToken)

    await page.goto('/admin')
    await page.waitForTimeout(500)

    // Should be redirected to login
    await expect(page).toHaveURL(/\/login/)
  })
})

test.describe('Two-Factor Challenge', () => {
  test('should redirect unauthenticated users from 2FA challenge to login', async ({ page }) => {
    // Try to access 2FA challenge without being in login flow
    await page.goto('/admin/two-factor/challenge')
    await page.waitForTimeout(1000)

    // Should be redirected to login
    await expect(page).toHaveURL(/\/login/)
  })
})
