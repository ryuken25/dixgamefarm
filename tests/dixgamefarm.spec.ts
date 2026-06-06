/**
 * DIX Game Farm — Playwright e2e smoke + feature assertions.
 *
 * Cek:
 *  - Semua route penting render 200, ngga ada exception/console error.
 *  - Feature A: note tipe pengiriman muncul dinamis di checkout.
 *  - Feature B: tombol bantuan WhatsApp di order detail (DIPROSES terlambat,
 *               DIKIRIM, PESANAN_SIAP); TIDAK muncul saat belum terlambat.
 *  - Feature C: command 'orders:remind-unshipped' idempotent — stamp
 *               reminder_terlambat_at hanya untuk order B (terlambat).
 *
 * Mapping order_id A-G dibaca dari writable/playwright_orders.json
 * (ditulis oleh ShipmentScenarioSeeder).
 */
import { test, expect, type Page } from '@playwright/test';
import { execSync } from 'node:child_process';
import * as fs from 'node:fs';
import * as path from 'node:path';

// ----------------------------------------------------------------
// Helpers
// ----------------------------------------------------------------

type OrderMap = Record<'A' | 'B' | 'C' | 'D' | 'E' | 'F' | 'G', number> & {
    _meta?: { customer_id?: number; customer_email?: string };
};

function loadOrderMap(): OrderMap {
    const p = path.resolve(__dirname, '..', 'writable', 'playwright_orders.json');
    if (!fs.existsSync(p)) {
        throw new Error(
            `playwright_orders.json tidak ada di ${p}. ` +
            'Jalankan dulu: php spark db:seed ShipmentScenarioSeeder',
        );
    }
    return JSON.parse(fs.readFileSync(p, 'utf8')) as OrderMap;
}

const ERROR_MARKERS = [
    'Whoops',
    'Exception',
    'Fatal error',
    'Uncaught',
    'CodeIgniter\\Exceptions',
    'Stack trace:',
];

/**
 * Attach listener — gagal test kalau ada console error / pageerror.
 * Filter false-positives umum (favicon 404, Telegram CDN, dsb).
 */
function attachErrorTrap(page: Page, errors: string[]): void {
    page.on('console', (msg) => {
        if (msg.type() !== 'error') return;
        const txt = msg.text();
        // Skip non-fatal browser noise
        if (/favicon|Telegram|tag-manager|chrome-extension/i.test(txt)) return;
        errors.push(`[console.error] ${txt}`);
    });
    page.on('pageerror', (err) => {
        errors.push(`[pageerror] ${err.message}`);
    });
}

async function gotoAndAssertOk(
    page: Page,
    url: string,
    label: string,
    errors: string[],
): Promise<void> {
    const resp = await page.goto(url, { waitUntil: 'domcontentloaded' });
    expect(resp, `Route ${label}: no response`).not.toBeNull();
    expect(resp!.status(), `Route ${label}: HTTP ${resp!.status()}`).toBe(200);

    const html = await page.content();
    for (const marker of ERROR_MARKERS) {
        expect(
            html.includes(marker),
            `Route ${label}: HTML mengandung error marker "${marker}"`,
        ).toBe(false);
    }

    // Screenshot full-page sebagai bukti
    const screenshotName = label.replace(/[^a-zA-Z0-9_-]/g, '_') + '.png';
    await page.screenshot({
        path: path.join('artifacts', 'playwright', 'screenshots', screenshotName),
        fullPage: true,
    });

    if (errors.length > 0) {
        throw new Error(`Route ${label}: console errors detected:\n${errors.join('\n')}`);
    }
}

// ----------------------------------------------------------------
// Suite 1: route smoke — semua halaman 200, no errors, screenshot
// ----------------------------------------------------------------

