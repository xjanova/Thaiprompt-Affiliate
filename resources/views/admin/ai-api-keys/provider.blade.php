@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: แดชบอร์ด Provider (ธีม V4 "นวลทองคำ")
     ── 7 สถิติ + ฟอร์มตั้งค่าการวนใช้ + ตาราง Keys + Error Modal
     ── คงฟังก์ชัน/AJAX/Alpine/field/route เดิม 100%
     ════════════════════════════════════════════════════════════ --}}
<div x-data="providerDashboard()" x-init="init()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header: ปุ่มย้อนกลับ + eyebrow + h1 + ปุ่ม action ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="display:flex;align-items:flex-start;gap:14px;min-width:0;">
            {{-- ปุ่มย้อนกลับไปหน้า index --}}
            <a href="{{ route('admin.ai-api-keys.index') }}" class="tp-icon-btn" title="ย้อนกลับ" style="margin-top:18px;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div style="min-width:0;">
                <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                    หลังบ้าน · AI · Provider
                </div>
                <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;">{{ $providerName }}</h1>
                <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">จัดการ API Keys สำหรับ {{ $providerName }}</p>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            {{-- รีเฟรชสถิติ (AJAX → provider.stats → reload) --}}
            <button @click="refreshStats()" class="tp-btn tp-btn-sm" type="button">
                <i class="fas fa-rotate" :class="{ 'fa-spin': loading }"></i>
                <span>รีเฟรช</span>
            </button>

            {{-- เพิ่ม API Key --}}
            <a href="{{ route('admin.ai-api-keys.create', $provider) }}" class="tp-btn tp-btn-primary tp-btn-sm">
                <i class="fas fa-plus"></i>
                <span>เพิ่ม API Key</span>
            </a>
        </div>
    </div>

    {{-- ── 7 Stat Cards ── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;">

        {{-- Keys ทั้งหมด --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:44px;height:44px;font-size:17px;">
                    <i class="fas fa-key"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);line-height:1;">{{ $stats['total_keys'] }}</div>
                    <div class="tp-muted" style="font-size:.76rem;margin-top:3px;">Keys ทั้งหมด</div>
                </div>
            </div>
        </div>

        {{-- Active --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:44px;height:44px;font-size:17px;background:#5aa07e;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.6rem;font-weight:800;color:#5aa07e;line-height:1;">{{ $stats['active_keys'] }}</div>
                    <div class="tp-muted" style="font-size:.76rem;margin-top:3px;">Active</div>
                </div>
            </div>
        </div>

        {{-- พร้อมใช้ --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:44px;height:44px;font-size:17px;background:#b79ae8;">
                    <i class="fas fa-bolt"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.6rem;font-weight:800;color:#b79ae8;line-height:1;">{{ $stats['available_keys'] }}</div>
                    <div class="tp-muted" style="font-size:.76rem;margin-top:3px;">พร้อมใช้</div>
                </div>
            </div>
        </div>

        {{-- Tokens วันนี้ --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:44px;height:44px;font-size:17px;background:#e0a52e;">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.4rem;font-weight:800;color:var(--ink);line-height:1;">{{ number_format($stats['tokens_used_today']) }}</div>
                    <div class="tp-muted" style="font-size:.76rem;margin-top:3px;">Tokens วันนี้</div>
                </div>
            </div>
        </div>

        {{-- Tokens เดือนนี้ --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:44px;height:44px;font-size:17px;background:#5689b8;">
                    <i class="fas fa-calendar-days"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.4rem;font-weight:800;color:var(--ink);line-height:1;">{{ number_format($stats['tokens_used_month']) }}</div>
                    <div class="tp-muted" style="font-size:.76rem;margin-top:3px;">Tokens เดือนนี้</div>
                </div>
            </div>
        </div>

        {{-- Tokens รวม --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:44px;height:44px;font-size:17px;background:#5689b8;">
                    <i class="fas fa-database"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.4rem;font-weight:800;color:var(--ink);line-height:1;">{{ number_format($stats['tokens_used_total']) }}</div>
                    <div class="tp-muted" style="font-size:.76rem;margin-top:3px;">Tokens รวม</div>
                </div>
            </div>
        </div>

        {{-- Requests วันนี้ --}}
        <div class="tp-card tp-card-hover">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="tp-tile" style="width:44px;height:44px;font-size:17px;background:#d6824a;">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:1.4rem;font-weight:800;color:var(--ink);line-height:1;">{{ number_format($stats['requests_today']) }}</div>
                    <div class="tp-muted" style="font-size:.76rem;margin-top:3px;">Requests วันนี้</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ฟอร์มตั้งค่าการวนใช้ Keys ── --}}
    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-arrows-rotate" style="color:var(--accent1);"></i>
            <span>ตั้งค่าการวนใช้ Keys</span>
        </div>
        <form @submit.prevent="saveSettings()" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;align-items:end;">
            {{-- โหมดวนใช้ --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.8rem;font-weight:700;color:var(--ink2);margin-bottom:6px;">โหมดวนใช้</label>
                <select x-model="currentSettings.rotation_mode" class="tp-input">
                    @foreach($rotationModes as $mode => $description)
                    <option value="{{ $mode }}">{{ ucfirst(str_replace('_', ' ', $mode)) }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Max Errors ก่อน Disable --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.8rem;font-weight:700;color:var(--ink2);margin-bottom:6px;">Max Errors ก่อน Disable</label>
                <input type="number" x-model="currentSettings.max_consecutive_errors" min="1" max="10" class="tp-input">
            </div>
            {{-- Disable Duration (นาที) --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.8rem;font-weight:700;color:var(--ink2);margin-bottom:6px;">Disable Duration (นาที)</label>
                <input type="number" x-model="currentSettings.disable_duration_minutes" min="1" max="1440" class="tp-input">
            </div>
            {{-- ปุ่มบันทึก --}}
            <div>
                <button type="submit" :disabled="savingSettings" class="tp-btn tp-btn-primary tp-btn-sm" style="width:100%;justify-content:center;">
                    <i class="fas fa-floppy-disk" :class="{ 'fa-spin': savingSettings }"></i>
                    <span>บันทึก Settings</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── ตาราง Keys ── --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:1000px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);">ชื่อ / Key</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);">สถานะ</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);">Priority</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);">Tokens วันนี้</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);">Tokens เดือนนี้</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);">Requests</th>
                        <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);">ใช้ล่าสุด</th>
                        <th style="text-align:right;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);border-bottom:1px solid var(--sd);">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keys as $key)
                    <tr x-data="{ showActions: false }">
                        {{-- ชื่อ / Key --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);color:var(--ink);">
                            <div style="display:flex;flex-direction:column;">
                                <span style="font-weight:600;color:var(--ink);">{{ $key['name'] }}</span>
                                <span class="tp-muted" style="font-size:.75rem;font-family:monospace;">{{ $key['masked_key'] }}</span>
                            </div>
                        </td>
                        {{-- สถานะ + badge ต่างๆ --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);color:var(--ink);">
                            <div style="display:flex;flex-wrap:wrap;gap:5px;align-items:center;">
                                {{-- 🔴 (2026-05-01) Critical state — สูงสุด priority --}}
                                @if(!empty($key['is_critical']))
                                <span class="tp-pill" style="background:#d46a6a;color:#fff;font-weight:700;">🔴 CRITICAL</span>
                                @elseif($key['is_available'])
                                <span class="tp-pill" style="background:color-mix(in srgb, #5aa07e 18%, transparent);color:#3f7a5e;">พร้อมใช้</span>
                                @elseif($key['is_active'])
                                <span class="tp-pill" style="background:color-mix(in srgb, #e0a52e 20%, transparent);color:#a87916;">
                                    @if($key['disabled_until'])
                                        Disabled {{ $key['disabled_until'] }}
                                    @elseif($key['daily_usage_percent'] >= 100)
                                        ถึง Limit
                                    @else
                                        Active
                                    @endif
                                </span>
                                @else
                                <span class="tp-pill" style="background:color-mix(in srgb, #d46a6a 18%, transparent);color:#b04a4a;">ปิดใช้งาน</span>
                                @endif

                                {{-- 🩺 Health-check attempts (1/3, 2/3, 3/3) --}}
                                @if(!empty($key['error_check_attempts']) && $key['error_check_attempts'] > 0)
                                <span class="tp-pill" style="background:color-mix(in srgb, #d6824a 18%, transparent);color:#b3622e;" title="Health-check attempts ก่อน critical">
                                    🩺 {{ $key['error_check_attempts'] }}/3
                                </span>
                                @endif

                                {{-- 🎯 Per-key model badge --}}
                                @if(!empty($key['model']))
                                <span class="tp-pill" style="background:color-mix(in srgb, #5689b8 18%, transparent);color:#3f6a92;font-family:monospace;" title="Model สำหรับ key นี้">
                                    🎯 {{ $key['model'] }}
                                </span>
                                @endif

                                {{-- 🌟 (2026-05-05) Purpose badge — แสดงวัตถุประสงค์ของ key --}}
                                @php
                                    $purposeBadges = [
                                        'free_card' => ['emoji' => '🎁', 'label' => 'ฟรี', 'color' => '#d46a9e'],
                                        'prediction' => ['emoji' => '💎', 'label' => 'ทำนายเสีย', 'color' => '#b79ae8'],
                                        'prediction_deep' => ['emoji' => '🌟', 'label' => 'Deep 39', 'color' => '#b79ae8'],
                                        'prediction_celtic' => ['emoji' => '💎', 'label' => 'Celtic 99', 'color' => '#b79ae8'],
                                        'chat' => ['emoji' => '💬', 'label' => 'แชท', 'color' => '#5689b8'],
                                        // 💙 (2026-05-23) Paid chat — LAST RESORT badge สีฟ้าเข้ม
                                        'chat_paid' => ['emoji' => '💙', 'label' => 'แชทพิเศษ', 'color' => '#5689b8'],
                                        'sensitive' => ['emoji' => '🌟', 'label' => 'Sensitive', 'color' => '#e0a52e'],
                                        'tts' => ['emoji' => '🎙️', 'label' => 'TTS', 'color' => '#9aa0a6'],
                                        // 🚫 (2026-05-23) purpose='any' = DEPRECATED — Pool ไม่ pick
                                        'any' => ['emoji' => '⚠️', 'label' => 'any (DEPRECATED)', 'color' => '#d46a6a'],
                                    ];
                                    // 🚫 (2026-05-23) ห้าม default 'any' — ถ้าไม่ระบุ ให้เป็น null
                                    $purpose = $key['purpose'] ?? null;
                                @endphp
                                @if($purpose && isset($purposeBadges[$purpose]))
                                <span class="tp-pill" style="background:color-mix(in srgb, {{ $purposeBadges[$purpose]['color'] }} 18%, transparent);color:{{ $purposeBadges[$purpose]['color'] }};" title="วัตถุประสงค์: {{ $purpose }}">
                                    {{ $purposeBadges[$purpose]['emoji'] }} {{ $purposeBadges[$purpose]['label'] }}
                                </span>
                                @endif

                                @if($key['consecutive_errors'] > 0)
                                {{-- 🔍 Clickable error badge — เปิด modal แสดง last_error + ปุ่มไปดู logs ทั้งหมด --}}
                                <button type="button"
                                        @click="showErrorDetail({
                                            id: {{ $key['id'] }},
                                            name: @js($key['name']),
                                            consecutive_errors: {{ $key['consecutive_errors'] }},
                                            last_error: @js($key['last_error'] ?? ''),
                                            last_error_at: @js($key['last_error_at'] ?? ''),
                                        })"
                                        class="tp-pill"
                                        style="background:color-mix(in srgb, #d46a6a 18%, transparent);color:#b04a4a;border:0;cursor:pointer;font-family:inherit;"
                                        title="คลิกเพื่อดู error message">
                                    <i class="fas fa-triangle-exclamation" style="margin-right:4px;"></i>{{ $key['consecutive_errors'] }} errors
                                </button>
                                @endif
                            </div>
                        </td>
                        {{-- Priority --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);color:var(--ink);">
                            <span style="font-size:.88rem;color:var(--ink);">{{ $key['priority'] }}</span>
                        </td>
                        {{-- Tokens วันนี้ + progress bar --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);color:var(--ink);">
                            <div style="display:flex;flex-direction:column;">
                                <span style="font-size:.88rem;color:var(--ink);">{{ number_format($key['tokens_used_today']) }}</span>
                                @if($key['tokens_limit_daily'])
                                <div class="tp-inset-sm" style="width:80px;height:6px;border-radius:99px;margin-top:5px;overflow:hidden;">
                                    <div style="height:100%;border-radius:99px;width:{{ min(100, $key['daily_usage_percent']) }}%;background:{{ $key['daily_usage_percent'] >= 90 ? '#d46a6a' : ($key['daily_usage_percent'] >= 70 ? '#e0a52e' : '#5aa07e') }};"></div>
                                </div>
                                <span class="tp-muted" style="font-size:.72rem;margin-top:2px;">{{ $key['daily_usage_percent'] }}%</span>
                                @endif
                            </div>
                        </td>
                        {{-- Tokens เดือนนี้ + progress bar --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);color:var(--ink);">
                            <div style="display:flex;flex-direction:column;">
                                <span style="font-size:.88rem;color:var(--ink);">{{ number_format($key['tokens_used_month']) }}</span>
                                @if($key['tokens_limit_monthly'])
                                <div class="tp-inset-sm" style="width:80px;height:6px;border-radius:99px;margin-top:5px;overflow:hidden;">
                                    <div style="height:100%;border-radius:99px;width:{{ min(100, $key['monthly_usage_percent']) }}%;background:{{ $key['monthly_usage_percent'] >= 90 ? '#d46a6a' : ($key['monthly_usage_percent'] >= 70 ? '#e0a52e' : '#5aa07e') }};"></div>
                                </div>
                                <span class="tp-muted" style="font-size:.72rem;margin-top:2px;">{{ $key['monthly_usage_percent'] }}%</span>
                                @endif
                            </div>
                        </td>
                        {{-- Requests --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);color:var(--ink);">
                            <span style="font-size:.88rem;color:var(--ink);">{{ number_format($key['requests_today']) }}</span>
                        </td>
                        {{-- ใช้ล่าสุด --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);color:var(--ink);">
                            <span class="tp-muted" style="font-size:.84rem;">{{ $key['last_used_at'] ?? 'ยังไม่ใช้งาน' }}</span>
                        </td>
                        {{-- จัดการ --}}
                        <td style="padding:12px 16px;border-bottom:1px solid var(--sd);text-align:right;">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                {{-- toggle เปิด/ปิด --}}
                                <button @click="toggleKey({{ $key['id'] }})" type="button" class="tp-icon-btn"
                                        title="{{ $key['is_active'] ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                    @if($key['is_active'])
                                    <i class="fas fa-eye" style="color:#5aa07e;"></i>
                                    @else
                                    <i class="fas fa-eye-slash" style="color:var(--ink2);"></i>
                                    @endif
                                </button>
                                {{-- ทดสอบ --}}
                                <button @click="testKey({{ $key['id'] }})" type="button" class="tp-icon-btn" title="ทดสอบ">
                                    <i class="fas fa-circle-check" style="color:#5689b8;"></i>
                                </button>
                                @if($key['consecutive_errors'] > 0)
                                {{-- รีเซ็ต Errors --}}
                                <button @click="resetErrors({{ $key['id'] }})" type="button" class="tp-icon-btn" title="รีเซ็ต Errors">
                                    <i class="fas fa-rotate" style="color:#e0a52e;"></i>
                                </button>
                                @endif
                                {{-- 📜 ดู logs ของ key นี้ทั้งหมด — รองรับทุก provider --}}
                                <a href="{{ route('admin.ai-api-keys.key.logs', $key['id']) }}" class="tp-icon-btn" title="ดู Usage Logs">
                                    <i class="fas fa-clipboard-list" style="color:#b79ae8;"></i>
                                </a>
                                {{-- แก้ไข --}}
                                <a href="{{ route('admin.ai-api-keys.edit', $key['id']) }}" class="tp-icon-btn" title="แก้ไข">
                                    <i class="fas fa-pen"></i>
                                </a>
                                {{-- ลบ --}}
                                <button @click="deleteKey({{ $key['id'] }}, '{{ $key['name'] }}')" type="button" class="tp-icon-btn" title="ลบ">
                                    <i class="fas fa-trash" style="color:#d46a6a;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding:48px 16px;text-align:center;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                <i class="fas fa-key" style="font-size:2.2rem;color:var(--ink2);opacity:.5;"></i>
                                <p class="tp-muted" style="margin:0;">ยังไม่มี API Key สำหรับ {{ $providerName }}</p>
                                <a href="{{ route('admin.ai-api-keys.create', $provider) }}" class="tp-btn tp-btn-primary tp-btn-sm">
                                    <i class="fas fa-plus"></i>
                                    <span>เพิ่ม API Key</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🚨 Error Detail Modal — ดูรายละเอียด error ของ key ที่คลิก --}}
    <div x-show="errorModal.open"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="errorModal.open = false"
         @click.self="errorModal.open = false"
         style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.5);">
        <div class="tp-card"
             style="max-width:680px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;padding:0;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            {{-- Header --}}
            <div style="padding:16px 22px;border-bottom:1px solid var(--sd);display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                    <div class="tp-inset" style="width:40px;height:40px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-triangle-exclamation" style="color:#d46a6a;"></i>
                    </div>
                    <div style="min-width:0;">
                        <h3 style="margin:0;font-weight:700;color:var(--ink);font-size:1.05rem;">
                            Error ล่าสุดของ <span x-text="errorModal.name"></span>
                        </h3>
                        <p class="tp-muted" style="margin:2px 0 0;font-size:.78rem;">
                            <span x-text="errorModal.consecutive_errors"></span> errors ติดต่อกัน
                            <template x-if="errorModal.last_error_at">
                                <span> · เกิดเมื่อ <span x-text="errorModal.last_error_at"></span></span>
                            </template>
                        </p>
                    </div>
                </div>
                <button type="button" @click="errorModal.open = false" class="tp-icon-btn" title="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            {{-- Body --}}
            <div style="flex:1;overflow-y:auto;padding:20px 22px;display:flex;flex-direction:column;gap:16px;">
                <template x-if="errorModal.last_error">
                    <div>
                        <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--ink2);margin-bottom:8px;">
                            Error message
                        </label>
                        <pre class="tp-inset" style="border-radius:12px;padding:14px;font-size:.85rem;color:#d46a6a;white-space:pre-wrap;word-break:break-word;font-family:monospace;margin:0;"
                             x-text="errorModal.last_error"></pre>
                    </div>
                </template>
                <template x-if="!errorModal.last_error">
                    <div style="text-align:center;padding:32px 0;color:var(--ink2);">
                        <i class="fas fa-circle-info" style="font-size:2rem;opacity:.5;"></i>
                        <p style="margin:12px 0 0;font-size:.88rem;">ยังไม่มี error message ที่บันทึกไว้</p>
                        <p class="tp-muted" style="margin:4px 0 0;font-size:.78rem;">บิลรุ่นเก่าอาจไม่ได้บันทึก — ดู Usage Logs เพื่อดูรายละเอียด</p>
                    </div>
                </template>

                <div class="tp-inset-sm" style="border-radius:12px;padding:12px;">
                    <p class="tp-muted" style="margin:0;font-size:.8rem;">
                        💡 ดู usage logs ทั้งหมดของ key นี้เพื่อตรวจประวัติ error และคำขอที่สำเร็จ/ล้มเหลว
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:16px 22px;border-top:1px solid var(--sd);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <button type="button"
                        @click="resetErrors(errorModal.id); errorModal.open = false"
                        class="tp-btn tp-btn-sm">
                    <i class="fas fa-rotate" style="color:#e0a52e;"></i>
                    <span>รีเซ็ต Errors</span>
                </button>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" @click="errorModal.open = false" class="tp-btn tp-btn-sm">
                        <span>ปิด</span>
                    </button>
                    <a :href="`/admin/ai-api-keys/${errorModal.id}/logs`" class="tp-btn tp-btn-primary tp-btn-sm">
                        <i class="fas fa-clipboard-list"></i>
                        <span>ดู Usage Logs ทั้งหมด</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- scripts stack: Alpine providerDashboard (คง endpoint/method/payload/CSRF เดิม 100%) --}}
@push('scripts')
<script>
function providerDashboard() {
    return {
        loading: false,
        savingSettings: false,
        currentSettings: @json($settings),

        // 🚨 Error detail modal state
        errorModal: {
            open: false,
            id: null,
            name: '',
            consecutive_errors: 0,
            last_error: '',
            last_error_at: '',
        },

        // 📋 เปิด modal แสดง error detail (เรียกจาก clickable badge)
        showErrorDetail(payload) {
            this.errorModal = {
                open: true,
                id: payload.id,
                name: payload.name || '',
                consecutive_errors: payload.consecutive_errors || 0,
                last_error: payload.last_error || '',
                last_error_at: payload.last_error_at || '',
            };
        },

        init() {
            setInterval(() => this.refreshStats(), 30000);
        },

        async refreshStats() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.provider.stats', $provider) }}');
                const data = await response.json();
                if (data.success) {
                    // Refresh page to update table data
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },

        async saveSettings() {
            this.savingSettings = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.provider.settings', $provider) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.currentSettings)
                });
                const data = await response.json();
                if (data.success) {
                    alert('บันทึก Settings สำเร็จ');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด');
            } finally {
                this.savingSettings = false;
            }
        },

        async toggleKey(id) {
            try {
                const response = await fetch(`/admin/ai-api-keys/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async testKey(id) {
            try {
                const response = await fetch(`/admin/ai-api-keys/${id}/test`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                alert(data.message);
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการทดสอบ');
            }
        },

        async resetErrors(id) {
            try {
                const response = await fetch(`/admin/ai-api-keys/${id}/reset-errors`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async deleteKey(id, name) {
            if (!confirm(`ต้องการลบ API Key "${name}" หรือไม่?`)) return;

            try {
                const response = await fetch(`/admin/ai-api-keys/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    };
}
</script>
@endpush
