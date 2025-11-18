#!/bin/bash

# =====================================================
# TP-Affiliate Demo Data Cleanup Script
# =====================================================
# สคริปต์สำหรับลบข้อมูลทดสอบ (Demo Data)
# ใช้สำหรับเตรียมระบบก่อนใช้งานจริง (Production)
#
# Version: 1.0.0
# Author: TP-Affiliate Team
# =====================================================

set -e  # Exit on error

# สี
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# ฟังก์ชันแสดงข้อความ
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_header() {
    echo ""
    echo -e "${MAGENTA}════════════════════════════════════════${NC}"
    echo -e "${MAGENTA}  $1${NC}"
    echo -e "${MAGENTA}════════════════════════════════════════${NC}"
    echo ""
}

error_exit() {
    print_error "$1"
    exit 1
}

# ตรวจสอบ PHP Artisan
check_requirements() {
    if [ ! -f "artisan" ]; then
        error_exit "ไม่พบไฟล์ artisan - กรุณารันสคริปต์นี้จาก root directory ของโปรเจค"
    fi

    if ! command -v php &> /dev/null; then
        error_exit "ไม่พบ PHP - กรุณาติดตั้ง PHP ก่อน"
    fi
}

# Header
clear
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}🧹 ระบบลบข้อมูลทดสอบ (Demo Data Cleanup)${NC}             ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${CYAN}เตรียมระบบสำหรับใช้งานจริง (Production-Ready)${NC}          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ตรวจสอบความพร้อม
check_requirements

# แสดงข้อมูล
print_info "สคริปต์นี้จะช่วยคุณลบข้อมูลทดสอบออกจากระบบ"
echo ""
echo "คุณสามารถเลือกได้ว่าจะลบข้อมูลประเภทไหน:"
echo ""
echo -e "  ${CYAN}1)${NC} ลบผู้ใช้ทดสอบ (Demo Users)"
echo -e "     ลบบัญชีผู้ใช้ที่มี email @example.com, @thaiprompt.com"
echo ""
echo -e "  ${CYAN}2)${NC} ลบหน้าเพจตัวอย่าง (Demo Pages)"
echo -e "     ลบหน้า About, FAQ, Contact, Terms, Privacy"
echo ""
echo -e "  ${CYAN}3)${NC} ลบข้อมูล KYC ตัวอย่าง (Demo KYC)"
echo -e "     ลบข้อมูล KYC verifications ทั้งหมด"
echo ""
echo -e "  ${CYAN}4)${NC} ลบ LINE Sessions ตัวอย่าง (Demo LINE)"
echo -e "     ลบ LINE signup sessions และ bot profiles"
echo ""
echo -e "  ${CYAN}5)${NC} ลบข้อมูลบัญชีตัวอย่าง (Demo Accounting)"
echo -e "     ลบรายการบัญชีที่มีคำว่า \"demo\" หรือ \"ทดสอบ\""
echo ""
echo -e "  ${CYAN}6)${NC} ลบทั้งหมด (All Demo Data)"
echo -e "     ลบข้อมูลทดสอบทุกประเภท"
echo ""
echo -e "  ${CYAN}7)${NC} Interactive Mode (เลือกแบบ Interactive)"
echo -e "     ใช้เมนูแบบโต้ตอบของ Laravel Artisan"
echo ""
echo -e "  ${CYAN}0)${NC} ยกเลิก (Cancel)"
echo ""

# รับคำตอบจากผู้ใช้
read -p "กรุณาเลือก (0-7) [7]: " CHOICE
CHOICE=${CHOICE:-7}

echo ""

# ตรวจสอบว่าเป็น production หรือไม่
if grep -q "APP_ENV=production" .env 2>/dev/null; then
    print_warning "⚠️  คุณกำลังอยู่ใน Production environment!"
    echo ""
    read -p "คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ? (yes/no): " CONFIRM
    if [ "$CONFIRM" != "yes" ]; then
        echo "ยกเลิกการดำเนินการ"
        exit 0
    fi
    echo ""
fi

# ดำเนินการตามที่เลือก
case $CHOICE in
    0)
        echo "ยกเลิกการดำเนินการ"
        exit 0
        ;;
    1)
        print_header "ลบผู้ใช้ทดสอบ"
        php artisan demo:reset --users
        ;;
    2)
        print_header "ลบหน้าเพจตัวอย่าง"
        php artisan demo:reset --pages
        ;;
    3)
        print_header "ลบข้อมูล KYC ตัวอย่าง"
        php artisan demo:reset --kyc
        ;;
    4)
        print_header "ลบ LINE Sessions ตัวอย่าง"
        php artisan demo:reset --line
        ;;
    5)
        print_header "ลบข้อมูลบัญชีตัวอย่าง"
        php artisan demo:reset --accounting
        ;;
    6)
        print_header "ลบทั้งหมด"
        print_warning "⚠️  จะลบข้อมูลทดสอบทั้งหมด!"
        echo ""
        read -p "คุณแน่ใจหรือไม่? (yes/no): " CONFIRM_ALL
        if [ "$CONFIRM_ALL" != "yes" ]; then
            echo "ยกเลิกการดำเนินการ"
            exit 0
        fi
        echo ""
        php artisan demo:reset --all
        ;;
    7)
        print_header "Interactive Mode"
        php artisan demo:reset
        ;;
    *)
        print_error "ตัวเลือกไม่ถูกต้อง"
        exit 1
        ;;
esac

# แสดงข้อความเสร็จสิ้น
echo ""
print_header "🎉 เสร็จสิ้น!"
echo ""
print_success "ระบบพร้อมสำหรับการใช้งานจริงแล้ว!"
echo ""
echo -e "${BLUE}💡 คำแนะนำเพิ่มเติม:${NC}"
echo ""
echo "  1. ตรวจสอบข้อมูลที่เหลืออยู่ในระบบ"
echo "  2. สร้างผู้ใช้งานจริงตามต้องการ"
echo "  3. ปรับแต่งการตั้งค่าระบบให้เหมาะสม"
echo "  4. ตรวจสอบ .env ให้อยู่ใน production mode"
echo "  5. เปิดใช้งาน maintenance mode ก่อน deploy:"
echo -e "     ${CYAN}php artisan down${NC}"
echo ""
echo -e "${GREEN}ขอบคุณที่ใช้ TP-Affiliate! 🚀${NC}"
echo ""
