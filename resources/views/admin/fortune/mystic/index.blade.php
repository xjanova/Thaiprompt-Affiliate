{{-- คอนเทนต์สายมูอัตโนมัติ — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
<div x-data="mysticContentPage()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ──────────── Header ──────────── --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · คอนเทนต์สายมูอัตโนมัติ
            </div>
            <h1 class="tp-num" style="margin:0;font-size:26px;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-moon" style="color:var(--accent1);"></i> คอนเทนต์สายมูอัตโนมัติ
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                โพส Facebook ของแม่หมอจันทรา — สายมู / แก้เคล็ด / สิ่งลี้ลับ ฯลฯ ตาม schedule
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="tp-pill {{ $settings->mystic_content_enabled ? 'tp-pill-gold' : 'tp-pill-soft' }}">
                <i class="fas {{ $settings->mystic_content_enabled ? 'fa-circle-check' : 'fa-circle-pause' }}"></i>
                {{ $settings->mystic_content_enabled ? 'ระบบเปิดทำงาน' : 'ระบบปิดอยู่' }}
            </span>
        </div>
    </div>

    {{-- ──────────── KPI Grid ──────────── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        <div class="tp-card tp-raise" style="padding:18px;">
            <div class="tp-muted" style="font-size:12px;">โพสรวมทั้งหมด</div>
            <div class="tp-num" style="font-size:28px;margin-top:6px;">{{ number_format($stats['total_posts']) }}</div>
            <div class="tp-spark" style="margin-top:8px;color:var(--ink2);font-size:12px;">
                <i class="fas fa-layer-group"></i> สะสมตั้งแต่เริ่มระบบ
            </div>
        </div>
        <div class="tp-card tp-raise" style="padding:18px;">
            <div class="tp-muted" style="font-size:12px;">โพสสำเร็จวันนี้</div>
            <div class="tp-num" style="font-size:28px;margin-top:6px;color:#5aa07e;">{{ $stats['posted_today'] }}</div>
            <div class="tp-spark" style="margin-top:8px;color:#5aa07e;font-size:12px;">
                <i class="fas fa-circle-check"></i> ส่งขึ้น FB เรียบร้อย
            </div>
        </div>
        <div class="tp-card tp-raise" style="padding:18px;">
            <div class="tp-muted" style="font-size:12px;">ล้มเหลววันนี้</div>
            <div class="tp-num" style="font-size:28px;margin-top:6px;color:{{ $stats['failed_today'] > 0 ? '#d9534f' : 'var(--ink2)' }};">{{ $stats['failed_today'] }}</div>
            <div class="tp-spark" style="margin-top:8px;color:{{ $stats['failed_today'] > 0 ? '#d9534f' : 'var(--ink2)' }};font-size:12px;">
                <i class="fas {{ $stats['failed_today'] > 0 ? 'fa-triangle-exclamation' : 'fa-circle-minus' }}"></i>
                {{ $stats['failed_today'] > 0 ? 'ตรวจสอบ error ด้านล่าง' : 'ไม่มีข้อผิดพลาด' }}
            </div>
        </div>
        <div class="tp-card tp-raise" style="padding:18px;">
            <div class="tp-muted" style="font-size:12px;">หมวดที่เปิดใช้งาน</div>
            <div class="tp-num" style="font-size:28px;margin-top:6px;color:#b79ae8;">{{ $stats['enabled_topics'] }}<span class="tp-muted" style="font-size:18px;">/{{ $topics->count() }}</span></div>
            <div class="tp-spark" style="margin-top:8px;color:var(--ink2);font-size:12px;">
                <i class="fas fa-book-open"></i> หมุนเวียนสุ่มหัวข้อ
            </div>
        </div>
    </div>

    {{-- ──────────── Validation Errors ──────────── --}}
    @if($errors->any())
        <div class="tp-card" style="padding:16px 18px;border-left:4px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;color:#d9534f;font-weight:600;margin-bottom:8px;">
                <i class="fas fa-circle-exclamation"></i> พบข้อผิดพลาด
            </div>
            <ul style="margin:0;padding-left:20px;color:var(--ink2);font-size:13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ──────────── Settings Form ──────────── --}}
    <form action="{{ route('admin.fortune.mystic.settings.update') }}" method="POST" style="display:flex;flex-direction:column;gap:18px;">
        @csrf
        @method('PUT')

        {{-- ── สถานะระบบ (Toggles) ── --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-sliders" style="color:var(--accent1);"></i>
                <span style="font-weight:700;font-size:16px;">สถานะระบบ</span>
            </div>

            {{-- Daily Horoscope Per Day toggle --}}
            <label class="tp-inset" style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:16px;border-radius:12px;margin-bottom:12px;">
                <input type="hidden" name="daily_horoscope_per_day_enabled" value="0">
                <input type="checkbox" name="daily_horoscope_per_day_enabled" value="1"
                       style="width:18px;height:18px;margin-top:2px;accent-color:var(--accent1);cursor:pointer;"
                       {{ $settings->daily_horoscope_per_day_enabled ? 'checked' : '' }}>
                <div style="flex:1;">
                    <span style="font-weight:600;color:var(--ink);">📅 ระบบดวงรายวันเกิด (เดิม) — ตี 1-7 โมงเช้า</span>
                    <p class="tp-muted" style="font-size:12px;margin:4px 0 0;">
                        โพสดวงประจำวันสำหรับคนเกิดวันจันทร์-อาทิตย์ (รายชั่วโมง 01:00-07:00)
                        <strong style="color:#d6824a;">— ปิดเป็น default หลัง deploy v3</strong>
                    </p>
                </div>
            </label>

            {{-- Mystic Content toggle --}}
            <label class="tp-inset" style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:16px;border-radius:12px;border:2px solid var(--accent1);background:var(--a1soft);">
                <input type="hidden" name="mystic_content_enabled" value="0">
                <input type="checkbox" name="mystic_content_enabled" value="1"
                       style="width:18px;height:18px;margin-top:2px;accent-color:var(--accent1);cursor:pointer;"
                       {{ $settings->mystic_content_enabled ? 'checked' : '' }}>
                <div style="flex:1;">
                    <span style="font-weight:600;color:var(--ink);">🌙 ระบบคอนเทนต์สายมู (ใหม่)</span>
                    <p class="tp-muted" style="font-size:12px;margin:4px 0 0;">
                        โพสคอนเทนต์ สายมู / แก้เคล็ด / ปัญหาชีวิต / สิ่งลี้ลับ / รู้หรือไม่ทั่วโลก
                        — สุ่มหัวข้อหมุนเวียน + AI เขียนใหม่จากแหล่งเว็บที่น่าเชื่อถือ
                    </p>
                </div>
            </label>
        </div>

        {{-- ── ตารางเวลาโพส ── --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <i class="fas fa-clock" style="color:var(--accent1);"></i>
                <span style="font-weight:700;font-size:16px;">ตารางเวลาโพส</span>
            </div>
            <p class="tp-muted" style="font-size:13px;margin:0 0 16px;">
                กำหนดชั่วโมงที่ต้องการโพส (Asia/Bangkok) — โพสได้สูงสุด 6 ครั้ง/วัน เพื่อไม่ให้โดน FB ลด reach
            </p>

            <div x-data="{ slots: @json(!empty($settings->mystic_content_schedule) ? $settings->mystic_content_schedule : ['08:00', '20:00']) }">
                <template x-for="(slot, idx) in slots" :key="idx">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <div class="tp-well tp-input" style="padding:0;flex:1;max-width:220px;">
                            <input type="time"
                                   :name="'mystic_content_schedule[' + idx + ']'"
                                   x-model="slots[idx]"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                        </div>
                        <button type="button"
                                @click="slots.splice(idx, 1)"
                                class="tp-icon-btn"
                                style="color:#d9534f;"
                                title="ลบ slot">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </div>
                </template>

                <button type="button"
                        @click="if (slots.length < 6) slots.push('12:00')"
                        :disabled="slots.length >= 6"
                        class="tp-btn tp-btn-sm"
                        style="margin-top:8px;"
                        :style="slots.length >= 6 ? 'opacity:.5;cursor:not-allowed;' : ''">
                    <i class="fas fa-plus"></i> เพิ่ม slot (สูงสุด 6 ตัว)
                </button>
            </div>
        </div>

        {{-- ── คุณภาพคอนเทนต์ ── --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-pen-nib" style="color:var(--accent1);"></i>
                <span style="font-weight:700;font-size:16px;">คุณภาพคอนเทนต์</span>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:8px;">
                        ความยาวขั้นต่ำ (ตัวอักษร)
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="mystic_content_caption_min"
                               value="{{ $settings->mystic_content_caption_min ?? 400 }}"
                               min="100" max="2000"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:8px;">
                        ความยาวสูงสุด (ตัวอักษร)
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="mystic_content_caption_max"
                               value="{{ $settings->mystic_content_caption_max ?? 700 }}"
                               min="200" max="3000"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:8px;">
                        จำนวน Hashtag/โพส
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="mystic_content_hashtag_count"
                               value="{{ $settings->mystic_content_hashtag_count ?? 6 }}"
                               min="0" max="10"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                    <p class="tp-muted" style="font-size:11px;margin:6px 0 0;">แนะนำ ≤7 (เกินจะโดน FB ลด reach)</p>
                </div>
            </div>
        </div>

        {{-- ── Web Search API ── --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <i class="fas fa-magnifying-glass" style="color:var(--accent1);"></i>
                <span style="font-weight:700;font-size:16px;">Web Search API</span>
            </div>
            <p class="tp-muted" style="font-size:13px;margin:0 0 16px;">
                AI จะค้นข้อมูลจากเว็บที่น่าเชื่อถือก่อนเขียนบทความ — เลือกอย่างน้อย 1 service หรือใช้ DuckDuckGo ฟรี (ไม่ต้องคีย์)
            </p>

            <div style="display:flex;flex-direction:column;gap:16px;">
                <div>
                    <label style="display:flex;align-items:center;justify-content:space-between;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:8px;gap:8px;">
                        <span>🥇 Tavily API Key <span class="tp-muted" style="font-size:11px;font-weight:400;">(แนะนำ — 1000 free/เดือน)</span></span>
                        @if(!empty($settings->tavily_api_key))
                            <span class="tp-pill" style="color:#5aa07e;border-color:#5aa07e;font-size:11px;">
                                <i class="fas fa-check"></i> บันทึกแล้ว ({{ strlen($settings->tavily_api_key) }} ตัวอักษร)
                            </span>
                        @endif
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="password" name="tavily_api_key"
                               placeholder="{{ $settings->tavily_api_key ? '••••••••••••• (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน — กรอกใหม่เพื่อแทนที่)' : 'tvly-... (สมัครฟรีที่ tavily.com)' }}"
                               autocomplete="off"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                    @if(!empty($settings->tavily_api_key))
                        <p style="font-size:11px;color:#5aa07e;margin:6px 0 0;">
                            🔒 Key ถูกบันทึกในฐานข้อมูลแล้ว — ช่อง input ว่างเป็นเรื่องปกติ (browser ไม่แสดง password ที่บันทึกไว้)
                        </p>
                    @endif
                </div>

                <div>
                    <label style="display:flex;align-items:center;justify-content:space-between;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:8px;gap:8px;">
                        <span>🥈 Brave Search API Key <span class="tp-muted" style="font-size:11px;font-weight:400;">(fallback — 2000 free/เดือน)</span></span>
                        @if(!empty($settings->brave_search_api_key))
                            <span class="tp-pill" style="color:#5aa07e;border-color:#5aa07e;font-size:11px;">
                                <i class="fas fa-check"></i> บันทึกแล้ว ({{ strlen($settings->brave_search_api_key) }} ตัวอักษร)
                            </span>
                        @endif
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="password" name="brave_search_api_key"
                               placeholder="{{ $settings->brave_search_api_key ? '••••••••••••• (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)' : 'BSA... (สมัครที่ brave.com/search/api)' }}"
                               autocomplete="off"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                <div class="tp-inset-sm" style="padding:14px 16px;border-radius:12px;border-left:4px solid #5689b8;font-size:13px;color:var(--ink2);">
                    💡 <strong style="color:var(--ink);">ฟรีเกือบทั้งระบบ:</strong> 2 โพส/วัน × 30 วัน = 60 search/เดือน → Tavily ฟรี 1000 หรือ Brave ฟรี 2000 ใช้ไม่หมดแน่นอน หากไม่กรอกคีย์ใดๆ ระบบจะใช้ DuckDuckGo HTML scraping (ฟรีไม่จำกัด)
                </div>
            </div>
        </div>

        {{-- Save button --}}
        <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    {{-- ──────────── Manual Publish ──────────── --}}
    <div class="tp-card" style="padding:22px;border-left:4px solid #e0a52e;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="fas fa-bolt" style="color:#e0a52e;"></i>
            <span style="font-weight:700;font-size:16px;">โพสทันที (ทดสอบ)</span>
        </div>
        <p class="tp-muted" style="font-size:13px;margin:0 0 16px;">
            โพสตอนนี้เลย ไม่ต้องรอ schedule — ใช้สำหรับทดสอบหรือรันโพสที่พลาด
        </p>

        <form action="{{ route('admin.fortune.mystic.publish-now') }}" method="POST" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;">
            @csrf

            <div>
                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:8px;">
                    Slot Hour (0-23)
                </label>
                <div class="tp-well tp-input" style="padding:0;width:120px;">
                    <input type="number" name="slot" value="{{ now('Asia/Bangkok')->hour }}" min="0" max="23"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
            </div>

            <label class="tp-inset-sm" style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:9px 14px;border-radius:10px;">
                <input type="hidden" name="force" value="0">
                <input type="checkbox" name="force" value="1"
                       style="width:16px;height:16px;accent-color:#e0a52e;cursor:pointer;">
                <span style="font-size:13px;color:var(--ink2);">--force (ลบของเก่าก่อน)</span>
            </label>

            <button type="submit" class="tp-btn tp-btn-sm" style="background:#e0a52e;color:#fff;border-color:#e0a52e;">
                <i class="fas fa-bolt"></i> โพสทันที
            </button>
        </form>
    </div>

    {{-- ──────────── Topics Management ──────────── --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="fas fa-book-open" style="color:var(--accent1);"></i>
            <span style="font-weight:700;font-size:16px;">หมวดและหัวข้อ</span>
        </div>
        <p class="tp-muted" style="font-size:13px;margin:0 0 16px;">
            สุ่มหมวด → สุ่ม sub-topic → search web → AI rewrite → โพส (1 บรรทัด = 1 รายการ ใน textarea)
        </p>

        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($topics as $topic)
                <details class="tp-inset" style="border-radius:12px;overflow:hidden;">
                    <summary style="cursor:pointer;padding:16px;display:flex;justify-content:space-between;align-items:center;gap:12px;list-style:none;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="font-size:26px;">{{ $topic->emoji }}</span>
                            <div>
                                <p style="margin:0;font-weight:600;color:var(--ink);">{{ $topic->name_th }}</p>
                                <p class="tp-muted" style="margin:2px 0 0;font-size:12px;">
                                    {{ count($topic->sub_topic_pool ?? []) }} sub-topics •
                                    ใช้ไป {{ $topic->use_count }} ครั้ง
                                    @if($topic->last_used_at) • ล่าสุด {{ $topic->last_used_at->diffForHumans() }} @endif
                                </p>
                            </div>
                        </div>
                        <span class="tp-pill {{ $topic->is_enabled ? 'tp-pill-gold' : 'tp-pill-soft' }}" style="font-size:11px;">
                            <i class="fas {{ $topic->is_enabled ? 'fa-circle-check' : 'fa-circle-pause' }}"></i>
                            {{ $topic->is_enabled ? 'เปิด' : 'ปิด' }}
                        </span>
                    </summary>

                    <form action="{{ route('admin.fortune.mystic.topics.update', $topic) }}" method="POST" class="tp-divider" style="padding:18px;border-top:1px solid var(--sd);">
                        @csrf
                        @method('PUT')

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:16px;">
                            <div>
                                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">ชื่อหมวด</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="text" name="name_th" value="{{ $topic->name_th }}"
                                           style="width:100%;background:transparent;border:0;outline:0;padding:10px 14px;color:var(--ink);font-size:14px;">
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Emoji</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="text" name="emoji" value="{{ $topic->emoji }}" maxlength="10"
                                           style="width:100%;background:transparent;border:0;outline:0;padding:10px 14px;color:var(--ink);font-size:14px;">
                                </div>
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">คำอธิบาย</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="text" name="description_th" value="{{ $topic->description_th }}"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:10px 14px;color:var(--ink);font-size:14px;">
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:16px;">
                            <div>
                                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Sub-topics (1 บรรทัด/รายการ)</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <textarea name="sub_topic_pool_text" rows="6"
                                              style="width:100%;background:transparent;border:0;outline:0;padding:10px 14px;color:var(--ink);font-size:13px;font-family:monospace;resize:vertical;">{{ implode("\n", $topic->sub_topic_pool ?? []) }}</textarea>
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Hashtags (1 บรรทัด/รายการ — ขึ้นต้นด้วย #)</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <textarea name="hashtag_pool_text" rows="6"
                                              style="width:100%;background:transparent;border:0;outline:0;padding:10px 14px;color:var(--ink);font-size:13px;font-family:monospace;resize:vertical;">{{ implode("\n", $topic->hashtag_pool ?? []) }}</textarea>
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Image Keywords (eng — สำหรับ AI image)</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <textarea name="image_keywords_text" rows="4"
                                              style="width:100%;background:transparent;border:0;outline:0;padding:10px 14px;color:var(--ink);font-size:13px;font-family:monospace;resize:vertical;">{{ implode("\n", $topic->image_keywords ?? []) }}</textarea>
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Search Queries (สำหรับ web search)</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <textarea name="search_queries_text" rows="4"
                                              style="width:100%;background:transparent;border:0;outline:0;padding:10px 14px;color:var(--ink);font-size:13px;font-family:monospace;resize:vertical;">{{ implode("\n", $topic->search_queries ?? []) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:16px;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="hidden" name="is_enabled" value="0">
                                <input type="checkbox" name="is_enabled" value="1"
                                       style="width:16px;height:16px;accent-color:var(--accent1);cursor:pointer;"
                                       {{ $topic->is_enabled ? 'checked' : '' }}>
                                <span style="font-size:13px;color:var(--ink2);">เปิดใช้งานหมวดนี้</span>
                            </label>

                            <div style="display:flex;align-items:center;gap:8px;">
                                <label style="font-size:13px;color:var(--ink2);">ลำดับ:</label>
                                <div class="tp-well tp-input" style="padding:0;width:80px;">
                                    <input type="number" name="sort_order" value="{{ $topic->sort_order }}" min="0"
                                           style="width:100%;background:transparent;border:0;outline:0;padding:7px 10px;color:var(--ink);font-size:13px;">
                                </div>
                            </div>

                            <button type="submit" class="tp-btn tp-btn-sm" style="margin-left:auto;">
                                <i class="fas fa-floppy-disk"></i> บันทึกหมวดนี้
                            </button>
                        </div>
                    </form>
                </details>
            @endforeach
        </div>
    </div>

    {{-- ──────────── Recent Posts ──────────── --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <i class="fas fa-scroll" style="color:var(--accent1);"></i>
            <span style="font-weight:700;font-size:16px;">โพสล่าสุด</span>
        </div>

        @if($recentPosts->isEmpty())
            <div style="text-align:center;padding:48px 16px;color:var(--ink2);">
                <i class="fas fa-inbox" style="font-size:42px;opacity:.4;"></i>
                <p style="margin:14px 0 0;font-size:14px;">ยังไม่มีโพส — กดปุ่ม "โพสทันที" ด้านบนเพื่อทดสอบ</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="min-width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--sd);">
                            <th class="tp-muted" style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">วันที่ • slot</th>
                            <th class="tp-muted" style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">หมวด</th>
                            <th class="tp-muted" style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">หัวข้อ</th>
                            <th class="tp-muted" style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Search</th>
                            <th class="tp-muted" style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Status</th>
                            <th class="tp-muted" style="padding:10px 12px;text-align:right;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPosts as $post)
                            <tr style="border-bottom:1px solid var(--sd);">
                                <td style="padding:10px 12px;color:var(--ink);white-space:nowrap;">
                                    {{ $post->post_date->format('d/m') }} • {{ sprintf('%02d:00', $post->slot_hour) }}
                                </td>
                                <td style="padding:10px 12px;color:var(--ink2);">
                                    @if($post->topic)
                                        {{ $post->topic->emoji }} {{ $post->topic->name_th }}
                                    @else
                                        <span class="tp-muted">—</span>
                                    @endif
                                </td>
                                <td style="padding:10px 12px;color:var(--ink2);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $post->sub_topic ?? '—' }}</td>
                                <td style="padding:10px 12px;color:var(--ink2);font-size:12px;">{{ $post->search_provider ?? '—' }}</td>
                                <td style="padding:10px 12px;">
                                    @if($post->status === 'posted')
                                        <span class="tp-pill" style="color:#5aa07e;border-color:#5aa07e;font-size:11px;"><i class="fas fa-circle-check"></i> posted</span>
                                    @elseif($post->status === 'failed')
                                        <span class="tp-pill" style="color:#d9534f;border-color:#d9534f;font-size:11px;" title="{{ $post->error_message }}"><i class="fas fa-circle-xmark"></i> failed</span>
                                    @else
                                        <span class="tp-pill tp-pill-soft" style="font-size:11px;">{{ $post->status }}</span>
                                    @endif
                                </td>
                                <td style="padding:10px 12px;text-align:right;white-space:nowrap;">
                                    @if($post->fb_post_url)
                                        <a href="{{ $post->fb_post_url }}" target="_blank" style="color:#5689b8;text-decoration:none;font-size:12px;">FB <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;"></i></a>
                                    @endif
                                    <button type="button" @click="viewPost({{ $post->id }})" class="tp-btn tp-btn-sm" style="margin-left:8px;padding:4px 12px;font-size:12px;">
                                        <i class="fas fa-eye"></i> ดู
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ──────────── Modal: Post Detail ──────────── --}}
    <div x-show="modalOpen"
         x-cloak
         class="tp-modal-backdrop"
         style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.55);"
         @click.self="modalOpen = false">
        <div class="tp-card" style="max-width:680px;width:100%;max-height:90vh;overflow-y:auto;padding:24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 class="tp-num" style="margin:0;font-size:18px;font-weight:700;">รายละเอียดโพส</h3>
                <button @click="modalOpen = false" class="tp-icon-btn"><i class="fas fa-xmark"></i></button>
            </div>

            <template x-if="currentPost">
                <div style="display:flex;flex-direction:column;gap:12px;font-size:13px;">
                    <div><strong class="tp-muted">วันที่:</strong> <span x-text="currentPost.post_date + ' slot ' + currentPost.slot_hour + ':00'" style="color:var(--ink);"></span></div>
                    <div><strong class="tp-muted">หมวด:</strong> <span x-text="currentPost.topic" style="color:var(--ink);"></span></div>
                    <div><strong class="tp-muted">หัวข้อ:</strong> <span x-text="currentPost.sub_topic" style="color:var(--ink);"></span></div>
                    <div x-show="currentPost.image_url">
                        <img :src="currentPost.image_url" style="max-width:100%;border-radius:12px;margin:8px 0;box-shadow:var(--raise);">
                    </div>
                    <div>
                        <strong class="tp-muted">Caption:</strong>
                        <pre x-text="currentPost.caption" class="tp-inset-sm" style="margin-top:6px;padding:12px;border-radius:10px;white-space:pre-wrap;color:var(--ink);font-size:12px;font-family:inherit;"></pre>
                    </div>
                    <div x-show="currentPost.sources && currentPost.sources.length > 0">
                        <strong class="tp-muted">แหล่งอ้างอิง (<span x-text="currentPost.search_provider"></span>):</strong>
                        <ul style="margin:6px 0 0;padding-left:18px;display:flex;flex-direction:column;gap:4px;">
                            <template x-for="src in currentPost.sources" :key="src.url">
                                <li style="font-size:12px;">
                                    <a :href="src.url" target="_blank" style="color:#5689b8;" x-text="src.title || src.url"></a>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div x-show="currentPost.error_message" class="tp-inset-sm" style="padding:10px 12px;border-radius:10px;border-left:4px solid #d9534f;color:#d9534f;font-size:12px;">
                        <strong>Error:</strong> <span x-text="currentPost.error_message"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- scripts stack: Alpine component สำหรับหน้าคอนเทนต์สายมู (modal + AJAX โหลดรายละเอียดโพส) --}}
<style>
    [x-cloak] { display: none !important; }
    details > summary::-webkit-details-marker { display: none; }
</style>
<script>
function mysticContentPage() {
    return {
        modalOpen: false,
        currentPost: null,

        async viewPost(id) {
            try {
                const res = await fetch(`{{ url('/admin/fortune/mystic/posts') }}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                this.currentPost = await res.json();
                this.modalOpen = true;
            } catch (e) {
                this.$dispatch('notify', { type: 'error', message: 'โหลดข้อมูลล้มเหลว: ' + e.message });
            }
        }
    };
}
</script>
@endpush
