@extends('layouts.user-v4')

@section('title', 'การตั้งค่าอีเมล')

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── หมวดอีเมลแจ้งเตือน (ตรงกับ field ใน EmailPreferenceController::update) ──
    // [ชื่อ field, อิโมจิ, สีไอคอน (hex), หัวข้อ, คำอธิบาย, ค่าตั้งต้น]
    $emailCategories = [
        [
            'name'    => 'security_alerts',
            'emoji'   => '🔐',
            'color'   => '#d9534f',
            'title'   => 'แจ้งเตือนความปลอดภัย',
            'desc'    => 'การเข้าสู่ระบบ, เปลี่ยนรหัสผ่าน, เปลี่ยนการตั้งค่าสำคัญ',
            'default' => true,
            'note'    => 'แนะนำให้เปิด: เพื่อความปลอดภัยของบัญชีคุณ',
        ],
        [
            'name'    => 'commission_notifications',
            'emoji'   => '💰',
            'color'   => '#5aa07e',
            'title'   => 'แจ้งเตือนค่าคอมมิชชั่น',
            'desc'    => 'การรับค่าคอมมิชชั่น, โบนัส, รายได้',
            'default' => true,
        ],
        [
            'name'    => 'withdrawal_notifications',
            'emoji'   => '🏦',
            'color'   => '#5689b8',
            'title'   => 'แจ้งเตือนการถอนเงิน',
            'desc'    => 'สถานะการถอนเงิน, ยืนยันการโอน',
            'default' => true,
        ],
        [
            'name'    => 'system_announcements',
            'emoji'   => '📢',
            'color'   => '#8a6fb0',
            'title'   => 'ประกาศระบบ',
            'desc'    => 'ข่าวสารสำคัญ, การปรับปรุงระบบ, กิจกรรมพิเศษ',
            'default' => true,
        ],
        [
            'name'    => 'weekly_reports',
            'emoji'   => '📊',
            'color'   => '#5d6cb0',
            'title'   => 'รายงานประจำสัปดาห์',
            'desc'    => 'สรุปรายได้, สถิติการขาย, ยอดคอมมิชชั่น',
            'default' => true,
        ],
        [
            'name'    => 'marketing_emails',
            'emoji'   => '🎯',
            'color'   => '#e0a52e',
            'title'   => 'อีเมลการตลาด',
            'desc'    => 'โปรโมชั่น, ข้อเสนอพิเศษ, เคล็ดลับการขาย',
            'default' => false,
        ],
    ];

    $prefLang = old('preferred_language', $preference->preferred_language ?? 'th');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; padding-bottom:24px;">

    {{-- ── การ์ดหัวเรื่อง (Hero) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:56px; height:56px; border-radius:17px; font-size:26px;"><i class="fas fa-envelope-open" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">การตั้งค่าอีเมล · EMAIL PREFERENCES</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">การตั้งค่าอีเมล</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">จัดการการรับอีเมลแจ้งเตือนและข่าวสาร</div>
            </div>
        </div>
    </div>

    <form action="{{ route('user.email.preferences.update') }}" method="POST" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- ── สวิตช์หลัก: การแจ้งเตือนทั้งหมด ──────────────────── --}}
        <div class="tp-card" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <span class="tp-tile" style="width:48px; height:48px; border-radius:15px; font-size:22px;">🔔</span>
                <div>
                    <h2 style="font-size:17px; font-weight:800; margin:0;">การแจ้งเตือนทั้งหมด</h2>
                    <p style="font-size:12.5px; color:var(--ink2); margin:2px 0 0;">เปิด/ปิดการรับอีเมลทั้งหมด</p>
                </div>
            </div>
            <label class="tp-switch">
                <input type="checkbox"
                       name="all_emails"
                       value="1"
                       {{ old('all_emails', $preference->all_emails ?? true) ? 'checked' : '' }}
                       onchange="toggleAllEmails(this)">
                <span class="tp-switch-track"></span>
            </label>
        </div>

        {{-- ── หมวดอีเมลแต่ละประเภท ──────────────────────────── --}}
        <div id="emailCategories" style="display:flex; flex-direction:column; gap:14px; transition:opacity .25s;">
            @foreach($emailCategories as $cat)
                <div class="tp-card" style="display:flex; align-items:flex-start; gap:14px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:15px; font-size:22px; flex-shrink:0; background:{{ $cat['color'] }};">{{ $cat['emoji'] }}</span>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                            <div>
                                <h3 style="font-size:15.5px; font-weight:800; margin:0;">{{ $cat['title'] }}</h3>
                                <p style="font-size:12.5px; color:var(--ink2); margin:3px 0 0;">{{ $cat['desc'] }}</p>
                            </div>
                            <label class="tp-switch tp-switch-sm" style="flex-shrink:0;">
                                <input type="checkbox"
                                       name="{{ $cat['name'] }}"
                                       value="1"
                                       {{ old($cat['name'], $preference->{$cat['name']} ?? $cat['default']) ? 'checked' : '' }}
                                       class="email-toggle">
                                <span class="tp-switch-track" style="--sw-on:{{ $cat['color'] }};"></span>
                            </label>
                        </div>
                        @if(!empty($cat['note']))
                            <div style="margin-top:12px; padding:10px 12px; border-radius:12px; font-size:12.5px;
                                        box-shadow:var(--inset-sm); border-left:3px solid {{ $cat['color'] }};">
                                <span style="font-weight:700; color:{{ $cat['color'] }};">แนะนำให้เปิด:</span>
                                <span style="color:var(--ink2);">เพื่อความปลอดภัยของบัญชีคุณ</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── ภาษาที่ต้องการ ────────────────────────────────── --}}
        <div class="tp-card">
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:16px;">
                <span class="tp-tile" style="width:48px; height:48px; border-radius:15px; font-size:22px; background:#c97a96;">🌐</span>
                <div>
                    <h2 style="font-size:17px; font-weight:800; margin:0;">ภาษาที่ต้องการ</h2>
                    <p style="font-size:12.5px; color:var(--ink2); margin:2px 0 0;">เลือกภาษาสำหรับอีเมลที่ได้รับ</p>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                {{-- ภาษาไทย --}}
                <label class="tp-lang-opt {{ $prefLang === 'th' ? 'is-active' : '' }}" style="position:relative; cursor:pointer; display:block; padding:16px; border-radius:16px; box-shadow:var(--inset-sm);">
                    <input type="radio" name="preferred_language" value="th" {{ $prefLang === 'th' ? 'checked' : '' }}
                           onchange="syncLangActive(this)" style="position:absolute; opacity:0; width:0; height:0;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:30px;">🇹🇭</span>
                        <div>
                            <div style="font-weight:800; font-size:15px;">ภาษาไทย</div>
                            <div style="font-size:12px; color:var(--ink2);">Thai Language</div>
                        </div>
                    </div>
                    <span class="tp-lang-check" style="position:absolute; top:12px; right:12px; width:24px; height:24px; border-radius:50%; background:var(--deep1); align-items:center; justify-content:center;">
                        <i class="fas fa-check" style="color:#fff; font-size:11px;"></i>
                    </span>
                </label>

                {{-- English --}}
                <label class="tp-lang-opt {{ $prefLang === 'en' ? 'is-active' : '' }}" style="position:relative; cursor:pointer; display:block; padding:16px; border-radius:16px; box-shadow:var(--inset-sm);">
                    <input type="radio" name="preferred_language" value="en" {{ $prefLang === 'en' ? 'checked' : '' }}
                           onchange="syncLangActive(this)" style="position:absolute; opacity:0; width:0; height:0;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:30px;">🇬🇧</span>
                        <div>
                            <div style="font-weight:800; font-size:15px;">English</div>
                            <div style="font-size:12px; color:var(--ink2);">English Language</div>
                        </div>
                    </div>
                    <span class="tp-lang-check" style="position:absolute; top:12px; right:12px; width:24px; height:24px; border-radius:50%; background:var(--deep1); align-items:center; justify-content:center;">
                        <i class="fas fa-check" style="color:#fff; font-size:11px;"></i>
                    </span>
                </label>
            </div>
        </div>

        {{-- ── ปุ่มจัดการ ─────────────────────────────────────── --}}
        <div style="display:flex; flex-wrap:wrap; gap:12px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; min-width:200px; justify-content:center; font-size:15px; padding:14px 20px;">
                <i class="fas fa-floppy-disk"></i> บันทึกการตั้งค่า
            </button>
            <button type="button" onclick="disableAll()" class="tp-btn" style="justify-content:center; padding:14px 20px; color:#fff; background:#d9534f; border-color:transparent;">
                <i class="fas fa-xmark"></i> ปิดทั้งหมด
            </button>
            <button type="button" onclick="enableAll()" class="tp-btn" style="justify-content:center; padding:14px 20px; color:#fff; background:#5aa07e; border-color:transparent;">
                <i class="fas fa-check"></i> เปิดทั้งหมด
            </button>
        </div>
    </form>

    {{-- ── กล่องข้อมูล ───────────────────────────────────────── --}}
    <div class="tp-card" style="display:flex; gap:14px; box-shadow:var(--inset-sm); border-left:4px solid var(--deep1);">
        <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:18px; flex-shrink:0;">💡</span>
        <div style="flex:1; min-width:0;">
            <h3 style="font-weight:800; font-size:15px; margin:0 0 8px;">เกี่ยวกับการตั้งค่าอีเมล</h3>
            <ul style="font-size:12.5px; color:var(--ink2); margin:0; padding-left:18px; display:flex; flex-direction:column; gap:5px;">
                <li>คุณสามารถเปลี่ยนการตั้งค่าได้ทุกเมื่อ</li>
                <li>การแจ้งเตือนความปลอดภัยแนะนำให้เปิดไว้เสมอ</li>
                <li>อีเมลสำคัญจะถูกส่งไปยังอีเมลที่ลงทะเบียนไว้: <strong style="color:var(--ink);">{{ auth()->user()->email }}</strong></li>
                <li>การตั้งค่าจะมีผลทันทีหลังบันทึก</li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── เปิด/ปิดทุกหมวดเมื่อสลับสวิตช์หลัก ──────────────────────
