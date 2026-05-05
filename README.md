# DIX Game Farm

Sistem informasi pemesanan dan manajemen stok ayam berbasis web untuk DIX Game Farm. Aplikasi ini dibuat menggunakan CodeIgniter 4 dan MySQL.

## Fitur Utama

- Katalog produk ayam, DOC, telur, pakan, dan vitamin.
- Registrasi dan login pelanggan.
- Keranjang belanja dan checkout.
- Upload bukti pembayaran.
- Verifikasi pembayaran oleh admin.
- Manajemen produk, kategori, pesanan, stok, dan laporan.
- Notifikasi admin melalui Telegram.
- Pembatalan otomatis untuk pesanan yang melewati batas pembayaran.

## Kebutuhan Sistem

- PHP 8.2 atau lebih baru.
- Composer.
- MySQL atau MariaDB.
- XAMPP, Laragon, atau server lokal sejenis.
- Browser modern.

## Instalasi Lokal

### 1. Clone Repository

```bash
git clone https://github.com/ryuken25/dixgamefarm.git
cd dixgamefarm
```

### 2. Install Dependency

```bash
composer install
```

### 3. Konfigurasi Environment

File `.env` sengaja disertakan di repository karena project ini disiapkan untuk localhost.

Konfigurasi default:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = dixgamefarm_db
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

Jika MySQL memakai password, ubah bagian ini:

```ini
database.default.password = password_mysql_anda
```

### 4. Buat Database

Buat database baru bernama:

```sql
dixgamefarm_db
```

Contoh lewat MySQL CLI:

```sql
CREATE DATABASE dixgamefarm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 5. Import Database

Import file SQL berikut:

```text
dixgamefarm_db.sql
```

Lewat Command Prompt:

```bash
mysql -u root dixgamefarm_db < dixgamefarm_db.sql
```

Jika memakai password:

```bash
mysql -u root -p dixgamefarm_db < dixgamefarm_db.sql
```

Atau import melalui phpMyAdmin:

1. Buka `http://localhost/phpmyadmin`.
2. Pilih database `dixgamefarm_db`.
3. Klik tab Import.
4. Pilih file `dixgamefarm_db.sql`.
5. Klik Go/Kirim.

### 6. Jalankan Aplikasi

```bash
php spark serve
```

Buka browser:

```text
http://localhost:8080/
```

## Akun Default

### Admin

```text
Email    : admin@dixgamefarm.com
Password : admin123
```

### Pelanggan

```text
Email    : ketut.suarjana@gmail.com
Password : password123
```

```text
Email    : putu.ayu@gmail.com
Password : password123
```

```text
Email    : wayan.dharma@yahoo.com
Password : password123
```

```text
Email    : made.sri@gmail.com
Password : password123
```

```text
Email    : komang.ari@gmail.com
Password : password123
```

## Data Produk Utama

- Boston Roundhead: Rp 3.000.000.
- Moonwalker Grey: Rp 4.000.000.
- Popeye Grey: Rp 5.000.000.
- DOC ayam: Rp 1.300.000 per sarang, isi 8 ekor lengkap vaksin.

## Alternatif Isi Database dengan Migration dan Seeder

Jika ingin membangun database dari migration dan seeder:

```bash
php spark migrate
php spark db:seed DatabaseSeeder
```

Tetap disarankan memakai import `dixgamefarm_db.sql` agar data sama dengan paket project.

## Struktur Folder Penting

```text
app/                         Kode aplikasi CodeIgniter
public/                      Entry point, asset, dan upload produk
public/uploads/produk/       Foto produk
writable/                    Cache, log, session, dan file runtime
dixgamefarm_db.sql           File database siap import
cara install.md              Panduan install tambahan
.env                         Konfigurasi lokalhost
```

## Catatan Git

Repository hanya berisi file inti program. File laporan, dokumen, diagram, screenshot, cache, log, vendor, dan arsip zip dikecualikan melalui `.gitignore`.

Setelah clone, dependency CodeIgniter diinstall ulang memakai:

```bash
composer install
```
