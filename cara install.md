# Cara Install DIX Game Farm

Dokumen ini berisi langkah instalasi aplikasi **DIX Game Farm** berbasis CodeIgniter 4 di komputer lokal.

## 1. Kebutuhan Sistem

Pastikan komputer sudah memiliki:

- PHP 8.2 atau lebih baru.
- Composer.
- MySQL atau MariaDB.
- Web browser.
- Terminal atau Command Prompt.

Jika memakai XAMPP, pastikan service **Apache** dan **MySQL** dapat dijalankan.

## 2. Masuk ke Folder Project

Buka terminal, lalu masuk ke folder project:

```bat
cd dxfarmgemini
```

Jika project berada di folder lain, sesuaikan lokasi foldernya.

## 3. Install Dependency PHP

Jalankan perintah berikut:

```bat
composer install
```

Perintah ini akan mengunduh dependency CodeIgniter dan library yang dibutuhkan project.

## 4. Konfigurasi File .env

Pastikan file `.env` sudah tersedia di folder `dxfarmgemini`.

Konfigurasi utama yang perlu dicek:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'
app.indexPage = ''

database.default.hostname = localhost
database.default.database = dixgamefarm_db
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306

app.timezone = 'Asia/Makassar'
```

Jika password MySQL di komputer Anda tidak kosong, isi bagian berikut:

```ini
database.default.password = password_mysql_anda
```

## 5. Buat Database

Buka phpMyAdmin atau MySQL client, lalu buat database baru bernama:

```sql
dixgamefarm_db
```

Contoh jika memakai MySQL command line:

```sql
CREATE DATABASE dixgamefarm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

## 6. Import Database

Import file SQL berikut ke database `dixgamefarm_db`:

```text
dxfarmgemini/dixgamefarm_db.sql
```

### Opsi A: Import lewat phpMyAdmin

1. Buka phpMyAdmin.
2. Pilih database `dixgamefarm_db`.
3. Klik tab **Import**.
4. Pilih file `dxfarmgemini/dixgamefarm_db.sql`.
5. Klik **Go** atau **Kirim**.

### Opsi B: Import lewat Command Prompt

Jalankan dari folder workspace:

```bat
mysql -u root dixgamefarm_db < dxfarmgemini\dixgamefarm_db.sql
```

Jika MySQL memakai password:

```bat
mysql -u root -p dixgamefarm_db < dxfarmgemini\dixgamefarm_db.sql
```

## 7. Pastikan Folder Upload Tersedia

Pastikan folder berikut ada:

```text
dxfarmgemini/public/uploads/produk
```

Folder tersebut dipakai untuk menyimpan dan menampilkan foto produk.

Jika folder belum ada, buat foldernya:

```bat
mkdir public\uploads\produk
```

## 8. Jalankan Aplikasi

Masuk ke folder `dxfarmgemini`, lalu jalankan server lokal CodeIgniter:

```bat
php spark serve
```

Jika berhasil, aplikasi bisa dibuka melalui browser:

```text
http://localhost:8080/
```

## 9. Akun Login Default

Gunakan akun berikut untuk masuk ke sistem:

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

## 10. Alternatif: Reset Database dengan Seeder

Jika ingin membuat ulang tabel dan data dummy lewat migration dan seeder, jalankan:

```bat
php spark migrate
php spark db:seed DatabaseSeeder
```

Catatan: gunakan cara ini hanya jika struktur database sudah kosong atau Anda memang ingin mengisi ulang data dummy.

## 11. Troubleshooting

### Composer tidak ditemukan

Install Composer terlebih dahulu, lalu ulangi:

```bat
composer install
```

### PHP versi terlalu rendah

Project membutuhkan PHP 8.2 atau lebih baru untuk menjalankan `php spark`.

Cek versi PHP:

```bat
php -v
```

### Database gagal terkoneksi

Cek kembali konfigurasi database di `.env`:

```ini
database.default.hostname = localhost
database.default.database = dixgamefarm_db
database.default.username = root
database.default.password =
database.default.port = 3306
```

Pastikan MySQL sudah berjalan dan database `dixgamefarm_db` sudah dibuat/import.

### Gambar produk tidak muncul

Pastikan file gambar ada di:

```text
dxfarmgemini/public/uploads/produk
```

Pastikan juga path gambar di database memakai format:

```text
uploads/produk/nama_file_gambar.png
```

## 12. Ringkasan Perintah Cepat

```bat
cd dxfarmgemini
composer install
php spark serve
```

Buka browser:

```text
http://localhost:8080/
```
