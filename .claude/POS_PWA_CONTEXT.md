# 🏪 TP-POS PWA Development Guide

## 📋 Project Overview

ระบบ POS (Point of Sale) แบบ PWA สำหรับ TP-Affiliate Platform

**Branch:** `claude/build-pos-system-013U77cSnm9butYA2MEgi71G`

---

## 🗂️ File Structure

```
📁 POS System Files
├── 📁 Backend (Laravel)
│   ├── app/Http/Controllers/Pos/PosApiController.php    # API Controller
│   └── routes/pos.php                                   # Routes
│
├── 📁 Frontend (PWA)
│   ├── resources/js/pos/
│   │   ├── app.js          # Main Alpine.js app
│   │   ├── database.js     # IndexedDB service
│   │   ├── sync.js         # Offline sync service
│   │   └── hardware.js     # Printer/Scanner integration
│   │
│   └── resources/views/pos/
│       ├── pwa/
│       │   ├── index.blade.php       # Main POS UI
│       │   ├── inventory.blade.php   # Stock management
│       │   └── reports.blade.php     # Reports & Analytics
│       └── offline.blade.php         # Offline fallback page
│
├── 📁 PWA Assets
│   ├── public/pos-manifest.json      # PWA manifest
│   ├── public/pos-sw.js              # Service Worker
│   └── public/images/pos/            # Icons
│
└── 📁 Config
    └── vite.config.js                # Updated with POS entries
```

---

## 🔗 Routes & URLs

### Web Routes
| Route | Description |
|-------|-------------|
| `/pos/app` | Main POS PWA interface |
| `/pos/cashier` | Legacy cashier (existing) |
| `/pos/inventory` | Stock management |
| `/pos/reports` | Reports dashboard |
| `/pos/offline` | Offline fallback |

### API Routes (`/pos/api/...`)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/products/all` | GET | Get all products for offline cache |
| `/products/updated?since=` | GET | Get updated products |
| `/products/search?q=` | GET | Search products |
| `/categories/all` | GET | Get all categories |
| `/customers/all` | GET | Get all customers |
| `/customers/search?q=` | GET | Search customers |
| `/customers` | POST | Create customer |
| `/settings` | GET | Get POS settings |
| `/transactions/sync` | POST | Sync offline transaction |
| `/transactions/bulk-sync` | POST | Bulk sync transactions |
| `/transactions/recent` | GET | Get recent transactions |
| `/stock/movement` | POST | Record stock movement |
| `/stock/sync` | POST | Sync stock movements |
| `/reports/today` | GET | Today's sales summary |
| `/device/register` | POST | Register POS device |
| `/device/heartbeat` | POST | Device heartbeat |

---

## 💻 Technology Stack

### Frontend
- **Tailwind CSS** - Utility-first CSS
- **Alpine.js** - Reactive JavaScript
- **IndexedDB** - Offline database
- **Service Worker** - PWA caching
- **Chart.js** - Reports charts

### Backend
- **Laravel 11** - PHP framework
- **MySQL** - Database
- **Laravel Sanctum** - API auth

---

## 🚀 Key Features

### ✅ Implemented
- [x] PWA with offline support
- [x] IndexedDB local storage
- [x] Auto-sync when online
- [x] Touch-friendly UI
- [x] Barcode scanning (USB/Camera)
- [x] Receipt printer support (ESC/POS)
- [x] Cash drawer integration
- [x] Product search & categories
- [x] Cart management
- [x] Multiple payment methods
- [x] Customer management
- [x] Inventory management
- [x] Sales reports & charts

### 🔲 TODO / Improvements
- [ ] Generate actual PWA icons (multiple sizes)
- [ ] Implement camera barcode scanner UI
- [ ] Add discount/coupon system
- [ ] Loyalty points integration
- [ ] Multi-store support
- [ ] Staff management
- [ ] Print templates customization
- [ ] Data export (Excel/PDF)
- [ ] Push notifications
- [ ] Real-time WebSocket sync

---

## 🛠️ Development Commands

```bash
# Clone and checkout branch
git fetch origin
git checkout claude/build-pos-system-013U77cSnm9butYA2MEgi71G

# Install dependencies
composer install
npm install

# Build assets
npm run dev      # Development with HMR
npm run build    # Production build

# Run local server
php artisan serve

# Access POS
# http://localhost:8000/pos/app
```

---

## 📝 Important Notes

1. **V3 Stack**: ใช้ Tailwind CSS + Alpine.js (ไม่ใช้ Bootstrap/jQuery)
2. **Offline-First**: ทุก transaction ต้องบันทึก IndexedDB ก่อน แล้วค่อย sync
3. **Thai Language**: Comments และ UI ต้องเป็นภาษาไทย
4. **Touch-friendly**: ปุ่มต้อง ≥48px, รองรับ gesture

---

## 🔐 Database Models (Existing)

- `PosDevice` - POS devices/terminals
- `PosSession` - Cashier sessions
- `PosTransaction` - Sales transactions
- `PosTransactionItem` - Transaction line items
- `PosCategory` - POS product categories
- `PosSetting` - Store POS settings
- `PosAdvertisement` - Customer display ads
- `PosOfflineQueue` - Offline sync queue
- `Product` - Products
- `User` - Customers/Users

---

## 📱 PWA Installation

1. Open `/pos/app` in Chrome/Edge
2. Click "Install" or "Add to Home Screen"
3. App will work offline automatically

---

## 🧪 Testing

```bash
# Test offline mode
1. Open POS app
2. Disable network in DevTools
3. Add items, complete sale
4. Enable network
5. Check auto-sync
```

---

**Last Updated:** 2025-11-29
**Version:** 1.0.0
