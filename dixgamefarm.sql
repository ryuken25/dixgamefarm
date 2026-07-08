-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: dixgamefarm
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `detail_pesanan`
--

DROP TABLE IF EXISTS `detail_pesanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detail_pesanan` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pesanan_id` int(11) unsigned NOT NULL,
  `produk_id` int(11) unsigned NOT NULL,
  `harga_satuan_snapshot` decimal(10,2) NOT NULL COMMENT 'Harga terkunci saat checkout, tidak terpengaruh perubahan harga masa depan',
  `jumlah` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `is_preorder_item` tinyint(1) DEFAULT 0 COMMENT 'Snapshot: apakah item ini dipesan sebagai pre-order',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detail_pesanan_produk_id_foreign` (`produk_id`),
  KEY `idx_detail_pesanan_pesanan_produk` (`pesanan_id`,`produk_id`),
  CONSTRAINT `detail_pesanan_pesanan_id_foreign` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `detail_pesanan_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_pesanan`
--

LOCK TABLES `detail_pesanan` WRITE;
/*!40000 ALTER TABLE `detail_pesanan` DISABLE KEYS */;
INSERT INTO `detail_pesanan` VALUES (1,1,6,120000.00,3,360000.00,0,'2026-03-05 14:30:00','2026-03-05 14:30:00'),(2,1,1,3000.00,10,30000.00,0,'2026-03-05 14:30:00','2026-03-05 14:30:00'),(3,2,4,8000.00,25,200000.00,0,'2026-03-10 09:15:00','2026-03-10 09:15:00'),(4,3,6,120000.00,2,240000.00,0,'2026-03-15 18:45:00','2026-03-15 18:45:00'),(5,4,2,3500.00,10,35000.00,0,'2026-03-22 11:00:00','2026-03-22 11:00:00'),(6,4,1,3000.00,15,45000.00,0,'2026-03-22 11:00:00','2026-03-22 11:00:00'),(7,4,5,9000.00,0,0.00,0,'2026-03-22 11:00:00','2026-03-22 11:00:00'),(8,5,5,9000.00,30,270000.00,0,'2026-03-25 15:30:00','2026-03-25 15:30:00'),(9,6,6,120000.00,3,360000.00,0,'2026-03-27 10:00:00','2026-03-27 10:00:00'),(10,7,1,3000.00,35,105000.00,0,'2026-03-28 20:15:00','2026-03-28 20:15:00'),(11,8,4,8000.00,8,64000.00,0,'2026-03-29 08:00:00','2026-03-29 08:00:00'),(12,8,10,8000.00,1,8000.00,0,'2026-03-29 08:00:00','2026-03-29 08:00:00'),(13,9,4,1300000.00,1,1300000.00,0,'2026-05-21 13:29:28','2026-05-21 13:29:28'),(15,11,1,3000.00,1,3000.00,0,'2026-06-04 18:11:46','2026-06-04 18:11:46'),(16,12,8,5000000.00,1,5000000.00,0,'2026-06-06 08:33:01','2026-06-06 08:33:01'),(17,13,7,4000000.00,1,4000000.00,0,'2026-06-06 10:36:02','2026-06-06 10:36:02'),(18,14,7,4000000.00,1,4000000.00,0,'2026-06-06 10:46:05','2026-06-06 10:46:05'),(19,15,4,1300000.00,1,1300000.00,0,'2026-06-07 23:32:41','2026-06-07 23:32:41'),(20,16,4,1300000.00,1,1300000.00,0,'2026-06-07 23:41:11','2026-06-07 23:41:11'),(21,17,7,4000000.00,1,4000000.00,0,'2026-06-08 13:04:54','2026-06-08 13:04:54'),(23,19,8,5000000.00,1,5000000.00,0,'2026-06-08 20:56:49','2026-06-08 20:56:49'),(24,20,8,5000000.00,1,5000000.00,0,'2026-06-13 08:43:09','2026-06-13 08:43:09'),(25,21,8,5000000.00,1,5000000.00,0,'2026-06-13 08:46:39','2026-06-13 08:46:39'),(26,22,7,4000000.00,1,4000000.00,0,'2026-07-08 09:00:52','2026-07-08 09:00:52');
/*!40000 ALTER TABLE `detail_pesanan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_keranjang`
--

DROP TABLE IF EXISTS `item_keranjang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_keranjang` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `keranjang_id` int(11) unsigned NOT NULL,
  `produk_id` int(11) unsigned NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_item_keranjang_cart_product` (`keranjang_id`,`produk_id`),
  KEY `item_keranjang_produk_id_foreign` (`produk_id`),
  KEY `idx_item_keranjang_cart_product` (`keranjang_id`,`produk_id`),
  CONSTRAINT `item_keranjang_keranjang_id_foreign` FOREIGN KEY (`keranjang_id`) REFERENCES `keranjang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `item_keranjang_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_keranjang`
--

LOCK TABLES `item_keranjang` WRITE;
/*!40000 ALTER TABLE `item_keranjang` DISABLE KEYS */;
INSERT INTO `item_keranjang` VALUES (2,2,9,2,'2026-03-29 09:12:00','2026-03-29 09:12:00'),(3,4,1,30,'2026-03-29 10:50:00','2026-03-29 11:00:00'),(4,4,10,1,'2026-03-29 10:55:00','2026-03-29 10:55:00');
/*!40000 ALTER TABLE `item_keranjang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori`
--

DROP TABLE IF EXISTS `kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori`
--

LOCK TABLES `kategori` WRITE;
/*!40000 ALTER TABLE `kategori` DISABLE KEYS */;
INSERT INTO `kategori` VALUES (1,'Telur','Telur ayam kampung super berkualitas tinggi, segar langsung dari peternakan DIX Game Farm','2026-01-15 08:30:00','2026-01-15 08:30:00'),(2,'DOC (Day Old Chick)','Anak ayam umur 1 hari yang sudah divaksin, siap dibesarkan untuk peternakan','2026-01-15 08:31:00','2026-01-15 08:31:00'),(3,'Ayam Siap Jual','Ayam dewasa siap konsumsi, potong, atau untuk breeding berkualitas','2026-01-15 08:32:00','2026-01-15 08:32:00'),(4,'Pakan & Suplemen','Pakan ayam dan suplemen vitamin untuk peternakan ayam kampung','2026-01-15 08:33:00','2026-01-15 08:33:00');
/*!40000 ALTER TABLE `kategori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `keranjang`
--

DROP TABLE IF EXISTS `keranjang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `keranjang` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `last_updated` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_keranjang_user` (`user_id`),
  CONSTRAINT `keranjang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `keranjang`
--

LOCK TABLES `keranjang` WRITE;
/*!40000 ALTER TABLE `keranjang` DISABLE KEYS */;
INSERT INTO `keranjang` VALUES (1,2,'2026-07-08 08:59:44','2026-02-01 10:35:00'),(2,3,'2026-03-29 09:15:00','2026-02-10 14:20:00'),(3,4,'2026-03-20 18:30:00','2026-02-20 09:05:00'),(4,5,'2026-03-29 11:00:00','2026-03-01 12:00:00'),(5,6,'2026-03-15 14:00:00','2026-03-10 16:25:00'),(7,32,'2026-06-04 18:11:24','2026-06-04 18:11:05'),(8,33,'2026-06-13 08:45:14','2026-06-06 08:27:37');
/*!40000 ALTER TABLE `keranjang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-03-24-100000','App\\Database\\Migrations\\CreateUsersTable','default','App',1783503248,1),(2,'2026-03-24-100100','App\\Database\\Migrations\\CreateKategoriTable','default','App',1783503248,1),(3,'2026-03-24-100200','App\\Database\\Migrations\\CreateProdukTable','default','App',1783503248,1),(4,'2026-03-24-100300','App\\Database\\Migrations\\CreateKeranjangTable','default','App',1783503248,1),(5,'2026-03-24-100400','App\\Database\\Migrations\\CreateItemKeranjangTable','default','App',1783503248,1),(6,'2026-03-24-100500','App\\Database\\Migrations\\CreatePesananTable','default','App',1783503248,1),(7,'2026-03-24-100600','App\\Database\\Migrations\\CreateDetailPesananTable','default','App',1783503248,1),(8,'2026-03-24-100700','App\\Database\\Migrations\\CreatePembayaranTable','default','App',1783503248,1),(9,'2026-03-24-100900','App\\Database\\Migrations\\CreateNotifikasiTable','default','App',1783503248,1),(10,'2026-04-18-100000','App\\Database\\Migrations\\AddBankPilihanToPesanan','default','App',1783503248,1),(11,'2026-04-18-100100','App\\Database\\Migrations\\AddPreorderToProduk','default','App',1783503248,1),(12,'2026-04-18-100200','App\\Database\\Migrations\\AddPesananSiapAndResiToPesanan','default','App',1783503248,1),(13,'2026-04-18-100300','App\\Database\\Migrations\\AddPerformanceIndexes','default','App',1783503249,1),(14,'2026-04-18-100400','App\\Database\\Migrations\\AddCheckoutSnapshotToPesanan','default','App',1783503249,1),(15,'2026-04-18-101000','App\\Database\\Migrations\\AlignOrderStatusFlowAndCheckoutSchema','default','App',1783503249,1),(16,'2026-04-18-101100','App\\Database\\Migrations\\HardenCartItemUniqueness','default','App',1783503249,1),(17,'2026-04-18-101150','App\\Database\\Migrations\\HardenKeranjangUniqueness','default','App',1783503249,1),(18,'2026-04-18-101200','App\\Database\\Migrations\\CreateTelegramActionTokensTable','default','App',1783503249,1),(19,'2026-06-06-120000','App\\Database\\Migrations\\AddShipmentTimestampsToPesanan','default','App',1783503249,1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifikasi`
--

DROP TABLE IF EXISTS `notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifikasi` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifikasi_user_id_foreign` (`user_id`),
  CONSTRAINT `notifikasi_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifikasi`
--

LOCK TABLES `notifikasi` WRITE;
/*!40000 ALTER TABLE `notifikasi` DISABLE KEYS */;
INSERT INTO `notifikasi` VALUES (1,1,'Pesanan Baru Masuk','Pesanan baru INV-20260329-1122 dari Ni Putu Ayu Lestari senilai Rp 72.000. Silakan cek dashboard.',0,'2026-03-29 08:00:00'),(2,1,'Pembayaran Menunggu Verifikasi','Pembayaran untuk pesanan INV-20260327-2345 dari Ni Made Sri Wahyuni telah diupload. Silakan verifikasi.',0,'2026-03-27 12:30:00'),(3,1,'Pesanan Baru Masuk','Pesanan baru INV-20260328-6789 dari I Wayan Dharma Putra senilai Rp 105.000.',1,'2026-03-28 20:15:00'),(4,2,'Selamat Datang!','Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',1,'2026-02-01 10:30:00'),(5,2,'Pesanan Selesai','Pesanan INV-20260305-1234 telah selesai. Terima kasih telah berbelanja di DIX Game Farm!',1,'2026-03-08 10:00:00'),(6,2,'Pesanan Sedang Dikirim','Pesanan INV-20260322-3456 sedang dalam perjalanan ke alamat Anda. Harap bersiap menerima paket.',0,'2026-03-27 09:00:00'),(7,3,'Selamat Datang!','Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',1,'2026-02-10 14:15:00'),(8,3,'Pesanan Dibuat','Pesanan INV-20260329-1122 berhasil dibuat. Silakan lakukan pembayaran sebelum 30 Maret 2026 08:00.',0,'2026-03-29 08:00:00'),(9,4,'Selamat Datang!','Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',1,'2026-02-20 09:00:00'),(10,4,'Pesanan Dibatalkan','Pesanan INV-20260315-9012 telah dibatalkan. Stok telah dikembalikan.',1,'2026-03-16 07:00:00'),(11,4,'Pesanan Dibuat','Pesanan INV-20260328-6789 berhasil dibuat. Silakan lakukan pembayaran sebelum 29 Maret 2026 20:15.',0,'2026-03-28 20:15:00'),(12,5,'Selamat Datang!','Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',1,'2026-03-01 11:45:00'),(13,5,'Pembayaran Diterima','Bukti pembayaran untuk pesanan INV-20260327-2345 telah kami terima. Menunggu verifikasi admin.',1,'2026-03-27 12:30:00'),(14,6,'Selamat Datang!','Selamat datang di DIX Game Farm! Mulai berbelanja produk ayam kampung berkualitas kami.',1,'2026-03-10 16:20:00'),(15,6,'Pesanan Diproses','Pesanan INV-20260325-7890 sedang diproses oleh tim kami. Kami akan segera mengirimkan pesanan Anda.',0,'2026-03-27 08:30:00'),(16,2,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260521-7409 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-05-21 13:29:28'),(17,2,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260521-7409 telah diterima dan menunggu verifikasi admin.',0,'2026-05-21 13:38:49'),(18,2,'Pembayaran Diverifikasi','Pembayaran Anda untuk invoice INV-20260521-7409 telah diverifikasi. Pesanan sedang diproses.',0,'2026-05-21 18:27:59'),(22,32,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260604-8074 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-04 18:11:46'),(23,32,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260604-8074 telah diterima dan menunggu verifikasi admin.',0,'2026-06-04 18:12:54'),(24,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260606-8488 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-06 08:33:01'),(25,33,'Pesanan Dibatalkan','Pesanan INV-20260606-8488 telah dibatalkan. Alasan: Dibatalkan oleh pelanggan',0,'2026-06-06 10:16:05'),(26,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260606-7962 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-06 10:36:02'),(27,33,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260606-7962 telah diterima dan menunggu verifikasi admin.',0,'2026-06-06 10:36:51'),(28,33,'Pembayaran Diverifikasi','Pembayaran Anda untuk invoice INV-20260606-7962 telah diverifikasi. Pesanan sedang diproses.',0,'2026-06-06 10:37:44'),(29,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260606-7735 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-06 10:46:05'),(30,33,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260606-7735 telah diterima dan menunggu verifikasi admin.',0,'2026-06-06 10:46:40'),(31,33,'Pembayaran Diverifikasi','Pembayaran Anda untuk invoice INV-20260606-7735 telah diverifikasi. Pesanan sedang diproses.',0,'2026-06-06 11:07:34'),(32,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260607-1037 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-07 23:32:41'),(33,33,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260607-1037 telah diterima dan menunggu verifikasi admin.',0,'2026-06-07 23:33:39'),(34,33,'Pembayaran Diverifikasi','Pembayaran Anda untuk invoice INV-20260607-1037 telah diverifikasi. Pesanan sedang diproses.',0,'2026-06-07 23:34:37'),(35,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260607-1363 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-07 23:41:11'),(36,33,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260607-1363 telah diterima dan menunggu verifikasi admin.',0,'2026-06-07 23:41:47'),(37,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260608-1172 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-08 13:04:54'),(38,33,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260608-1172 telah diterima dan menunggu verifikasi admin.',0,'2026-06-08 13:05:56'),(39,33,'Pembayaran Diverifikasi','Pembayaran Anda untuk invoice INV-20260607-1363 telah diverifikasi. Pesanan sedang diproses.',0,'2026-06-08 13:06:49'),(40,33,'Pembayaran Diverifikasi','Pembayaran Anda untuk invoice INV-20260608-1172 telah diverifikasi. Pesanan sedang diproses.',0,'2026-06-08 13:07:12'),(42,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260608-6807 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-08 20:56:49'),(43,33,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260608-6807 telah diterima dan menunggu verifikasi admin.',0,'2026-06-08 20:57:59'),(44,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260613-5540 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-13 08:43:09'),(45,33,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260613-4233 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-06-13 08:46:39'),(46,33,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260613-4233 telah diterima dan menunggu verifikasi admin.',0,'2026-06-13 08:47:17'),(47,33,'Pembayaran Diverifikasi','Pembayaran Anda untuk invoice INV-20260608-6807 telah diverifikasi. Pesanan sedang diproses.',0,'2026-06-13 08:51:57'),(48,2,'Pesanan Berhasil Dibuat','Pesanan Anda dengan nomor invoice INV-20260708-1968 telah dibuat. Silakan lakukan pembayaran dalam 24 jam.',0,'2026-07-08 09:00:52'),(49,2,'Bukti Pembayaran Diterima','Bukti pembayaran untuk invoice INV-20260708-1968 telah diterima dan menunggu verifikasi admin.',0,'2026-07-08 09:01:47'),(50,2,'Pembayaran Diverifikasi','Pembayaran Anda untuk invoice INV-20260708-1968 telah diverifikasi. Pesanan sedang diproses.',0,'2026-07-08 09:03:34');
/*!40000 ALTER TABLE `notifikasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pembayaran`
--

DROP TABLE IF EXISTS `pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pembayaran` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pesanan_id` int(11) unsigned NOT NULL,
  `nominal_bayar` decimal(12,2) NOT NULL,
  `nama_bank` varchar(100) DEFAULT NULL COMMENT 'Bank pengirim (misal: BCA, BNI, Mandiri)',
  `bank_tujuan` varchar(100) DEFAULT NULL COMMENT 'Bank penerima DIX Game Farm',
  `bukti_bayar` varchar(255) DEFAULT NULL COMMENT 'Path file bukti transfer',
  `status_pembayaran` enum('PENDING','VALID','INVALID') NOT NULL DEFAULT 'PENDING',
  `tanggal_upload` datetime DEFAULT NULL,
  `tanggal_verifikasi` datetime DEFAULT NULL,
  `verifikator_id` int(11) unsigned DEFAULT NULL COMMENT 'ID Admin yang memverifikasi',
  `alasan_ditolak` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pembayaran_verifikator_id_foreign` (`verifikator_id`),
  KEY `idx_pembayaran_status_upload` (`status_pembayaran`,`tanggal_upload`),
  KEY `idx_pembayaran_pesanan_created` (`pesanan_id`,`created_at`),
  CONSTRAINT `pembayaran_pesanan_id_foreign` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pembayaran_verifikator_id_foreign` FOREIGN KEY (`verifikator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pembayaran`
--

LOCK TABLES `pembayaran` WRITE;
/*!40000 ALTER TABLE `pembayaran` DISABLE KEYS */;
INSERT INTO `pembayaran` VALUES (1,1,390000.00,'BCA','BCA - DIX Game Farm (1234567890)',NULL,'VALID','2026-03-05 15:20:00','2026-03-06 08:00:00',1,NULL,'2026-03-05 15:20:00','2026-03-06 08:00:00'),(2,2,200000.00,'BNI','BCA - DIX Game Farm (1234567890)',NULL,'VALID','2026-03-10 10:00:00','2026-03-10 14:30:00',1,NULL,'2026-03-10 10:00:00','2026-03-10 14:30:00'),(3,4,150000.00,'Mandiri','BCA - DIX Game Farm (1234567890)',NULL,'VALID','2026-03-22 12:00:00','2026-03-23 08:00:00',1,NULL,'2026-03-22 12:00:00','2026-03-23 08:00:00'),(4,5,270000.00,'BRI','BCA - DIX Game Farm (1234567890)',NULL,'VALID','2026-03-25 16:45:00','2026-03-27 08:30:00',1,NULL,'2026-03-25 16:45:00','2026-03-27 08:30:00'),(5,6,360000.00,'BCA','BCA - DIX Game Farm (1234567890)',NULL,'PENDING','2026-03-27 12:30:00',NULL,NULL,NULL,'2026-03-27 12:30:00','2026-03-27 12:30:00'),(6,9,1300000.00,NULL,'GoPay','uploads/bukti_bayar/bukti_INV-20260521-7409_1779341929.png','VALID','2026-05-21 13:38:49','2026-05-21 18:27:59',1,NULL,'2026-05-21 13:38:49','2026-05-21 18:27:59'),(8,11,3000.00,NULL,'DANA','uploads/bukti_bayar/bukti_INV-20260604-8074_1780567974.png','VALID','2026-06-04 18:12:54','2026-06-04 18:14:04',1,NULL,'2026-06-04 18:12:54','2026-06-04 18:14:04'),(9,13,4000000.00,NULL,'DANA','uploads/bukti_bayar/bukti_INV-20260606-7962_1780713411.png','VALID','2026-06-06 10:36:51','2026-06-06 10:37:44',1,NULL,'2026-06-06 10:36:51','2026-06-06 10:37:44'),(10,14,4000000.00,NULL,'DANA','uploads/bukti_bayar/bukti_INV-20260606-7735_1780714000.png','VALID','2026-06-06 10:46:40','2026-06-06 11:07:34',1,NULL,'2026-06-06 10:46:40','2026-06-06 11:07:34'),(11,15,1300000.00,NULL,'DANA','uploads/bukti_bayar/bukti_INV-20260607-1037_1780846419.png','VALID','2026-06-07 23:33:39','2026-06-07 23:34:37',1,NULL,'2026-06-07 23:33:39','2026-06-07 23:34:37'),(12,16,1300000.00,NULL,'Bank BCA','uploads/bukti_bayar/bukti_INV-20260607-1363_1780846907.png','VALID','2026-06-07 23:41:47','2026-06-08 13:06:49',1,NULL,'2026-06-07 23:41:47','2026-06-08 13:06:49'),(13,17,4000000.00,NULL,'Bank BCA','uploads/bukti_bayar/bukti_INV-20260608-1172_1780895156.png','VALID','2026-06-08 13:05:56','2026-06-08 13:07:12',1,NULL,'2026-06-08 13:05:56','2026-06-08 13:07:12'),(14,19,5000000.00,NULL,'Bank BCA','uploads/bukti_bayar/bukti_INV-20260608-6807_1780923479.png','VALID','2026-06-08 20:57:59','2026-06-13 08:51:57',1,NULL,'2026-06-08 20:57:59','2026-06-13 08:51:57'),(15,21,5000000.00,NULL,'Bank BCA','uploads/bukti_bayar/bukti_INV-20260613-4233_1781311637.png','PENDING','2026-06-13 08:47:17',NULL,NULL,NULL,'2026-06-13 08:47:17','2026-06-13 08:47:17'),(16,22,4000000.00,NULL,'GoPay','uploads/bukti_bayar/bukti_INV-20260708-1968_1783472507.png','VALID','2026-07-08 09:01:47','2026-07-08 09:03:34',1,NULL,'2026-07-08 09:01:47','2026-07-08 09:03:34');
/*!40000 ALTER TABLE `pembayaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pesanan`
--

DROP TABLE IF EXISTS `pesanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pesanan` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `nomor_invoice` varchar(50) NOT NULL COMMENT 'Format: INV-YYYYMMDD-RandomNumber',
  `tanggal_pesanan` datetime NOT NULL,
  `expired_at` datetime DEFAULT NULL COMMENT 'Batas bayar 24 jam dari tanggal_pesanan',
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status_pesanan` enum('MENUNGGU_BAYAR','DIPROSES','PESANAN_SIAP','DIKIRIM','SELESAI','BATAL') NOT NULL DEFAULT 'MENUNGGU_BAYAR',
  `kode_resi` varchar(100) DEFAULT NULL COMMENT 'Kode tracking pengiriman kurir',
  `diproses_at` datetime DEFAULT NULL COMMENT 'Saat status pindah ke DIPROSES (mulai countdown SLA 24 jam kurir)',
  `dikirim_at` datetime DEFAULT NULL COMMENT 'Saat status pindah ke DIKIRIM (kurir berangkat)',
  `reminder_terlambat_at` datetime DEFAULT NULL COMMENT 'Tanda reminder Telegram untuk pesanan terlambat sudah dikirim',
  `tipe_pengiriman` enum('AMBIL_SENDIRI','DIKIRIM_KURIR') NOT NULL DEFAULT 'AMBIL_SENDIRI',
  `metode_pembayaran` varchar(50) DEFAULT NULL COMMENT 'Bank/ewallet pilihan pelanggan saat checkout (BRI, BNI, BCA, ShopeePay, Dana, GoPay)',
  `nomor_rekening_tujuan` varchar(100) DEFAULT NULL COMMENT 'Nomor rekening / akun tujuan transfer',
  `catatan_pelanggan` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `nama_penerima` varchar(100) DEFAULT NULL,
  `email_penerima` varchar(100) DEFAULT NULL,
  `no_hp_penerima` varchar(20) DEFAULT NULL,
  `alamat_pengiriman` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_invoice` (`nomor_invoice`),
  KEY `idx_pesanan_status_tanggal` (`status_pesanan`,`tanggal_pesanan`),
  KEY `idx_pesanan_user_tanggal` (`user_id`,`tanggal_pesanan`),
  KEY `idx_pesanan_status_expired` (`status_pesanan`,`expired_at`),
  KEY `idx_pesanan_tanggal` (`tanggal_pesanan`),
  CONSTRAINT `pesanan_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pesanan`
--

LOCK TABLES `pesanan` WRITE;
/*!40000 ALTER TABLE `pesanan` DISABLE KEYS */;
INSERT INTO `pesanan` VALUES (1,2,'INV-20260305-1234','2026-03-05 14:30:00','2026-03-06 14:30:00',390000.00,'SELESAI',NULL,'2026-03-08 10:00:00','2026-03-08 10:00:00',NULL,'AMBIL_SENDIRI',NULL,NULL,'Tolong siapkan pagi-pagi ya pak','2026-03-05 14:30:00','2026-03-08 10:00:00',NULL,NULL,NULL,NULL),(2,3,'INV-20260310-5678','2026-03-10 09:15:00','2026-03-11 09:15:00',200000.00,'SELESAI',NULL,'2026-03-13 16:00:00','2026-03-13 16:00:00',NULL,'DIKIRIM_KURIR',NULL,NULL,'Kirim ke alamat rumah, ada orang di rumah siang','2026-03-10 09:15:00','2026-03-13 16:00:00',NULL,NULL,NULL,NULL),(3,4,'INV-20260315-9012','2026-03-15 18:45:00','2026-03-16 18:45:00',240000.00,'BATAL',NULL,NULL,NULL,NULL,'AMBIL_SENDIRI',NULL,NULL,NULL,'2026-03-15 18:45:00','2026-03-16 07:00:00',NULL,NULL,NULL,NULL),(4,2,'INV-20260322-3456','2026-03-22 11:00:00','2026-03-23 11:00:00',150000.00,'DIKIRIM',NULL,'2026-03-27 09:00:00','2026-03-27 09:00:00',NULL,'DIKIRIM_KURIR',NULL,NULL,'Telur tolong bungkus ekstra hati-hati','2026-03-22 11:00:00','2026-03-27 09:00:00',NULL,NULL,NULL,NULL),(5,6,'INV-20260325-7890','2026-03-25 15:30:00','2026-03-26 15:30:00',270000.00,'DIPROSES',NULL,'2026-03-27 08:30:00',NULL,NULL,'DIKIRIM_KURIR',NULL,NULL,'Kirim via JNE ke Singaraja','2026-03-25 15:30:00','2026-03-27 08:30:00',NULL,NULL,NULL,NULL),(6,5,'INV-20260327-2345','2026-03-27 10:00:00','2026-03-28 10:00:00',360000.00,'MENUNGGU_BAYAR',NULL,NULL,NULL,NULL,'AMBIL_SENDIRI',NULL,NULL,'Saya ambil hari Sabtu siang','2026-03-27 10:00:00','2026-03-27 12:30:00',NULL,NULL,NULL,NULL),(7,4,'INV-20260328-6789','2026-03-28 20:15:00','2026-03-29 20:15:00',105000.00,'MENUNGGU_BAYAR',NULL,NULL,NULL,NULL,'AMBIL_SENDIRI',NULL,NULL,NULL,'2026-03-28 20:15:00','2026-03-28 20:15:00',NULL,NULL,NULL,NULL),(8,3,'INV-20260329-1122','2026-03-29 08:00:00','2026-03-30 08:00:00',72000.00,'MENUNGGU_BAYAR',NULL,NULL,NULL,NULL,'AMBIL_SENDIRI',NULL,NULL,'Untuk acara upacara','2026-03-29 08:00:00','2026-03-29 08:00:00',NULL,NULL,NULL,NULL),(9,2,'INV-20260521-7409','2026-05-21 13:29:28',NULL,1300000.00,'DIPROSES',NULL,'2026-05-21 18:27:59',NULL,NULL,'AMBIL_SENDIRI','GoPay','08123456789','','2026-05-21 13:29:28','2026-05-21 18:27:59','I Ketut Suarjana','ketut.suarjana@gmail.com','081987654321','Jl. Hayam Wuruk No. 45, Denpasar Selatan, Bali'),(11,32,'INV-20260604-8074','2026-06-04 18:11:46',NULL,3000.00,'SELESAI',NULL,'2026-06-04 18:15:19','2026-06-04 18:15:19',NULL,'AMBIL_SENDIRI','Dana','08123456789','','2026-06-04 18:11:46','2026-06-04 18:15:19','wahina jelek','wahinaaa123@gmail.com','085792717468','1233lolokgede'),(12,33,'INV-20260606-8488','2026-06-06 08:33:01','2026-06-07 08:33:01',5000000.00,'BATAL',NULL,NULL,NULL,NULL,'AMBIL_SENDIRI','Dana','08123456789','','2026-06-06 08:33:01','2026-06-06 10:16:05','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(13,33,'INV-20260606-7962','2026-06-06 10:36:02',NULL,4000000.00,'DIPROSES',NULL,'2026-06-06 10:37:44',NULL,NULL,'AMBIL_SENDIRI','Dana','08123456789','','2026-06-06 10:36:02','2026-06-06 10:37:44','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(14,33,'INV-20260606-7735','2026-06-06 10:46:05',NULL,4000000.00,'DIKIRIM','JNE12345678','2026-06-06 11:09:28','2026-06-06 11:09:28',NULL,'DIKIRIM_KURIR','Dana','08123456789','','2026-06-06 10:46:05','2026-06-06 11:09:28','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(15,33,'INV-20260607-1037','2026-06-07 23:32:41',NULL,1300000.00,'SELESAI','JNT12345678','2026-06-07 23:39:02','2026-06-07 23:39:02',NULL,'DIKIRIM_KURIR','Dana','08123456789','','2026-06-07 23:32:41','2026-06-07 23:39:02','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(16,33,'INV-20260607-1363','2026-06-07 23:41:11',NULL,1300000.00,'DIPROSES',NULL,'2026-06-08 13:06:49',NULL,NULL,'DIKIRIM_KURIR','BCA','1234567890','','2026-06-07 23:41:11','2026-06-08 13:06:49','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(17,33,'INV-20260608-1172','2026-06-08 13:04:54',NULL,4000000.00,'DIKIRIM','JNE12345678','2026-06-08 13:07:51','2026-06-08 13:07:51',NULL,'DIKIRIM_KURIR','BCA','1234567890','','2026-06-08 13:04:54','2026-06-08 13:07:51','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(19,33,'INV-20260608-6807','2026-06-08 20:56:49',NULL,5000000.00,'DIPROSES',NULL,'2026-06-13 08:51:57',NULL,NULL,'DIKIRIM_KURIR','BCA','1234567890','','2026-06-08 20:56:49','2026-06-13 08:51:57','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(20,33,'INV-20260613-5540','2026-06-13 08:43:09','2026-06-14 08:43:09',5000000.00,'MENUNGGU_BAYAR',NULL,NULL,NULL,NULL,'DIKIRIM_KURIR','BCA','1234567890','','2026-06-13 08:43:09','2026-06-13 08:43:09','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(21,33,'INV-20260613-4233','2026-06-13 08:46:39',NULL,5000000.00,'MENUNGGU_BAYAR',NULL,NULL,NULL,NULL,'DIKIRIM_KURIR','BCA','1234567890','','2026-06-13 08:46:39','2026-06-13 08:47:17','putra krisna','putrakrisna232@gmail.com','085792717468','Jl. Gadung No 10, Denpasar Timur'),(22,2,'INV-20260708-1968','2026-07-08 09:00:52',NULL,4000000.00,'DIPROSES',NULL,'2026-07-08 09:03:34',NULL,NULL,'DIKIRIM_KURIR','GoPay','08123456789','','2026-07-08 09:00:52','2026-07-08 09:03:34','I Ketut Suarjana','ketut.suarjana@gmail.com','081987654321','Jl. Hayam Wuruk No. 45, Denpasar Selatan, Bali');
/*!40000 ALTER TABLE `pesanan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produk`
--

DROP TABLE IF EXISTS `produk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produk` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `kategori_id` int(11) unsigned NOT NULL,
  `nama_ayam` varchar(255) NOT NULL,
  `usia_berat` varchar(100) DEFAULT NULL,
  `stok_tersedia` int(11) NOT NULL DEFAULT 0 COMMENT 'Stok riil yang siap dibeli',
  `stok_dibooking` int(11) NOT NULL DEFAULT 0 COMMENT 'Stok yang sedang di-checkout, belum lunas',
  `harga` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL COMMENT 'Path file lokal di public/uploads',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_preorder` tinyint(1) DEFAULT 0 COMMENT 'Jika true, produk bisa dipesan meskipun stok = 0 (DOC / Day Old Chick)',
  `estimasi_pre_order` varchar(100) DEFAULT NULL COMMENT 'Estimasi ready (misal: "2-3 minggu", "H+7 setelah konfirmasi")',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produk_kategori_id_foreign` (`kategori_id`),
  KEY `idx_produk_active_stock` (`is_active`,`stok_tersedia`),
  KEY `idx_produk_active_kategori` (`is_active`,`kategori_id`),
  CONSTRAINT `produk_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produk`
--

LOCK TABLES `produk` WRITE;
/*!40000 ALTER TABLE `produk` DISABLE KEYS */;
INSERT INTO `produk` VALUES (1,1,'Telur Ayam Kampung Super','Grade A',84,15,3000.00,'Telur ayam kampung super berkualitas tinggi, segar dari peternakan langsung. Cocok untuk konsumsi sehari-hari dan MPASI bayi.','uploads/produk/produk_1778597503_6a033e7f3ff6b.png',1,0,NULL,'2026-01-20 09:00:00','2026-06-04 18:15:19'),(2,1,'Telur Ayam Kampung Premium','Grade AA',40,10,3500.00,'Telur ayam kampung premium ukuran besar dengan kuning telur pekat. Ideal untuk kue dan masakan spesial.',NULL,1,0,NULL,'2026-01-20 09:05:00','2026-03-27 10:00:00'),(4,2,'DOC Ayam Kampung Super','1 sarang isi 8 ekor',172,27,1300000.00,'DOC ayam paket 1 sarang isi 8 ekor lengkap vaksin. Siap dibesarkan dengan survival rate tinggi.',NULL,1,0,NULL,'2026-01-20 09:10:00','2026-06-07 23:41:11'),(5,2,'DOC Ayam Joper','1 sarang isi 8 ekor',120,30,1300000.00,'DOC ayam joper paket 1 sarang isi 8 ekor lengkap vaksin. Pertumbuhan cepat, tahan penyakit, cocok untuk ternak.',NULL,1,0,NULL,'2026-01-20 09:15:00','2026-03-26 09:00:00'),(6,3,'Boston Roundhead','Dewasa, 1.8-2.2 kg',18,7,3000000.00,'Ayam Boston Roundhead berkualitas, sehat, aktif, dan siap untuk koleksi atau breeding.','uploads/produk/Boston Roundhead.png',1,0,NULL,'2026-01-20 09:20:00','2026-03-28 08:00:00'),(7,3,'Moonwalker Grey','Dewasa, 1.7-2.1 kg',18,12,4000000.00,'Ayam Moonwalker Grey pilihan dengan kondisi prima, cocok untuk koleksi dan breeding.','uploads/produk/Moonwalker Grey.png',1,0,NULL,'2026-01-20 09:25:00','2026-07-08 09:00:52'),(8,3,'Popeye Grey','Dewasa, 1.8-2.3 kg',11,9,5000000.00,'Ayam Popeye Grey sehat dan aktif dengan kualitas terbaik dari DIX Game Farm.','uploads/produk/Popeye Grey.png',1,0,NULL,'2026-01-20 09:30:00','2026-06-13 08:46:39'),(9,4,'Pakan Ayam Kampung Starter','0-4 minggu',50,0,45000.00,'Pakan starter untuk DOC umur 0-4 minggu. Formula khusus pertumbuhan awal yang optimal. Per karung 5 kg.',NULL,1,0,NULL,'2026-02-15 10:00:00','2026-02-15 10:00:00'),(10,4,'Vitamin Ayam Super Complete','Semua umur',8,0,35000.00,'Vitamin lengkap untuk ayam kampung segala umur. Meningkatkan daya tahan tubuh dan produktivitas. Per botol 100ml.',NULL,1,0,NULL,'2026-02-15 10:05:00','2026-02-15 10:05:00');
/*!40000 ALTER TABLE `produk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telegram_action_tokens`
--

DROP TABLE IF EXISTS `telegram_action_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telegram_action_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(32) NOT NULL,
  `action_type` varchar(80) NOT NULL,
  `chat_id` varchar(50) NOT NULL,
  `payload_json` text DEFAULT NULL,
  `is_single_use` tinyint(1) NOT NULL DEFAULT 1,
  `used_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_telegram_action_token` (`token`),
  KEY `idx_telegram_action_chat_expiry` (`chat_id`,`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telegram_action_tokens`
--

LOCK TABLES `telegram_action_tokens` WRITE;
/*!40000 ALTER TABLE `telegram_action_tokens` DISABLE KEYS */;
INSERT INTO `telegram_action_tokens` VALUES (1,'d4ea82d2f0acda55a479f6d2','payment_action','1553203064','{\"payment_id\":8,\"decision\":\"approve\",\"message_id\":121}',1,NULL,'2026-06-07 18:12:54','2026-06-04 18:12:54','2026-06-04 18:13:09'),(2,'a5c5c0c5a62dab15e36ca40c','payment_action','1553203064','{\"payment_id\":8,\"decision\":\"reject\",\"message_id\":121}',1,NULL,'2026-06-07 18:12:54','2026-06-04 18:12:54','2026-06-04 18:13:09'),(3,'a8fc7c9e24941641d3f431a4','payment_action','8779270308','{\"payment_id\":8,\"decision\":\"approve\",\"message_id\":122}',1,NULL,'2026-06-07 18:13:09','2026-06-04 18:13:09','2026-06-04 18:13:11'),(4,'dc1cc03250e0c8b25567a1d8','payment_action','8779270308','{\"payment_id\":8,\"decision\":\"reject\",\"message_id\":122}',1,NULL,'2026-06-07 18:13:09','2026-06-04 18:13:09','2026-06-04 18:13:11'),(5,'bd631b47a7e27ac79eba4adc','payment_action','1553203064','{\"payment_id\":9,\"decision\":\"approve\",\"message_id\":127}',1,NULL,'2026-06-09 10:36:51','2026-06-06 10:36:51','2026-06-06 10:36:54'),(6,'3a2005abd209d5faa196fd14','payment_action','1553203064','{\"payment_id\":9,\"decision\":\"reject\",\"message_id\":127}',1,NULL,'2026-06-09 10:36:51','2026-06-06 10:36:51','2026-06-06 10:36:54'),(7,'edf47e4ff1ed8ee64ef5cb07','payment_action','8779270308','{\"payment_id\":9,\"decision\":\"approve\",\"message_id\":128}',1,NULL,'2026-06-09 10:36:54','2026-06-06 10:36:54','2026-06-06 10:36:57'),(8,'f37419a6eccf5170dfe7a3bf','payment_action','8779270308','{\"payment_id\":9,\"decision\":\"reject\",\"message_id\":128}',1,NULL,'2026-06-09 10:36:54','2026-06-06 10:36:54','2026-06-06 10:36:57'),(9,'93e696195fcdb54526d13632','payment_action','1553203064','{\"payment_id\":10,\"decision\":\"approve\",\"message_id\":131}',1,NULL,'2026-06-09 10:46:40','2026-06-06 10:46:40','2026-06-06 10:46:42'),(10,'4b1c688adb113cc3a48451c5','payment_action','1553203064','{\"payment_id\":10,\"decision\":\"reject\",\"message_id\":131}',1,NULL,'2026-06-09 10:46:40','2026-06-06 10:46:40','2026-06-06 10:46:42'),(11,'5ceee4ee9d27fc8eac0e9d24','payment_action','8779270308','{\"payment_id\":10,\"decision\":\"approve\",\"message_id\":132}',1,NULL,'2026-06-09 10:46:42','2026-06-06 10:46:42','2026-06-06 10:46:45'),(12,'499b0246b39893460f4de13a','payment_action','8779270308','{\"payment_id\":10,\"decision\":\"reject\",\"message_id\":132}',1,NULL,'2026-06-09 10:46:42','2026-06-06 10:46:42','2026-06-06 10:46:45'),(13,'ccdf9a20e11178f3babce539','payment_action','1553203064','{\"payment_id\":11,\"decision\":\"approve\",\"message_id\":137}',1,NULL,'2026-06-10 23:33:39','2026-06-07 23:33:39','2026-06-07 23:33:42'),(14,'79210cd116a6fec01626480a','payment_action','1553203064','{\"payment_id\":11,\"decision\":\"reject\",\"message_id\":137}',1,NULL,'2026-06-10 23:33:39','2026-06-07 23:33:39','2026-06-07 23:33:42'),(15,'af9404705a5607eb4a9180a7','payment_action','8779270308','{\"payment_id\":11,\"decision\":\"approve\",\"message_id\":138}',1,NULL,'2026-06-10 23:33:42','2026-06-07 23:33:42','2026-06-07 23:33:46'),(16,'9e62afbeeba847fbaec6c1d3','payment_action','8779270308','{\"payment_id\":11,\"decision\":\"reject\",\"message_id\":138}',1,NULL,'2026-06-10 23:33:42','2026-06-07 23:33:42','2026-06-07 23:33:46'),(17,'664164c43eeeed02d0a608a6','payment_action','1553203064','{\"payment_id\":12,\"decision\":\"approve\",\"message_id\":141}',1,NULL,'2026-06-10 23:41:47','2026-06-07 23:41:47','2026-06-07 23:41:50'),(18,'3ca6687da76962417cd539cf','payment_action','1553203064','{\"payment_id\":12,\"decision\":\"reject\",\"message_id\":141}',1,NULL,'2026-06-10 23:41:47','2026-06-07 23:41:47','2026-06-07 23:41:50'),(19,'fdcf93173be21e3e7527be0b','payment_action','8779270308','{\"payment_id\":12,\"decision\":\"approve\",\"message_id\":142}',1,NULL,'2026-06-10 23:41:50','2026-06-07 23:41:50','2026-06-07 23:41:53'),(20,'430e6527fce3a969eb1dbd10','payment_action','8779270308','{\"payment_id\":12,\"decision\":\"reject\",\"message_id\":142}',1,NULL,'2026-06-10 23:41:50','2026-06-07 23:41:50','2026-06-07 23:41:53'),(21,'0fd601edb69e4146a8ad8dc8','payment_action','1553203064','{\"payment_id\":13,\"decision\":\"approve\",\"message_id\":145}',1,NULL,'2026-06-11 13:05:56','2026-06-08 13:05:56','2026-06-08 13:05:59'),(22,'dfb6618bb38c0a23e241f155','payment_action','1553203064','{\"payment_id\":13,\"decision\":\"reject\",\"message_id\":145}',1,NULL,'2026-06-11 13:05:56','2026-06-08 13:05:56','2026-06-08 13:05:59'),(23,'3809cfa2030603ee76572ead','payment_action','8779270308','{\"payment_id\":13,\"decision\":\"approve\",\"message_id\":146}',1,NULL,'2026-06-11 13:05:59','2026-06-08 13:05:59','2026-06-08 13:06:02'),(24,'2bac12e431f6ed5da324d35d','payment_action','8779270308','{\"payment_id\":13,\"decision\":\"reject\",\"message_id\":146}',1,NULL,'2026-06-11 13:05:59','2026-06-08 13:05:59','2026-06-08 13:06:02'),(25,'5e48a5a2058917ac85359903','payment_action','1553203064','{\"payment_id\":14,\"decision\":\"approve\",\"message_id\":151}',1,NULL,'2026-06-11 20:58:00','2026-06-08 20:58:00','2026-06-08 20:58:04'),(26,'409adaff976ab14f22a139c5','payment_action','1553203064','{\"payment_id\":14,\"decision\":\"reject\",\"message_id\":151}',1,NULL,'2026-06-11 20:58:00','2026-06-08 20:58:00','2026-06-08 20:58:04'),(27,'5bf703c553bb21f0d280dedb','payment_action','8779270308','{\"payment_id\":14,\"decision\":\"approve\",\"message_id\":152}',1,NULL,'2026-06-11 20:58:04','2026-06-08 20:58:04','2026-06-08 20:58:08'),(28,'c3c8c7e7388182575acfb160','payment_action','8779270308','{\"payment_id\":14,\"decision\":\"reject\",\"message_id\":152}',1,NULL,'2026-06-11 20:58:04','2026-06-08 20:58:04','2026-06-08 20:58:08'),(29,'95a101e5d10da9e691bfbc45','payment_action','1553203064','{\"payment_id\":15,\"decision\":\"approve\",\"message_id\":157}',1,NULL,'2026-06-16 08:47:17','2026-06-13 08:47:17','2026-06-13 08:47:25'),(30,'c29e3229578e59afb3d14642','payment_action','1553203064','{\"payment_id\":15,\"decision\":\"reject\",\"message_id\":157}',1,NULL,'2026-06-16 08:47:17','2026-06-13 08:47:17','2026-06-13 08:47:25'),(31,'7346d16002081206a189d36b','payment_action','8779270308','{\"payment_id\":15,\"decision\":\"approve\",\"message_id\":158}',1,NULL,'2026-06-16 08:47:25','2026-06-13 08:47:25','2026-06-13 08:47:30'),(32,'388fd75d5bdabdc2dcea0b7f','payment_action','8779270308','{\"payment_id\":15,\"decision\":\"reject\",\"message_id\":158}',1,NULL,'2026-06-16 08:47:25','2026-06-13 08:47:25','2026-06-13 08:47:30'),(33,'e660a3a60ab2548fa1c15cdb','payment_action','1553203064','{\"payment_id\":16,\"decision\":\"approve\",\"message_id\":0}',1,NULL,'2026-07-11 09:01:47','2026-07-08 09:01:47','2026-07-08 09:01:47'),(34,'dc1acfbe2d452ef605106518','payment_action','1553203064','{\"payment_id\":16,\"decision\":\"reject\",\"message_id\":0}',1,NULL,'2026-07-11 09:01:47','2026-07-08 09:01:47','2026-07-08 09:01:47'),(35,'7fe467c2b459687549cbbcdc','payment_action','8779270308','{\"payment_id\":16,\"decision\":\"approve\",\"message_id\":0}',1,NULL,'2026-07-11 09:01:49','2026-07-08 09:01:49','2026-07-08 09:01:49'),(36,'dabe5057317f15a41aa216de','payment_action','8779270308','{\"payment_id\":16,\"decision\":\"reject\",\"message_id\":0}',1,NULL,'2026-07-11 09:01:49','2026-07-08 09:01:49','2026-07-08 09:01:49');
/*!40000 ALTER TABLE `telegram_action_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin@dixgamefarm.com','$2y$10$aME9uZ6/DaHhPDf0mNu9f.exfUpHLEJIZeZjA29Em.B.MavnVhx16','I Gusti Ngurah Made Wijaya','ADMIN','081234567890','DIX Game Farm, Br. Anyar, Tabanan, Bali','2026-01-15 08:00:00','2026-01-15 08:00:00'),(2,'ketut.suarjana@gmail.com','$2y$10$d.Ju40jLWFfuO2bpkKxpuuLW8tlyF80NgXc6aevj9ggAWhvI9D2N6','I Ketut Suarjana','PELANGGAN','081987654321','Jl. Hayam Wuruk No. 45, Denpasar Selatan, Bali','2026-02-01 10:30:00','2026-02-01 10:30:00'),(3,'putu.ayu@gmail.com','$2y$10$kDv2NvO6yQiU/xxrlJPIWeVm8n/6GGXMQg8TZoVYbjpUXYMgdmqQG','Ni Putu Ayu Lestari','PELANGGAN','082145678901','Jl. Raya Ubud No. 12, Gianyar, Bali','2026-02-10 14:15:00','2026-02-10 14:15:00'),(4,'wayan.dharma@yahoo.com','$2y$10$2mybTVtW7.YsNZ8k9ulbaua3tNMmZ95WSKTC4yPf0V13phIAiVgKK','I Wayan Dharma Putra','PELANGGAN','085678901234','Br. Kaja, Desa Mengwi, Badung, Bali','2026-02-20 09:00:00','2026-02-20 09:00:00'),(5,'made.sri@gmail.com','$2y$10$pqHQfCAQxduN6dkT5kxXRe6ZMAvs.hKisWPDVNkYwfEn.tkU7YH2K','Ni Made Sri Wahyuni','PELANGGAN','087890123456','Jl. Gatot Subroto No. 88, Denpasar Barat, Bali','2026-03-01 11:45:00','2026-03-01 11:45:00'),(6,'komang.ari@gmail.com','$2y$10$gbnphYAjn1tIbbWVYVLGp.R4UBjUfNj9BeTaddu0uT5lrM/lW8vdi','I Komang Ari Sudana','PELANGGAN','089012345678','Jl. Pulau Moyo No. 5, Singaraja, Buleleng, Bali','2026-03-10 16:20:00','2026-03-10 16:20:00'),(32,'wahinaaa123@gmail.com','$2y$10$.ekT1eG7pTGtRoj4SKRoNeTH7gCn0K7GypLLRFUPoiqREzRnDrMc6','wahina jelek','PELANGGAN','085792717468','1233lolokgede','2026-06-04 18:11:04','2026-06-04 18:11:04'),(33,'putrakrisna232@gmail.com','$2y$10$wjto5giFWr6I5i1L4iWUZeP7R3LCjDjakm6S4wBqmP8sHtPTLvhV2','putra krisna','PELANGGAN','085792717468','Jl. Gadung No 10, Denpasar Timur','2026-06-06 08:27:37','2026-06-06 08:27:37');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-08 17:38:15
