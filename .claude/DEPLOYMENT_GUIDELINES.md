# Deployment & Composer Package Management Guidelines

## 📦 Composer Package Management (บังคับ)

**ทุกครั้งที่มีการติดตั้ง Composer Package ใหม่ ต้องเพิ่มการติดตั้งใน deploy.sh เสมอ**

---

## หลักการสำคัญ

> **"ถ้ามีการใช้ composer require ในโปรเจกต์ ต้องเพิ่มใน deploy.sh ทันที"**

---

## 1. Composer Package Installation Checklist

**เมื่อติดตั้ง Package ใหม่:**
- [ ] ติดตั้ง package ในโปรเจกต์ด้วย `composer require package-name`
- [ ] **เพิ่มการตรวจสอบและติดตั้ง package ใน deploy.sh ทันที (บังคับ)**
- [ ] ใช้รูปแบบมาตรฐานเดียวกับ packages อื่นๆ
- [ ] เพิ่ม version detection และ logging
- [ ] มี error handling และ fallback message
- [ ] วาง step ในตำแหน่งที่เหมาะสมของ deployment script

---

## 2. Deploy.sh Package Installation Template

**รูปแบบมาตรฐานสำหรับเพิ่ม package ใน deploy.sh:**

```bash
# Step X.X: Install/Verify [Package Name] for [Purpose]
print_info "[X.X/20] Installing [Package Name]..."
if composer show package-name &>/dev/null; then
    PACKAGE_VERSION=$(composer show package-name 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
    print_success "[Package Name] already installed (${PACKAGE_VERSION})"
else
    print_info "Installing [Package Name]..."
    if ! composer require package-name --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
        print_warning "[Package Name] installation failed - [describe fallback behavior]"
        log "Warning: package-name installation failed"
    else
        PACKAGE_VERSION=$(composer show package-name 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
        print_success "[Package Name] installed successfully (${PACKAGE_VERSION})"
        log "[Package Name] installed: ${PACKAGE_VERSION}"
    fi
fi
```

---

## 3. ตัวอย่าง Package Installation

### DomPDF สำหรับ PDF Generation

```bash
# Step 7.6: Install/Verify DomPDF for PDF Generation
print_info "[7.6/20] Installing DomPDF for PDF generation..."
if composer show barryvdh/laravel-dompdf &>/dev/null; then
    DOMPDF_VERSION=$(composer show barryvdh/laravel-dompdf 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
    print_success "DomPDF already installed (${DOMPDF_VERSION})"
else
    print_info "Installing DomPDF..."
    if ! composer require barryvdh/laravel-dompdf --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
        print_warning "DomPDF installation failed - PDF quotations will use HTML fallback"
        log "Warning: barryvdh/laravel-dompdf installation failed"
    else
        DOMPDF_VERSION=$(composer show barryvdh/laravel-dompdf 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
        print_success "DomPDF installed successfully (${DOMPDF_VERSION})"
        log "DomPDF installed: ${DOMPDF_VERSION}"
    fi
fi
```

### Intervention Image สำหรับ Image Processing

```bash
# Step 7.7: Install/Verify Intervention Image for Image Processing
print_info "[7.7/20] Installing Intervention Image..."
if composer show intervention/image &>/dev/null; then
    IMAGE_VERSION=$(composer show intervention/image 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
    print_success "Intervention Image already installed (${IMAGE_VERSION})"
else
    print_info "Installing Intervention Image..."
    if ! composer require intervention/image --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
        print_warning "Intervention Image installation failed - image processing features may not work"
        log "Warning: intervention/image installation failed"
    else
        IMAGE_VERSION=$(composer show intervention/image 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
        print_success "Intervention Image installed successfully (${IMAGE_VERSION})"
        log "Intervention Image installed: ${IMAGE_VERSION}"
    fi
fi
```

---

## 4. Deploy.sh Placement Guidelines

**วาง package installation step ในตำแหน่งที่เหมาะสม:**

- **Step 7.x**: สำหรับ packages ที่เป็น optional dependencies หรือ feature-specific
  - DomPDF (PDF generation)
  - Intervention Image (image processing)
  - Laravel Excel (Excel import/export)
  - Package ที่ไม่ใช่ core dependencies

- **ก่อน Step 8** (Laravel Sanctum): สำหรับ packages ที่อาจต้องมีก่อน authentication/authorization

