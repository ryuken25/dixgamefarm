<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeder DEV-ONLY untuk skenario Playwright.
 *
 * Bikin 7 pesanan untuk customer pertama (sama dengan resolveCaptureUser('customer')
 * di CaptureController — biasanya user_id=3) yang mencakup semua state lifecycle:
 *
 *   A) MENUNGGU_BAYAR        - baru bikin, belum upload bukti
 *   B) DIPROSES kurir TERLAMBAT  - diproses_at = NOW-30h, harus muncul tombol WA
 *   C) DIPROSES kurir BARU       - diproses_at = NOW-2h,  TIDAK boleh muncul tombol WA
 *   D) DIKIRIM kurir + resi
 *   E) PESANAN_SIAP (AMBIL_SENDIRI)
 *   F) SELESAI
 *   G) BATAL
 *
 * Setelah selesai, mapping {scenario: order_id} ditulis ke
 * writable/playwright_orders.json supaya Playwright spec tahu ID persisnya.
 *
 * IDEMPOTENT: dipanggil ulang aman — hapus semua order milik customer yang
 * invoice-nya diawali "PWT-" sebelum re-create.
 *
 * Jalankan:  php spark db:seed ShipmentScenarioSeeder
 */
class ShipmentScenarioSeeder extends Seeder
{
    public function run()
    {
        // ----- 1. Resolve customer target (id=3 default, fallback PELANGGAN pertama) -----
        $customer = $this->db->table('users')
            ->where('id', 3)
            ->where('role', 'PELANGGAN')
            ->get()
            ->getRowArray();

        if (!$customer) {
            $customer = $this->db->table('users')
                ->where('role', 'PELANGGAN')
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        }

        if (!$customer) {
            echo "[ShipmentScenarioSeeder] No PELANGGAN user found. Run DatabaseSeeder dulu.\n";
            return;
        }

        $userId = (int) $customer['id'];
        echo "[ShipmentScenarioSeeder] Target customer: id={$userId} ({$customer['email']})\n";

        // ----- 2. Hapus order PWT- lama (idempotent) -----
        $oldOrderIds = array_column(
            $this->db->table('pesanan')
                ->select('id')
                ->where('user_id', $userId)
                ->like('nomor_invoice', 'PWT-', 'after')
                ->get()
                ->getResultArray(),
            'id'
        );

        if (!empty($oldOrderIds)) {
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
            $this->db->table('detail_pesanan')->whereIn('pesanan_id', $oldOrderIds)->delete();
            $this->db->table('pembayaran')->whereIn('pesanan_id', $oldOrderIds)->delete();
            $this->db->table('pesanan')->whereIn('id', $oldOrderIds)->delete();
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
            echo "[ShipmentScenarioSeeder] Hapus " . count($oldOrderIds) . " PWT-* order lama.\n";
        }

        // ----- 3. Pilih produk sebagai sampel item (produk pertama yang aktif) -----
        $produk = $this->db->table('produk')
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();
        if (!$produk) {
            echo "[ShipmentScenarioSeeder] No active produk found. Run DatabaseSeeder dulu.\n";
            return;
        }
        $produkId    = (int) $produk['id'];
        $hargaSnap   = (float) $produk['harga'];

        // ----- 4. Definisi skenario -----
        $now30hAgo = date('Y-m-d H:i:s', strtotime('-30 hours'));
        $now2hAgo  = date('Y-m-d H:i:s', strtotime('-2 hours'));
        $now4hAgo  = date('Y-m-d H:i:s', strtotime('-4 hours'));
        $now1hAgo  = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $nowPlus24 = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $catatan = 'Playwright test scenario';
        $scenarios = [
            'A' => [
                'invoice' => $this->genInvoice('A'),
                'fields'  => [
                    'status_pesanan'   => 'MENUNGGU_BAYAR',
                    'tipe_pengiriman'  => 'AMBIL_SENDIRI',
                    'expired_at'       => $nowPlus24,
                ],
            ],
            'B' => [
                'invoice' => $this->genInvoice('B'),
                'fields'  => [
                    'status_pesanan'   => 'DIPROSES',
                    'tipe_pengiriman'  => 'DIKIRIM_KURIR',
                    'expired_at'       => null,
                    'diproses_at'      => $now30hAgo,
                ],
            ],
            'C' => [
                'invoice' => $this->genInvoice('C'),
                'fields'  => [
                    'status_pesanan'   => 'DIPROSES',
                    'tipe_pengiriman'  => 'DIKIRIM_KURIR',
                    'expired_at'       => null,
                    'diproses_at'      => $now2hAgo,
                ],
            ],
            'D' => [
                'invoice' => $this->genInvoice('D'),
                'fields'  => [
                    'status_pesanan'   => 'DIKIRIM',
                    'tipe_pengiriman'  => 'DIKIRIM_KURIR',
                    'expired_at'       => null,
                    'diproses_at'      => $now4hAgo,
                    'dikirim_at'       => $now1hAgo,
                    'kode_resi'        => 'JNE12345678',
                ],
            ],
            'E' => [
                'invoice' => $this->genInvoice('E'),
                'fields'  => [
                    'status_pesanan'   => 'PESANAN_SIAP',
                    'tipe_pengiriman'  => 'AMBIL_SENDIRI',
                    'expired_at'       => null,
                    'diproses_at'      => $now4hAgo,
                ],
            ],
            'F' => [
                'invoice' => $this->genInvoice('F'),
                'fields'  => [
                    'status_pesanan'   => 'SELESAI',
                    'tipe_pengiriman'  => 'AMBIL_SENDIRI',
                    'expired_at'       => null,
                    'diproses_at'      => $now4hAgo,
                ],
            ],
            'G' => [
                'invoice' => $this->genInvoice('G'),
                'fields'  => [
                    'status_pesanan'   => 'BATAL',
                    'tipe_pengiriman'  => 'AMBIL_SENDIRI',
                    'expired_at'       => null,
                ],
            ],
        ];

        // ----- 5. Insert order + detail per skenario -----
        $mapping = [];
        $nowStr  = date('Y-m-d H:i:s');
        $qty     = 1;
        $subtotal = $hargaSnap * $qty;

        foreach ($scenarios as $key => $scn) {
            $orderRow = array_merge([
                'user_id'         => $userId,
                'nomor_invoice'   => $scn['invoice'],
                'tanggal_pesanan' => $nowStr,
                'grand_total'     => $subtotal,
                'nama_penerima'   => $customer['nama_lengkap'] ?? null,
                'email_penerima'  => $customer['email'] ?? null,
                'no_hp_penerima'  => $customer['no_hp'] ?? '081234567890',
                'alamat_pengiriman' => $customer['alamat_lengkap'] ?? 'Jl. Test, Bali',
                'metode_pembayaran' => 'BCA',
                'nomor_rekening_tujuan' => '1234567890',
                'catatan_pelanggan' => $catatan,
                'created_at'      => $nowStr,
                'updated_at'      => $nowStr,
            ], $scn['fields']);

            $this->db->table('pesanan')->insert($orderRow);
            $orderId = (int) $this->db->insertID();

            // 1 line item per order biar ringkasan tidak kosong
            $this->db->table('detail_pesanan')->insert([
                'pesanan_id'             => $orderId,
                'produk_id'              => $produkId,
                'harga_satuan_snapshot'  => $hargaSnap,
                'jumlah'                 => $qty,
                'subtotal'               => $subtotal,
                'is_preorder_item'       => 0,
                'created_at'             => $nowStr,
                'updated_at'             => $nowStr,
            ]);

            $mapping[$key] = $orderId;
            echo "  [{$key}] {$scn['invoice']} order_id={$orderId} ({$scn['fields']['status_pesanan']})\n";
        }

        // ----- 6. Tulis mapping ke writable/playwright_orders.json -----
        $mapping['_meta'] = [
            'customer_id'    => $userId,
            'customer_email' => $customer['email'] ?? null,
            'produk_id'      => $produkId,
            'generated_at'   => $nowStr,
        ];
        $jsonPath = WRITEPATH . 'playwright_orders.json';
        file_put_contents($jsonPath, json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "[ShipmentScenarioSeeder] Mapping ditulis ke writable/playwright_orders.json\n";
    }

    private function genInvoice(string $key): string
    {
        return 'PWT-' . date('Ymd') . '-' . $key . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
    }
}
