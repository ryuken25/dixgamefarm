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
 * Simulasi end-to-end full flow untuk testing email:
 *   register -> checkout -> upload bukti -> admin verifikasi
 *
 * Tujuan: kirim 2 email real (order created + invoice paid) ke alamat target.
 *
 * Pakai:
 *   php spark test:email-flow winayaarya@gmail.com
 *   php spark test:email-flow winayaarya@gmail.com Winaya 123123123
 */
class TestEmailFlow extends BaseCommand
{
    protected $group       = 'Test';
    protected $name        = 'test:email-flow';
    protected $description = 'Simulasi register+checkout+verify untuk uji email notifikasi.';
    protected $usage       = 'test:email-flow <email> [nama] [password]';
    protected $arguments   = [
        'email'    => 'Email customer target (wajib).',
        'nama'     => 'Nama customer (opsional; default: dari email).',
        'password' => 'Password customer (opsional; default: password123).',
    ];

    public function run(array $params = [])
    {
        $email    = $params[0] ?? null;
        $nama     = $params[1] ?? null;
        $password = $params[2] ?? 'password123';

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            CLI::error('Email target tidak valid. Contoh: php spark test:email-flow you@gmail.com');
            return;
        }
        if (!$nama) {
            $nama = ucwords(str_replace(['.', '_', '-'], ' ', explode('@', $email)[0]));
        }

        $userModel       = new UserModel();
        $keranjangModel  = new KeranjangModel();
        $itemKeranjangM  = new ItemKeranjangModel();
        $produkModel     = new ProdukModel();
        $pesananModel    = new PesananModel();
        $pembayaranModel = new PembayaranModel();
        $db              = \Config\Database::connect();

        // -------- STEP 1: Register/upsert customer --------
        CLI::write('==[ STEP 1 ]== Register customer', 'yellow');
        $existing = $userModel->where('email', $email)->first();
        if ($existing) {
            $userModel->update($existing['id'], ['password_hash' => $password]);
            $userId = (int) $existing['id'];
            CLI::write("  User sudah ada (id={$userId}). Password di-reset.", 'cyan');
        } else {
            $userId = $userModel->insert([
                'email'          => $email,
                'password_hash'  => $password,
                'nama_lengkap'   => $nama,
                'role'           => 'PELANGGAN',
                'no_hp'          => '081234567899',
                'alamat_lengkap' => 'Jl. Test Email Flow, Denpasar, Bali',
            ]);
            if (!$userId) {
                CLI::error('Gagal register: ' . json_encode($userModel->errors()));
                return;
            }
            CLI::write("  Register OK. user_id={$userId}", 'green');
        }

        // -------- STEP 2: Pilih produk --------
        CLI::write("\n==[ STEP 2 ]== Pilih produk", 'yellow');
        $produk = $produkModel->where('is_active', 1)
            ->where('stok_tersedia >', 0)
            ->orderBy('id', 'ASC')
            ->first();
        if (!$produk) {
            CLI::error('Tidak ada produk aktif dengan stok > 0. Jalankan seeder dulu.');
            return;
        }
        CLI::write("  {$produk['nama_ayam']} — Rp " . number_format($produk['harga'], 0, ',', '.') . " (stok={$produk['stok_tersedia']})", 'cyan');

        // -------- STEP 3: Cart --------
        CLI::write("\n==[ STEP 3 ]== Setup keranjang", 'yellow');
        $cart = $keranjangModel->where('user_id', $userId)->first();
        if (!$cart) {
            $cartId = $keranjangModel->insert([
                'user_id'      => $userId,
                'last_updated' => date('Y-m-d H:i:s'),
            ]);
            CLI::write("  Cart baru. cart_id={$cartId}", 'green');
        } else {
            $cartId = (int) $cart['id'];
            $db->table('item_keranjang')->where('keranjang_id', $cartId)->delete();
            CLI::write("  Cart sudah ada (id={$cartId}). Item lama dibersihkan.", 'cyan');
        }
        $qty = 2;
        $itemKeranjangM->insert([
            'keranjang_id' => $cartId,
            'produk_id'    => $produk['id'],
            'jumlah'       => $qty,
        ]);
        CLI::write("  Item: {$qty}x {$produk['nama_ayam']}", 'green');

