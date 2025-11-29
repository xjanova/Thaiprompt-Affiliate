# ============================================================================
# Thaiprompt Affiliate - Mobile App Clone Script (PowerShell)
# ============================================================================
# สคริปต์นี้ใช้สำหรับดึงเฉพาะส่วนแอพมือถือมาพัฒนาบน Windows
#
# วิธีใช้:
#   1. เปิด PowerShell as Administrator
#   2. รัน: Set-ExecutionPolicy Bypass -Scope Process
#   3. รัน: .\clone-mobile-app.ps1
#
# หรือรัน one-liner:
#   irm https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/main/scripts/clone-mobile-app.ps1 | iex
# ============================================================================

# ตั้งค่า Output Encoding เป็น UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host ""
Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host "   🚀 Thaiprompt Affiliate - Mobile App Clone Script (PowerShell)" -ForegroundColor Cyan
Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host ""

# ============================================================================
# ฟังก์ชันตรวจสอบ prerequisite
# ============================================================================

function Test-Prerequisites {
    $hasErrors = $false

    # ตรวจสอบ Git
    Write-Host "🔍 ตรวจสอบ Git..." -NoNewline
    if (Get-Command git -ErrorAction SilentlyContinue) {
        $gitVersion = git --version
        Write-Host " ✅ $gitVersion" -ForegroundColor Green
    } else {
        Write-Host " ❌ ไม่พบ Git" -ForegroundColor Red
        Write-Host "   ดาวน์โหลดที่: https://git-scm.com/download/win" -ForegroundColor Yellow
        $hasErrors = $true
    }

    # ตรวจสอบ .NET SDK
    Write-Host "🔍 ตรวจสอบ .NET SDK..." -NoNewline
    if (Get-Command dotnet -ErrorAction SilentlyContinue) {
        $dotnetVersion = dotnet --version
        Write-Host " ✅ .NET $dotnetVersion" -ForegroundColor Green
    } else {
        Write-Host " ❌ ไม่พบ .NET SDK" -ForegroundColor Red
        Write-Host "   ดาวน์โหลดที่: https://dotnet.microsoft.com/download" -ForegroundColor Yellow
        $hasErrors = $true
    }

    return -not $hasErrors
}

# ============================================================================
# ฟังก์ชันหลัก
# ============================================================================

function Clone-MobileApp {
    param (
        [string]$TargetPath = "$env:USERPROFILE\Projects\ThaipromptAffiliate-Mobile",
        [switch]$FullClone,
        [switch]$SkipFonts
    )

    $repoUrl = "https://github.com/xjanova/Thaiprompt-Affiliate.git"

    Write-Host ""
    Write-Host "📁 โฟลเดอร์ปลายทาง: $TargetPath" -ForegroundColor Cyan
    Write-Host ""

    # สร้างโฟลเดอร์ถ้ายังไม่มี
    if (-not (Test-Path $TargetPath)) {
        New-Item -ItemType Directory -Path $TargetPath -Force | Out-Null
        Write-Host "📁 สร้างโฟลเดอร์: $TargetPath" -ForegroundColor Green
    }

    Set-Location $TargetPath

    # ลบ .git folder เก่าถ้ามี
    if (Test-Path ".git") {
        Write-Host "⚠️  พบโปรเจคเดิม กำลังลบ..." -ForegroundColor Yellow
        Remove-Item -Recurse -Force ".git"
        Get-ChildItem -Path . -Recurse | Remove-Item -Force -Recurse -ErrorAction SilentlyContinue
    }

    if ($FullClone) {
        # Clone แบบเต็ม
        Write-Host "📥 กำลัง clone repository (full)..." -ForegroundColor Cyan

        $tempDir = Join-Path $env:TEMP "ThaipromptAffiliate_temp_$(Get-Random)"
        git clone --depth 1 $repoUrl $tempDir

        if ($LASTEXITCODE -ne 0) {
            Write-Host "❌ เกิดข้อผิดพลาดในการ clone" -ForegroundColor Red
            return $false
        }

        # คัดลอกเฉพาะ mobile folders
        Write-Host "📋 กำลังคัดลอกไฟล์ Mobile App..." -ForegroundColor Cyan

        Copy-Item -Path "$tempDir\ThaipromptAffiliateApp" -Destination "$TargetPath\ThaipromptAffiliateApp" -Recurse -Force

        if (Test-Path "$tempDir\mobile-app-samples") {
            Copy-Item -Path "$tempDir\mobile-app-samples" -Destination "$TargetPath\mobile-app-samples" -Recurse -Force
        }

        if (Test-Path "$tempDir\docs\features\mobile-app") {
            New-Item -ItemType Directory -Path "$TargetPath\docs" -Force | Out-Null
            Copy-Item -Path "$tempDir\docs\features\mobile-app" -Destination "$TargetPath\docs\mobile-app" -Recurse -Force
        }

        # ลบ temp folder
        Write-Host "🧹 กำลังลบไฟล์ชั่วคราว..." -ForegroundColor Cyan
        Remove-Item -Recurse -Force $tempDir

    } else {
        # Sparse Checkout
        Write-Host "📥 กำลัง clone repository (sparse)..." -ForegroundColor Cyan

        git init
        git remote add origin $repoUrl
        git config core.sparseCheckout true

        # กำหนดโฟลเดอร์ที่ต้องการ
        $sparseCheckoutFile = Join-Path $TargetPath ".git\info\sparse-checkout"
        @(
            "ThaipromptAffiliateApp/"
            "mobile-app-samples/dotnet-maui/"
            "docs/features/mobile-app/"
        ) | Out-File -FilePath $sparseCheckoutFile -Encoding UTF8

        # ดึงข้อมูล
        Write-Host "📥 กำลังดึงไฟล์..." -ForegroundColor Cyan
        git pull origin main

        if ($LASTEXITCODE -ne 0) {
            Write-Host "❌ เกิดข้อผิดพลาดในการดึงข้อมูล" -ForegroundColor Red
            return $false
        }
    }

    return $true
}

