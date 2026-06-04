@echo off
REM ============================================================
REM  DIX Game Farm - Windows Auto-Installer
REM
REM  Yang dilakukan script ini (urut):
REM    1. Cek prerequisites (php, composer, mysql)
REM    2. composer install
REM    3. Buat database dixgamefarm_db kalau belum ada
REM    4. Jalankan php spark migrate (buat semua tabel)
REM    5. Jalankan php spark db:seed DatabaseSeeder
REM
REM  CATATAN: script ini TIDAK menyalin .env. File .env sudah
REM  ikut tracked di repository untuk handover localhost.
REM  Kalau .env hilang, copy manual dari .env.example.
REM
REM  Cara pakai: klik 2x install.bat (atau jalankan dari cmd)
REM ============================================================

setlocal enabledelayedexpansion
cd /d "%~dp0"

echo.
echo =====================================================
echo   DIX Game Farm - Auto Installer
echo =====================================================
echo.

REM ----- Step 1: Prerequisites -----
echo [1/5] Cek prerequisites...

where php >nul 2>nul
if errorlevel 1 (
    echo   [ERROR] PHP tidak ditemukan di PATH.
    echo           Install XAMPP / pasang PHP ke PATH dulu.
    pause
    exit /b 1
)
for /f "tokens=2" %%v in ('php -r "echo PHP_VERSION;" 2^>nul') do set PHPVER=%%v
echo   [OK] PHP terdeteksi.

where composer >nul 2>nul
if errorlevel 1 (
    echo   [ERROR] Composer tidak ditemukan di PATH.
    echo           Download dari https://getcomposer.org
    pause
    exit /b 1
)
echo   [OK] Composer terdeteksi.

where mysql >nul 2>nul
if errorlevel 1 (
    echo   [WARN] mysql client tidak ada di PATH.
    echo          XAMPP biasanya menaruhnya di C:\xampp\mysql\bin
    echo          Tambahin folder itu ke PATH atau buat database manual via phpMyAdmin.
    set "MYSQL_AVAILABLE=0"
) else (
    set "MYSQL_AVAILABLE=1"
    echo   [OK] mysql client terdeteksi.
)
echo.

REM ----- Step 2: Cek .env -----
echo [2/5] Cek file .env...
if not exist ".env" (
    if exist ".env.example" (
        echo   [WARN] .env tidak ada — meng-copy dari .env.example.
        copy /Y ".env.example" ".env" >nul
        echo   [INFO] .env dibuat dari template. EDIT FILE INI dulu sebelum lanjut
        echo          isi: database.default.password, TELEGRAM_*, email.SMTP*.
        echo.
        pause
    ) else (
        echo   [ERROR] .env DAN .env.example dua-duanya tidak ada.
        echo           Pastikan kamu clone repo lengkap.
        pause
        exit /b 1
    )
) else (
    echo   [OK] .env sudah ada — tidak ditimpa.
)
echo.

REM ----- Step 3: composer install -----
echo [3/5] composer install...
call composer install --no-interaction
if errorlevel 1 (
    echo   [ERROR] composer install gagal.
    pause
    exit /b 1
)
echo   [OK] Dependencies terpasang.
echo.

REM ----- Step 4: Buat database -----
echo [4/5] Pastikan database dixgamefarm_db ada...
if "%MYSQL_AVAILABLE%"=="1" (
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS dixgamefarm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci" 2>nul
    if errorlevel 1 (
        echo   [WARN] Gagal koneksi mysql tanpa password.
        echo          Kalau MySQL kamu pakai password, buat database manual:
        echo          CREATE DATABASE dixgamefarm_db CHARACTER SET utf8mb4;
    ) else (
        echo   [OK] Database dixgamefarm_db ready.
    )
) else (
    echo   [SKIP] mysql client tidak ada — skip auto-create database.
    echo          Pastikan database 'dixgamefarm_db' sudah ada via phpMyAdmin.
)
echo.

REM ----- Step 5: Migrate + seed -----
echo [5/5] Migrate ^& seed database...
echo   ^> php spark migrate
call php spark migrate
if errorlevel 1 (
    echo   [ERROR] Migrasi gagal. Cek koneksi DB di .env dan ulangi.
    pause
    exit /b 1
)

echo   ^> php spark db:seed DatabaseSeeder
call php spark db:seed DatabaseSeeder
if errorlevel 1 (
    echo   [ERROR] Seeder gagal. Coba jalankan ulang setelah baca error di atas.
    pause
    exit /b 1
)

echo.
echo =====================================================
echo   INSTALL SELESAI
echo =====================================================
echo.
echo   Akun default:
echo     Admin    : admin@dixgamefarm.com / admin123
echo     Customer : ketut.suarjana@gmail.com / password123
echo.
echo   Jalankan aplikasi dengan:
echo     php spark serve --port 8080
echo.
echo   Lalu buka http://localhost:8080
echo.
pause
endlocal