- **หลัง Step 8**: สำหรับ packages ที่ depend on authentication

---

## 5. Common Packages Checklist

**Packages ที่มักใช้และต้องเพิ่มใน deploy.sh:**

```bash
# PDF Generation
composer require barryvdh/laravel-dompdf

# Image Processing
composer require intervention/image

# Excel Import/Export
composer require maatwebsite/excel

# Payment Gateways
composer require omnipay/omnipay

# API Resources
composer require spatie/laravel-query-builder

# Testing Tools (dev only)
composer require --dev barryvdh/laravel-debugbar
```

---

## 6. Error Handling Best Practices

**สิ่งที่ต้องมีใน error handling:**

1. **ข้อความเตือนที่ชัดเจน** - บอกว่าถ้า package ติดตั้งไม่สำเร็จจะเกิดอะไร
2. **Fallback behavior** - อธิบายว่าระบบจะทำงานอย่างไรถ้าไม่มี package
3. **Logging** - บันทึกลงไฟล์ log เพื่อ debugging
4. **Non-breaking** - อย่าให้ deployment fail ถ้า package ไม่สำคัญมาก

```bash
if ! composer require package-name --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
    # ✅ ดี: บอกผลกระทบและ fallback ชัดเจน
    print_warning "Package installation failed - Feature X will use fallback method Y"
    log "Warning: package-name installation failed"

    # ❌ ไม่ดี: ไม่บอกผลกระทบ
    # print_warning "Failed"
fi
```

---

## 7. Testing Deployment Script

**ทดสอบ deploy.sh หลังเพิ่ม package:**

```bash
# ทดสอบ syntax
bash -n deploy.sh

# ทดสอบ dry-run (ถ้ามี)
./deploy.sh --dry-run

# ทดสอบจริงบน staging environment
./deploy.sh

# ตรวจสอบว่า package ติดตั้งสำเร็จ
composer show | grep package-name
```

---

## 8. Documentation Update

**เมื่อเพิ่ม package ใหม่ ต้องอัปเดต:**

- [ ] `deploy.sh` - เพิ่ม installation step
- [ ] `README.md` - อัปเดต dependencies list
- [ ] `composer.json` - verify package อยู่ใน require/require-dev
- [ ] Documentation - อธิบายการใช้งาน package
- [ ] `.env.example` - เพิ่ม config variables ถ้ามี

---

## 9. ตัวอย่าง Workflow ที่ถูกต้อง

1. **พัฒนา Feature ใหม่** - ต้องการใช้ DomPDF
   ```bash
   composer require barryvdh/laravel-dompdf
   ```

2. **ทันทีที่ติดตั้ง - เพิ่มใน deploy.sh**
   ```bash
   # เปิดไฟล์ deploy.sh
   # เพิ่ม Step 7.6 สำหรับ DomPDF installation
   # บันทึกไฟล์
   ```

3. **Commit ทั้งสองอย่างพร้อมกัน**
   ```bash
   git add composer.json composer.lock deploy.sh
   git commit -m "feat: Add DomPDF for PDF quotation generation

   - Install barryvdh/laravel-dompdf package
   - Add DomPDF installation to deploy.sh
   - Include HTML fallback if installation fails"
   ```

4. **ทดสอบ Deployment**
   ```bash
   ./deploy.sh
   # ตรวจสอบว่า DomPDF ติดตั้งสำเร็จ
   ```

---

## ❌ ห้ามทำ (Deploy.sh):

- ❌ ติดตั้ง package แล้วไม่เพิ่มใน deploy.sh
- ❌ ใช้รูปแบบที่ไม่สม่ำเสมอกับ packages อื่น
- ❌ ไม่มี error handling หรือ fallback
- ❌ ไม่ log การติดตั้ง
- ❌ ไม่ตรวจสอบว่า package ติดตั้งแล้วหรือยัง (จะติดตั้งซ้ำทุกครั้ง)
- ❌ ไม่แสดง version ของ package
- ❌ ทำให้ deployment fail ถ้า optional package ติดตั้งไม่สำเร็จ
- ❌ วาง step ไม่เป็นระเบียบหรือไม่อัปเดต step numbers

---

**Last Updated:** 2025-11-10
**Version:** 1.0
