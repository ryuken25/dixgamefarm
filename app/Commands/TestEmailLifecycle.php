<?php

namespace App\Commands;

use App\Models\ItemKeranjangModel;
use App\Models\KeranjangModel;
use App\Models\PembayaranModel;
use App\Models\PesananModel;
use App\Models\ProdukModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Simulasi end-to-end lengkap untuk testing SEMUA email notifikasi.
 *
 * Flow lengkap (5 email): created -> paid -> shipped -> completed
 * (atau cancelled di skenario terpisah).
 *
 * Skenario tersedia:
 *   - default   : Telur Ayam Kampung Super (murah, untuk smoke test)
 *   - expensive : Popeye Grey (Rp 5juta)
 *   - preorder  : DOC Ayam Joper (PO product)
 *   - all       : jalankan ketiga skenario sekaligus (kirim 3x4 = 12 email)
 *
 * Pakai:
 *   php spark test:email-lifecycle target@gmail.com expensive
 *   php spark test:email-lifecycle target@gmail.com preorder
 *   php spark test:email-lifecycle target@gmail.com all
 */
class TestEmailLifecycle extends BaseCommand
{
    protected $group       = 'Test';
    protected $name        = 'test:email-lifecycle';
    protected $description = 'Simulasi full lifecycle (created->paid->shipped->completed) untuk uji email.';
    protected $usage       = 'test:email-lifecycle <email> [scenario]';
    protected $arguments   = [
        'email'    => 'Email target (wajib).',
        'scenario' => 'default|expensive|preorder|all (default: expensive).',
    ];

    private const SCENARIOS = [
        'default' => [
            'label'   => 'Standard product (Telur Ayam Kampung Super)',
            'nama'    => 'Test Buyer',
            'product' => ['nama' => 'Telur Ayam Kampung Super'],
            'qty'     => 2,
            'tipe'    => 'AMBIL_SENDIRI',
        ],
        'expensive' => [
            'label'   => 'EXPENSIVE — Popeye Grey (Rp 5 juta)',
            'nama'    => 'Pak Kolektor',
            'product' => ['nama' => 'Popeye Grey'],
            'qty'     => 1,
            'tipe'    => 'DIKIRIM_KURIR',
        ],
        'preorder' => [
            'label'   => 'PRE-ORDER — DOC Ayam Joper',
            'nama'    => 'Peternak Joper',
            'product' => ['nama' => 'DOC Ayam Joper'],
            'qty'     => 2,
            'tipe'    => 'DIKIRIM_KURIR',
        ],
    ];

    public function run(array $params = [])
    {
        $email    = $params[0] ?? null;
        $scenario = strtolower($params[1] ?? 'expensive');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            CLI::error('Email target tidak valid. Contoh: php spark test:email-lifecycle target@gmail.com expensive');
            return;
        }

        if ($scenario === 'all') {
            foreach (['default', 'expensive', 'preorder'] as $s) {
                $this->runScenario($email, $s);
                CLI::write("\n" . str_repeat('=', 60) . "\n", 'white');
            }
            return;
        }

        if (!isset(self::SCENARIOS[$scenario])) {
            CLI::error("Skenario '{$scenario}' tidak dikenal. Pilihan: default|expensive|preorder|all");
            return;
        }

