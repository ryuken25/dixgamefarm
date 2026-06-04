@echo off
REM ============================================================
REM  DIX Game Farm - Windows Auto-Installer
REM
REM  Steps:
REM    1. Cek prerequisites (php, composer; mysql opsional)
REM    2. Cek / buat .env dari .env.example (minta konfirmasi sebelum lanjut)
REM    3. composer install
REM    4. Pastikan database dixgamefarm_db ada
REM    5. Cek koneksi DB via spark
REM    6. php spark migrate + php spark db:seed DatabaseSeeder
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

REM ----- Sanity: pastikan kita di folder project -----
if not exist "spark" (
    echo [ERROR] File 'spark' tidak ditemukan di folder ini.
    echo         Pastikan install.bat dijalankan dari root project CodeIgniter.
    pause
    exit /b 1
)

REM ============================================================
REM Step 1: Prerequisites
REM ============================================================
echo [1/6] Cek prerequisites...

where php >nul 2>nul
if errorlevel 1 (
    echo   [ERROR] PHP tidak ditemukan di PATH.
    echo           Install XAMPP atau pasang PHP ke PATH dulu.
    pause
    exit /b 1
)
echo   [OK]   PHP terdeteksi.

where composer >nul 2>nul
if errorlevel 1 (
    echo   [ERROR] Composer tidak ditemukan di PATH.
    echo           Download dari https://getcomposer.org
    pause
    exit /b 1
)
echo   [OK]   Composer terdeteksi.

REM mysql client: cek PATH dulu, fallback ke XAMPP default
set "MYSQL_BIN="
where mysql >nul 2>nul
if not errorlevel 1 (
    set "MYSQL_BIN=mysql"
) else (
    if exist "C:\xampp\mysql\bin\mysql.exe" (
        set "MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe"
    )
)
if defined MYSQL_BIN (
    echo   [OK]   mysql client: !MYSQL_BIN!
) else (
    echo   [WARN] mysql client tidak ada di PATH maupun C:\xampp\mysql\bin
    echo          Auto-create database akan di-skip. Buat manual via phpMyAdmin.
)
echo.

REM ============================================================
REM Step 2: Cek / buat .env
REM ============================================================
echo [2/6] Cek file .env...
if exist ".env" (
    echo   [OK]   .env sudah ada. Tidak ditimpa.
) else (
    if not exist ".env.example" (
        echo   [ERROR] .env DAN .env.example dua-duanya tidak ada.
        echo           Pastikan kamu clone repo lengkap.
        pause
        exit /b 1
    )
    echo   [INFO] .env tidak ada. Meng-copy dari .env.example...
    copy /Y ".env.example" ".env" >nul
    if errorlevel 1 (
        echo   [ERROR] Gagal copy .env.example -^> .env.
        pause
        exit /b 1
    )
    echo   [OK]   .env dibuat dari template.
    echo.
    echo   ============================================================
    echo   PENTING: edit .env dulu sebelum lanjut !!
    echo     - database.default.password : password MySQL kamu
    echo     - TELEGRAM_BOT_TOKEN ^/ chat IDs : kalau pakai notif Telegram
    echo     - email.SMTPUser ^/ SMTPPass ^/ fromEmail : Gmail App Password
    echo   ============================================================
    echo.
    set /p CONFIRM_ENV=Sudah edit .env ? Ketik 'y' lalu Enter untuk lanjut (n=batal):
    if /i not "!CONFIRM_ENV!"=="y" (
        echo   [INFO] Install dibatalkan. Edit .env lalu jalankan install.bat lagi.
        pause
        exit /b 0
    )
)
echo.

REM ============================================================
REM Step 3: composer install
REM ============================================================
echo [3/6] composer install...
call composer install --no-interaction
if errorlevel 1 (
    echo   [ERROR] composer install gagal.
    pause
    exit /b 1
)
echo   [OK]   Dependencies terpasang.
echo.

REM ============================================================
REM Step 4: Auto-create database (opsional, kalau mysql client ada)
REM ============================================================
echo [4/6] Pastikan database 'dixgamefarm_db' ada...

REM Parse password DB dari .env (best-effort) — match baris yang dimulai
REM dengan 'database.default.password' (skip yang comment).
set "DB_PASS="
for /f "usebackq tokens=1,* delims==" %%a in ("%~dp0.env") do (
    set "_KEY=%%a"
    set "_VAL=%%b"
    REM trim spasi di awal/akhir key
    for /f "tokens=* delims= " %%k in ("!_KEY!") do set "_KEY=%%k"
    if /i "!_KEY:~0,1!"=="#" (
        rem comment line, skip
    ) else if /i "!_KEY!"=="database.default.password " (
        set "DB_PASS=!_VAL!"
    ) else if /i "!_KEY!"=="database.default.password" (
        set "DB_PASS=!_VAL!"
    )
)
REM trim leading space dari value
if defined DB_PASS (
    for /f "tokens=* delims= " %%v in ("!DB_PASS!") do set "DB_PASS=%%v"
)

if defined MYSQL_BIN (
    if defined DB_PASS (
        "!MYSQL_BIN!" -u root --password=!DB_PASS! -e "CREATE DATABASE IF NOT EXISTS dixgamefarm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci" 2>nul
    ) else (
        "!MYSQL_BIN!" -u root -e "CREATE DATABASE IF NOT EXISTS dixgamefarm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci" 2>nul
    )
    if errorlevel 1 (
        echo   [WARN] Gagal connect MySQL pakai user 'root'.
        echo          Cek database.default.username/password di .env.
        echo          Atau buat database manual via phpMyAdmin:
        echo            CREATE DATABASE dixgamefarm_db CHARACTER SET utf8mb4;
    ) else (
        echo   [OK]   Database 'dixgamefarm_db' ready.
    )
) else (
    echo   [SKIP] mysql client tidak ada. Skip auto-create.
    echo          Pastikan 'dixgamefarm_db' sudah ada via phpMyAdmin.
)
echo.

REM ============================================================
REM Step 5: Test koneksi DB via spark
REM ============================================================
echo [5/6] Test koneksi DB...
php spark migrate:status >nul 2>nul
if errorlevel 1 (
    echo   [ERROR] CodeIgniter tidak bisa connect ke database.
    echo           Periksa di .env:
    echo             database.default.hostname
    echo             database.default.database
    echo             database.default.username
    echo             database.default.password
    echo           Pastikan juga MySQL/MariaDB udah jalan (XAMPP Control Panel).
    pause
    exit /b 1
)
echo   [OK]   Koneksi DB sukses.
echo.

REM ============================================================
REM Step 6: Migrate + seed
REM ============================================================
echo [6/6] Migrate ^& seed database...
echo   ^> php spark migrate
php spark migrate
if errorlevel 1 (
    echo   [ERROR] Migrasi gagal. Baca pesan error di atas, fix, lalu ulangi install.bat.
    pause
    exit /b 1
)

echo.
echo   ^> php spark db:seed DatabaseSeeder
php spark db:seed DatabaseSeeder
if errorlevel 1 (
    echo   [ERROR] Seeder gagal. Baca pesan error di atas dan ulangi.
    pause
    exit /b 1
)

echo.
echo =====================================================
echo   INSTALL SELESAI
echo =====================================================
echo.
echo   Akun default:
echo     Admin    : admin@dixgamefarm.com   / admin123
echo     Customer : ketut.suarjana@gmail.com / password123
echo.
echo   Jalankan aplikasi:
echo     php spark serve --port 8080
echo.
echo   Lalu buka:
echo     http://localhost:8080
echo.
pause
endlocal
