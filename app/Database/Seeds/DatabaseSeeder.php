<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ============================================================
        // 1. USERS (1 Admin + 5 Customers)
        // ============================================================
        $users = [
            [
                'email' => 'admin@dixgamefarm.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'nama_lengkap' => 'I Gusti Ngurah Made Wijaya',
                'role' => 'ADMIN',
                'no_hp' => '081234567890',
                'alamat_lengkap' => 'DIX Game Farm, Br. Anyar, Tabanan, Bali',
                'created_at' => '2026-01-15 08:00:00',
                'updated_at' => '2026-01-15 08:00:00',
            ],
            [
                'email' => 'ketut.suarjana@gmail.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'nama_lengkap' => 'I Ketut Suarjana',
                'role' => 'PELANGGAN',
                'no_hp' => '081987654321',
                'alamat_lengkap' => 'Jl. Hayam Wuruk No. 45, Denpasar Selatan, Bali',
                'created_at' => '2026-02-01 10:30:00',
                'updated_at' => '2026-02-01 10:30:00',
            ],
            [
                'email' => 'putu.ayu@gmail.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'nama_lengkap' => 'Ni Putu Ayu Lestari',
                'role' => 'PELANGGAN',
                'no_hp' => '082145678901',
                'alamat_lengkap' => 'Jl. Raya Ubud No. 12, Gianyar, Bali',
                'created_at' => '2026-02-10 14:15:00',
                'updated_at' => '2026-02-10 14:15:00',
            ],
            [
                'email' => 'wayan.dharma@yahoo.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'nama_lengkap' => 'I Wayan Dharma Putra',
                'role' => 'PELANGGAN',
                'no_hp' => '085678901234',
                'alamat_lengkap' => 'Br. Kaja, Desa Mengwi, Badung, Bali',
                'created_at' => '2026-02-20 09:00:00',
                'updated_at' => '2026-02-20 09:00:00',
            ],
            [
                'email' => 'made.sri@gmail.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'nama_lengkap' => 'Ni Made Sri Wahyuni',
                'role' => 'PELANGGAN',
                'no_hp' => '087890123456',
                'alamat_lengkap' => 'Jl. Gatot Subroto No. 88, Denpasar Barat, Bali',
                'created_at' => '2026-03-01 11:45:00',
                'updated_at' => '2026-03-01 11:45:00',
            ],
            [
                'email' => 'komang.ari@gmail.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'nama_lengkap' => 'I Komang Ari Sudana',
                'role' => 'PELANGGAN',
                'no_hp' => '089012345678',
                'alamat_lengkap' => 'Jl. Pulau Moyo No. 5, Singaraja, Buleleng, Bali',
                'created_at' => '2026-03-10 16:20:00',
                'updated_at' => '2026-03-10 16:20:00',
            ],
        ];

        $this->db->table('users')->insertBatch($users);

        // ============================================================
        // 2. KATEGORI (4 categories)
        // ============================================================
        $categories = [
            [
                'nama_kategori' => 'Telur',
                'deskripsi' => 'Telur ayam kampung super berkualitas tinggi, segar langsung dari peternakan DIX Game Farm',
                'created_at' => '2026-01-15 08:30:00',
                'updated_at' => '2026-01-15 08:30:00',
            ],
            [
                'nama_kategori' => 'DOC (Day Old Chick)',
                'deskripsi' => 'Anak ayam umur 1 hari yang sudah divaksin, siap dibesarkan untuk peternakan',
                'created_at' => '2026-01-15 08:31:00',
                'updated_at' => '2026-01-15 08:31:00',
            ],
            [
                'nama_kategori' => 'Ayam Siap Jual',
                'deskripsi' => 'Ayam dewasa siap konsumsi, potong, atau untuk breeding berkualitas',
                'created_at' => '2026-01-15 08:32:00',
                'updated_at' => '2026-01-15 08:32:00',
            ],
            [
                'nama_kategori' => 'Pakan & Suplemen',
                'deskripsi' => 'Pakan ayam dan suplemen vitamin untuk peternakan ayam kampung',
                'created_at' => '2026-01-15 08:33:00',
                'updated_at' => '2026-01-15 08:33:00',
            ],
        ];

        $this->db->table('kategori')->insertBatch($categories);

        // ============================================================
        // 3. PRODUK (10 products across categories)
        // ============================================================
        $products = [
            // --- Telur (kategori_id = 1) ---
            [
                'kategori_id' => 1,
                'nama_ayam' => 'Telur Ayam Kampung Super',
                'usia_berat' => 'Grade A',
                'stok_tersedia' => 85,
                'stok_dibooking' => 15,
                'harga' => 3000.00,
                'deskripsi' => 'Telur ayam kampung super berkualitas tinggi, segar dari peternakan langsung. Cocok untuk konsumsi sehari-hari dan MPASI bayi.',
                'foto' => null,
                'is_active' => true,
                'created_at' => '2026-01-20 09:00:00',
                'updated_at' => '2026-03-28 14:00:00',
            ],
            [
                'kategori_id' => 1,
                'nama_ayam' => 'Telur Ayam Kampung Premium',
                'usia_berat' => 'Grade AA',
                'stok_tersedia' => 40,
                'stok_dibooking' => 10,
                'harga' => 3500.00,
                'deskripsi' => 'Telur ayam kampung premium ukuran besar dengan kuning telur pekat. Ideal untuk kue dan masakan spesial.',
                'foto' => null,
                'is_active' => true,
                'created_at' => '2026-01-20 09:05:00',
                'updated_at' => '2026-03-27 10:00:00',
            ],
            [
                'kategori_id' => 1,
                'nama_ayam' => 'Telur Ayam Kampung Organik',
                'usia_berat' => 'Grade AAA',
                'stok_tersedia' => 30,
                'stok_dibooking' => 0,
                'harga' => 4500.00,
                'deskripsi' => 'Telur organik dari ayam kampung yang dipelihara secara alami tanpa pakan kimia. Premium quality.',
                'foto' => null,
                'is_active' => true,
                'created_at' => '2026-02-05 08:00:00',
                'updated_at' => '2026-02-05 08:00:00',
            ],

            // --- DOC (kategori_id = 2) ---
            [
                'kategori_id' => 2,
                'nama_ayam' => 'DOC Ayam Kampung Super',
                'usia_berat' => '1 sarang isi 8 ekor',
                'stok_tersedia' => 175,
                'stok_dibooking' => 25,
                'harga' => 1300000.00,
                'deskripsi' => 'DOC ayam paket 1 sarang isi 8 ekor lengkap vaksin. Siap dibesarkan dengan survival rate tinggi.',
                'foto' => null,
                'is_active' => true,
                'created_at' => '2026-01-20 09:10:00',
                'updated_at' => '2026-03-25 11:30:00',
            ],
            [
                'kategori_id' => 2,
                'nama_ayam' => 'DOC Ayam Joper',
                'usia_berat' => '1 sarang isi 8 ekor',
                'stok_tersedia' => 120,
                'stok_dibooking' => 30,
                'harga' => 1300000.00,
                'deskripsi' => 'DOC ayam joper paket 1 sarang isi 8 ekor lengkap vaksin. Pertumbuhan cepat, tahan penyakit, cocok untuk ternak.',
                'foto' => null,
                'is_active' => true,
                'created_at' => '2026-01-20 09:15:00',
                'updated_at' => '2026-03-26 09:00:00',
            ],

            // --- Ayam Siap Jual (kategori_id = 3) ---
            [
                'kategori_id' => 3,
                'nama_ayam' => 'Boston Roundhead',
                'usia_berat' => 'Dewasa, 1.8-2.2 kg',
                'stok_tersedia' => 18,
                'stok_dibooking' => 7,
                'harga' => 3000000.00,
                'deskripsi' => 'Ayam Boston Roundhead berkualitas, sehat, aktif, dan siap untuk koleksi atau breeding.',
                'foto' => 'uploads/produk/Boston Roundhead.png',
                'is_active' => true,
                'created_at' => '2026-01-20 09:20:00',
                'updated_at' => '2026-03-28 08:00:00',
            ],
            [
                'kategori_id' => 3,
                'nama_ayam' => 'Moonwalker Grey',
                'usia_berat' => 'Dewasa, 1.7-2.1 kg',
                'stok_tersedia' => 22,
                'stok_dibooking' => 8,
                'harga' => 4000000.00,
                'deskripsi' => 'Ayam Moonwalker Grey pilihan dengan kondisi prima, cocok untuk koleksi dan breeding.',
                'foto' => 'uploads/produk/Moonwalker Grey.png',
                'is_active' => true,
                'created_at' => '2026-01-20 09:25:00',
                'updated_at' => '2026-03-27 15:00:00',
            ],
            [
                'kategori_id' => 3,
                'nama_ayam' => 'Popeye Grey',
                'usia_berat' => 'Dewasa, 1.8-2.3 kg',
                'stok_tersedia' => 15,
                'stok_dibooking' => 5,
                'harga' => 5000000.00,
                'deskripsi' => 'Ayam Popeye Grey sehat dan aktif dengan kualitas terbaik dari DIX Game Farm.',
                'foto' => 'uploads/produk/Popeye Grey.png',
                'is_active' => true,
                'created_at' => '2026-01-20 09:30:00',
                'updated_at' => '2026-03-25 16:00:00',
            ],

            // --- Pakan & Suplemen (kategori_id = 4) ---
            [
                'kategori_id' => 4,
                'nama_ayam' => 'Pakan Ayam Kampung Starter',
                'usia_berat' => '0-4 minggu',
                'stok_tersedia' => 50,
                'stok_dibooking' => 0,
                'harga' => 45000.00,
                'deskripsi' => 'Pakan starter untuk DOC umur 0-4 minggu. Formula khusus pertumbuhan awal yang optimal. Per karung 5 kg.',
                'foto' => null,
                'is_active' => true,
                'created_at' => '2026-02-15 10:00:00',
                'updated_at' => '2026-02-15 10:00:00',
            ],
            [
                'kategori_id' => 4,
                'nama_ayam' => 'Vitamin Ayam Super Complete',
                'usia_berat' => 'Semua umur',
                'stok_tersedia' => 8,
                'stok_dibooking' => 0,
                'harga' => 35000.00,
                'deskripsi' => 'Vitamin lengkap untuk ayam kampung segala umur. Meningkatkan daya tahan tubuh dan produktivitas. Per botol 100ml.',
                'foto' => null,
                'is_active' => true,
                'created_at' => '2026-02-15 10:05:00',
                'updated_at' => '2026-02-15 10:05:00',
            ],
        ];

        $this->db->table('produk')->insertBatch($products);

        // ============================================================
        // 4. KERANJANG (carts for active customers)
        // ============================================================
        $carts = [
            ['user_id' => 2, 'last_updated' => '2026-03-28 20:00:00', 'created_at' => '2026-02-01 10:35:00'],
            ['user_id' => 3, 'last_updated' => '2026-03-29 09:15:00', 'created_at' => '2026-02-10 14:20:00'],
            ['user_id' => 4, 'last_updated' => '2026-03-20 18:30:00', 'created_at' => '2026-02-20 09:05:00'],
            ['user_id' => 5, 'last_updated' => '2026-03-29 11:00:00', 'created_at' => '2026-03-01 12:00:00'],
            ['user_id' => 6, 'last_updated' => '2026-03-15 14:00:00', 'created_at' => '2026-03-10 16:25:00'],
        ];

        $this->db->table('keranjang')->insertBatch($carts);

        // ============================================================
        // 5. ITEM_KERANJANG (items in active carts - only for users who haven't checked out yet)
        // ============================================================
        $cartItems = [
            // Cart user 3 (Ni Putu Ayu) - browsing, has items in cart
            ['keranjang_id' => 2, 'produk_id' => 3, 'jumlah' => 20, 'created_at' => '2026-03-29 09:10:00', 'updated_at' => '2026-03-29 09:15:00'],
            ['keranjang_id' => 2, 'produk_id' => 9, 'jumlah' => 2, 'created_at' => '2026-03-29 09:12:00', 'updated_at' => '2026-03-29 09:12:00'],

            // Cart user 5 (Ni Made Sri) - has items in cart
            ['keranjang_id' => 4, 'produk_id' => 1, 'jumlah' => 30, 'created_at' => '2026-03-29 10:50:00', 'updated_at' => '2026-03-29 11:00:00'],
            ['keranjang_id' => 4, 'produk_id' => 10, 'jumlah' => 1, 'created_at' => '2026-03-29 10:55:00', 'updated_at' => '2026-03-29 10:55:00'],
        ];

        $this->db->table('item_keranjang')->insertBatch($cartItems);

        // ============================================================
        // 6. PESANAN (8 orders at various statuses)
        // ============================================================
        $orders = [
            // Order 1 - SELESAI (completed) by Ketut
            [
                'user_id' => 2,
                'nomor_invoice' => 'INV-20260305-1234',
                'tanggal_pesanan' => '2026-03-05 14:30:00',
                'expired_at' => '2026-03-06 14:30:00',
                'grand_total' => 390000.00,
                'status_pesanan' => 'SELESAI',
                'tipe_pengiriman' => 'AMBIL_SENDIRI',
                'catatan_pelanggan' => 'Tolong siapkan pagi-pagi ya pak',
                'created_at' => '2026-03-05 14:30:00',
                'updated_at' => '2026-03-08 10:00:00',
            ],
            // Order 2 - SELESAI (completed) by Putu Ayu
            [
                'user_id' => 3,
                'nomor_invoice' => 'INV-20260310-5678',
                'tanggal_pesanan' => '2026-03-10 09:15:00',
                'expired_at' => '2026-03-11 09:15:00',
                'grand_total' => 200000.00,
                'status_pesanan' => 'SELESAI',
                'tipe_pengiriman' => 'DIKIRIM_KURIR',
                'catatan_pelanggan' => 'Kirim ke alamat rumah, ada orang di rumah siang',
                'created_at' => '2026-03-10 09:15:00',
                'updated_at' => '2026-03-13 16:00:00',
            ],
            // Order 3 - BATAL (cancelled by customer) by Wayan
            [
                'user_id' => 4,
                'nomor_invoice' => 'INV-20260315-9012',
                'tanggal_pesanan' => '2026-03-15 18:45:00',
                'expired_at' => '2026-03-16 18:45:00',
                'grand_total' => 240000.00,
                'status_pesanan' => 'BATAL',
                'tipe_pengiriman' => 'AMBIL_SENDIRI',
                'catatan_pelanggan' => null,
                'created_at' => '2026-03-15 18:45:00',
                'updated_at' => '2026-03-16 07:00:00',
            ],
            // Order 4 - DIKIRIM (being delivered) by Ketut
            [
                'user_id' => 2,
                'nomor_invoice' => 'INV-20260322-3456',
                'tanggal_pesanan' => '2026-03-22 11:00:00',
                'expired_at' => '2026-03-23 11:00:00',
                'grand_total' => 150000.00,
                'status_pesanan' => 'DIKIRIM',
                'tipe_pengiriman' => 'DIKIRIM_KURIR',
                'catatan_pelanggan' => 'Telur tolong bungkus ekstra hati-hati',
                'created_at' => '2026-03-22 11:00:00',
                'updated_at' => '2026-03-27 09:00:00',
            ],
            // Order 5 - DIPROSES (being processed) by Komang
            [
                'user_id' => 6,
                'nomor_invoice' => 'INV-20260325-7890',
                'tanggal_pesanan' => '2026-03-25 15:30:00',
                'expired_at' => '2026-03-26 15:30:00',
                'grand_total' => 270000.00,
                'status_pesanan' => 'DIPROSES',
                'tipe_pengiriman' => 'DIKIRIM_KURIR',
                'catatan_pelanggan' => 'Kirim via JNE ke Singaraja',
                'created_at' => '2026-03-25 15:30:00',
                'updated_at' => '2026-03-27 08:30:00',
            ],
            // Order 6 - MENUNGGU_VERIFIKASI (payment uploaded, waiting admin) by Made Sri
            [
                'user_id' => 5,
                'nomor_invoice' => 'INV-20260327-2345',
                'tanggal_pesanan' => '2026-03-27 10:00:00',
                'expired_at' => '2026-03-28 10:00:00',
                'grand_total' => 360000.00,
                'status_pesanan' => 'MENUNGGU_VERIFIKASI',
                'tipe_pengiriman' => 'AMBIL_SENDIRI',
                'catatan_pelanggan' => 'Saya ambil hari Sabtu siang',
                'created_at' => '2026-03-27 10:00:00',
                'updated_at' => '2026-03-27 12:30:00',
            ],
            // Order 7 - MENUNGGU_BAYAR (waiting payment) by Wayan
            [
                'user_id' => 4,
                'nomor_invoice' => 'INV-20260328-6789',
                'tanggal_pesanan' => '2026-03-28 20:15:00',
                'expired_at' => '2026-03-29 20:15:00',
                'grand_total' => 105000.00,
                'status_pesanan' => 'MENUNGGU_BAYAR',
                'tipe_pengiriman' => 'AMBIL_SENDIRI',
                'catatan_pelanggan' => null,
                'created_at' => '2026-03-28 20:15:00',
                'updated_at' => '2026-03-28 20:15:00',
            ],
            // Order 8 - MENUNGGU_BAYAR (recent, by Putu Ayu)
            [
                'user_id' => 3,
                'nomor_invoice' => 'INV-20260329-1122',
                'tanggal_pesanan' => '2026-03-29 08:00:00',
                'expired_at' => '2026-03-30 08:00:00',
                'grand_total' => 72000.00,
                'status_pesanan' => 'MENUNGGU_BAYAR',
                'tipe_pengiriman' => 'AMBIL_SENDIRI',
                'catatan_pelanggan' => 'Untuk acara upacara',
                'created_at' => '2026-03-29 08:00:00',
                'updated_at' => '2026-03-29 08:00:00',
            ],
        ];

        $this->db->table('pesanan')->insertBatch($orders);

        // ============================================================
        // 7. DETAIL_PESANAN (order line items with price snapshots)
        // ============================================================
        $orderDetails = [
            // Order 1 (SELESAI) - 3x Ayam Jantan + 10x Telur Kampung Super = 360000 + 30000 = 390000
            ['pesanan_id' => 1, 'produk_id' => 6, 'harga_satuan_snapshot' => 120000.00, 'jumlah' => 3, 'subtotal' => 360000.00, 'created_at' => '2026-03-05 14:30:00', 'updated_at' => '2026-03-05 14:30:00'],
            ['pesanan_id' => 1, 'produk_id' => 1, 'harga_satuan_snapshot' => 3000.00, 'jumlah' => 10, 'subtotal' => 30000.00, 'created_at' => '2026-03-05 14:30:00', 'updated_at' => '2026-03-05 14:30:00'],

            // Order 2 (SELESAI) - 25x DOC Ayam Kampung = 200000
            ['pesanan_id' => 2, 'produk_id' => 4, 'harga_satuan_snapshot' => 8000.00, 'jumlah' => 25, 'subtotal' => 200000.00, 'created_at' => '2026-03-10 09:15:00', 'updated_at' => '2026-03-10 09:15:00'],

            // Order 3 (BATAL) - 2x Ayam Jantan + 1x Joper = 240000 + 0 = stock was released
            ['pesanan_id' => 3, 'produk_id' => 6, 'harga_satuan_snapshot' => 120000.00, 'jumlah' => 2, 'subtotal' => 240000.00, 'created_at' => '2026-03-15 18:45:00', 'updated_at' => '2026-03-15 18:45:00'],

            // Order 4 (DIKIRIM) - 15x Telur Premium + 15x Telur Super = 52500 + 45000 + 52500 = 150000
            ['pesanan_id' => 4, 'produk_id' => 2, 'harga_satuan_snapshot' => 3500.00, 'jumlah' => 10, 'subtotal' => 35000.00, 'created_at' => '2026-03-22 11:00:00', 'updated_at' => '2026-03-22 11:00:00'],
            ['pesanan_id' => 4, 'produk_id' => 1, 'harga_satuan_snapshot' => 3000.00, 'jumlah' => 15, 'subtotal' => 45000.00, 'created_at' => '2026-03-22 11:00:00', 'updated_at' => '2026-03-22 11:00:00'],
            ['pesanan_id' => 4, 'produk_id' => 5, 'harga_satuan_snapshot' => 9000.00, 'jumlah' => 0, 'subtotal' => 0.00, 'created_at' => '2026-03-22 11:00:00', 'updated_at' => '2026-03-22 11:00:00'],

            // Order 5 (DIPROSES) - 30x DOC Joper = 270000
            ['pesanan_id' => 5, 'produk_id' => 5, 'harga_satuan_snapshot' => 9000.00, 'jumlah' => 30, 'subtotal' => 270000.00, 'created_at' => '2026-03-25 15:30:00', 'updated_at' => '2026-03-25 15:30:00'],

            // Order 6 (MENUNGGU_VERIFIKASI) - 3x Ayam Jantan = 360000
            ['pesanan_id' => 6, 'produk_id' => 6, 'harga_satuan_snapshot' => 120000.00, 'jumlah' => 3, 'subtotal' => 360000.00, 'created_at' => '2026-03-27 10:00:00', 'updated_at' => '2026-03-27 10:00:00'],

            // Order 7 (MENUNGGU_BAYAR) - 35x Telur Super = 105000
            ['pesanan_id' => 7, 'produk_id' => 1, 'harga_satuan_snapshot' => 3000.00, 'jumlah' => 35, 'subtotal' => 105000.00, 'created_at' => '2026-03-28 20:15:00', 'updated_at' => '2026-03-28 20:15:00'],

            // Order 8 (MENUNGGU_BAYAR) - 8x DOC Kampung = 64000 + 1x Pakan = 8000 (Note: recalc to 72000)
            ['pesanan_id' => 8, 'produk_id' => 4, 'harga_satuan_snapshot' => 8000.00, 'jumlah' => 8, 'subtotal' => 64000.00, 'created_at' => '2026-03-29 08:00:00', 'updated_at' => '2026-03-29 08:00:00'],
            ['pesanan_id' => 8, 'produk_id' => 10, 'harga_satuan_snapshot' => 8000.00, 'jumlah' => 1, 'subtotal' => 8000.00, 'created_at' => '2026-03-29 08:00:00', 'updated_at' => '2026-03-29 08:00:00'],
        ];

        $this->db->table('detail_pesanan')->insertBatch($orderDetails);

        // ============================================================
        // 8. PEMBAYARAN (payment records for orders that have paid)
        // ============================================================
        $payments = [
            // Payment for Order 1 (SELESAI) - VALID
            [
                'pesanan_id' => 1,
                'nominal_bayar' => 390000.00,
                'nama_bank' => 'BCA',
                'bank_tujuan' => 'BCA - DIX Game Farm (1234567890)',
                'bukti_bayar' => null,
                'status_pembayaran' => 'VALID',
                'tanggal_upload' => '2026-03-05 15:20:00',
                'tanggal_verifikasi' => '2026-03-06 08:00:00',
                'verifikator_id' => 1,
                'alasan_ditolak' => null,
                'created_at' => '2026-03-05 15:20:00',
                'updated_at' => '2026-03-06 08:00:00',
            ],
            // Payment for Order 2 (SELESAI) - VALID
            [
                'pesanan_id' => 2,
                'nominal_bayar' => 200000.00,
                'nama_bank' => 'BNI',
                'bank_tujuan' => 'BCA - DIX Game Farm (1234567890)',
                'bukti_bayar' => null,
                'status_pembayaran' => 'VALID',
                'tanggal_upload' => '2026-03-10 10:00:00',
                'tanggal_verifikasi' => '2026-03-10 14:30:00',
                'verifikator_id' => 1,
                'alasan_ditolak' => null,
                'created_at' => '2026-03-10 10:00:00',
                'updated_at' => '2026-03-10 14:30:00',
            ],
            // Payment for Order 4 (DIKIRIM) - VALID
            [
                'pesanan_id' => 4,
                'nominal_bayar' => 150000.00,
                'nama_bank' => 'Mandiri',
                'bank_tujuan' => 'BCA - DIX Game Farm (1234567890)',
                'bukti_bayar' => null,
                'status_pembayaran' => 'VALID',
                'tanggal_upload' => '2026-03-22 12:00:00',
                'tanggal_verifikasi' => '2026-03-23 08:00:00',
                'verifikator_id' => 1,
                'alasan_ditolak' => null,
                'created_at' => '2026-03-22 12:00:00',
                'updated_at' => '2026-03-23 08:00:00',
            ],
            // Payment for Order 5 (DIPROSES) - VALID
            [
                'pesanan_id' => 5,
                'nominal_bayar' => 270000.00,
                'nama_bank' => 'BRI',
                'bank_tujuan' => 'BCA - DIX Game Farm (1234567890)',
                'bukti_bayar' => null,
                'status_pembayaran' => 'VALID',
                'tanggal_upload' => '2026-03-25 16:45:00',
                'tanggal_verifikasi' => '2026-03-27 08:30:00',
                'verifikator_id' => 1,
                'alasan_ditolak' => null,
                'created_at' => '2026-03-25 16:45:00',
                'updated_at' => '2026-03-27 08:30:00',
            ],
            // Payment for Order 6 (MENUNGGU_VERIFIKASI) - PENDING (waiting admin)
            [
                'pesanan_id' => 6,
                'nominal_bayar' => 360000.00,
                'nama_bank' => 'BCA',
                'bank_tujuan' => 'BCA - DIX Game Farm (1234567890)',
                'bukti_bayar' => null,
                'status_pembayaran' => 'PENDING',
                'tanggal_upload' => '2026-03-27 12:30:00',
                'tanggal_verifikasi' => null,
                'verifikator_id' => null,
                'alasan_ditolak' => null,
                'created_at' => '2026-03-27 12:30:00',
                'updated_at' => '2026-03-27 12:30:00',
            ],
        ];

        $this->db->table('pembayaran')->insertBatch($payments);

        // ============================================================
        // 9. NOTIFIKASI (notifications for various users)
        // ============================================================
        $notifications = [
            // Admin notifications
            [
                'user_id' => 1,
                'judul' => 'Pesanan Baru Masuk',
                'pesan' => 'Pesanan baru INV-20260329-1122 dari Ni Putu Ayu Lestari senilai Rp 72.000. Silakan cek dashboard.',
                'is_read' => false,
                'created_at' => '2026-03-29 08:00:00',
            ],
            [
                'user_id' => 1,
                'judul' => 'Pembayaran Menunggu Verifikasi',
                'pesan' => 'Pembayaran untuk pesanan INV-20260327-2345 dari Ni Made Sri Wahyuni telah diupload. Silakan verifikasi.',
                'is_read' => false,
                'created_at' => '2026-03-27 12:30:00',
            ],
            [
                'user_id' => 1,
                'judul' => 'Pesanan Baru Masuk',
                'pesan' => 'Pesanan baru INV-20260328-6789 dari I Wayan Dharma Putra senilai Rp 105.000.',
                'is_read' => true,
                'created_at' => '2026-03-28 20:15:00',
            ],

            // Customer: Ketut
            [
                'user_id' => 2,
                'judul' => 'Selamat Datang!',
                'pesan' => 'Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',
                'is_read' => true,
                'created_at' => '2026-02-01 10:30:00',
            ],
            [
                'user_id' => 2,
                'judul' => 'Pesanan Selesai',
                'pesan' => 'Pesanan INV-20260305-1234 telah selesai. Terima kasih telah berbelanja di DIX Game Farm!',
                'is_read' => true,
                'created_at' => '2026-03-08 10:00:00',
            ],
            [
                'user_id' => 2,
                'judul' => 'Pesanan Sedang Dikirim',
                'pesan' => 'Pesanan INV-20260322-3456 sedang dalam perjalanan ke alamat Anda. Harap bersiap menerima paket.',
                'is_read' => false,
                'created_at' => '2026-03-27 09:00:00',
            ],

            // Customer: Putu Ayu
            [
                'user_id' => 3,
                'judul' => 'Selamat Datang!',
                'pesan' => 'Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',
                'is_read' => true,
                'created_at' => '2026-02-10 14:15:00',
            ],
            [
                'user_id' => 3,
                'judul' => 'Pesanan Dibuat',
                'pesan' => 'Pesanan INV-20260329-1122 berhasil dibuat. Silakan lakukan pembayaran sebelum 30 Maret 2026 08:00.',
                'is_read' => false,
                'created_at' => '2026-03-29 08:00:00',
            ],

            // Customer: Wayan
            [
                'user_id' => 4,
                'judul' => 'Selamat Datang!',
                'pesan' => 'Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',
                'is_read' => true,
                'created_at' => '2026-02-20 09:00:00',
            ],
            [
                'user_id' => 4,
                'judul' => 'Pesanan Dibatalkan',
                'pesan' => 'Pesanan INV-20260315-9012 telah dibatalkan. Stok telah dikembalikan.',
                'is_read' => true,
                'created_at' => '2026-03-16 07:00:00',
            ],
            [
                'user_id' => 4,
                'judul' => 'Pesanan Dibuat',
                'pesan' => 'Pesanan INV-20260328-6789 berhasil dibuat. Silakan lakukan pembayaran sebelum 29 Maret 2026 20:15.',
                'is_read' => false,
                'created_at' => '2026-03-28 20:15:00',
            ],

            // Customer: Made Sri
            [
                'user_id' => 5,
                'judul' => 'Selamat Datang!',
                'pesan' => 'Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',
                'is_read' => true,
                'created_at' => '2026-03-01 11:45:00',
            ],
            [
                'user_id' => 5,
                'judul' => 'Pembayaran Diterima',
                'pesan' => 'Bukti pembayaran untuk pesanan INV-20260327-2345 telah kami terima. Menunggu verifikasi admin.',
                'is_read' => true,
                'created_at' => '2026-03-27 12:30:00',
            ],

            // Customer: Komang
            [
                'user_id' => 6,
                'judul' => 'Selamat Datang!',
                'pesan' => 'Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',
                'is_read' => true,
                'created_at' => '2026-03-10 16:20:00',
            ],
            [
                'user_id' => 6,
                'judul' => 'Pesanan Diproses',
                'pesan' => 'Pesanan INV-20260325-7890 sedang diproses oleh tim kami. Kami akan segera mengirimkan pesanan Anda.',
                'is_read' => false,
                'created_at' => '2026-03-27 08:30:00',
            ],
        ];

        $this->db->table('notifikasi')->insertBatch($notifications);

        // ============================================================
        // DONE!
        // ============================================================
        echo "\n";
        echo "============================================\n";
        echo "  Database seeded successfully!\n";
        echo "============================================\n";
        echo "\n";
        echo "  AKUN LOGIN:\n";
        echo "  -------------------------------------------------\n";
        echo "  Admin    : admin@dixgamefarm.com / admin123\n";
        echo "  Customer : ketut.suarjana@gmail.com / password123\n";
        echo "  Customer : putu.ayu@gmail.com / password123\n";
        echo "  Customer : wayan.dharma@yahoo.com / password123\n";
        echo "  Customer : made.sri@gmail.com / password123\n";
        echo "  Customer : komang.ari@gmail.com / password123\n";
        echo "  -------------------------------------------------\n";
        echo "\n";
        echo "  DATA DUMMY:\n";
        echo "  - 6 users (1 admin + 5 pelanggan)\n";
        echo "  - 4 kategori produk\n";
        echo "  - 10 produk\n";
        echo "  - 8 pesanan (berbagai status)\n";
        echo "  - 5 pembayaran\n";
        echo "  - 15 notifikasi\n";
        echo "\n";
    }
}
