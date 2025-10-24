# 🔧 คู่มือระบบ Database Migration & System Update

## สารบัญ

1. [ภาพรวม](#ภาพรวม)
2. [คุณสมบัติหลัก](#คุณสมบัติหลัก)
3. [การใช้งานสำหรับ Super Admin](#การใช้งานสำหรับ-super-admin)
4. [Database Backup System](#database-backup-system)
5. [System Update Process](#system-update-process)
6. [Maintenance Mode](#maintenance-mode)
7. [Rollback System](#rollback-system)
8. [Best Practices](#best-practices)

---

## ภาพรวม

ระบบนี้ช่วยให้ Super Admin สามารถอัพเดทระบบและฐานข้อมูลอย่างปลอดภัยและเป็นมืออาชีพ พร้อมระบบสำรองข้อมูลอัตโนมัติและการแสดงความคืบหน้าแบบเรียลไทม์

### จุดเด่น

✅ **Automatic Backup** - สำรองฐานข้อมูลอัตโนมัติก่อนอัพเดท
✅ **Progress Tracking** - ติดตามความคืบหน้าแบบเรียลไทม์
✅ **Maintenance Mode** - ปิดระบบชั่วคราวระหว่างอัพเดท
✅ **Rollback Support** - ย้อนกลับได้ถ้าเกิดปัญหา
✅ **Error Logging** - บันทึก Error แต่ละขั้นตอน
✅ **Health Checks** - ตรวจสอบสุขภาพระบบ

---

## คุณสมบัติหลัก

### 1. Database Backup System

ระบบสำรองฐานข้อมูลที่ครบครัน:

- **Full Backup** - สำรองข้อมูลทั้งหมด
- **Gzip Compression** - บีบอัดเพื่อประหยัดพื้นที่
- **Auto-verification** - ตรวจสอบความถูกต้องอัตโนมัติ
- **Auto-cleanup** - ลบไฟล์สำรองเก่าอัตโนมัติ
- **Download & Restore** - ดาวน์โหลดและคืนค่าได้

### 2. System Update System

ระบบอัพเดทที่ปลอดภัย:

- **Version Tracking** - ติดตามเวอร์ชันระบบ
- **Migration Runner** - รัน Migration อัตโนมัติ
- **Step-by-step Progress** - แสดงความคืบหน้าทุกขั้นตอน
- **Error Handling** - จัดการ Error อย่างเป็นระบบ
- **Downtime Tracking** - ติดตามระยะเวลาปิดระบบ

### 3. Maintenance Mode

โหมดปิดปรับปรุงระบบ:

- **Scheduled Maintenance** - กำหนดเวลาล่วงหน้า
- **Custom Message** - ข้อความแจ้งผู้ใช้แบบกำหนดเอง
- **IP Whitelist** - อนุญาตให้ Admin เข้าถึงได้
- **Auto Enable/Disable** - เปิด/ปิดอัตโนมัติ

---

## การใช้งานสำหรับ Super Admin

### 1. สร้าง Backup Manual

```php
use App\Services\System\DatabaseBackupService;

$backupService = app(DatabaseBackupService::class);

$backup = $backupService->createBackup(
    createdBy: auth()->id(),
    triggerType: 'manual',
    notes: 'Backup before major update'
);

// ผลลัพธ์
echo "Backup created: {$backup->backup_name}";
echo "Size: {$backup->getHumanReadableSize()}";
echo "Path: {$backup->backup_path}";
```

### 2. Restore Backup

```php
$backup = DatabaseBackup::find($backupId);

try {
    $backupService->restoreBackup($backup);
    echo "Database restored successfully!";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}";
}
```

### 3. Download Backup

```php
$backup = DatabaseBackup::find($backupId);
$filePath = $backupService->downloadBackup($backup);

return response()->download($filePath);
```

### 4. สร้าง System Update

```php
use App\Services\System\SystemUpdateService;

$updateService = app(SystemUpdateService::class);

$update = $updateService->createUpdate(
    versionFrom: '1.0.0',
    versionTo: '1.1.0',
    description: 'Added new features and bug fixes',
    initiatedBy: auth()->id(),
    changes: [
        'Added Feature Manager system',
        'Added LINE OA KYC system',
        'Improved performance',
    ],
    updateType: 'minor', // major, minor, patch, hotfix
    requiresDowntime: true
);
```

### 5. Execute Update

```php
try {
    $updateService->executeUpdate($update);

    echo "Update completed successfully!";
    echo "Version: {$update->version_to}";
    echo "Duration: {$update->duration_seconds} seconds";
} catch (\Exception $e) {
    echo "Update failed: {$e->getMessage()}";

    // Rollback if needed
    $updateService->rollbackUpdate(
        $update,
        "Error: {$e->getMessage()}",
        auth()->id()
    );
}
```

### 6. Check for Updates

```php
$updateInfo = $updateService->checkForUpdates();

/*
[
    'current_version' => '1.0.0',
    'has_updates' => true,
    'pending_migrations' => 3,
    'migrations' => [
        '2024_01_15_000014_create_vendor_features_tables.php',
        '2024_01_16_000015_create_line_oa_kyc_tables.php',
        '2024_01_17_000016_create_system_management_tables.php',
    ]
]
*/
```

---

## Database Backup System

### Interface ใน Admin Panel

```blade
<!-- resources/views/admin/backups/index.blade.php -->
<div class="backups-page">
    <div class="page-header">
        <h1>Database Backups</h1>
        <button onclick="createBackup()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Backup
        </button>
    </div>

    <div class="backups-stats">
        <div class="stat-card">
            <h3>{{ $totalBackups }}</h3>
            <p>Total Backups</p>
        </div>
        <div class="stat-card">
            <h3>{{ $totalSize }}</h3>
            <p>Total Size</p>
        </div>
        <div class="stat-card">
            <h3>{{ $latestBackup?->created_at?->diffForHumans() }}</h3>
            <p>Latest Backup</p>
        </div>
    </div>

    <table class="backups-table">
        <thead>
            <tr>
                <th>Backup Name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($backups as $backup)
            <tr>
                <td>{{ $backup->backup_name }}</td>
                <td>
                    <span class="badge badge-{{ $backup->trigger_type }}">
                        {{ $backup->trigger_type }}
                    </span>
                </td>
                <td>{{ $backup->getHumanReadableSize() }}</td>
                <td>
                    <span class="badge badge-{{ $backup->status }}">
                        {{ $backup->status }}
                    </span>
                    @if($backup->is_verified)
                    <i class="fas fa-check-circle text-success"></i>
                    @endif
                </td>
                <td>{{ $backup->created_at->format('Y-m-d H:i:s') }}</td>
                <td>
                    <a href="{{ route('admin.backups.download', $backup) }}"
                       class="btn btn-sm btn-info">
                        <i class="fas fa-download"></i>
                    </a>
                    <button onclick="restoreBackup({{ $backup->id }})"
                            class="btn btn-sm btn-warning">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button onclick="deleteBackup({{ $backup->id }})"
                            class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

### JavaScript สำหรับจัดการ Backups

```javascript
async function createBackup() {
    if (!confirm('Are you sure you want to create a backup?')) {
        return;
    }

    showLoader('Creating backup...');

    try {
        const response = await fetch('/admin/backups/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                notes: prompt('Enter backup notes (optional):')
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Backup created successfully!', 'success');
            location.reload();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Error creating backup: ' + error.message, 'error');
    } finally {
        hideLoader();
    }
}

async function restoreBackup(backupId) {
    if (!confirm('WARNING: This will restore the database to this backup. All current data will be lost. Continue?')) {
        return;
    }

    if (!confirm('Are you ABSOLUTELY sure? This action cannot be undone!')) {
        return;
    }

    showLoader('Restoring backup...');

    try {
        const response = await fetch(`/admin/backups/${backupId}/restore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Backup restored successfully!', 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Error restoring backup: ' + error.message, 'error');
    } finally {
        hideLoader();
    }
}
```

---

## System Update Process

### Interface ใน Admin Panel

```blade
<!-- resources/views/admin/updates/index.blade.php -->
<div class="updates-page">
    <div class="current-version">
        <h2>Current Version: {{ $currentVersion }}</h2>

        @if($hasUpdates)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            There are {{ $pendingMigrations }} pending migrations available.
            <button onclick="showUpdateModal()" class="btn btn-primary">
                Update Now
            </button>
        </div>
        @else
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Your system is up to date!
        </div>
        @endif
    </div>

    <div class="update-history">
        <h3>Update History</h3>

        @foreach($updates as $update)
        <div class="update-card">
            <div class="update-header">
                <h4>{{ $update->version_from }} → {{ $update->version_to }}</h4>
                <span class="badge badge-{{ $update->status }}">
                    {{ $update->status }}
                </span>
            </div>

            <p>{{ $update->description }}</p>

            <div class="update-meta">
                <span><i class="fas fa-user"></i> {{ $update->initiator->name }}</span>
                <span><i class="fas fa-clock"></i> {{ $update->created_at->diffForHumans() }}</span>
                <span><i class="fas fa-stopwatch"></i> {{ $update->duration_seconds }}s</span>
            </div>

            @if($update->status === 'running')
            <div class="progress">
                <div class="progress-bar" style="width: {{ $update->progress_percentage }}%">
                    {{ $update->completed_steps }}/{{ $update->total_steps }}
                </div>
            </div>
            @endif

            @if($update->status === 'failed')
            <div class="alert alert-danger">
                <strong>Error:</strong> {{ $update->error_message }}
                <button onclick="rollbackUpdate({{ $update->id }})"
                        class="btn btn-sm btn-warning">
                    <i class="fas fa-undo"></i> Rollback
                </button>
            </div>
            @endif

            <button onclick="viewUpdateDetails({{ $update->id }})"
                    class="btn btn-sm btn-secondary">
                View Details
            </button>
        </div>
        @endforeach
    </div>
</div>
```

### Update Modal

```blade
<!-- Update Modal -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <h2>System Update</h2>

        <form id="updateForm">
            <div class="form-group">
                <label>Version From</label>
                <input type="text" value="{{ $currentVersion }}" readonly>
            </div>

            <div class="form-group">
                <label>Version To</label>
                <input type="text" name="version_to" required
                       placeholder="e.g., 1.1.0">
            </div>

            <div class="form-group">
                <label>Update Type</label>
                <select name="update_type" required>
                    <option value="patch">Patch (Bug fixes)</option>
                    <option value="minor">Minor (New features)</option>
                    <option value="major">Major (Breaking changes)</option>
                    <option value="hotfix">Hotfix (Urgent fix)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required
                          placeholder="Describe what's new in this update"></textarea>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="requires_downtime">
                    Requires Downtime (Enable maintenance mode)
                </label>
            </div>

            <div class="form-group">
                <label>Pending Migrations ({{ count($pendingMigrations) }})</label>
                <ul class="migration-list">
                    @foreach($pendingMigrations as $migration)
                    <li>{{ $migration }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="form-actions">
                <button type="button" onclick="closeUpdateModal()"
                        class="btn btn-secondary">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Start Update
                </button>
            </div>
        </form>

        <div id="updateProgress" style="display:none;">
            <h3>Update in Progress...</h3>
            <div class="progress">
                <div id="progressBar" class="progress-bar"></div>
            </div>
            <div id="currentStep"></div>
            <div id="migrationLogs"></div>
        </div>
    </div>
</div>
```

### JavaScript สำหรับ Update Process

```javascript
async function executeUpdate(formData) {
    const updateProgress = document.getElementById('updateProgress');
    updateProgress.style.display = 'block';

    try {
        // Create update
        const createResponse = await fetch('/admin/updates/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(formData)
        });

        const createData = await createResponse.json();
        const updateId = createData.data.id;

        // Execute update
        const executeResponse = await fetch(`/admin/updates/${updateId}/execute`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });

        // Start polling for progress
        pollUpdateProgress(updateId);

    } catch (error) {
        showAlert('Error: ' + error.message, 'error');
        updateProgress.style.display = 'none';
    }
}

function pollUpdateProgress(updateId) {
    const interval = setInterval(async () => {
        try {
            const response = await fetch(`/admin/updates/${updateId}/progress`);
            const data = await response.json();

            const progressBar = document.getElementById('progressBar');
            const currentStep = document.getElementById('currentStep');

            progressBar.style.width = data.progress_percentage + '%';
            progressBar.textContent = data.completed_steps + '/' + data.total_steps;

            currentStep.textContent = data.current_step || 'Processing...';

            if (data.status === 'completed') {
                clearInterval(interval);
                showAlert('Update completed successfully!', 'success');
                setTimeout(() => location.reload(), 2000);
            } else if (data.status === 'failed') {
                clearInterval(interval);
                showAlert('Update failed: ' + data.error_message, 'error');
            }
        } catch (error) {
            console.error('Error polling progress:', error);
        }
    }, 2000); // Poll every 2 seconds
}
```

---

## Maintenance Mode

### เปิด Maintenance Mode

```php
use App\Models\SystemMaintenanceMode;

$maintenance = SystemMaintenanceMode::create([
    'title' => 'System Maintenance',
    'message' => 'We are performing system updates. We will be back soon!',
    'scheduled_start' => now(),
    'scheduled_end' => now()->addHours(2),
    'created_by' => auth()->id(),
]);

$maintenance->activate();
```

### ปิด Maintenance Mode

```php
$maintenance = SystemMaintenanceMode::getActive();
if ($maintenance) {
    $maintenance->deactivate();
}
```

### Middleware สำหรับ Maintenance Mode

```php
// app/Http/Middleware/CheckMaintenanceMode.php
public function handle($request, Closure $next)
{
    $maintenance = SystemMaintenanceMode::getActive();

    if (!$maintenance) {
        return $next($request);
    }

    // Allow whitelisted IPs
    if ($maintenance->isIpAllowed($request->ip())) {
        return $next($request);
    }

    // Allow whitelisted users
    if (auth()->check() && $maintenance->isUserAllowed(auth()->id())) {
        return $next($request);
    }

    // Show maintenance page
    return response()->view('maintenance', [
        'maintenance' => $maintenance
    ], 503);
}
```

---

## Rollback System

### Automatic Rollback

```php
try {
    $updateService->executeUpdate($update);
} catch (\Exception $e) {
    // Auto rollback on failure
    $updateService->rollbackUpdate(
        $update,
        "Automatic rollback due to error: {$e->getMessage()}",
        auth()->id()
    );

    throw $e;
}
```

### Manual Rollback

```php
$update = SystemUpdate::find($updateId);

$updateService->rollbackUpdate(
    $update,
    "Manual rollback requested by admin",
    auth()->id()
);
```

---

## Best Practices

### 1. ก่อนทำ Update ทุกครั้ง

- ✅ สร้าง Backup ก่อนเสมอ
- ✅ ทดสอบบน Development/Staging environment ก่อน
- ✅ อ่าน Release notes ให้ละเอียด
- ✅ แจ้งผู้ใช้ล่วงหน้า
- ✅ เลือกเวลาที่ผู้ใช้น้อย (กลางคืน/วันหยุด)

### 2. ระหว่าง Update

- ✅ เปิด Maintenance Mode
- ✅ ติดตาม Progress แบบ Real-time
- ✅ บันทึก Logs ทั้งหมด
- ✅ มีทีม Stand-by กรณีเกิดปัญหา

### 3. หลัง Update

- ✅ ทดสอบ Critical features
- ✅ ตรวจสอบ Error logs
- ✅ Monitor performance
- ✅ เก็บ Backup ไว้อย่างน้อย 7 วัน
- ✅ แจ้งผู้ใช้ว่าระบบกลับมาแล้ว

### 4. Backup Strategy

- ✅ Daily auto backup
- ✅ Pre-update backup (manual)
- ✅ Keep backups for 30 days
- ✅ Store off-site (AWS S3, Google Cloud Storage)
- ✅ Test restore regularly

---

## Scheduled Tasks

### app/Console/Kernel.php

```php
protected function schedule(Schedule $schedule)
{
    // Daily auto backup at 2 AM
    $schedule->call(function () {
        app(DatabaseBackupService::class)->createBackup(
            null,
            'auto',
            'Daily automatic backup'
        );
    })->dailyAt('02:00');

    // Clean old backups (older than 30 days)
    $schedule->call(function () {
        DatabaseBackup::where('auto_delete', true)
            ->where('delete_after', '<', now())
            ->each(function ($backup) {
                $backup->deleteFile();
                $backup->delete();
            });
    })->daily();

    // Health checks every 5 minutes
    $schedule->call(function () {
        // Check database
        SystemHealthCheck::create([
            'check_type' => 'database',
            'status' => DB::connection()->getPdo() ? 'healthy' : 'down',
            'checked_at' => now(),
        ]);

        // Check storage
        $freeSpace = disk_free_space(storage_path());
        SystemHealthCheck::create([
            'check_type' => 'storage',
            'status' => $freeSpace > 1073741824 ? 'healthy' : 'warning', // 1GB
            'metrics' => ['free_space' => $freeSpace],
            'checked_at' => now(),
        ]);
    })->everyFiveMinutes();
}
```

---

Made with ❤️ by ThaiPrompt Team
