// PLAYWRIGHT_MODULE=/ruta/externa/playwright/index.mjs node tests/Browser/menu-administrativo.mjs
// Renderiza el layout real con un usuario no persistido. No inicia sesión ni consulta MySQL.
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE
    ? pathToFileURL(process.env.PLAYWRIGHT_MODULE).href : 'playwright');
const html = execFileSync('php', ['-r', `
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
echo view('layouts.administrativo', [
    'administrador' => new App\\Models\\User(['name' => 'Prueba', 'email' => 'prueba@example.test']),
    'currentDate' => 'Prueba de teclado',
])->render();
`], { encoding: 'utf8' }).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '').replace(/<link\b[^>]*>/gi, '');

const browser = await chromium.launch({ headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 390, height: 844 }, reducedMotion: 'reduce' });
    await page.route('**/*', async (route) => {
        const path = new URL(route.request().url()).pathname;
        if (path.startsWith('/resources/') && !path.includes('..')) {
            await route.fulfill({ body: await readFile(resolve('.' + path)), contentType: path.endsWith('.js') ? 'text/javascript' : 'text/css' });
        } else {
            await route.fulfill({ body: html, contentType: 'text/html' });
        }
    });
    await page.goto('http://citavital.test');
    await page.addStyleTag({ url: 'http://citavital.test/resources/css/administrativo.css' });
    await page.addScriptTag({ url: 'http://citavital.test/resources/js/administrativo.js', type: 'module' });
    const sidebar = page.locator('[data-admin-sidebar]');
    const toggle = page.locator('[data-admin-menu-toggle]');
    const close = page.locator('[data-admin-menu-close]');
    await page.waitForFunction(() => document.querySelector('[data-admin-sidebar]').inert);
    for (let i = 0; i < 20; i++) {
        await page.keyboard.press('Tab');
        assert.equal(await sidebar.evaluate(el => el.contains(document.activeElement)), false);
    }
    await toggle.focus();
    await page.keyboard.press('Enter');
    assert.equal(await close.evaluate(el => el === document.activeElement), true);
    assert.equal(await sidebar.getAttribute('aria-modal'), 'true');
    assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
    for (const key of ['Tab', 'Shift+Tab']) {
        for (let i = 0; i < 24; i++) {
            await page.keyboard.press(key);
            assert.equal(await sidebar.evaluate(el => el.contains(document.activeElement)), true);
        }
    }
    assert.equal(await sidebar.evaluate(el => getComputedStyle(el).transitionDuration), '0s');
    await page.keyboard.press('Escape');
    assert.equal(await toggle.evaluate(el => el === document.activeElement), true);
    assert.equal(await sidebar.evaluate(el => el.inert), true);
    assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
    await page.keyboard.press('Enter');
    await close.press('Enter');
    assert.equal(await toggle.evaluate(el => el === document.activeElement), true);
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.waitForFunction(() => !document.querySelector('[data-admin-sidebar]').inert);
    assert.equal(await sidebar.getAttribute('aria-modal'), null);
    await sidebar.locator('a').first().focus();
    await page.setViewportSize({ width: 390, height: 844 });
    await page.waitForFunction(() => document.querySelector('[data-admin-sidebar]').inert);
    assert.equal(await toggle.evaluate(el => el === document.activeElement), true);
    console.log('PASS: menú cerrado, apertura por teclado, foco circular Tab/Shift+Tab, Escape, cierre, ARIA, reduced-motion y cambios de viewport.');
} finally {
    await browser.close();
}
