@echo off
REM ============================================================
REM  DIX Game Farm - Smart Auto-Installer (Windows)
REM
REM  Workflow:
REM    1. Cek prerequisites (PHP, Composer; MySQL opsional)
REM    2. Backup .env -> git pull -> restore .env (kalau ada arg --pull)
REM    3. Cek / generate .env dari .env.example
REM    4. composer install (+ optional npm install)
REM    5. Auto-create database 'dixgamefarm_db'
REM    6. Verifikasi koneksi DB
REM    7. php spark migrate + php spark db:seed
REM
REM  Pakai:
REM    setup-windows.bat            -> install/repair lokal (TANPA git pull)
REM    setup-windows.bat --pull     -> backup .env, git pull, restore .env, install
REM    setup-windows.bat --reset    -> drop & recreate database (DANGER, minta konfirmasi)
REM ============================================================

setlocal enabledelayedexpansion
cd /d "%~dp0"

set "MODE_PULL=0"
set "MODE_RESET=0"
for %%a in (%*) do (
    if /i "%%a"=="--pull"  set "MODE_PULL=1"
    if /i "%%a"=="--reset" set "MODE_RESET=1"
)

echo.
echo =====================================================
echo   DIX Game Farm - Auto Installer
echo =====================================================
echo.

if not exist "spark" (
    echo [ERROR] File 'spark' tidak ditemukan. Jalankan setup-windows.bat dari root project CI4.
    pause & exit /b 1
)

REM ============================================================
REM Step 1: Prerequisites
REM ============================================================
echo [1/7] Cek prerequisites...

where php >nul 2>nul
if errorlevel 1 (
    echo   [ERROR] PHP tidak ada di PATH. Install XAMPP / pasang PHP ke PATH.
    pause & exit /b 1
)
echo   [OK]   PHP terdeteksi.

where composer >nul 2>nul
if errorlevel 1 (
    echo   [ERROR] Composer tidak ada di PATH. https://getcomposer.org
    pause & exit /b 1
)
echo   [OK]   Composer terdeteksi.

REM mysql client probe
set "MYSQL_BIN="
where mysql >nul 2>nul
if not errorlevel 1 (
    set "MYSQL_BIN=mysql"
) else (
    if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe"
)
if defined MYSQL_BIN (
    echo   [OK]   mysql client: !MYSQL_BIN!
) else (
    echo   [WARN] mysql client tidak ditemukan. Auto-create DB akan di-skip.
)

REM node optional (kalau ada playwright tests)
where node >nul 2>nul
if not errorlevel 1 (
    echo   [OK]   Node.js terdeteksi (untuk Playwright tests, opsional).
    set "NODE_AVAILABLE=1"
) else (
    set "NODE_AVAILABLE=0"
)
echo.

REM ============================================================
REM Step 2: Git pull (opsional, dengan .env preservation)
REM ============================================================
if "%MODE_PULL%"=="1" (
    echo [2/7] Git pull dengan .env preservation...
    where git >nul 2>nul
    if errorlevel 1 (
        echo   [ERROR] git tidak ada di PATH.
        pause & exit /b 1
    )

    if exist ".env" (
        echo   ^> Backup .env -^> .env.bak
        copy /Y ".env" ".env.bak" >nul
        if errorlevel 1 (
            echo   [ERROR] Gagal backup .env.
            pause & exit /b 1
        )

        REM Kalau .env tracked di git, reset ke HEAD biar pull ngga conflict
        git ls-files --error-unmatch .env >nul 2>nul
        if not errorlevel 1 (
            echo   ^> .env tracked di git, reset ke HEAD supaya pull bersih
            git checkout HEAD -- .env >nul 2>nul
        )
    )

    echo   ^> git pull
    git pull
    if errorlevel 1 (
        echo   [WARN] git pull gagal. Cek koneksi / merge conflict.
        if exist ".env.bak" (
            echo   ^> Restore .env dari backup
            move /Y ".env.bak" ".env" >nul
        )
        pause & exit /b 1
    )

    if exist ".env.bak" (
        echo   ^> Restore .env dari backup
        move /Y ".env.bak" ".env" >nul
        echo   [OK]   .env asli kamu sudah dikembalikan.
    )
    echo.
) else (
    echo [2/7] Skip git pull ^(jalankan: setup-windows.bat --pull untuk pull dari origin^)
    echo.
)

REM ============================================================
REM Step 3: Cek / generate .env
REM ============================================================
echo [3/7] Cek file .env...
if exist ".env" (
    echo   [OK]   .env sudah ada. Tidak ditimpa.
) else (
    if not exist ".env.example" (
        echo   [ERROR] .env DAN .env.example tidak ada. Re-clone repo.
        pause & exit /b 1
    )
    echo   [INFO] .env tidak ada. Copy dari .env.example...
    copy /Y ".env.example" ".env" >nul
    if errorlevel 1 (
        echo   [ERROR] Gagal copy .env.example -^> .env.
        pause & exit /b 1
    )
    echo   [OK]   .env dibuat dari template.
    echo.
    echo   ============================================================
    echo   PENTING: edit .env dulu sebelum lanjut!
    echo     - database.default.password           : password MySQL kamu
    echo     - TELEGRAM_BOT_TOKEN / chat IDs       : notif Telegram admin
    echo     - email.SMTPUser / SMTPPass / fromEmail : Gmail App Password
    echo     - WHATSAPP_ADMIN_NUMBER               : nomor WA admin (62xxxx)
    echo   ============================================================
    echo.
    set /p CONFIRM_ENV=Sudah edit .env? (y=lanjut, lainnya=batal):
    if /i not "!CONFIRM_ENV!"=="y" (
        echo   [INFO] Install dibatalkan. Edit .env lalu jalankan ulang.
        pause & exit /b 0
    )
)
echo.

