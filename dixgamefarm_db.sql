-- Database: dixgamefarm_db
-- Generated from CodeIgniter 4 Migrations
-- Date: 2026-04-14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `role` enum('ADMIN','PELANGGAN') NOT NULL DEFAULT 'PELANGGAN',
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat_lengkap` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `kategori`
-- --------------------------------------------------------

CREATE TABLE `kategori` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `produk`
-- --------------------------------------------------------

CREATE TABLE `produk` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kategori_id` int(11) UNSIGNED NOT NULL,
  `nama_ayam` varchar(255) NOT NULL,
  `usia_berat` varchar(100) DEFAULT NULL,
  `stok_tersedia` int(11) NOT NULL DEFAULT 0 COMMENT 'Stok riil yang siap dibeli',
  `stok_dibooking` int(11) NOT NULL DEFAULT 0 COMMENT 'Stok yang sedang di-checkout, belum lunas',
  `harga` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL COMMENT 'Path file lokal di public/uploads',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kategori_id` (`kategori_id`),
  CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `keranjang`
-- --------------------------------------------------------

CREATE TABLE `keranjang` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `last_updated` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `item_keranjang`
-- --------------------------------------------------------

CREATE TABLE `item_keranjang` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `keranjang_id` int(11) UNSIGNED NOT NULL,
  `produk_id` int(11) UNSIGNED NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `keranjang_id` (`keranjang_id`),
  KEY `produk_id` (`produk_id`),
  CONSTRAINT `item_keranjang_ibfk_1` FOREIGN KEY (`keranjang_id`) REFERENCES `keranjang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `item_keranjang_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `pesanan`
-- --------------------------------------------------------

CREATE TABLE `pesanan` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `nomor_invoice` varchar(50) NOT NULL COMMENT 'Format: INV-YYYYMMDD-RandomNumber',
  `tanggal_pesanan` datetime NOT NULL,
  `expired_at` datetime DEFAULT NULL COMMENT 'Batas bayar 24 jam dari tanggal_pesanan',
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status_pesanan` enum('MENUNGGU_BAYAR','MENUNGGU_VERIFIKASI','DIPROSES','DIKIRIM','SELESAI','BATAL') NOT NULL DEFAULT 'MENUNGGU_BAYAR',
  `tipe_pengiriman` enum('AMBIL_SENDIRI','DIKIRIM_KURIR') NOT NULL DEFAULT 'AMBIL_SENDIRI',
  `catatan_pelanggan` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_invoice` (`nomor_invoice`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `detail_pesanan`
-- --------------------------------------------------------

CREATE TABLE `detail_pesanan` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pesanan_id` int(11) UNSIGNED NOT NULL,
  `produk_id` int(11) UNSIGNED NOT NULL,
  `harga_satuan_snapshot` decimal(10,2) NOT NULL COMMENT 'Harga terkunci saat checkout, tidak terpengaruh perubahan harga masa depan',
  `jumlah` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pesanan_id` (`pesanan_id`),
  KEY `produk_id` (`produk_id`),
  CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `pembayaran`
-- --------------------------------------------------------

CREATE TABLE `pembayaran` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pesanan_id` int(11) UNSIGNED NOT NULL,
  `nominal_bayar` decimal(12,2) NOT NULL,
  `nama_bank` varchar(100) DEFAULT NULL COMMENT 'Bank pengirim (misal: BCA, BNI, Mandiri)',
  `bank_tujuan` varchar(100) DEFAULT NULL COMMENT 'Bank penerima DIX Game Farm',
  `bukti_bayar` varchar(255) DEFAULT NULL COMMENT 'Path file bukti transfer',
  `status_pembayaran` enum('PENDING','VALID','INVALID') NOT NULL DEFAULT 'PENDING',
  `tanggal_upload` datetime DEFAULT NULL,
  `tanggal_verifikasi` datetime DEFAULT NULL,
  `verifikator_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'ID Admin yang memverifikasi',
  `alasan_ditolak` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pesanan_id` (`pesanan_id`),
  KEY `verifikator_id` (`verifikator_id`),
  CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`verifikator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `notifikasi`
-- --------------------------------------------------------

CREATE TABLE `notifikasi` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Default data for `users` (admin account)
-- --------------------------------------------------------
-- Password: admin123 (hashed with PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `email`, `password_hash`, `nama_lengkap`, `role`, `no_hp`, `alamat_lengkap`, `created_at`, `updated_at`) VALUES
(1, 'admin@dixgamefarm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin DIX Game Farm', 'ADMIN', '+6285847937050', 'Jl. Umadiwang, Br. Penyucuk, Dusun Perean Kauh, Desa Perean, Kecamatan Baturiti, Kabupaten Tabanan, Bali', NOW(), NOW());

-- --------------------------------------------------------
-- Default data for `kategori`
-- --------------------------------------------------------

INSERT INTO `kategori` (`id`, `nama_kategori`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Ayam Hidup', 'Ayam hidup siap kirim', NOW(), NOW()),
(2, 'DOC (Day Old Chicken)', 'Anak ayam umur 1 hari', NOW(), NOW()),
(3, 'Telur Tetas', 'Telur untuk ditetaskan', NOW(), NOW()),
(4, 'Pakan & Vitamin', 'Pakan dan vitamin untuk ayam', NOW(), NOW());

-- --------------------------------------------------------
-- Default data for `produk` (sample products)
-- --------------------------------------------------------

INSERT INTO `produk` (`id`, `kategori_id`, `nama_ayam`, `usia_berat`, `stok_tersedia`, `stok_dibooking`, `harga`, `deskripsi`, `foto`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Boston Roundhead', 'Dewasa, 1.8-2.2 kg', 50, 0, 3000000.00, 'Ayam Boston Roundhead berkualitas, sehat, aktif, dan siap untuk koleksi atau breeding.', 'uploads/produk/Boston Roundhead.png', 1, NOW(), NOW()),
(2, 1, 'Moonwalker Grey', 'Dewasa, 1.7-2.1 kg', 30, 0, 4000000.00, 'Ayam Moonwalker Grey pilihan dengan kondisi prima, cocok untuk koleksi dan breeding.', 'uploads/produk/Moonwalker Grey.png', 1, NOW(), NOW()),
(3, 1, 'Popeye Grey', 'Dewasa, 1.8-2.3 kg', 100, 0, 5000000.00, 'Ayam Popeye Grey sehat dan aktif dengan kualitas terbaik dari DIX Game Farm.', 'uploads/produk/Popeye Grey.png', 1, NOW(), NOW()),
(4, 3, 'Telur Kampung Premium', '-', 200, 0, 3500.00, 'Telur kampung premium untuk ditetaskan', 'uploads/produk/telur_kampung_premium.jpg', 1, NOW(), NOW()),
(5, 4, 'Pakan Starter', '1 kg', 50, 0, 15000.00, 'Pakan starter untuk anakan ayam', 'uploads/produk/pakan_starter.jpg', 1, NOW(), NOW()),
(6, 4, 'Vitamin Ayam', '100 ml', 30, 0, 25000.00, 'Vitamin untuk kesehatan ayam', 'uploads/produk/vitamin_ayam.jpg', 1, NOW(), NOW()),
(7, 2, 'DOC Ayam Paket 1 Sarang', '1 sarang isi 8 ekor', 100, 0, 1300000.00, 'DOC ayam paket 1 sarang isi 8 ekor lengkap vaksin.', 'uploads/produk/doc_joper.jpg', 1, NOW(), NOW());

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
