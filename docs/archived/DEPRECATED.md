# DEPRECATED - ระบบที่ถูกลบออก

> **เอกสารนี้บันทึกสิ่งที่ถูกลบออกจากระบบ**
> วันที่: 2025-11-15
> Commit: chore: remove old team system completely

---

## สรุปการลบ

ลบระบบทีมเก่า (Old Affiliate/Organization System) ทั้งหมดออกจากโค้ดเบส เพื่อหลีกเลี่ยงความสับสนและให้ใช้ระบบใหม่ (MLM Team System) เท่านั้น

**สาเหตุ:**
- ป้องกันความสับสนระหว่างระบบเก่าและระบบใหม่
- Single Source of Truth - ใช้ MenuService V3 เท่านั้น
- Cleaner codebase - ลบโค้ดที่ไม่ได้ใช้งานแล้ว

**ระบบใหม่ที่ใช้แทน:**
- ✅ `user.team` - MLM Team Dashboard (MlmDashboardController@team)
- ✅ `user.prospects.index` - Prospect Management
- ✅ `user.ranks.leaderboard` - Leaderboard

---

## 1. Routes ที่ถูกลบ

### routes/user.php

```php
// ❌ DELETED - Old referrals route
Route::get('/referrals', [DashboardController::class, 'referrals'])->name('referrals');

// ❌ DELETED - Old organization chart route
Route::get('/organization', [DashboardController::class, 'organizationChart'])->name('organization');

// ❌ DELETED - Old binary organization chart route
Route::get('/organization-binary', [DashboardController::class, 'binaryOrganizationChart'])->name('organization.binary');

// ❌ DELETED - Old AJAX API for organization tree data
Route::get('/organization/tree-data', [DashboardController::class, 'getOrganizationTreeData'])->name('organization.tree-data');
```

**ใช้แทนด้วย:**
```php
// ✅ NEW - MLM Team routes (in routes/user.php)
Route::prefix('mlm')->name('mlm.')->group(function () {
    Route::get('/team', [\App\Http\Controllers\User\MlmDashboardController::class, 'team'])->name('team');
    // ... other MLM routes
});
```

---

## 2. Controller Methods ที่ถูกลบ

### app/Http/Controllers/User/DashboardController.php

ลบ methods ทั้งหมด **~260 บรรทัด**:

```php
// ❌ DELETED - Get maximum level depth in referral tree
private function getMaxLevel($affiliate, $level = 1)

// ❌ DELETED - Display user referrals
public function referrals()

// ❌ DELETED - Display user organization chart (downline only)
public function organizationChart()

// ❌ DELETED - Count total network size (all downlines)
private function countTotalNetwork($affiliate)

// ❌ DELETED - Sum total network earnings (all downlines)
private function sumNetworkEarnings($affiliate)

// ❌ DELETED - Get organization tree data via AJAX (for web session)
public function getOrganizationTreeData()

// ❌ DELETED - Build tree node recursively
protected function buildTreeNode($affiliate, $currentDepth = 0, $maxDepth = 10)

// ❌ DELETED - Get user avatar (use profile_picture_url accessor with cache busting)
protected function getAvatar($user)

// ❌ DELETED - Display user binary organization chart
public function binaryOrganizationChart()

// ❌ DELETED - Get maximum binary level depth
private function getMaxBinaryLevel($affiliate, $level = 1)
```

**ใช้แทนด้วย:**
```php
// ✅ NEW - MLM Team methods in MlmDashboardController
- team() - แสดง MLM Team Dashboard
- getTeamData() - ดึงข้อมูลทีม
- getTeamTree() - ดึงโครงสร้างทีม
```

---

## 3. Views ที่ถูกลบ

ลบ views ทั้งหมด **~77KB**:

### resources/views/user/

```
❌ referrals.blade.php (~9.8KB)
   - หน้าแสดงรายชื่อผู้แนะนำ (old affiliate referrals list)

❌ organization.blade.php (~12.4KB)
   - หน้าผังองค์กรแบบเก่า (old organization chart - basic tree view)

❌ organization-binary.blade.php (~18.1KB)
   - หน้าผังองค์กรแบบไบนารี (old binary tree visualization)

❌ organization-new.blade.php (~37.2KB)
   - หน้าผังองค์กรแบบใหม่ (experimental Google Maps style with circles)
```

**ใช้แทนด้วย:**
```
✅ resources/views/user/mlm/team.blade.php
   - MLM Team Dashboard ใหม่ที่มีฟีเจอร์ครบถ้วน
```

---

## 4. Menu References ที่ถูกแก้ไข

### config/menus.php