        // -------- STEP 4: createOrder -> email order-created --------
        CLI::write("\n==[ STEP 4 ]== Checkout (createOrder)", 'yellow');
        $cartData = $keranjangModel->getCartWithItems($userId);
        $result   = $pesananModel->createOrder(
            $userId,
            $cartData['items'],
            'AMBIL_SENDIRI',
            'Test email flow — abaikan',
            'BCA',
            '1234567890',
            [
                'nama_lengkap'   => $nama,
                'email'          => $email,
                'no_hp'          => '081234567899',
                'alamat_lengkap' => 'Jl. Test Email Flow, Denpasar, Bali',
            ]
        );
        if (!$result['success']) {
            CLI::error('createOrder gagal: ' . $result['message']);
            return;
        }
        $orderId = (int) $result['order_id'];
        $invoice = $result['invoice_number'];
        CLI::write("  Order #{$orderId} ({$invoice}) terbuat.", 'green');
        CLI::write("  >> [EMAIL 1] sendOrderCreated dikirim ke {$email}", 'magenta');

        // -------- STEP 5: Upload bukti bayar --------
        CLI::write("\n==[ STEP 5 ]== Upload bukti pembayaran (dummy PNG)", 'yellow');
        $buktiDir = FCPATH . 'uploads/bukti_bayar';
        if (!is_dir($buktiDir)) {
            mkdir($buktiDir, 0755, true);
        }
        $buktiFilename = 'test_bukti_' . $orderId . '.png';
        $buktiPath     = $buktiDir . DIRECTORY_SEPARATOR . $buktiFilename;
        file_put_contents($buktiPath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        CLI::write("  Bukti: uploads/bukti_bayar/{$buktiFilename}", 'cyan');

        $upload = $pembayaranModel->uploadPaymentProof(
            $orderId,
            (float) $cartData['total'],
            'BCA Customer Test',
            'BCA',
            'uploads/bukti_bayar/' . $buktiFilename
        );
        if (!$upload['success']) {
            CLI::error('uploadPaymentProof gagal: ' . ($upload['message'] ?? 'unknown'));
            return;
        }
        $paymentId = (int) $upload['payment_id'];
        CLI::write("  Payment #{$paymentId} tersimpan (PENDING).", 'green');

        // -------- STEP 6: Admin verifikasi -> email invoice-paid --------
        CLI::write("\n==[ STEP 6 ]== Admin verifyPayment", 'yellow');
        $admin   = $userModel->where('role', 'ADMIN')->first();
        $adminId = $admin ? (int) $admin['id'] : 1;
        $verify  = $pembayaranModel->verifyPayment($paymentId, $adminId, true, null);
        if (!$verify['success']) {
            CLI::error('verifyPayment gagal: ' . $verify['message']);
            return;
        }
        CLI::write("  Payment VALID. Order pindah ke DIPROSES.", 'green');
        CLI::write("  >> [EMAIL 2] sendInvoicePaid dikirim ke {$email}", 'magenta');

        // -------- DONE --------
        CLI::write("\n==[ DONE ]==", 'green');
        CLI::write("  Login    : {$email} / {$password}", 'white');
        CLI::write("  Order    : #{$orderId} ({$invoice}) — status: DIPROSES", 'white');
        CLI::write("  Email yang dikirim ke {$email}:", 'white');
        CLI::write("    1. [DIX Game Farm] Pesanan Diterima - {$invoice}", 'white');
        CLI::write("    2. Invoice {$invoice} - Pembayaran Diverifikasi", 'white');
        CLI::write("  Cek Gmail inbox + folder Spam.", 'yellow');
    }
}
