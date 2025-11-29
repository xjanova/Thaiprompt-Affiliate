@echo off
chcp 65001 >nul
title Thaiprompt Mobile App - Clone Script

REM ============================================================================
REM Thaiprompt Affiliate - Mobile App Clone Script for Windows
REM ============================================================================
REM สคริปต์นี้ใช้สำหรับดึงเฉพาะส่วนแอพมือถือมาพัฒนาบน Windows
REM
REM ความต้องการ:
REM   - Git for Windows
REM   - .NET 8.0 SDK
REM   - Visual Studio 2022 (พร้อม MAUI workload)
REM ============================================================================

echo.
echo ============================================================================
echo   🚀 Thaiprompt Affiliate - Mobile App Clone Script
echo ============================================================================
echo.

REM ตรวจสอบว่ามี Git หรือไม่
where git >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ❌ ไม่พบ Git กรุณาติดตั้ง Git for Windows ก่อน
    echo    ดาวน์โหลดได้ที่: https://git-scm.com/download/win
    echo.
    pause
    exit /b 1
)
echo ✅ พบ Git

REM ตรวจสอบว่ามี dotnet หรือไม่
where dotnet >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ❌ ไม่พบ .NET SDK กรุณาติดตั้ง .NET 8.0 SDK ก่อน
    echo    ดาวน์โหลดได้ที่: https://dotnet.microsoft.com/download
    echo.
    pause
    exit /b 1
)
echo ✅ พบ .NET SDK

REM แสดง version
echo.
echo 📋 ข้อมูลระบบ:
git --version
dotnet --version
echo.

REM กำหนดโฟลเดอร์ปลายทาง
set "TARGET_DIR=%USERPROFILE%\Projects\ThaipromptAffiliate-Mobile"
set "REPO_URL=https://github.com/xjanova/Thaiprompt-Affiliate.git"

echo 📁 โฟลเดอร์ปลายทาง: %TARGET_DIR%
echo.

REM ถามผู้ใช้ว่าต้องการดึงแบบไหน
echo เลือกวิธีการดึงโปรเจค:
echo   1. ดึงแบบ Sparse Checkout (เร็ว - เฉพาะ mobile app)
echo   2. ดึงทั้งหมดแล้วคัดลอกเฉพาะ mobile app
echo   3. ออกจากโปรแกรม
echo.
set /p CHOICE="กรุณาเลือก (1/2/3): "

if "%CHOICE%"=="1" goto :sparse_checkout
if "%CHOICE%"=="2" goto :full_clone
if "%CHOICE%"=="3" goto :exit
goto :invalid_choice

REM ============================================================================
:sparse_checkout
REM ============================================================================
echo.
echo 🔄 กำลังดึงแบบ Sparse Checkout...
echo.

REM สร้างโฟลเดอร์ถ้ายังไม่มี
if not exist "%TARGET_DIR%" (
    mkdir "%TARGET_DIR%"
)

cd /d "%TARGET_DIR%"

REM ลบโฟลเดอร์เก่าถ้ามี
if exist ".git" (
    echo ⚠️  พบโปรเจคเดิม กำลังลบ...
    rmdir /s /q ".git" 2>nul
    del /q * 2>nul
)

REM Sparse clone
echo 📥 กำลัง clone repository (sparse)...
git init
git remote add origin %REPO_URL%
git config core.sparseCheckout true

REM กำหนดโฟลเดอร์ที่ต้องการ
echo ThaipromptAffiliateApp/ > .git\info\sparse-checkout
echo mobile-app-samples/dotnet-maui/ >> .git\info\sparse-checkout
echo docs/features/mobile-app/ >> .git\info\sparse-checkout

REM ดึงข้อมูล
echo 📥 กำลังดึงไฟล์...
git pull origin main

if %ERRORLEVEL% NEQ 0 (
    echo ❌ เกิดข้อผิดพลาดในการดึงข้อมูล
    pause
    exit /b 1
)

