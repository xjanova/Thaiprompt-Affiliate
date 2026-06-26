@extends('layouts.user-v4')

@section('title', 'การตั้งค่า')

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── ค่าปัจจุบันจาก controller (compact('user','settings')) ──────────
    // เก็บไว้เป็นตัวแปรกลาง กันค่า null + ใช้ซ้ำในแต่ละแท็บ
    $prefLang   = $settings['preferences']['language']   ?? ($user->preferred_language ?? 'th');
    $prefTz     = $settings['preferences']['timezone']   ?? ($user->timezone ?? 'Asia/Bangkok');
    $prefCur    = $settings['preferences']['currency']   ?? ($user->preferred_currency ?? 'THB');

    $nEmail     = $settings['notifications']['email_enabled']    ?? ($user->email_notifications ?? true);
    $nCommission= $settings['notifications']['email_commission'] ?? ($user->email_commission_notifications ?? true);
    $nTeam      = $settings['notifications']['email_team']       ?? ($user->email_team_notifications ?? true);
    $nMarketing = $settings['notifications']['email_marketing']  ?? ($user->email_marketing_notifications ?? false);
    $nLine      = $settings['notifications']['line_enabled']     ?? ($user->line_notifications ?? false);
    $nPush      = $settings['notifications']['push_enabled']     ?? ($user->push_notifications ?? true);

    $pVisible   = ($settings['privacy']['profile_visibility'] ?? ($user->profile_visibility ?? 'public')) !== 'private';
    $pStats     = $settings['privacy']['show_stats'] ?? ($user->show_stats ?? true);
    $pTeam      = $settings['privacy']['show_team']  ?? ($user->show_team ?? true);

    $aTheme     = $settings['appearance']['theme']          ?? ($user->theme_preference ?? 'auto');
    $aMotion    = $settings['appearance']['reduced_motion'] ?? ($user->reduced_motion ?? false);

    $twoFa      = $settings['security']['two_factor_enabled'] ?? ($user->two_factor_enabled ?? false);
    $sessCount  = $settings['security']['active_sessions_count'] ?? 1;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="settingsManager()" x-init="init()">

    {{-- ── Hero header ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-sliders-h" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">การตั้งค่า</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ปรับแต่งประสบการณ์การใช้งานของคุณ</div>
            </div>
        </div>
    </div>

    {{-- ── ข้อความแจ้งผลลัพธ์ ───────────────────────────────── --}}
    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:12px;
                    box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;"
             x-data="{ show: true }" x-show="show" x-transition>
            <span class="tp-tile" style="width:36px; height:36px; border-radius:11px; font-size:15px; background:#5aa07e;"><i class="fas fa-circle-check" style="color:#fff;"></i></span>
            <span style="flex:1; font-weight:600; font-size:13.5px;">{{ session('success') }}</span>
            <button type="button" @click="show = false" class="tp-icon-btn" aria-label="ปิด"><i class="fas fa-times"></i></button>
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:12px;
                    box-shadow:var(--inset-sm); border-left:4px solid #d9534f;"
             x-data="{ show: true }" x-show="show" x-transition>
            <span class="tp-tile" style="width:36px; height:36px; border-radius:11px; font-size:15px; background:#d9534f;"><i class="fas fa-circle-exclamation" style="color:#fff;"></i></span>
            <span style="flex:1; font-weight:600; font-size:13.5px;">{{ session('error') }}</span>
            <button type="button" @click="show = false" class="tp-icon-btn" aria-label="ปิด"><i class="fas fa-times"></i></button>
        </div>
    @endif

    {{-- ── แท็บ + เนื้อหา ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">

        {{-- Tabs header --}}
        <div style="display:flex; overflow-x:auto; box-shadow:var(--inset-sm);">
            @php
                $tabs = [
                    ['preferences',   'fa-sliders-h',  'ตั้งค่าทั่วไป'],
                    ['notifications', 'fa-bell',       'การแจ้งเตือน'],
                    ['privacy',       'fa-shield-halved', 'ความเป็นส่วนตัว'],
                    ['appearance',    'fa-palette',    'รูปแบบ'],
                    ['security',      'fa-lock',       'ความปลอดภัย'],
                ];
            @endphp
            @foreach($tabs as [$tabKey, $tabIcon, $tabLabel])
                <button type="button" @click="activeTab = '{{ $tabKey }}'"
                        :style="activeTab === '{{ $tabKey }}'
                            ? 'color:var(--deep1); border-bottom-color:var(--accent1); font-weight:700;'
                            : 'color:var(--ink2); border-bottom-color:transparent; font-weight:600;'"
                        style="padding:14px 20px; border:none; border-bottom:3px solid transparent; background:transparent;
                               font-size:13px; white-space:nowrap; cursor:pointer; transition:color .2s;">
                    <i class="fas {{ $tabIcon }}" style="margin-right:7px;"></i>{{ $tabLabel }}
                </button>
            @endforeach
        </div>

        {{-- Form หลัก (preferences / notifications / privacy / appearance) --}}
        <form action="{{ route('user.settings.update') }}" method="POST" style="padding:22px 24px;">
            @csrf
            @method('PUT')

            {{-- ════ แท็บ: ตั้งค่าทั่วไป ════ --}}
            <div x-show="activeTab === 'preferences'" x-transition>
                <div class="tp-section-h" style="margin-bottom:18px;">⚙️ ตั้งค่าทั่วไป</div>
                <div style="display:flex; flex-direction:column; gap:14px;">

                    {{-- ภาษา --}}
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="flex:1; min-width:200px;">
                            <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-language" style="color:var(--accent1); margin-right:8px;"></i>ภาษา</div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:3px;">เลือกภาษาที่ใช้แสดงผลในระบบ</div>
                        </div>
                        <select name="preferred_language" class="tp-input" style="width:auto; min-width:160px;">
                            <option value="th" {{ $prefLang === 'th' ? 'selected' : '' }}>ไทย</option>
                            <option value="en" {{ $prefLang === 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>

                    {{-- เขตเวลา --}}
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="flex:1; min-width:200px;">
                            <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-clock" style="color:var(--accent1); margin-right:8px;"></i>เขตเวลา</div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:3px;">เลือกเขตเวลาสำหรับแสดงผล</div>
                        </div>
                        <select name="timezone" class="tp-input" style="width:auto; min-width:160px;">
                            <option value="Asia/Bangkok"   {{ $prefTz === 'Asia/Bangkok' ? 'selected' : '' }}>Bangkok (GMT+7)</option>
                            <option value="Asia/Singapore" {{ $prefTz === 'Asia/Singapore' ? 'selected' : '' }}>Singapore (GMT+8)</option>
                            <option value="Asia/Tokyo"     {{ $prefTz === 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo (GMT+9)</option>
                        </select>
                    </div>

                    {{-- สกุลเงิน --}}
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="flex:1; min-width:200px;">
                            <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-money-bill" style="color:var(--accent1); margin-right:8px;"></i>สกุลเงิน</div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:3px;">สกุลเงินที่ใช้แสดงผล</div>
                        </div>
                        <select name="preferred_currency" class="tp-input" style="width:auto; min-width:160px;">
                            <option value="THB" {{ $prefCur === 'THB' ? 'selected' : '' }}>บาท (THB)</option>
                            <option value="USD" {{ $prefCur === 'USD' ? 'selected' : '' }}>ดอลลาร์ (USD)</option>
                            <option value="EUR" {{ $prefCur === 'EUR' ? 'selected' : '' }}>ยูโร (EUR)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ════ แท็บ: การแจ้งเตือน ════ --}}
            <div x-show="activeTab === 'notifications'" x-transition style="display:none;">
                <div class="tp-section-h" style="margin-bottom:18px;">🔔 การแจ้งเตือน</div>
                <div style="display:flex; flex-direction:column; gap:14px;">

                    {{-- อีเมล --}}
                    <div style="padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:14px;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-envelope" style="color:var(--deep1);"></i></span>
                                <div>
                                    <div style="font-weight:700; font-size:13.5px;">การแจ้งเตือนทางอีเมล</div>
                                    <div style="font-size:12px; color:var(--ink2);">รับการแจ้งเตือนผ่านอีเมล</div>
                                </div>
                            </div>
                            {{-- Toggle: email_notifications --}}
                            <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                                <input type="checkbox" name="email_notifications" value="1" {{ $nEmail ? 'checked' : '' }} class="peer" style="position:absolute; opacity:0; width:0; height:0;">
                                <span style="width:44px; height:24px; border-radius:20px; box-shadow:var(--inset-sm); position:relative; transition:background .25s;"
                                      :style="$el.previousElementSibling.checked ? 'background:linear-gradient(90deg,var(--accent1),var(--accent2));' : 'background:color-mix(in srgb,var(--ink2) 22%,transparent);'">
                                    <span style="position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .25s;"
                                          :style="$el.parentElement.previousElementSibling.checked ? 'transform:translateX(20px);' : ''"></span>
                                </span>
                            </label>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:10px; padding-left:50px;">
                            <label style="display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer;">
                                <input type="checkbox" name="email_commission_notifications" value="1" {{ $nCommission ? 'checked' : '' }} style="width:16px; height:16px; accent-color:var(--accent1);">
                                <span>คอมมิชชั่นใหม่</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer;">
                                <input type="checkbox" name="email_team_notifications" value="1" {{ $nTeam ? 'checked' : '' }} style="width:16px; height:16px; accent-color:var(--accent1);">
                                <span>ลูกทีม / สมาชิกใหม่</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer;">
                                <input type="checkbox" name="email_marketing_notifications" value="1" {{ $nMarketing ? 'checked' : '' }} style="width:16px; height:16px; accent-color:var(--accent1);">
                                <span>ข่าวสารและโปรโมชั่น</span>
                            </label>
                        </div>
                    </div>

                    {{-- LINE --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:#5aa07e;"><i class="fab fa-line" style="color:#fff;"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">การแจ้งเตือนทาง LINE</div>
                                <div style="font-size:12px; color:var(--ink2);">รับการแจ้งเตือนผ่าน LINE</div>
                            </div>
                        </div>
                        <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" name="line_notifications" value="1" {{ $nLine ? 'checked' : '' }} style="position:absolute; opacity:0; width:0; height:0;">
                            <span style="width:44px; height:24px; border-radius:20px; box-shadow:var(--inset-sm); position:relative; transition:background .25s;"
                                  :style="$el.previousElementSibling.checked ? 'background:linear-gradient(90deg,var(--accent1),var(--accent2));' : 'background:color-mix(in srgb,var(--ink2) 22%,transparent);'">
                                <span style="position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .25s;"
                                      :style="$el.parentElement.previousElementSibling.checked ? 'transform:translateX(20px);' : ''"></span>
                            </span>
                        </label>
                    </div>

                    {{-- Push --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:#e0a52e;"><i class="fas fa-mobile-alt" style="color:#fff;"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">Push Notifications</div>
                                <div style="font-size:12px; color:var(--ink2);">รับการแจ้งเตือนบนอุปกรณ์</div>
                            </div>
                        </div>
                        <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" name="push_notifications" value="1" {{ $nPush ? 'checked' : '' }} style="position:absolute; opacity:0; width:0; height:0;">
                            <span style="width:44px; height:24px; border-radius:20px; box-shadow:var(--inset-sm); position:relative; transition:background .25s;"
                                  :style="$el.previousElementSibling.checked ? 'background:linear-gradient(90deg,var(--accent1),var(--accent2));' : 'background:color-mix(in srgb,var(--ink2) 22%,transparent);'">
                                <span style="position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .25s;"
                                      :style="$el.parentElement.previousElementSibling.checked ? 'transform:translateX(20px);' : ''"></span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ════ แท็บ: ความเป็นส่วนตัว ════ --}}
            <div x-show="activeTab === 'privacy'" x-transition style="display:none;">
                <div class="tp-section-h" style="margin-bottom:18px;">🛡️ ความเป็นส่วนตัว</div>
                <div style="display:flex; flex-direction:column; gap:14px;">

                    {{-- โปรไฟล์สาธารณะ (map → profile_visibility) --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-user-shield" style="color:var(--deep1);"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">แสดงโปรไฟล์สาธารณะ</div>
                                <div style="font-size:12px; color:var(--ink2);">ให้ผู้อื่นเห็นโปรไฟล์ของคุณ</div>
                            </div>
                        </div>
                        {{-- ส่ง public/private ผ่าน hidden + checkbox คุมค่า --}}
                        <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;"
                               x-data="{ pub: {{ $pVisible ? 'true' : 'false' }} }">
                            <input type="hidden" name="profile_visibility" :value="pub ? 'public' : 'private'">
                            <input type="checkbox" x-model="pub" style="position:absolute; opacity:0; width:0; height:0;">
                            <span style="width:44px; height:24px; border-radius:20px; box-shadow:var(--inset-sm); position:relative; transition:background .25s;"
                                  :style="pub ? 'background:linear-gradient(90deg,var(--accent1),var(--accent2));' : 'background:color-mix(in srgb,var(--ink2) 22%,transparent);'">
                                <span style="position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .25s;"
                                      :style="pub ? 'transform:translateX(20px);' : ''"></span>
                            </span>
                        </label>
                    </div>

                    {{-- แสดงสถิติ --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-chart-line" style="color:var(--deep1);"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">แสดงสถิติ</div>
                                <div style="font-size:12px; color:var(--ink2);">แสดงสถิติรายได้และลูกทีม</div>
                            </div>
                        </div>
                        <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" name="show_stats" value="1" {{ $pStats ? 'checked' : '' }} style="position:absolute; opacity:0; width:0; height:0;">
                            <span style="width:44px; height:24px; border-radius:20px; box-shadow:var(--inset-sm); position:relative; transition:background .25s;"
                                  :style="$el.previousElementSibling.checked ? 'background:linear-gradient(90deg,var(--accent1),var(--accent2));' : 'background:color-mix(in srgb,var(--ink2) 22%,transparent);'">
                                <span style="position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .25s;"
                                      :style="$el.parentElement.previousElementSibling.checked ? 'transform:translateX(20px);' : ''"></span>
                            </span>
                        </label>
                    </div>

                    {{-- แสดงลูกทีม --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-users" style="color:var(--deep1);"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">แสดงลูกทีม</div>
                                <div style="font-size:12px; color:var(--ink2);">แสดงรายชื่อลูกทีมของคุณ</div>
                            </div>
                        </div>
                        <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" name="show_team" value="1" {{ $pTeam ? 'checked' : '' }} style="position:absolute; opacity:0; width:0; height:0;">
                            <span style="width:44px; height:24px; border-radius:20px; box-shadow:var(--inset-sm); position:relative; transition:background .25s;"
                                  :style="$el.previousElementSibling.checked ? 'background:linear-gradient(90deg,var(--accent1),var(--accent2));' : 'background:color-mix(in srgb,var(--ink2) 22%,transparent);'">
                                <span style="position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .25s;"
                                      :style="$el.parentElement.previousElementSibling.checked ? 'transform:translateX(20px);' : ''"></span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ════ แท็บ: รูปแบบ ════ --}}
            <div x-show="activeTab === 'appearance'" x-transition style="display:none;">
                <div class="tp-section-h" style="margin-bottom:18px;">🎨 รูปแบบการแสดงผล</div>
                <div style="display:flex; flex-direction:column; gap:14px;">

                    {{-- ธีม (theme_preference) --}}
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-circle-half-stroke" style="color:var(--deep1);"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">ธีมการแสดงผล</div>
                                <div style="font-size:12px; color:var(--ink2);">เลือกโหมดสว่าง / มืด / อัตโนมัติ</div>
                            </div>
                        </div>
                        <select name="theme_preference" class="tp-input" style="width:auto; min-width:160px;"
                                @change="syncTheme($event.target.value)">
                            <option value="auto"  {{ $aTheme === 'auto'  ? 'selected' : '' }}>อัตโนมัติ</option>
                            <option value="light" {{ $aTheme === 'light' ? 'selected' : '' }}>โหมดสว่าง</option>
                            <option value="dark"  {{ $aTheme === 'dark'  ? 'selected' : '' }}>โหมดมืด</option>
                        </select>
                    </div>

                    {{-- ลด Animation (reduced_motion) --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-wand-magic-sparkles" style="color:var(--deep1);"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">ลดการเคลื่อนไหว (Reduced Motion)</div>
                                <div style="font-size:12px; color:var(--ink2);">ลด Animation และ Transition เพื่อการใช้งานที่นิ่งขึ้น</div>
                            </div>
                        </div>
                        <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" name="reduced_motion" value="1" {{ $aMotion ? 'checked' : '' }} style="position:absolute; opacity:0; width:0; height:0;">
                            <span style="width:44px; height:24px; border-radius:20px; box-shadow:var(--inset-sm); position:relative; transition:background .25s;"
                                  :style="$el.previousElementSibling.checked ? 'background:linear-gradient(90deg,var(--accent1),var(--accent2));' : 'background:color-mix(in srgb,var(--ink2) 22%,transparent);'">
                                <span style="position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .25s;"
                                      :style="$el.parentElement.previousElementSibling.checked ? 'transform:translateX(20px);' : ''"></span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ════ แท็บ: ความปลอดภัย (ส่วนแสดงผล/ลิงก์ — อยู่ในฟอร์มเพื่อให้สลับแท็บได้) ════ --}}
            <div x-show="activeTab === 'security'" x-transition style="display:none;">
                <div class="tp-section-h" style="margin-bottom:18px;">🔒 ความปลอดภัย</div>
                <div style="display:flex; flex-direction:column; gap:14px;">

                    {{-- 2FA --}}
                    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid {{ $twoFa ? '#5aa07e' : '#d9534f' }};">
                        <div style="display:flex; align-items:flex-start; gap:12px; flex:1; min-width:220px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:{{ $twoFa ? '#5aa07e' : '#d9534f' }};"><i class="fas fa-shield-halved" style="color:#fff;"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">การยืนยันตัวตนแบบ 2 ชั้น (2FA)</div>
                                <div style="font-size:12px; color:var(--ink2); margin:3px 0 8px;">เพิ่มความปลอดภัยให้บัญชีของคุณ</div>
                                @if($twoFa)
                                    <span class="tp-pill" style="color:#fff; background:#5aa07e;"><i class="fas fa-circle-check" style="font-size:10px;"></i> เปิดใช้งานแล้ว</span>
                                @else
                                    <span class="tp-pill" style="color:#fff; background:#d9534f;"><i class="fas fa-circle-xmark" style="font-size:10px;"></i> ยังไม่ได้เปิดใช้งาน</span>
                                @endif
                            </div>
                        </div>
                        @if(RouteFacade::has('user.two-factor.setup'))
                            <a href="{{ route('user.two-factor.setup') }}" class="tp-btn tp-btn-primary" style="align-self:center;"><i class="fas fa-cog"></i> ตั้งค่า</a>
                        @endif
                    </div>

                    {{-- จำนวนอุปกรณ์ที่เข้าสู่ระบบ --}}
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;
                                padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:16px; background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-desktop" style="color:var(--deep1);"></i></span>
                            <div>
                                <div style="font-weight:700; font-size:13.5px;">อุปกรณ์ที่เข้าสู่ระบบ</div>
                                <div style="font-size:12px; color:var(--ink2);">
                                    มีอุปกรณ์ที่ใช้งานอยู่ <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ number_format($sessCount) }}</span> เครื่อง
                                </div>
                            </div>
                        </div>
                        @if(RouteFacade::has('user.settings.sessions.delete-others'))
                            <button type="button" class="tp-btn tp-btn-sm" @click="$refs.signoutForm.requestSubmit()">
                                <i class="fas fa-right-from-bracket"></i> ออกจากระบบอุปกรณ์อื่น
                            </button>
                        @endif
                    </div>

                    {{-- ข้อความเตือนความปลอดภัย --}}
                    <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm); font-size:12.5px; color:var(--ink2); border-left:4px solid #e0a52e;">
                        <i class="fas fa-circle-info" style="color:#e0a52e; margin-right:6px;"></i>
                        หากพบกิจกรรมที่ไม่คุ้นเคย แนะนำให้ออกจากระบบอุปกรณ์อื่นทั้งหมด แล้วเปลี่ยนรหัสผ่านทันที
                    </div>
                </div>
            </div>

            {{-- ปุ่มบันทึก (แสดงเฉพาะแท็บที่บันทึกค่าได้) --}}
            <div x-show="activeTab !== 'security'" style="margin-top:22px; display:flex; justify-content:flex-end;">
                <button type="submit" class="tp-btn tp-btn-primary" style="padding:11px 26px; font-weight:700;">
                    <i class="fas fa-save"></i> บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>

    {{-- ── ฟอร์มลบ session อื่นทั้งหมด (แยกออกนอกฟอร์มหลัก กัน nested form) ── --}}
    @if(RouteFacade::has('user.settings.sessions.delete-others'))
        <form x-ref="signoutForm" action="{{ route('user.settings.sessions.delete-others') }}" method="POST" style="display:none;"
              @submit="return confirm('ยืนยันออกจากระบบอุปกรณ์อื่นทั้งหมด?')">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
    /**
     * Settings Manager — Alpine.js component
     * จัดการสลับแท็บ + ซิงก์ธีม light/dark/auto ให้ดูผลทันที
     */
    function settingsManager() {
        return {
            activeTab: 'preferences',

            init() {
                // ไม่ต้องทำอะไรเพิ่มตอนเริ่ม — แท็บ default = preferences
            },

            /**
             * ซิงก์ธีมทันทีที่เลือก (light/dark/auto)
             * ค่าจริงถูกบันทึกผ่านฟอร์มเมื่อกด "บันทึกการตั้งค่า"
             */
            syncTheme(value) {
                try {
                    const root = document.documentElement;
                    if (value === 'dark') {
                        root.classList.add('dark');
                        localStorage.setItem('darkMode', 'true');
                    } else if (value === 'light') {
                        root.classList.remove('dark');
                        localStorage.setItem('darkMode', 'false');
                    } else {
                        // auto — ตามระบบปฏิบัติการ
                        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                        root.classList.toggle('dark', prefersDark);
                        localStorage.removeItem('darkMode');
                    }
                    window.dispatchEvent(new CustomEvent('dark-mode-changed', {
                        detail: { isDark: root.classList.contains('dark') }
                    }));
                    if (typeof window.showNotification === 'function') {
                        window.showNotification('ปรับธีมแล้ว — อย่าลืมกดบันทึกเพื่อจดจำการตั้งค่า', 'info');
                    }
                } catch (e) {
                    // เงียบไว้ — ธีมเป็น enhancement ไม่ใช่ฟังก์ชันหลัก
                }
            }
        };
    }
</script>
@endpush