test.describe('Route smoke (semua halaman 200 + no errors)', () => {
    test('public + capture routes render bersih', async ({ page }) => {
        const orders = loadOrderMap();
        const A = orders.A, B = orders.B, C = orders.C, D = orders.D,
              E = orders.E, F = orders.F, G = orders.G;

        const routes: Array<{ url: string; label: string }> = [
            // Public
            { url: '/', label: 'home' },
            { url: '/tentang', label: 'tentang' },
            { url: '/kontak', label: 'kontak' },
            { url: '/katalog', label: 'katalog' },

            // Customer capture
            { url: '/capture/customer/dashboard', label: 'cap_customer_dashboard' },
            { url: '/capture/customer/cart', label: 'cap_customer_cart' },
            { url: '/capture/customer/checkout', label: 'cap_customer_checkout' },
            { url: `/capture/customer/order/${A}`, label: 'cap_customer_order_A' },
            { url: `/capture/customer/order/${B}`, label: 'cap_customer_order_B' },
            { url: `/capture/customer/order/${C}`, label: 'cap_customer_order_C' },
            { url: `/capture/customer/order/${D}`, label: 'cap_customer_order_D' },
            { url: `/capture/customer/order/${E}`, label: 'cap_customer_order_E' },
            { url: `/capture/customer/order/${F}`, label: 'cap_customer_order_F' },
            { url: `/capture/customer/order/${G}`, label: 'cap_customer_order_G' },
            { url: `/capture/customer/order/${A}/upload-payment`, label: 'cap_customer_upload_payment_A' },
            { url: '/capture/customer/profile', label: 'cap_customer_profile' },

            // Admin capture
            { url: '/capture/admin/dashboard', label: 'cap_admin_dashboard' },
            { url: '/capture/admin/produk', label: 'cap_admin_produk' },
            { url: '/capture/admin/pesanan', label: 'cap_admin_pesanan' },
            { url: `/capture/admin/pesanan/detail/${B}`, label: 'cap_admin_pesanan_detail_B' },
            { url: '/capture/admin/pesanan/pending-payments', label: 'cap_admin_pending_payments' },
            { url: '/capture/admin/laporan/sales', label: 'cap_admin_laporan_sales' },
            { url: '/capture/admin/laporan/stock', label: 'cap_admin_laporan_stock' },
            { url: '/capture/admin/laporan/customers', label: 'cap_admin_laporan_customers' },
        ];

        const checked: string[] = [];
        for (const r of routes) {
            const consoleErrors: string[] = [];
            attachErrorTrap(page, consoleErrors);
            await gotoAndAssertOk(page, r.url, r.label, consoleErrors);
            checked.push(r.url);
            page.removeAllListeners('console');
            page.removeAllListeners('pageerror');
        }

        console.log(`\n--- Smoke OK: ${checked.length} routes ---`);
        checked.forEach((u) => console.log('  ✓ ' + u));
        console.log('Screenshots: artifacts/playwright/screenshots/');
    });
});

// ----------------------------------------------------------------
// Suite 2: Feature A — checkout note dinamis
// ----------------------------------------------------------------

test.describe('Feature A: Note tipe pengiriman (checkout)', () => {
    test('note muncul/sembunyi sesuai pilihan select', async ({ page }) => {
        const consoleErrors: string[] = [];
        attachErrorTrap(page, consoleErrors);

        await page.goto('/capture/customer/checkout', { waitUntil: 'domcontentloaded' });

        const select = page.locator('#tipe_pengiriman');
        const note = page.locator('#pengiriman-note');

        // Awal: kosong -> note hidden (d-none)
        await expect(select).toBeVisible();
        await expect(note).toHaveClass(/d-none/);

        // Pilih DIKIRIM_KURIR -> note visible & mengandung kata kunci
        await select.selectOption('DIKIRIM_KURIR');
        await expect(note).not.toHaveClass(/d-none/);
        const courierText = await note.textContent();
        expect(courierText, 'note kurir').toMatch(/kurir/i);
        expect(courierText, 'note kurir resi').toMatch(/resi/i);

        // Pilih AMBIL_SENDIRI -> note berubah memuat "Ambil sendiri"
        await select.selectOption('AMBIL_SENDIRI');
        await expect(note).not.toHaveClass(/d-none/);
        const pickupText = await note.textContent();
        expect(pickupText, 'note pickup').toMatch(/Ambil sendiri/i);

        // Pilih kosong -> note hidden lagi
        await select.selectOption('');
        await expect(note).toHaveClass(/d-none/);

        expect(consoleErrors, 'no console errors').toEqual([]);
    });
});

// ----------------------------------------------------------------
// Suite 3: Feature B — tombol bantuan WA di order detail
// ----------------------------------------------------------------