echo.
echo ✅ ดึงโปรเจคสำเร็จ!
goto :success

REM ============================================================================
:full_clone
REM ============================================================================
echo.
echo 🔄 กำลังดึงทั้งหมด (จะใช้เวลานานกว่า)...
echo.

REM สร้าง temp folder
set "TEMP_DIR=%TEMP%\ThaipromptAffiliate_temp_%RANDOM%"
mkdir "%TEMP_DIR%"

echo 📥 กำลัง clone repository...
git clone --depth 1 %REPO_URL% "%TEMP_DIR%"

if %ERRORLEVEL% NEQ 0 (
    echo ❌ เกิดข้อผิดพลาดในการ clone
    rmdir /s /q "%TEMP_DIR%" 2>nul
    pause
    exit /b 1
)

REM สร้างโฟลเดอร์ปลายทาง
if not exist "%TARGET_DIR%" mkdir "%TARGET_DIR%"

REM คัดลอก mobile app folders
echo 📋 กำลังคัดลอกไฟล์ Mobile App...
xcopy /e /i /y "%TEMP_DIR%\ThaipromptAffiliateApp" "%TARGET_DIR%\ThaipromptAffiliateApp"
xcopy /e /i /y "%TEMP_DIR%\mobile-app-samples\dotnet-maui" "%TARGET_DIR%\mobile-app-samples\dotnet-maui"

REM คัดลอก docs ถ้ามี
if exist "%TEMP_DIR%\docs\features\mobile-app" (
    xcopy /e /i /y "%TEMP_DIR%\docs\features\mobile-app" "%TARGET_DIR%\docs\mobile-app"
)

REM ลบ temp folder
echo 🧹 กำลังลบไฟล์ชั่วคราว...
rmdir /s /q "%TEMP_DIR%"

echo.
echo ✅ คัดลอกโปรเจคสำเร็จ!
goto :success

REM ============================================================================
:success
REM ============================================================================
echo.
echo ============================================================================
echo   ✨ ดึง Mobile App สำเร็จ!
echo ============================================================================
echo.
echo 📁 ที่ตั้งโปรเจค: %TARGET_DIR%
echo.
echo 📂 โครงสร้างไฟล์:
echo    ThaipromptAffiliateApp\         ← แอพหลัก (พร้อมใช้งาน)
echo    mobile-app-samples\dotnet-maui\ ← ตัวอย่าง
echo    docs\mobile-app\                ← เอกสาร
echo.
echo 🚀 ขั้นตอนถัดไป:
echo    1. เปิด Visual Studio 2022
echo    2. เลือก "Open a project or solution"
echo    3. เปิดไฟล์: %TARGET_DIR%\ThaipromptAffiliateApp\ThaipromptAffiliateApp.sln
echo    4. รอ restore NuGet packages
echo    5. กด F5 เพื่อ Run บน Android Emulator
echo.
echo 📖 อ่านคู่มือเพิ่มเติมที่:
echo    %TARGET_DIR%\ThaipromptAffiliateApp\QUICKSTART.md
echo    %TARGET_DIR%\ThaipromptAffiliateApp\README.md
echo.

REM ถามว่าต้องการเปิด folder หรือไม่
set /p OPEN_FOLDER="ต้องการเปิดโฟลเดอร์โปรเจคหรือไม่? (y/n): "
if /i "%OPEN_FOLDER%"=="y" (
    explorer "%TARGET_DIR%"
)

echo.
echo 👋 ขอให้โชคดีในการพัฒนา!
echo.
pause
exit /b 0

REM ============================================================================
:invalid_choice
REM ============================================================================
echo.
echo ❌ ตัวเลือกไม่ถูกต้อง กรุณาเลือก 1, 2 หรือ 3
echo.
pause
goto :eof

REM ============================================================================
:exit
REM ============================================================================
echo.
echo 👋 ขอบคุณที่ใช้งาน!
echo.
pause
exit /b 0