REM ============================================================
REM Step 4: composer install
REM ============================================================
echo [4/7] composer install...
call composer install --no-interaction --no-progress
if errorlevel 1 (
    echo   [ERROR] composer install gagal.
    pause & exit /b 1
)
echo   [OK]   Dependencies PHP terpasang.

if "%NODE_AVAILABLE%"=="1" (
    if exist "package.json" (
        echo   ^> Detected package.json -^> npm install
        call npm install --no-audit --no-fund
        if errorlevel 1 (
            echo   [WARN] npm install gagal. Lanjut tanpa node modules ^(skip Playwright tests^).
        ) else (
            echo   [OK]   Dependencies Node terpasang.
        )
    )
)
echo.

REM ============================================================
REM Step 5: Parse DB password dari .env, lalu CREATE DATABASE
REM ============================================================
echo [5/7] Pastikan database 'dixgamefarm_db' ada...

set "DB_PASS="
for /f "usebackq tokens=1,* delims==" %%a in ("%~dp0.env") do (
    set "_KEY=%%a"
    set "_VAL=%%b"
    for /f "tokens=* delims= " %%k in ("!_KEY!") do set "_KEY=%%k"
    if /i "!_KEY:~0,1!"=="#" (
        rem comment, skip
    ) else if /i "!_KEY!"=="database.default.password" (
        set "DB_PASS=!_VAL!"
    ) else if /i "!_KEY!"=="database.default.password " (
        set "DB_PASS=!_VAL!"
    )
)
if defined DB_PASS (
    for /f "tokens=* delims= " %%v in ("!DB_PASS!") do set "DB_PASS=%%v"
)

if defined MYSQL_BIN (
    if "%MODE_RESET%"=="1" (
        echo   [WARN] --reset mode: database akan DROP ^& di-recreate.
        set /p CONFIRM_RESET=Yakin drop dixgamefarm_db? Ketik 'YES' untuk lanjut:
        if /i "!CONFIRM_RESET!"=="YES" (
            if defined DB_PASS (
                "!MYSQL_BIN!" -u root --password=!DB_PASS! -e "DROP DATABASE IF EXISTS dixgamefarm_db" 2>nul
            ) else (
                "!MYSQL_BIN!" -u root -e "DROP DATABASE IF EXISTS dixgamefarm_db" 2>nul
            )
            echo   [OK]   Database lama di-drop.
        ) else (
            echo   [INFO] Reset dibatalkan, lanjut tanpa drop.
        )
    )

    if defined DB_PASS (
        "!MYSQL_BIN!" -u root --password=!DB_PASS! -e "CREATE DATABASE IF NOT EXISTS dixgamefarm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci" 2>nul
    ) else (
        "!MYSQL_BIN!" -u root -e "CREATE DATABASE IF NOT EXISTS dixgamefarm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci" 2>nul
    )
    if errorlevel 1 (
        echo   [WARN] Gagal koneksi MySQL pakai user 'root'.
        echo          Cek database.default.username/password di .env atau MySQL service.
        echo          Buat database manual via phpMyAdmin kalau perlu:
        echo            CREATE DATABASE dixgamefarm_db CHARACTER SET utf8mb4;
    ) else (
        echo   [OK]   Database 'dixgamefarm_db' ready.
    )
) else (
    echo   [SKIP] mysql client tidak ada. Pastikan DB sudah ada via phpMyAdmin.
)
echo.

REM ============================================================
REM Step 6: Verifikasi koneksi DB via spark
REM ============================================================
echo [6/7] Verifikasi koneksi DB...
php spark migrate:status >nul 2>nul
if errorlevel 1 (
    echo   [ERROR] CodeIgniter tidak bisa connect ke database.
    echo           Cek di .env: hostname/database/username/password.
    echo           Pastikan MySQL/MariaDB jalan (XAMPP Control Panel).
    pause & exit /b 1
)
echo   [OK]   Koneksi DB sukses.
echo.

REM ============================================================
REM Step 7: Migrate + seed
REM ============================================================
echo [7/7] Migrate ^& seed database...
echo   ^> php spark migrate
php spark migrate
if errorlevel 1 (
    echo   [ERROR] Migrasi gagal. Baca error di atas, fix, ulangi.
    pause & exit /b 1
)
echo.
echo   ^> php spark db:seed DatabaseSeeder
php spark db:seed DatabaseSeeder
if errorlevel 1 (
    echo   [ERROR] Seeder gagal. Baca error di atas, ulangi.
    pause & exit /b 1
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
echo   Cron / scheduled task (opsional):
echo     php spark orders:cleanup-expired   ^(tiap 10-15 menit^)
echo     php spark orders:remind-unshipped  ^(tiap 60 menit^)
echo.
echo   Buka:  http://localhost:8080
echo.
pause
endlocal
