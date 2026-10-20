# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: school-support-contrast.spec.js >> les actions de contexte école restent lisibles quand le contexte est actif
- Location: tests\ui\school-support-contrast.spec.js:3:1

# Error details

```
Test timeout of 180000ms exceeded.
```

```
Error: page.waitForResponse: Test timeout of 180000ms exceeded.
```

# Page snapshot

```yaml
- generic [active] [ref=e1]:
  - main [ref=e4]:
    - generic [ref=e6]:
      - generic [ref=e7]:
        - generic [ref=e8]: Scolaris
        - heading "Connectez-vous à votre compte" [level=1] [ref=e9]
      - generic [ref=e10]:
        - generic [ref=e11]:
          - generic [ref=e14]:
            - generic [ref=e17]:
              - text: Adresse e-mail ou téléphone
              - superscript [ref=e18]: "*"
            - textbox "Adresse e-mail ou téléphone*" [ref=e22]: superadmin@example.com
          - generic [ref=e25]:
            - generic [ref=e28]:
              - text: Mot de passe
              - superscript [ref=e29]: "*"
            - generic [ref=e31]:
              - textbox "Mot de passe*" [ref=e33]: password
              - button "Montrer le mot de passe" [disabled] [ref=e36]
          - generic [ref=e45]:
            - checkbox "Se souvenir de moi" [disabled]
            - generic [ref=e46]: Se souvenir de moi
        - button "Connexion" [disabled] [ref=e49]
  - generic:
    - status
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | test('les actions de contexte école restent lisibles quand le contexte est actif', async ({ page }) => {
  4  |     test.setTimeout(180_000);
  5  |     await page.setViewportSize({ width: 1280, height: 800 });
  6  |     await page.goto('/admin/login', { waitUntil: 'domcontentloaded' });
  7  |     await page.getByLabel('Adresse e-mail ou téléphone').fill('superadmin@example.com');
  8  |     await page.getByLabel('Mot de passe').fill('password');
  9  | 
> 10 |     const loginSubmission = page.waitForResponse((response) => response.url().includes('/livewire/update'));
     |                                  ^ Error: page.waitForResponse: Test timeout of 180000ms exceeded.
  11 |     await page.getByRole('button', { name: 'Connexion' }).click();
  12 |     expect((await loginSubmission).status()).toBe(200);
  13 |     await expect(page).toHaveURL(/\/admin\/?$/);
  14 | 
  15 |     await page.goto('/admin/school-support', { waitUntil: 'domcontentloaded' });
  16 |     await page.getByRole('button', { name: 'Ouvrir le contexte école' }).click();
  17 |     await expect(page).toHaveURL(/\/admin\/?$/);
  18 |     await page.goto('/admin/school-support', { waitUntil: 'domcontentloaded' });
  19 | 
  20 |     const contrast = await page.getByRole('button', { name: 'Configurer l\'école' }).evaluate((button) => {
  21 |         const parse = (color) => color.match(/[\d.]+/g).slice(0, 3).map(Number);
  22 |         const luminance = (color) => {
  23 |             const [red, green, blue] = parse(color).map((value) => {
  24 |                 const channel = value / 255;
  25 |                 return channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
  26 |             });
  27 | 
  28 |             return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
  29 |         };
  30 |         const style = getComputedStyle(button);
  31 |         const background = style.backgroundColor === 'rgba(0, 0, 0, 0)'
  32 |             ? getComputedStyle(button.parentElement).backgroundColor
  33 |             : style.backgroundColor;
  34 |         const values = [luminance(style.color), luminance(background)].sort((a, b) => b - a);
  35 | 
  36 |         return { color: style.color, background, ratio: (values[0] + 0.05) / (values[1] + 0.05) };
  37 |     });
  38 | 
  39 |     expect(contrast.ratio, JSON.stringify(contrast)).toBeGreaterThanOrEqual(4.5);
  40 | });
  41 | 
```