function Show-NextSteps {
    param (
        [string]$TargetPath
    )

    Write-Host ""
    Write-Host "============================================================================" -ForegroundColor Green
    Write-Host "   ✨ ดึง Mobile App สำเร็จ!" -ForegroundColor Green
    Write-Host "============================================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "📁 ที่ตั้งโปรเจค:" -ForegroundColor Cyan
    Write-Host "   $TargetPath" -ForegroundColor White
    Write-Host ""
    Write-Host "📂 โครงสร้างไฟล์:" -ForegroundColor Cyan
    Write-Host "   ThaipromptAffiliateApp\         <- แอพหลัก (พร้อมใช้งาน)" -ForegroundColor White
    Write-Host "   mobile-app-samples\dotnet-maui\ <- ตัวอย่าง" -ForegroundColor Gray
    Write-Host "   docs\mobile-app\                <- เอกสาร" -ForegroundColor Gray
    Write-Host ""
    Write-Host "🚀 ขั้นตอนถัดไป:" -ForegroundColor Cyan
    Write-Host "   1. เปิด Visual Studio 2022" -ForegroundColor White
    Write-Host "   2. เลือก 'Open a project or solution'" -ForegroundColor White
    Write-Host "   3. เปิดไฟล์: $TargetPath\ThaipromptAffiliateApp\ThaipromptAffiliateApp.sln" -ForegroundColor White
    Write-Host "   4. รอ restore NuGet packages" -ForegroundColor White
    Write-Host "   5. กด F5 เพื่อ Run บน Android Emulator" -ForegroundColor White
    Write-Host ""
    Write-Host "⚠️  อย่าลืม:" -ForegroundColor Yellow
    Write-Host "   - ติดตั้ง Open Sans fonts ใน Resources\Fonts\" -ForegroundColor Yellow
    Write-Host "   - แก้ไข API URL ใน Helpers\Constants.cs" -ForegroundColor Yellow
    Write-Host ""
}

# ============================================================================
# Main Script
# ============================================================================

# ตรวจสอบ prerequisites
if (-not (Test-Prerequisites)) {
    Write-Host ""
    Write-Host "❌ กรุณาติดตั้งซอฟต์แวร์ที่จำเป็นก่อนรันสคริปต์นี้อีกครั้ง" -ForegroundColor Red
    Write-Host ""
    Read-Host "กด Enter เพื่อปิด"
    exit 1
}

Write-Host ""
Write-Host "เลือกวิธีการดึงโปรเจค:" -ForegroundColor Cyan
Write-Host "  1. Sparse Checkout (เร็ว - เฉพาะ mobile app)" -ForegroundColor White
Write-Host "  2. Clone ทั้งหมดแล้วคัดลอกเฉพาะ mobile app" -ForegroundColor White
Write-Host "  3. ออกจากโปรแกรม" -ForegroundColor White
Write-Host ""

$choice = Read-Host "กรุณาเลือก (1/2/3)"

$targetPath = "$env:USERPROFILE\Projects\ThaipromptAffiliate-Mobile"

switch ($choice) {
    "1" {
        $success = Clone-MobileApp -TargetPath $targetPath
    }
    "2" {
        $success = Clone-MobileApp -TargetPath $targetPath -FullClone
    }
    "3" {
        Write-Host "👋 ขอบคุณที่ใช้งาน!" -ForegroundColor Cyan
        exit 0
    }
    default {
        Write-Host "❌ ตัวเลือกไม่ถูกต้อง" -ForegroundColor Red
        exit 1
    }
}

if ($success) {
    Show-NextSteps -TargetPath $targetPath

    # ถามว่าต้องการเปิด folder หรือไม่
    $openFolder = Read-Host "ต้องการเปิดโฟลเดอร์โปรเจคหรือไม่? (y/n)"
    if ($openFolder -eq "y" -or $openFolder -eq "Y") {
        explorer $targetPath
    }

    # ถามว่าต้องการเปิด Visual Studio หรือไม่
    $openVS = Read-Host "ต้องการเปิด Visual Studio หรือไม่? (y/n)"
    if ($openVS -eq "y" -or $openVS -eq "Y") {
        $solutionPath = Join-Path $targetPath "ThaipromptAffiliateApp\ThaipromptAffiliateApp.sln"
        if (Test-Path $solutionPath) {
            Start-Process $solutionPath
        } else {
            Write-Host "⚠️  ไม่พบไฟล์ .sln" -ForegroundColor Yellow
        }
    }
}

Write-Host ""
Write-Host "👋 ขอให้โชคดีในการพัฒนา!" -ForegroundColor Cyan
Write-Host ""
Read-Host "กด Enter เพื่อปิด"