        $this->runScenario($email, $scenario);
    }

    private function runScenario(string $email, string $scenario): void
    {
        $cfg = self::SCENARIOS[$scenario];

        CLI::newLine();
        CLI::write('################################################################', 'yellow');
        CLI::write('# SKENARIO: ' . $cfg['label'], 'yellow');
        CLI::write('################################################################', 'yellow');

        $userModel       = new UserModel();
        $keranjangModel  = new KeranjangModel();
        $itemKeranjangM  = new ItemKeranjangModel();
        $produkModel     = new ProdukModel();
        $pesananModel    = new PesananModel();
        $pembayaranModel = new PembayaranModel();
        $db              = \Config\Database::connect();

        // 1. Register
        CLI::write("\n[1/7] Register customer", 'cyan');
        $existing = $userModel->where('email', $email)->first();
        if ($existing) {
            $userModel->update($existing['id'], ['password_hash' => '123123123']);
            $userId = (int) $existing['id'];
            CLI::write("    User id={$userId} sudah ada. Password reset.", 'white');
        } else {
            $userId = $userModel->insert([
                'email'          => $email,
                'password_hash'  => '123123123',
                'nama_lengkap'   => $cfg['nama'],
                'role'           => 'PELANGGAN',
                'no_hp'          => '081234567890',
                'alamat_lengkap' => 'Jl. Test, Denpasar, Bali',
            ]);
            CLI::write("    Register OK. user_id={$userId}", 'green');
        }

        // 2. Pick product
        CLI::write("\n[2/7] Pilih produk: {$cfg['product']['nama']}", 'cyan');
        $produk = $produkModel->where('nama_ayam', $cfg['product']['nama'])
            ->where('is_active', 1)
            ->first();
        if (!$produk) {
            CLI::error("    Produk '{$cfg['product']['nama']}' tidak ditemukan.");
            return;
        }
        $poFlag = !empty($produk['is_preorder']) ? ' [PRE-ORDER]' : '';
        CLI::write("    {$produk['nama_ayam']}{$poFlag} — Rp " . number_format($produk['harga'], 0, ',', '.') . " (stok={$produk['stok_tersedia']})", 'white');

        // 3. Cart setup
        CLI::write("\n[3/7] Setup keranjang", 'cyan');
        $cart = $keranjangModel->where('user_id', $userId)->first();
        if (!$cart) {
            $cartId = $keranjangModel->insert([
                'user_id'      => $userId,
                'last_updated' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $cartId = (int) $cart['id'];
            $db->table('item_keranjang')->where('keranjang_id', $cartId)->delete();
        }
        $itemKeranjangM->insert([
            'keranjang_id' => $cartId,
            'produk_id'    => $produk['id'],
            'jumlah'       => $cfg['qty'],
        ]);
        CLI::write("    Cart id={$cartId}. Item: {$cfg['qty']}x {$produk['nama_ayam']}", 'white');

        // 4. createOrder -> Email 1
        CLI::write("\n[4/7] Checkout (createOrder) -> [EMAIL 1: Pesanan Diterima]", 'cyan');
        $cartData = $keranjangModel->getCartWithItems($userId);
        $alamat = $cfg['tipe'] === 'DIKIRIM_KURIR'
            ? 'Jl. Sudirman No. 123, Denpasar Selatan, Bali 80114'
            : 'Jl. Test, Denpasar, Bali';
        $result = $pesananModel->createOrder(
            $userId,
            $cartData['items'],
            $cfg['tipe'],
            'Test lifecycle scenario: ' . $scenario,
            'BCA',
            '1234567890',
            [
                'nama_lengkap'   => $cfg['nama'],
                'email'          => $email,
                'no_hp'          => '081234567890',
                'alamat_lengkap' => $alamat,
            ]
        );
        if (!$result['success']) {
            CLI::error('    createOrder gagal: ' . $result['message']);
            return;
        }
        $orderId = (int) $result['order_id'];
        $invoice = $result['invoice_number'];
        $total   = (float) $cartData['total'];
        CLI::write("    Order #{$orderId} ({$invoice}) — Rp " . number_format($total, 0, ',', '.'), 'green');

        // 5. Upload bukti
        CLI::write("\n[5/7] Upload bukti pembayaran (dummy PNG)", 'cyan');
        $buktiDir = FCPATH . 'uploads/bukti_bayar';
        if (!is_dir($buktiDir)) {
            mkdir($buktiDir, 0755, true);
        }
        $buktiFilename = "test_bukti_{$scenario}_{$orderId}.png";
        $buktiPath = $buktiDir . DIRECTORY_SEPARATOR . $buktiFilename;
        file_put_contents($buktiPath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $upload = $pembayaranModel->uploadPaymentProof(
            $orderId,
            $total,
            'BCA - ' . $cfg['nama'],
            'BCA',
            'uploads/bukti_bayar/' . $buktiFilename
        );
        if (!$upload['success']) {
            CLI::error('    uploadPaymentProof gagal: ' . ($upload['message'] ?? 'unknown'));
            return;
        }
        $paymentId = (int) $upload['payment_id'];
        CLI::write("    Payment #{$paymentId} PENDING.", 'white');

        // 6. Admin verify -> Email 2
        CLI::write("\n[6/7] Admin verifyPayment -> [EMAIL 2: Pembayaran Diverifikasi]", 'cyan');
        $admin   = $userModel->where('role', 'ADMIN')->first();
        $adminId = $admin ? (int) $admin['id'] : 1;
        $verify  = $pembayaranModel->verifyPayment($paymentId, $adminId, true, null);
        if (!$verify['success']) {
            CLI::error('    verifyPayment gagal: ' . $verify['message']);
            return;
        }
        CLI::write("    Payment VALID. Status: DIPROSES.", 'green');

        // 7. Status ke DIKIRIM/PESANAN_SIAP -> Email 3
        $nextStatus = $cfg['tipe'] === 'DIKIRIM_KURIR' ? 'DIKIRIM' : 'PESANAN_SIAP';
        CLI::write("\n[7a] Update ke {$nextStatus} -> [EMAIL 3: Pesanan Dikirim / Siap Diambil]", 'cyan');
        $extraData = ['admin_id' => $adminId];
        if ($nextStatus === 'DIKIRIM') {
            $extraData['kode_resi'] = 'JNE' . strtoupper(bin2hex(random_bytes(4)));
        }
        $ok = $pesananModel->updateOrderStatus($orderId, $nextStatus, $extraData);
        if (!$ok) {
            CLI::error("    updateOrderStatus -> {$nextStatus} gagal.");
            return;
        }
        CLI::write("    Status: {$nextStatus}" . (isset($extraData['kode_resi']) ? " (resi: {$extraData['kode_resi']})" : ''), 'green');

        // 7b. Status ke SELESAI -> Email 4
        CLI::write("\n[7b] Update ke SELESAI -> [EMAIL 4: Pesanan Selesai]", 'cyan');
        $ok = $pesananModel->updateOrderStatus($orderId, 'SELESAI', ['admin_id' => $adminId]);
        if (!$ok) {
            CLI::error('    updateOrderStatus -> SELESAI gagal.');
            return;
        }
        CLI::write("    Status: SELESAI. Stock finalized.", 'green');

        // Done
        CLI::newLine();
        CLI::write("==[ SKENARIO '{$scenario}' SELESAI ]==", 'green');
        CLI::write("    Customer : {$email} / 123123123", 'white');
        CLI::write("    Order    : #{$orderId} ({$invoice}) — Rp " . number_format($total, 0, ',', '.'), 'white');
        CLI::write("    4 email terkirim ke {$email}:", 'white');
        CLI::write("      1. [DIX Game Farm] Pesanan Diterima - {$invoice}", 'white');
        CLI::write("      2. Invoice {$invoice} - Pembayaran Diverifikasi", 'white');
        CLI::write("      3. [DIX Game Farm] " . ($nextStatus === 'DIKIRIM' ? 'Pesanan Dikirim' : 'Pesanan Siap Diambil') . " - {$invoice}", 'white');
        CLI::write("      4. [DIX Game Farm] Terima Kasih - Pesanan {$invoice} Selesai", 'white');
    }
}