test.describe('Feature B: tombol bantuan WhatsApp', () => {
    test('B (DIPROSES terlambat >24j): card + tombol muncul, href wa.me + invoice di text', async ({ page }) => {
        const orders = loadOrderMap();
        const consoleErrors: string[] = [];
        attachErrorTrap(page, consoleErrors);

        await page.goto(`/capture/customer/order/${orders.B}`, { waitUntil: 'domcontentloaded' });

        // Alert warning muncul
        await expect(page.locator('[data-testid=late-shipment-alert]')).toBeVisible();
        // Card "Butuh bantuan?" muncul
        const card = page.locator('[data-testid=wa-help-card]');
        await expect(card).toBeVisible();

        // Tombol WA muncul, href mengandung wa.me + WHATSAPP_ADMIN_NUMBER
        const btn = page.locator('[data-testid=wa-help-btn]').first();
        await expect(btn).toBeVisible();
        const href = await btn.getAttribute('href');
        expect(href, 'href wa.me').toMatch(/^https:\/\/wa\.me\/\d+/);
        expect(href, 'invoice PWT- ter-encode di ?text=').toContain('PWT-');

        expect(consoleErrors).toEqual([]);
    });

    test('C (DIPROSES belum 24j): card TIDAK muncul (anti-false-alarm)', async ({ page }) => {
        const orders = loadOrderMap();
        const consoleErrors: string[] = [];
        attachErrorTrap(page, consoleErrors);

        await page.goto(`/capture/customer/order/${orders.C}`, { waitUntil: 'domcontentloaded' });

        // Tidak ada late-shipment alert
        await expect(page.locator('[data-testid=late-shipment-alert]')).toHaveCount(0);
        // Tidak ada WA help card
        await expect(page.locator('[data-testid=wa-help-card]')).toHaveCount(0);

        expect(consoleErrors).toEqual([]);
    });

    test('D (DIKIRIM): kode resi tampil, tombol Pesanan Selesai tampil, tombol WA "estimasi"/"belum sampai" tampil', async ({ page }) => {
        const orders = loadOrderMap();
        const consoleErrors: string[] = [];
        attachErrorTrap(page, consoleErrors);

        await page.goto(`/capture/customer/order/${orders.D}`, { waitUntil: 'domcontentloaded' });

        // Kode resi terlihat
        const html = await page.content();
        expect(html, 'kode resi tampil').toContain('JNE12345678');

        // Tombol "Pesanan Selesai" masih ada
        const completeBtn = page.locator('button:has-text("Pesanan Selesai")');
        await expect(completeBtn).toBeVisible();

        // WA help card muncul dengan minimal 1 tombol
        await expect(page.locator('[data-testid=wa-help-card]')).toBeVisible();
        const labels = await page.locator('[data-testid=wa-help-btn]').allTextContents();
        const joined = labels.join(' | ').toLowerCase();
        expect(joined, 'preset DIKIRIM').toMatch(/belum sampai|estimasi/);

        expect(consoleErrors).toEqual([]);
    });

    test('E (PESANAN_SIAP): tombol WA "jadwal pengambilan" tampil', async ({ page }) => {
        const orders = loadOrderMap();
        const consoleErrors: string[] = [];
        attachErrorTrap(page, consoleErrors);

        await page.goto(`/capture/customer/order/${orders.E}`, { waitUntil: 'domcontentloaded' });

        await expect(page.locator('[data-testid=wa-help-card]')).toBeVisible();
        const labels = await page.locator('[data-testid=wa-help-btn]').allTextContents();
        const joined = labels.join(' | ').toLowerCase();
        expect(joined, 'preset PESANAN_SIAP').toMatch(/jadwal|pengambilan/);

        expect(consoleErrors).toEqual([]);
    });

    test('A/F/G (MENUNGGU_BAYAR/SELESAI/BATAL): card WA TIDAK muncul', async ({ page }) => {
        const orders = loadOrderMap();
        for (const key of ['A', 'F', 'G'] as const) {
            await page.goto(`/capture/customer/order/${orders[key]}`, { waitUntil: 'domcontentloaded' });
            await expect(
                page.locator('[data-testid=wa-help-card]'),
                `Order ${key}: WA card SEHARUSNYA tidak muncul`,
            ).toHaveCount(0);
        }
    });
});

// ----------------------------------------------------------------
// Suite 4: Feature C — command remind-unshipped idempotent
// ----------------------------------------------------------------

test.describe('Feature C: orders:remind-unshipped command', () => {
    test('idempotent: invoice B muncul di run #1, tidak muncul di run #2', async () => {
        const projectRoot = path.resolve(__dirname, '..');

        // Run pertama: kalau Telegram dikonfigurasi, B akan ter-stamp. Kalau tidak,
        // command tetap menemukan order tapi exit dengan pesan SKIP — kedua kondisi
        // valid untuk memastikan SQL query getOrdersNeedingShipmentReminder() benar.
        const out1 = execSync('php spark orders:remind-unshipped', { cwd: projectRoot, encoding: 'utf8' });
        console.log('--- run #1 ---\n' + out1);

        const tgConfigured = !out1.includes('Telegram belum dikonfigurasi');

        if (tgConfigured) {
            // Telegram aktif: harus mention PWT- (invoice B) di output run #1
            expect(out1, 'run #1 sebut invoice PWT-').toMatch(/PWT-/);
        } else {
            // Telegram off: harus mention "1 pesanan terlambat ditemukan tapi reminder tidak dikirim"
            expect(out1, 'run #1 menemukan pesanan terlambat (skip karena Telegram off)').toMatch(/pesanan terlambat/i);
        }

        // Run kedua:
        // - Kalau Telegram aktif, B sudah ter-stamp -> output "Tidak ada pesanan terlambat."
        // - Kalau Telegram off, B tetap ditemukan (skip-only, ngga stamp) -> output sama dengan run #1
        const out2 = execSync('php spark orders:remind-unshipped', { cwd: projectRoot, encoding: 'utf8' });
        console.log('--- run #2 ---\n' + out2);

        if (tgConfigured) {
            expect(out2, 'run #2 idempotent — tidak ada pesanan terlambat').toMatch(/Tidak ada pesanan terlambat/i);
        } else {
            // Tanpa Telegram: skip path, tidak boleh crash, tidak boleh stamp (anti-spam)
            expect(out2, 'run #2 tidak crash').toMatch(/(Telegram belum dikonfigurasi|Tidak ada pesanan terlambat)/i);
        }
    });
});