function toggleAllEmails(checkbox) {
    const emailToggles = document.querySelectorAll('.email-toggle');
    const emailCategories = document.getElementById('emailCategories');

    if (checkbox.checked) {
        emailCategories.style.opacity = '';
        emailCategories.style.pointerEvents = '';
    } else {
        emailCategories.style.opacity = '0.5';
        emailCategories.style.pointerEvents = 'none';
        emailToggles.forEach(function (toggle) {
            toggle.checked = false;
        });
    }
}

// ── ไฮไลต์ตัวเลือกภาษาที่เลือก ─────────────────────────────
function syncLangActive(radio) {
    document.querySelectorAll('input[name="preferred_language"]').forEach(function (el) {
        const card = el.closest('.tp-lang-opt');
        if (card) card.classList.toggle('is-active', el.checked);
    });
}

// ── ปิดการรับอีเมลทั้งหมด (AJAX) ──────────────────────────
async function disableAll() {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะปิดการรับอีเมลทั้งหมด?')) {
        return;
    }

    try {
        const response = await fetch('{{ route("user.email.preferences.disable-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        if (response.ok) {
            location.reload();
        } else {
            window.showNotification ? window.showNotification('เกิดข้อผิดพลาด', 'error') : alert('เกิดข้อผิดพลาด');
        }
    } catch (error) {
        window.showNotification ? window.showNotification('เกิดข้อผิดพลาด', 'error') : alert('เกิดข้อผิดพลาด');
    }
}