**ลบ submenu items เก่า:**
```php
// ❌ DELETED from Team submenu
['label' => 'ผู้แนะนำ', 'route' => 'user.referrals'],
['label' => 'ผังสายงาน', 'route' => 'user.organization'],
['label' => 'ผังแบบไบนารี', 'route' => 'user.organization.binary'],
```

**เก็บเฉพาะ submenu ใหม่:**
```php
// ✅ KEPT - New MLM submenu items
['label' => 'ผู้มุ่งหวัง', 'route' => 'user.prospects.index'],
['label' => 'ลีดเดอร์บอร์ด', 'route' => 'user.ranks.leaderboard'],
```

### resources/views/components/millennium-taskbar.blade.php

**เปลี่ยนจาก:**
```php
['icon' => '👥', 'label' => 'ผู้แนะนำ', 'url' => route('user.referrals'), ...],
['icon' => '🌳', 'label' => 'ผังสายงาน', 'url' => route('user.organization'), ...],
```

**เป็น:**
```php
['icon' => '👥', 'label' => 'ทีมงาน', 'url' => route('user.team'), ...],
```

### resources/views/components/dashboard-switcher.blade.php

**เปลี่ยนจาก:**
```blade
<a href="{{ route('user.organization') }}">
    <span>ผังสายงาน</span>
</a>
```

**เป็น:**
```blade
<a href="{{ route('user.team') }}">
    <span>ทีมงาน</span>
</a>
```

### resources/views/user/dashboard.blade.php

**เปลี่ยนจาก:** (4 จุด)
```blade
<a href="{{ route('user.referrals') }}">Total Referrals</a>
<a href="{{ route('user.referrals') }}">Conversion Rate</a>
<a href="{{ route('user.referrals') }}">View Details</a>
<a href="{{ route('user.referrals') }}">Quick Link</a>
```

**เป็น:**
```blade
<a href="{{ route('user.team') }}">...</a>
```

---

## 5. Database & Migrations

**ไม่มีการลบ migrations หรือ tables** เนื่องจาก:
- Tables เหล่านี้ยังใช้งานโดยระบบใหม่ (`affiliates`, `users`, etc.)
- ระบบใหม่ใช้ data structure เดียวกัน แต่เพิ่มฟีเจอร์ใหม่
- Backward compatibility - ข้อมูลเก่ายังคงสามารถใช้งานได้

**Tables ที่ยังใช้งาน:**
- ✅ `affiliates` - ใช้โดย MLM system
- ✅ `users` - ใช้โดยทุกระบบ
- ✅ `commissions` - ใช้โดย MLM system
- ✅ `ranks` - ใช้โดย MLM ranking system

---

## 6. สรุปผลกระทบ

### ✅ ส่วนที่ลบสำเร็จ
- Routes: 4 routes
- Controller methods: 10 methods (~260 บรรทัด)
- Views: 4 ไฟล์ (~77KB)
- Menu items: 3 submenu items
- References: แก้ไขทั้งหมด 4 ไฟล์

### ⚠️ สิ่งที่ต้องระวัง
- ผู้ใช้ที่คั่นหน้าเก่าไว้ (bookmarks) จะเจอ 404 error
- Links ภายนอกที่ชี้ไปยัง old routes จะไม่ทำงาน
- Documentation ภายนอกที่อ้างอิง old routes ต้องอัพเดท

### ✅ Migration Path
ผู้ใช้ควรใช้:
- `user.team` แทน `user.referrals` และ `user.organization`
- MenuService V3 จะแสดงเมนูที่ถูกต้องโดยอัตโนมัติ
- หน้า dashboard จะ redirect ไปยัง team page ที่ใหม่

---

## 7. Git History

หากต้องการดูโค้ดเก่า สามารถย้อนกลับไปดูได้จาก git history:

```bash
# ดู commit ก่อนหน้าที่ลบระบบเก่า
git log --all --grep="remove old team system"

# ดูไฟล์ที่ถูกลบ
git log --diff-filter=D --summary

# Restore ไฟล์เก่า (ถ้าจำเป็น)
git checkout <commit-hash> -- resources/views/user/referrals.blade.php
```

---

## 8. Related Commits

- `feat: integrate team menu system with V3 MenuService` - Migration ไป V3
- `chore: remove deprecated code comments` - ลบ comments เก่า
- `chore: remove old team system completely` - ลบระบบเก่าทั้งหมด (commit นี้)

---

**หมายเหตุ:** เอกสารนี้สร้างขึ้นเพื่อให้ผู้พัฒนาในอนาคตเข้าใจว่าทำไมระบบบางส่วนถึงหายไป และใช้อะไรแทน
