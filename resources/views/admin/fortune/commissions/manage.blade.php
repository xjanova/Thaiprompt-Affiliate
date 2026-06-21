@extends('layouts.admin-v4')

@section('title', 'จัดการคอมมิชชั่นดูดวง')

@section('content')
{{-- ธีม V4 "นวลทองคำ" — หน้าจัดการคอมมิชชั่นดูดวง (อนุมัติ/ปฏิเสธ/ปรับยอด/จ่าย/สร้างเอง + ตั้งค่า) --}}
<div x-data="commissionManager()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · คอมมิชชั่น
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;color:var(--ink);">
                <i class="fas fa-sliders-h" style="color:var(--accent1);margin-right:8px;"></i>จัดการคอมมิชชั่นดูดวง
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                อนุมัติ จ่ายเงิน ปรับจำนวน และตั้งค่าคอมมิชชั่น
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.fortune.commissions.index'))
            <a href="{{ route('admin.fortune.commissions.index') }}" class="tp-btn">
                <i class="fas fa-chart-pie"></i> ภาพรวม
            </a>
            @endif
            <button @click="showCreateModal = true" class="tp-btn tp-btn-primary">
                <i class="fas fa-plus"></i> สร้างมือ
            </button>
        </div>
    </div>

    {{-- ===== SETTINGS PANEL (Collapsible) ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <button @click="showSettings = !showSettings"
                style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px;background:transparent;border:0;cursor:pointer;text-align:left;color:var(--ink);">
            <div style="display:flex;align-items:center;gap:14px;">
                <span style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:var(--inset-sm);color:var(--accent1);font-size:18px;">
                    <i class="fas fa-cog"></i>
                </span>
                <div>
                    <div style="font-weight:700;font-size:15px;color:var(--ink);">ตั้งค่าคอมมิชชั่น</div>
                    <div class="tp-muted" style="font-size:12px;margin-top:2px;">
                        L1: {{ $settings?->fortune_level1_commission_type === 'percent' ? ($settings->fortune_level1_commission_amount ?? 10).'%' : '฿'.($settings->fortune_level1_commission_amount ?? 10) }}
                        &nbsp;|&nbsp;
                        L2: {{ $settings?->isFortuneLevel2Enabled() ? ($settings->fortune_level2_commission_type === 'percent' ? ($settings->fortune_level2_commission_amount ?? 5).'%' : '฿'.($settings->fortune_level2_commission_amount ?? 5)) : 'ปิด' }}
                    </div>
                </div>
            </div>
            <i class="fas fa-chevron-down" :style="showSettings ? 'transform:rotate(180deg);transition:transform .25s;color:var(--accent1);' : 'transition:transform .25s;color:var(--ink2);'"></i>
        </button>

        <div x-show="showSettings" x-collapse class="tp-divider" style="margin:0;">
            <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">

                {{-- Level 1 Settings --}}
                <div class="tp-tile" style="padding:16px;">
                    <h4 style="font-weight:700;font-size:14px;margin:0 0 12px;color:var(--ink);">
                        <i class="fas fa-user" style="color:#5689b8;margin-right:6px;"></i>Level 1 — สายตรง
                    </h4>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div>
                            <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">ประเภท</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <select x-model="settingsForm.fortune_level1_commission_type"
                                        style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                                    <option value="fixed">คงที่ (บาท)</option>
                                    <option value="percent">เปอร์เซ็นต์ (%)</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">
                                จำนวน <span x-text="settingsForm.fortune_level1_commission_type === 'percent' ? '(%)' : '(บาท)'"></span>
                            </label>
                            <div class="tp-well tp-input">
                                <input type="number" step="0.01" min="0"
                                       x-model="settingsForm.fortune_level1_commission_amount"
                                       style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Level 2 Settings --}}
                <div class="tp-tile" style="padding:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <h4 style="font-weight:700;font-size:14px;margin:0;color:var(--ink);">
                            <i class="fas fa-users" style="color:#b79ae8;margin-right:6px;"></i>Level 2 — ชั้นหลาน
                        </h4>
                        <label style="position:relative;display:inline-flex;align-items:center;cursor:pointer;">
                            <input type="checkbox" x-model="settingsForm.fortune_level2_enabled" style="position:absolute;opacity:0;width:0;height:0;">
                            <span :style="settingsForm.fortune_level2_enabled
                                    ? 'width:42px;height:24px;border-radius:999px;background:var(--accent1);position:relative;transition:.25s;box-shadow:var(--inset-sm);'
                                    : 'width:42px;height:24px;border-radius:999px;background:var(--sd);position:relative;transition:.25s;box-shadow:var(--inset-sm);'">
                                <span :style="settingsForm.fortune_level2_enabled
                                        ? 'position:absolute;top:2px;left:20px;width:20px;height:20px;border-radius:50%;background:#fff;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.3);'
                                        : 'position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#fff;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.3);'"></span>
                            </span>
                        </label>
                    </div>
                    <div x-show="settingsForm.fortune_level2_enabled" style="display:flex;flex-direction:column;gap:12px;">
                        <div>
                            <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">ประเภท</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <select x-model="settingsForm.fortune_level2_commission_type"
                                        style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                                    <option value="fixed">คงที่ (บาท)</option>
                                    <option value="percent">เปอร์เซ็นต์ (%)</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">
                                จำนวน <span x-text="settingsForm.fortune_level2_commission_type === 'percent' ? '(%)' : '(บาท)'"></span>
                            </label>
                            <div class="tp-well tp-input">
                                <input type="number" step="0.01" min="0"
                                       x-model="settingsForm.fortune_level2_commission_amount"
                                       style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                            </div>
                        </div>
                    </div>
                    <div x-show="!settingsForm.fortune_level2_enabled" class="tp-muted" style="font-size:13px;padding:8px 0;">
                        Level 2 ปิดอยู่ — ไม่จ่ายคอมมิชชั่นชั้นหลาน
                    </div>
                </div>
            </div>
            <div style="padding:0 20px 20px;display:flex;justify-content:flex-end;">
                <button @click="saveSettings()" :disabled="savingSettings" class="tp-btn tp-btn-primary">
                    <span x-show="!savingSettings"><i class="fas fa-save"></i> บันทึกการตั้งค่า</span>
                    <span x-show="savingSettings"><i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== KPI GRID ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        {{-- รอดำเนินการ --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:var(--inset-sm);color:#e0a52e;font-size:18px;">
                    <i class="fas fa-hourglass-half"></i>
                </span>
                <div style="flex:1;">
                    <div class="tp-muted" style="font-size:12px;">รอดำเนินการ</div>
                    <div class="tp-num" style="font-size:24px;font-weight:800;color:var(--ink);">{{ number_format($stats['pending_count']) }}</div>
                </div>
            </div>
            <div style="margin-top:10px;font-size:14px;font-weight:700;color:#e0a52e;">฿{{ number_format($stats['pending_amount'], 2) }}</div>
        </div>

        {{-- อนุมัติแล้ว (รอจ่าย) --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:var(--inset-sm);color:#5aa07e;font-size:18px;">
                    <i class="fas fa-check-circle"></i>
                </span>
                <div style="flex:1;">
                    <div class="tp-muted" style="font-size:12px;">อนุมัติแล้ว (รอจ่าย)</div>
                    <div class="tp-num" style="font-size:24px;font-weight:800;color:var(--ink);">{{ number_format($stats['approved_count']) }}</div>
                </div>
            </div>
            <div style="margin-top:10px;font-size:14px;font-weight:700;color:#5aa07e;">฿{{ number_format($stats['approved_amount'], 2) }}</div>
        </div>

        {{-- จ่ายแล้ว --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:var(--inset-sm);color:#5689b8;font-size:18px;">
                    <i class="fas fa-money-bill-wave"></i>
                </span>
                <div style="flex:1;">
                    <div class="tp-muted" style="font-size:12px;">จ่ายแล้ว</div>
                    <div class="tp-num" style="font-size:24px;font-weight:800;color:var(--ink);">{{ number_format($stats['paid_count']) }}</div>
                </div>
            </div>
            <div style="margin-top:10px;font-size:14px;font-weight:700;color:#5689b8;">฿{{ number_format($stats['paid_amount'], 2) }}</div>
        </div>
    </div>

    {{-- ===== FILTERS ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:14px;">
            <i class="fas fa-filter" style="color:var(--accent1);margin-right:6px;"></i>กรองข้อมูล
        </div>
        <form method="GET" action="{{ route('admin.fortune.commissions.manage') }}"
              style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;align-items:end;">
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">สถานะ</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="status" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                        <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                        <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                        <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">ชั้น</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="level" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        <option value="all" {{ ($filters['level'] ?? 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="1" {{ ($filters['level'] ?? '') == '1' ? 'selected' : '' }}>L1 สายตรง</option>
                        <option value="2" {{ ($filters['level'] ?? '') == '2' ? 'selected' : '' }}>L2 ชั้นหลาน</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">วันที่เริ่มต้น</label>
                <div class="tp-well tp-input">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                </div>
            </div>
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">วันที่สิ้นสุด</label>
                <div class="tp-well tp-input">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-search"></i> กรอง
                </button>
                <a href="{{ route('admin.fortune.commissions.manage') }}" class="tp-btn">
                    <i class="fas fa-eraser"></i> ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- ===== BULK ACTION BAR ===== --}}
    <div x-show="selectedIds.length > 0" x-transition class="tp-card tp-raise"
         style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;border:1px solid var(--a1soft);">
        <div style="font-weight:700;color:var(--ink);">
            <i class="fas fa-check-square" style="color:var(--accent1);margin-right:6px;"></i>
            เลือก <span class="tp-num" x-text="selectedIds.length"></span> รายการ
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button @click="bulkAction('approve')" :disabled="processing" class="tp-btn tp-btn-sm" style="color:#5aa07e;">
                <i class="fas fa-check"></i> อนุมัติที่เลือก
            </button>
            <button @click="bulkAction('pay')" :disabled="processing" class="tp-btn tp-btn-sm" style="color:#5689b8;">
                <i class="fas fa-money-bill-wave"></i> จ่ายที่เลือก
            </button>
            <button @click="selectedIds = []" class="tp-btn tp-btn-sm">
                <i class="fas fa-times"></i> ยกเลิกเลือก
            </button>
        </div>
    </div>

    {{-- ===== COMMISSION TABLE ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:separate;border-spacing:0 8px;padding:0 16px;">
                <thead>
                    <tr style="color:var(--ink2);font-size:12px;text-transform:uppercase;letter-spacing:.04em;">
                        <th style="padding:12px 10px;text-align:left;">
                            <input type="checkbox" @change="toggleAll($event)" style="width:16px;height:16px;cursor:pointer;accent-color:var(--accent1);">
                        </th>
                        <th style="padding:12px 10px;text-align:left;font-weight:600;">ผู้รับ</th>
                        <th style="padding:12px 10px;text-align:left;font-weight:600;">จากผู้ใช้</th>
                        <th style="padding:12px 10px;text-align:left;font-weight:600;">ชั้น</th>
                        <th style="padding:12px 10px;text-align:right;font-weight:600;">จำนวน (฿)</th>
                        <th style="padding:12px 10px;text-align:left;font-weight:600;">สถานะ</th>
                        <th style="padding:12px 10px;text-align:left;font-weight:600;">วันที่</th>
                        <th style="padding:12px 10px;text-align:right;font-weight:600;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $c)
                        <tr style="box-shadow:var(--inset-sm);border-radius:12px;">
                            {{-- Checkbox --}}
                            <td style="padding:14px 10px;border-radius:12px 0 0 12px;">
                                <input type="checkbox" :value="{{ $c->id }}" x-model="selectedIds"
                                       style="width:16px;height:16px;cursor:pointer;accent-color:var(--accent1);">
                            </td>

                            {{-- ผู้รับ --}}
                            <td style="padding:14px 10px;white-space:nowrap;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span style="width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:var(--inset-sm);color:var(--accent1);font-weight:700;font-size:13px;">
                                        {{ mb_substr($c->user?->name ?? '?', 0, 1) }}
                                    </span>
                                    <div>
                                        <div style="font-weight:600;color:var(--ink);font-size:14px;">{{ Str::limit($c->user?->name ?? '-', 18) }}</div>
                                        <div class="tp-muted" style="font-size:12px;">{{ Str::limit($c->user?->email ?? '', 22) }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- จากผู้ใช้ --}}
                            <td style="padding:14px 10px;white-space:nowrap;color:var(--ink);font-size:14px;">
                                {{ Str::limit($c->fromUser?->name ?? '-', 18) }}
                            </td>

                            {{-- ชั้น --}}
                            <td style="padding:14px 10px;white-space:nowrap;">
                                @if($c->level === 1)
                                    <span class="tp-pill" style="color:#5689b8;">L1</span>
                                @else
                                    <span class="tp-pill" style="color:#b79ae8;">L2</span>
                                @endif
                            </td>

                            {{-- จำนวน --}}
                            <td class="tp-num" style="padding:14px 10px;white-space:nowrap;text-align:right;font-weight:800;color:var(--ink);font-size:14px;">
                                ฿{{ number_format($c->amount, 2) }}
                            </td>

                            {{-- สถานะ --}}
                            <td style="padding:14px 10px;white-space:nowrap;">
                                @switch($c->status)
                                    @case('pending')
                                        <span class="tp-pill" style="color:#e0a52e;"><i class="fas fa-hourglass-half" style="margin-right:4px;"></i>รอ</span>
                                        @break
                                    @case('approved')
                                        <span class="tp-pill" style="color:#5aa07e;"><i class="fas fa-check" style="margin-right:4px;"></i>อนุมัติ</span>
                                        @break
                                    @case('paid')
                                        <span class="tp-pill" style="color:#5689b8;"><i class="fas fa-money-bill-wave" style="margin-right:4px;"></i>จ่ายแล้ว</span>
                                        @break
                                    @case('rejected')
                                        <span class="tp-pill" style="color:#d9534f;"><i class="fas fa-times" style="margin-right:4px;"></i>ปฏิเสธ</span>
                                        @break
                                @endswitch
                            </td>

                            {{-- วันที่ --}}
                            <td style="padding:14px 10px;white-space:nowrap;color:var(--ink);font-size:14px;">
                                <div class="tp-num">{{ $c->created_at?->format('d/m/Y') }}</div>
                                <div class="tp-muted" style="font-size:12px;">{{ $c->created_at?->format('H:i') }}</div>
                            </td>

                            {{-- จัดการ --}}
                            <td style="padding:14px 10px;white-space:nowrap;text-align:right;border-radius:0 12px 12px 0;">
                                <div style="display:flex;justify-content:flex-end;gap:6px;">
                                    @if($c->status === 'pending')
                                        <button @click="approveOne({{ $c->id }})" class="tp-btn tp-btn-sm" style="color:#5aa07e;">
                                            อนุมัติ
                                        </button>
                                        <button @click="openRejectModal({{ $c->id }})" class="tp-btn tp-btn-sm" style="color:#d9534f;">
                                            ปฏิเสธ
                                        </button>
                                    @endif
                                    @if(in_array($c->status, ['pending', 'approved']))
                                        <button @click="openAdjustModal({{ $c->id }}, {{ $c->amount }})" class="tp-btn tp-btn-sm" style="color:#e0a52e;">
                                            ปรับ
                                        </button>
                                    @endif
                                    @if($c->status === 'approved')
                                        <button @click="payOne({{ $c->id }})" class="tp-btn tp-btn-sm" style="color:#5689b8;">
                                            จ่าย
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:48px 16px;text-align:center;">
                                <div style="font-size:42px;color:var(--ink2);margin-bottom:10px;"><i class="fas fa-inbox"></i></div>
                                <p class="tp-muted" style="margin:0;">ไม่พบข้อมูลคอมมิชชั่น</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== PAGINATION ===== --}}
    @if($commissions->hasPages())
        <div>
            {{ $commissions->appends(request()->query())->links() }}
        </div>
    @endif

    {{-- ===== REJECT MODAL ===== --}}
    <div x-show="showRejectModal" x-transition.opacity
         style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;"
         @keydown.escape.window="showRejectModal = false">
        <div class="tp-card tp-raise" style="width:100%;max-width:440px;" @click.outside="showRejectModal = false">
            <h3 style="font-size:17px;font-weight:700;margin:0 0 16px;color:var(--ink);">
                <i class="fas fa-times-circle" style="color:#d9534f;margin-right:6px;"></i>ปฏิเสธคอมมิชชั่น
            </h3>
            <div style="margin-bottom:16px;">
                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">เหตุผล (ไม่บังคับ)</label>
                <div class="tp-well tp-input">
                    <textarea x-model="rejectReason" rows="3"
                              style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;resize:vertical;"
                              placeholder="ระบุเหตุผลที่ปฏิเสธ..."></textarea>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button @click="showRejectModal = false" class="tp-btn">ยกเลิก</button>
                <button @click="confirmReject()" :disabled="processing" class="tp-btn tp-btn-primary" style="background:linear-gradient(135deg,#d9534f,#b8413d);">
                    ยืนยันปฏิเสธ
                </button>
            </div>
        </div>
    </div>

    {{-- ===== ADJUST AMOUNT MODAL ===== --}}
    <div x-show="showAdjustModal" x-transition.opacity
         style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;"
         @keydown.escape.window="showAdjustModal = false">
        <div class="tp-card tp-raise" style="width:100%;max-width:440px;" @click.outside="showAdjustModal = false">
            <h3 style="font-size:17px;font-weight:700;margin:0 0 16px;color:var(--ink);">
                <i class="fas fa-pen" style="color:#e0a52e;margin-right:6px;"></i>ปรับจำนวนเงิน
            </h3>
            <div style="margin-bottom:14px;">
                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">จำนวนเงินใหม่ (บาท)</label>
                <div class="tp-well tp-input">
                    <input type="number" step="0.01" min="0" x-model="adjustAmount"
                           style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">เหตุผล (ไม่บังคับ)</label>
                <div class="tp-well tp-input">
                    <input type="text" x-model="adjustReason"
                           style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;"
                           placeholder="เหตุผลที่ปรับ...">
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button @click="showAdjustModal = false" class="tp-btn">ยกเลิก</button>
                <button @click="confirmAdjust()" :disabled="processing" class="tp-btn tp-btn-primary">
                    ยืนยันปรับ
                </button>
            </div>
        </div>
    </div>

    {{-- ===== CREATE MANUAL MODAL ===== --}}
    <div x-show="showCreateModal" x-transition.opacity
         style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;"
         @keydown.escape.window="showCreateModal = false">
        <div class="tp-card tp-raise" style="width:100%;max-width:560px;" @click.outside="showCreateModal = false">
            <h3 style="font-size:17px;font-weight:700;margin:0 0 16px;color:var(--ink);">
                <i class="fas fa-plus-circle" style="color:var(--accent1);margin-right:6px;"></i>สร้างคอมมิชชั่นด้วยมือ
            </h3>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">User ID ผู้รับ</label>
                        <div class="tp-well tp-input">
                            <input type="number" x-model="createForm.user_id"
                                   style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;"
                                   placeholder="ID ผู้รับคอมมิชชั่น">
                        </div>
                    </div>
                    <div>
                        <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">User ID ผู้จ่าย (คนดูดวง)</label>
                        <div class="tp-well tp-input">
                            <input type="number" x-model="createForm.from_user_id"
                                   style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;"
                                   placeholder="ID คนดูดวง">
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">ชั้น</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select x-model="createForm.level"
                                    style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                                <option value="1">L1 สายตรง</option>
                                <option value="2">L2 ชั้นหลาน</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">จำนวน (บาท)</label>
                        <div class="tp-well tp-input">
                            <input type="number" step="0.01" min="0.01" x-model="createForm.amount"
                                   style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">บิลดูดวง # (ไม่บังคับ)</label>
                    <div class="tp-well tp-input">
                        <input type="number" x-model="createForm.fortune_reading_id"
                               style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;"
                               placeholder="ID บิลดูดวง (ถ้ามี)">
                    </div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;">หมายเหตุ</label>
                    <div class="tp-well tp-input">
                        <textarea x-model="createForm.notes" rows="2"
                                  style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;resize:vertical;"
                                  placeholder="หมายเหตุ..."></textarea>
                    </div>
                </div>
            </div>
            <div style="margin-top:18px;display:flex;justify-content:flex-end;gap:10px;">
                <button @click="showCreateModal = false" class="tp-btn">ยกเลิก</button>
                <button @click="confirmCreate()" :disabled="processing" class="tp-btn tp-btn-primary">
                    สร้างคอมมิชชั่น
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับจัดการคอมมิชชั่น
 * (logic เดิม 100% — แค่ปรับ toast ให้แจ้งเตือนผ่าน $dispatch('notify') ของ layout V4)
 */
function commissionManager() {
    return {
        // สถานะ
        selectedIds: [],
        processing: false,
        showSettings: false,
        savingSettings: false,

        // Modals
        showRejectModal: false,
        showAdjustModal: false,
        showCreateModal: false,

        // ข้อมูล modal
        rejectId: null,
        rejectReason: '',
        adjustId: null,
        adjustAmount: 0,
        adjustReason: '',

        // ฟอร์มสร้างมือ
        createForm: {
            user_id: '',
            from_user_id: '',
            level: '1',
            amount: '',
            fortune_reading_id: '',
            notes: '',
        },

        // การตั้งค่า
        settingsForm: {
            fortune_level1_commission_type: '{{ $settings->fortune_level1_commission_type ?? "fixed" }}',
            fortune_level1_commission_amount: '{{ $settings->fortune_level1_commission_amount ?? 10 }}',
            fortune_level2_enabled: {{ ($settings?->isFortuneLevel2Enabled() ?? true) ? 'true' : 'false' }},
            fortune_level2_commission_type: '{{ $settings->fortune_level2_commission_type ?? "fixed" }}',
            fortune_level2_commission_amount: '{{ $settings->fortune_level2_commission_amount ?? 5 }}',
        },

        /**
         * แสดง toast notification ผ่านระบบ toast ของ layout V4
         */
        showToast(message, type = 'success') {
            this.$dispatch('notify', { type: type, message: message });
        },

        /**
         * เลือก/ยกเลิกเลือกทั้งหมด
         */
        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = @json($commissions->pluck('id'));
            } else {
                this.selectedIds = [];
            }
        },

        /**
         * Bulk action (อนุมัติ/จ่าย)
         */
        async bulkAction(action) {
            if (this.selectedIds.length === 0) return;

            const actionText = action === 'approve' ? 'อนุมัติ' : 'จ่าย';
            if (!confirm(`ต้องการ${actionText} ${this.selectedIds.length} รายการ?`)) return;

            this.processing = true;
            try {
                const url = action === 'approve'
                    ? '{{ route("admin.fortune.commissions.approve") }}'
                    : '{{ route("admin.fortune.commissions.pay") }}';

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ commission_ids: this.selectedIds }),
                });

                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            }
            this.processing = false;
        },

        /**
         * อนุมัติรายการเดียว
         */
        async approveOne(id) {
            if (!confirm('ต้องการอนุมัติคอมมิชชั่นนี้?')) return;

            this.processing = true;
            try {
                const res = await fetch('{{ route("admin.fortune.commissions.approve") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ commission_ids: [id] }),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },

        /**
         * จ่ายรายการเดียว
         */
        async payOne(id) {
            if (!confirm('ต้องการจ่ายคอมมิชชั่นนี้เข้า wallet?')) return;

            this.processing = true;
            try {
                const res = await fetch('{{ route("admin.fortune.commissions.pay") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ commission_ids: [id] }),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },

        /**
         * เปิด modal ปฏิเสธ
         */
        openRejectModal(id) {
            this.rejectId = id;
            this.rejectReason = '';
            this.showRejectModal = true;
        },

        /**
         * ยืนยันปฏิเสธ
         */
        async confirmReject() {
            this.processing = true;
            try {
                const res = await fetch(`{{ url('admin/fortune/commissions') }}/${this.rejectId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ reason: this.rejectReason }),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                this.showRejectModal = false;
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },

        /**
         * เปิด modal ปรับจำนวนเงิน
         */
        openAdjustModal(id, currentAmount) {
            this.adjustId = id;
            this.adjustAmount = currentAmount;
            this.adjustReason = '';
            this.showAdjustModal = true;
        },

        /**
         * ยืนยันปรับจำนวนเงิน
         */
        async confirmAdjust() {
            this.processing = true;
            try {
                const res = await fetch(`{{ url('admin/fortune/commissions') }}/${this.adjustId}/adjust`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        amount: this.adjustAmount,
                        reason: this.adjustReason,
                    }),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                this.showAdjustModal = false;
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },

        /**
         * บันทึกการตั้งค่า
         */
        async saveSettings() {
            this.savingSettings = true;
            try {
                const res = await fetch('{{ route("admin.fortune.commissions.update-settings") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.settingsForm),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.savingSettings = false;
        },

        /**
         * สร้างคอมมิชชั่นด้วยมือ
         */
        async confirmCreate() {
            if (!this.createForm.user_id || !this.createForm.from_user_id || !this.createForm.amount) {
                this.showToast('กรุณากรอก User ID ผู้รับ, ผู้จ่าย, และจำนวนเงิน', 'error');
                return;
            }

            this.processing = true;
            try {
                const res = await fetch('{{ route("admin.fortune.commissions.create-manual") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.createForm),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                this.showCreateModal = false;
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },
    };
}
</script>
@endpush
@endsection
