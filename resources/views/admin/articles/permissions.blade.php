@extends('layouts.admin')

@section('title', 'จัดการสิทธิ์ - ' . $article->title)

@push('styles')
<style>
:root { --primary: #4f46e5; --primary-dark: #4338ca; }
.af-container { max-width: 1200px; margin: 0 auto; }
.af-header { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border-radius: 16px; padding: 32px; color: white; margin-bottom: 32px; }
.af-form { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.af-section { padding: 32px; border-bottom: 1px solid #e5e7eb; }
.af-section:last-child { border-bottom: none; }
.af-section-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 20px; }
.af-form-group { margin-bottom: 24px; }
.af-label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px; }
.af-input, .af-select { width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.2s; }
.af-input:focus, .af-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
.af-btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; }
.af-btn-primary { background: var(--primary); color: white; }
.af-btn-primary:hover { background: var(--primary-dark); }
.af-btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.af-btn-danger { background: #ef4444; color: white; }
.af-btn-danger:hover { background: #dc2626; }
.af-btn-sm { padding: 8px 16px; font-size: 13px; }
.permission-item { display: flex; gap: 12px; padding: 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 12px; align-items: center; }
.permission-item .flex-grow { flex-grow: 1; }
.info-box { padding: 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; margin-bottom: 24px; }
.info-box-icon { font-size: 20px; margin-right: 12px; }
.info-box-text { color: #1e40af; font-size: 14px; line-height: 1.5; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="af-container">
        <!-- Header -->
        <div class="af-header">
            <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">จัดการสิทธิ์การเข้าถึง</h1>
            <p style="opacity: 0.9; margin: 0;">{{ $article->title }}</p>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('admin.articles.permissions.update', $article) }}">
            @csrf
            @method('PUT')

            <div class="af-form">
                <!-- Info Box -->
                <div class="af-section">
                    <div class="info-box">
                        <div style="display: flex; align-items: start;">
                            <span class="info-box-icon">ℹ️</span>
                            <div class="info-box-text">
                                <strong>หมายเหตุ:</strong> หากไม่มีการกำหนดสิทธิ์ใดๆ บทความนี้จะเปิดให้ทุกคนเข้าถึงได้
                                <br>เมื่อมีการกำหนดสิทธิ์แล้ว จะมีเฉพาะบทบาทหรือผู้ใช้ที่ระบุเท่านั้นที่สามารถเข้าถึงได้
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="af-section">
                    <h2 class="af-section-title">สิทธิ์การเข้าถึง</h2>

                    <div id="permissions-container">
                        @forelse($article->permissions as $index => $permission)
                            <div class="permission-item">
                                <div class="flex-grow">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="af-label">ประเภท</label>
                                            <select name="permissions[{{ $index }}][type]" class="af-select" required>
                                                <option value="role" {{ $permission->permission_type === 'role' ? 'selected' : '' }}>บทบาท</option>
                                                <option value="user" {{ $permission->permission_type === 'user' ? 'selected' : '' }}>ผู้ใช้เฉพาะ</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="af-label">ค่า</label>
                                            <input type="text" name="permissions[{{ $index }}][value]"
                                                   class="af-input"
                                                   value="{{ $permission->permission_value }}"
                                                   placeholder="เช่น admin, user, seller หรือ user ID"
                                                   required>
                                            <small style="color: #6b7280;">สำหรับบทบาท: admin, user, seller / สำหรับผู้ใช้: ระบุ User ID</small>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="af-btn af-btn-danger af-btn-sm" onclick="removePermission(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="permission-item">
                                <div class="flex-grow">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="af-label">ประเภท</label>
                                            <select name="permissions[0][type]" class="af-select" required>
                                                <option value="role">บทบาท</option>
                                                <option value="user">ผู้ใช้เฉพาะ</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="af-label">ค่า</label>
                                            <input type="text" name="permissions[0][value]"
                                                   class="af-input"
                                                   placeholder="เช่น admin, user, seller หรือ user ID"
                                                   required>
                                            <small style="color: #6b7280;">สำหรับบทบาท: admin, user, seller / สำหรับผู้ใช้: ระบุ User ID</small>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="af-btn af-btn-danger af-btn-sm" onclick="removePermission(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <button type="button" class="af-btn af-btn-outline" onclick="addPermission()">
                        <i class="fas fa-plus"></i> เพิ่มสิทธิ์
                    </button>
                </div>

                <!-- Common Roles Reference -->
                <div class="af-section">
                    <h2 class="af-section-title">บทบาทที่มีในระบบ</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div style="padding: 16px; background: #f3f4f6; border-radius: 8px;">
                            <div style="font-weight: 600; margin-bottom: 4px;">admin</div>
                            <small style="color: #6b7280;">ผู้ดูแลระบบ</small>
                        </div>
                        <div style="padding: 16px; background: #f3f4f6; border-radius: 8px;">
                            <div style="font-weight: 600; margin-bottom: 4px;">seller</div>
                            <small style="color: #6b7280;">ผู้ขาย</small>
                        </div>
                        <div style="padding: 16px; background: #f3f4f6; border-radius: 8px;">
                            <div style="font-weight: 600; margin-bottom: 4px;">user</div>
                            <small style="color: #6b7280;">ผู้ใช้ทั่วไป</small>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="af-section" style="background: #f9fafb;">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.articles.edit', $article) }}" class="af-btn af-btn-outline">
                            <i class="fas fa-arrow-left"></i> กลับ
                        </a>
                        <button type="submit" class="af-btn af-btn-primary">
                            <i class="fas fa-save"></i> บันทึกสิทธิ์
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let permissionIndex = {{ $article->permissions->count() }};

function addPermission() {
    const container = document.getElementById('permissions-container');
    const newPermission = document.createElement('div');
    newPermission.className = 'permission-item';
    newPermission.innerHTML = `
        <div class="flex-grow">
            <div class="row">
                <div class="col-md-4">
                    <label class="af-label">ประเภท</label>
                    <select name="permissions[${permissionIndex}][type]" class="af-select" required>
                        <option value="role">บทบาท</option>
                        <option value="user">ผู้ใช้เฉพาะ</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="af-label">ค่า</label>
                    <input type="text" name="permissions[${permissionIndex}][value]"
                           class="af-input"
                           placeholder="เช่น admin, user, seller หรือ user ID"
                           required>
                    <small style="color: #6b7280;">สำหรับบทบาท: admin, user, seller / สำหรับผู้ใช้: ระบุ User ID</small>
                </div>
            </div>
        </div>
        <div>
            <button type="button" class="af-btn af-btn-danger af-btn-sm" onclick="removePermission(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newPermission);
    permissionIndex++;
}

function removePermission(button) {
    const item = button.closest('.permission-item');
    const container = document.getElementById('permissions-container');

    // Keep at least one permission item visible for better UX
    if (container.children.length > 1) {
        item.remove();
    } else {
        // Clear the inputs instead
        const inputs = item.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        });
    }
}
</script>
@endpush