// ── เปิดการรับอีเมลทั้งหมด (AJAX) ──────────────────────────
async function enableAll() {
    try {
        const response = await fetch('{{ route("user.email.preferences.enable-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        if (response.ok) {
            location.reload();
        } else {
            window.showNotification ? window.showNotification('เกิดข้อผิดพลาด', 'error') : alert('เกิดข้อผิดพลาด');
        }
    } catch (error) {
        window.showNotification ? window.showNotification('เกิดข้อผิดพลาด', 'error') : alert('เกิดข้อผิดพลาด');
    }
}

// ── แสดง toast จาก session (success / error) ───────────────
@if(session('success'))
    document.addEventListener('DOMContentLoaded', function () {
        if (window.showNotification) window.showNotification(@json(session('success')), 'success');
    });
@endif
@if(session('error'))
    document.addEventListener('DOMContentLoaded', function () {
        if (window.showNotification) window.showNotification(@json(session('error')), 'error');
    });
@endif

// ── ตั้งค่าสถานะเริ่มต้นเมื่อโหลดหน้า ──────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const allEmailsCheckbox = document.querySelector('input[name="all_emails"]');
    if (allEmailsCheckbox && !allEmailsCheckbox.checked) {
        toggleAllEmails(allEmailsCheckbox);
    }
});
</script>
<style>
/* ── สวิตช์เปิด/ปิด (Theme V4) ─────────────────────────── */
.tp-switch { position:relative; display:inline-flex; cursor:pointer; flex-shrink:0; }
.tp-switch input { position:absolute; opacity:0; width:0; height:0; }
.tp-switch .tp-switch-track {
    width:52px; height:30px; border-radius:30px; position:relative;
    background:color-mix(in srgb, var(--ink2) 28%, transparent);
    box-shadow:var(--inset-sm); transition:background .2s;
}
.tp-switch .tp-switch-track::after {
    content:""; position:absolute; top:3px; left:3px; width:24px; height:24px;
    border-radius:50%; background:var(--surf);
    box-shadow:0 1px 4px rgba(0,0,0,.25); transition:transform .2s;
}
.tp-switch input:checked + .tp-switch-track {
    background:var(--sw-on, linear-gradient(90deg, var(--accent1), var(--accent2)));
}
.tp-switch input:checked + .tp-switch-track::after { transform:translateX(22px); }
.tp-switch input:focus-visible + .tp-switch-track { outline:2px solid var(--deep1); outline-offset:2px; }

.tp-switch-sm .tp-switch-track { width:46px; height:26px; }
.tp-switch-sm .tp-switch-track::after { width:20px; height:20px; top:3px; left:3px; }
.tp-switch-sm input:checked + .tp-switch-track::after { transform:translateX(20px); }

/* ── ตัวเลือกภาษา (radio card) ─────────────────────────── */
.tp-lang-opt { transition:box-shadow .2s, background .2s; }
.tp-lang-opt .tp-lang-check { display:none; }
.tp-lang-opt.is-active {
    box-shadow:0 0 0 2px var(--deep1) inset, var(--inset-sm);
    background:color-mix(in srgb, var(--accent1) 12%, transparent);
}
.tp-lang-opt.is-active .tp-lang-check { display:flex; }
</style>
@endpush
@endsection
