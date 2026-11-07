@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- 🔁 รีโพสลงเพจสาขา (2026-11-07)
     ทุกเพจ Facebook ในระบบสาขาโผล่ที่นี่เอง — เพิ่มสาขาใหม่ในหน้า "สาขา / เพจแม่หมอ" แล้วมาเปิดสวิตช์ที่นี่ได้เลย
     ใช้สไตล์เดียวกับหน้าสาขา (layouts.admin-v4 + คลาส tp-*) เพื่อให้หน้าตาเข้าชุดกัน --}}
@php
    $statusTone = [
        'sending' => '#c98a3a',
        'scheduled' => 'var(--accent1)',
        'published' => '#3f9b5a',
        'failed' => '#d9534f',
        'cancelled' => 'var(--ink2)',
    ];
@endphp
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · สาขา</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">รีโพสลงเพจสาขา 🔁</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                เอาโพสของเพจหลักไปลงเพจสาขาอัตโนมัติ เวลาเหลื่อมกันตามที่ตั้ง — วันเดียวกันสองเพจไม่มีโพสซ้ำกัน
            </div>
        </div>
        <a href="{{ route('admin.fortune.pages.index') }}" class="tp-btn">
            <i class="fas fa-store"></i> จัดการสาขา / เพจ
        </a>
    </div>

    {{-- ===== วิธีทำงาน ===== --}}
    <div class="tp-card" style="padding:18px 20px;" x-data="{ open: false }">
        <button type="button" @click="open = !open" style="all:unset; cursor:pointer; display:flex; align-items:center; gap:8px; width:100%;">
            <span class="tp-section-h"><i class="fas fa-circle-info"></i> ระบบนี้ทำงานยังไง</span>
            <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" style="margin-left:auto; color:var(--ink2);"></i>
        </button>
        <div x-show="open" style="font-size:12.5px; color:var(--ink); line-height:1.75; margin-top:10px;">
            <div>1. <b>เวลาโพส</b> = เวลาที่เพจต้นทางโพส + เวลาเหลื่อมของสาขา (เช่น ต้นทาง 07:00 → สาขา 07:30) ต้นทางเปลี่ยนตารางวันไหน สาขาตามเอง</div>
            <div>2. <b>คอนเทนต์</b> = โพสที่ต้นทางลงไว้ <b>ก่อนวันนั้น</b> เท่านั้น → วันเดียวกันสองเพจไม่ซ้ำกัน · โพสต้นฉบับ 1 ใบลงสาขาเดียวกันได้ครั้งเดียว</div>
            <div>3. <b>ตัดทิ้งอัตโนมัติ</b>: โพสที่มีวันที่ในข้อความ (เช่น ดวงรายวัน 06/11/2569) และคำที่ตั้งห้ามไว้ · อัลบั้มหลายรูป · โพสแชร์</div>
            <div>4. ระบบตั้งเวลาไว้ใน Facebook ล่วงหน้า ทำงานทุก 15 นาที · <b>ปิดสวิตช์ = ยกเลิกโพสที่ตั้งเวลาไว้ทั้งหมดของสาขานั้น</b></div>
            <div>5. โพสรูปที่ตั้งเวลาผ่านระบบ <b>ไม่โผล่ในหน้า "กำหนดเวลาไว้" ของ Business Suite</b> แต่ขึ้นตรงเวลา — ดู/ยกเลิกได้จากประวัติด้านล่าง</div>
        </div>
    </div>

    @if($pages->isEmpty())
        <div class="tp-card" style="padding:28px; text-align:center; color:var(--ink2); font-size:13px;">
            ยังไม่มีเพจ Facebook ในระบบสาขา — เพิ่มเพจในหน้า “สาขา / เพจแม่หมอ” ก่อน
        </div>
    @endif

    {{-- ===== การ์ดรายสาขา ===== --}}
    @foreach($pages as $page)
        @php
            $cfg = $page->repostConfig();
            $pageStats = $stats[$page->id] ?? [];
            $next = $nextSlots[$page->id] ?? null;
            $branchNo = $page->branchNumber();
            $sourceName = $page->repostSource?->display_label
                ?? ($defaultSource && $defaultSource->id !== $page->id ? $defaultSource->display_label.' (สาขาหลัก)' : '— ยังไม่มี —');
        @endphp
        <div class="tp-card" style="padding:18px 20px; {{ $page->is_active ? '' : 'opacity:.6;' }}"
             x-data="tpRepostCard(@js(route('admin.fortune.reposts.preview', $page)), {{ ($page->repost_enabled || $filterPageId === $page->id) ? 'true' : 'false' }})">

            {{-- หัวการ์ด --}}
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px 14px;">
                <div style="flex:1; min-width:200px;">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px;">
                        <span style="font-weight:800; font-size:15px;">{{ $page->display_label }}</span>
                        @if($page->is_default)<span class="tp-pill tp-pill-gold" style="font-size:10.5px;">สาขาหลัก</span>@endif
                        @if($branchNo !== null)<span class="tp-pill tp-pill-soft" style="font-size:10.5px;">สาขา {{ $branchNo }}</span>@endif
                        @unless($page->is_active)<span class="tp-pill" style="font-size:10.5px;">ปิดใช้งาน</span>@endunless
                        @if(empty($page->page_access_token))<span class="tp-pill" style="font-size:10.5px; color:#d9534f;">ไม่มี token</span>@endif
                    </div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">
                        ต้นทาง: {{ $sourceName }} · เหลื่อม +{{ (int) $page->repost_offset_minutes }} นาที
                        · {{ (int) $page->repost_max_per_day > 0 ? 'สูงสุด '.(int) $page->repost_max_per_day.' โพส/วัน' : 'ทุกช่องของต้นทาง' }}
                    </div>
                    <div style="font-size:12px; margin-top:4px; display:flex; flex-wrap:wrap; gap:4px 12px;">
                        <span style="color:var(--ink2);">7 วันล่าสุด:</span>
                        <span style="color:{{ $statusTone['scheduled'] }};">ตั้งเวลา {{ $pageStats['scheduled'] ?? 0 }}</span>
                        <span style="color:{{ $statusTone['published'] }};">ขึ้นแล้ว {{ $pageStats['published'] ?? 0 }}</span>
                        <span style="color:{{ $statusTone['failed'] }};">ไม่ผ่าน {{ $pageStats['failed'] ?? 0 }}</span>
                        @if($next)<span style="color:var(--ink);">· โพสถัดไป {{ \Illuminate\Support\Carbon::parse($next)->format('d/m H:i') }}</span>@endif
                    </div>
                </div>

                {{-- 🔘 สวิตช์เปิด/ปิด --}}
                <form method="POST" action="{{ route('admin.fortune.reposts.toggle', $page) }}"
                      @submit.prevent="if (confirm(@js($page->repost_enabled
                            ? 'ปิดรีโพสของ “'.$page->display_label.'” ?\n\nโพสที่ตั้งเวลาไว้ล่วงหน้าทั้งหมดของสาขานี้จะถูกยกเลิกด้วย'
                            : 'เปิดรีโพสลง “'.$page->display_label.'” ?\n\nระบบจะเริ่มตั้งเวลาโพสลงหน้าเพจนี้จริงภายใน 15 นาที'))) $el.submit()">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="tp-btn {{ $page->repost_enabled ? 'tp-btn-primary' : '' }}"
                            style="min-width:132px; min-height:44px;"
                            aria-pressed="{{ $page->repost_enabled ? 'true' : 'false' }}">
                        <i class="fas {{ $page->repost_enabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        {{ $page->repost_enabled ? 'เปิดรีโพสอยู่' : 'ปิดรีโพสอยู่' }}
                    </button>
                </form>

                <button type="button" class="tp-btn" style="min-height:44px;" @click="open = !open">
                    <i class="fas fa-sliders"></i> <span x-text="open ? 'ซ่อนการตั้งค่า' : 'ตั้งค่า'"></span>
                </button>
            </div>

            <div x-show="open" x-cloak>
                <hr class="tp-divider" style="margin:16px 0;">

                @if($page->is_default)
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:12px;">
                        ℹ️ เพจนี้เป็นสาขาหลัก = เพจต้นทางของสาขาอื่น ปกติไม่ต้องเปิดรีโพส
                    </div>
                @endif

                {{-- ⚙️ ฟอร์มตั้งค่า --}}
                <form method="POST" action="{{ route('admin.fortune.reposts.update', $page) }}">
                    @csrf
                    @method('PUT')
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
                        <label style="display:flex; flex-direction:column; gap:6px; font-size:12.5px; font-weight:600;">
                            เพจต้นทาง (เอาโพสมาจากเพจไหน)
                            <select name="repost_source_page_id" class="tp-input">
                                <option value="">สาขาหลัก{{ $defaultSource ? ' — '.$defaultSource->display_label : '' }}</option>
                                @foreach($pages as $opt)
                                    @continue($opt->id === $page->id || $opt->is_default)
                                    <option value="{{ $opt->id }}" @selected((int) $page->repost_source_page_id === $opt->id)>{{ $opt->display_label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label style="display:flex; flex-direction:column; gap:6px; font-size:12.5px; font-weight:600;">
                            เหลื่อมจากต้นทาง (นาที)
                            <input type="number" name="repost_offset_minutes" min="5" max="720" required class="tp-input"
                                   value="{{ old('repost_offset_minutes', (int) ($page->repost_offset_minutes ?: 30)) }}">
                        </label>

                        <label style="display:flex; flex-direction:column; gap:6px; font-size:12.5px; font-weight:600;">
                            สูงสุดกี่โพสต่อวัน (0 = ทุกช่องของต้นทาง)
                            <input type="number" name="repost_max_per_day" min="0" max="50" required class="tp-input"
                                   value="{{ old('repost_max_per_day', (int) $page->repost_max_per_day) }}">
                        </label>

                        <label style="display:flex; flex-direction:column; gap:6px; font-size:12.5px; font-weight:600;">
                            เลือกโพสแบบไหนก่อน
                            <select name="pick_order" class="tp-input">
                                <option value="newest" @selected($cfg['pick_order'] === 'newest')>ใหม่สุดก่อน (ตามหลังต้นทาง ~1 วัน)</option>
                                <option value="oldest" @selected($cfg['pick_order'] === 'oldest')>เก่าสุดก่อน (ไล่คลังจากของเก่า)</option>
                                <option value="random" @selected($cfg['pick_order'] === 'random')>สุ่ม (หลายสาขาจะได้คนละโพส)</option>
                            </select>
                        </label>

                        <label style="display:flex; flex-direction:column; gap:6px; font-size:12.5px; font-weight:600;">
                            ดึงคลังโพสต้นทางย้อนหลัง (วัน)
                            <input type="number" name="lookback_days" min="1" max="180" required class="tp-input"
                                   value="{{ old('lookback_days', $cfg['lookback_days']) }}">
                        </label>

                        <label style="display:flex; flex-direction:column; gap:6px; font-size:12.5px; font-weight:600;">
                            ตั้งเวลาล่วงหน้า (ชั่วโมง)
                            <input type="number" name="lookahead_hours" min="1" max="48" required class="tp-input"
                                   value="{{ old('lookahead_hours', $cfg['lookahead_hours']) }}">
                        </label>
                    </div>

                    <div style="margin-top:14px; font-size:12.5px; font-weight:600;">ประเภทโพสที่รีโพส</div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px 18px; margin-top:6px; font-size:13px;">
                        @foreach(['photo' => 'รูปภาพ (บทความ)', 'video' => 'วิดีโอ / คลิป', 'text' => 'ข้อความล้วน'] as $type => $label)
                            <label style="display:flex; align-items:center; gap:6px; min-height:32px; cursor:pointer;">
                                <input type="checkbox" name="media_types[]" value="{{ $type }}" @checked(in_array($type, $cfg['media_types'], true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:14px; margin-top:14px;">
                        <label style="display:flex; flex-direction:column; gap:6px; font-size:12.5px; font-weight:600;">
                            ข้อความต่อท้ายทุกโพส (เว้นว่างได้)
                            <textarea name="caption_suffix" rows="3" maxlength="500" class="tp-input" style="resize:vertical;"
                                      placeholder="เช่น แม่หมอจันทรา สาขาจังหวัดเลย">{{ old('caption_suffix', $cfg['caption_suffix']) }}</textarea>
                        </label>
                        <label style="display:flex; flex-direction:column; gap:6px; font-size:12.5px; font-weight:600;">
                            คำที่ห้ามรีโพส (บรรทัดละคำ)
                            <textarea name="exclude_keywords" rows="3" maxlength="2000" class="tp-input" style="resize:vertical;"
                                      placeholder="ดวงรายวัน">{{ old('exclude_keywords', implode("\n", $cfg['exclude_keywords'])) }}</textarea>
                            <span class="tp-muted" style="font-weight:400; font-size:11.5px;">โพสที่มีวันที่ในข้อความถูกตัดทิ้งเสมอ ไม่ต้องใส่</span>
                        </label>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:16px;">
                        <button type="submit" class="tp-btn tp-btn-primary" style="min-height:44px;">
                            <i class="fas fa-floppy-disk"></i> บันทึกการตั้งค่า
                        </button>
                        <button type="button" class="tp-btn" style="min-height:44px;" @click="loadPreview()" :disabled="loading">
                            <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-eye'"></i> ดูตัวอย่างแผน (ไม่โพสจริง)
                        </button>
                    </div>
                </form>

                @if($page->repost_enabled)
                    <form method="POST" action="{{ route('admin.fortune.reposts.run', $page) }}" style="margin-top:10px;"
                          @submit.prevent="if (confirm(@js('ตั้งเวลาโพสลง “'.$page->display_label.'” เดี๋ยวนี้?\n\nระบบจะสร้างโพสตั้งเวลาไว้ใน Facebook จริง'))) { running = true; $el.submit(); }">
                        @csrf
                        <button type="submit" class="tp-btn" style="min-height:44px;" :disabled="running">
                            <i class="fas" :class="running ? 'fa-spinner fa-spin' : 'fa-bolt'"></i> ตั้งเวลาเดี๋ยวนี้ (ไม่ต้องรอรอบ 15 นาที)
                        </button>
                    </form>
                @endif

                {{-- 👁️ ผลดูตัวอย่าง --}}
                <template x-if="preview">
                    <div class="tp-well" style="margin-top:14px; padding:14px; border-radius:14px; font-size:12.5px;">
                        <div style="font-weight:700;" :style="preview.ok ? '' : 'color:#d9534f'" x-text="preview.message"></div>
                        <template x-if="preview.planned && preview.planned.length">
                            <div style="overflow-x:auto; margin-top:8px;">
                                <table style="width:100%; border-collapse:collapse; min-width:520px;">
                                    <thead>
                                        <tr style="text-align:left; color:var(--ink2); font-size:11.5px;">
                                            <th style="padding:6px 8px;">เวลาโพส</th>
                                            <th style="padding:6px 8px;">ประเภท</th>
                                            <th style="padding:6px 8px;">ต้นฉบับลงเมื่อ</th>
                                            <th style="padding:6px 8px;">ข้อความ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in preview.planned" :key="row.source_post_id">
                                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 18%, transparent);">
                                                <td style="padding:6px 8px; white-space:nowrap;" x-text="row.slot_at"></td>
                                                <td style="padding:6px 8px;" x-text="mediaLabel(row.media_type)"></td>
                                                <td style="padding:6px 8px; white-space:nowrap; color:var(--ink2);" x-text="row.source_created_at"></td>
                                                <td style="padding:6px 8px;">
                                                    <a :href="'https://www.facebook.com/' + row.source_post_id" target="_blank" rel="noopener" style="color:inherit;" x-text="row.caption || '(ไม่มีข้อความ)'"></a>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <template x-if="preview.skipped && preview.skipped.length">
                            <div style="margin-top:8px; color:var(--ink2);">
                                <template x-for="why in preview.skipped" :key="why"><div x-text="'· ' + why"></div></template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    @endforeach

    {{-- ===== ประวัติ ===== --}}
    <div class="tp-card" style="padding:18px 20px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px;">
            <div class="tp-section-h"><i class="fas fa-clock-rotate-left"></i> ประวัติรีโพส</div>
            <form method="GET" action="{{ route('admin.fortune.reposts.index') }}" style="display:flex; flex-wrap:wrap; gap:8px;">
                <select name="page_id" class="tp-input" style="width:auto; min-width:180px;" onchange="this.form.submit()">
                    <option value="">ทุกสาขา</option>
                    @foreach($pages as $opt)
                        <option value="{{ $opt->id }}" @selected($filterPageId === $opt->id)>{{ $opt->display_label }}</option>
                    @endforeach
                </select>
                <select name="status" class="tp-input" style="width:auto; min-width:140px;" onchange="this.form.submit()">
                    <option value="">ทุกสถานะ</option>
                    @foreach(['scheduled' => 'ตั้งเวลาแล้ว', 'published' => 'ขึ้นหน้าเพจแล้ว', 'failed' => 'ส่งไม่ผ่าน', 'cancelled' => 'ยกเลิกแล้ว', 'sending' => 'กำลังส่ง'] as $value => $label)
                        <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($history->isEmpty())
            <div style="padding:24px; text-align:center; color:var(--ink2); font-size:13px;">ยังไม่มีประวัติรีโพส</div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:12.5px; min-width:720px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); font-size:11.5px;">
                            <th style="padding:8px;">เวลาโพส</th>
                            <th style="padding:8px;">สาขา</th>
                            <th style="padding:8px;">ประเภท</th>
                            <th style="padding:8px;">ข้อความ / ต้นฉบับ</th>
                            <th style="padding:8px;">สถานะ</th>
                            <th style="padding:8px; text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $row)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 18%, transparent); vertical-align:top;">
                                <td style="padding:8px; white-space:nowrap;" class="tp-num">{{ $row->slot_at?->format('d/m/y H:i') }}</td>
                                <td style="padding:8px;">{{ $row->page?->display_label ?? '—' }}</td>
                                <td style="padding:8px; white-space:nowrap;">{{ $row->media_label }}</td>
                                <td style="padding:8px; max-width:360px;">
                                    <div style="overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                                        {{ $row->caption_preview ?: '(ไม่มีข้อความ)' }}
                                    </div>
                                    <a href="{{ $row->source_url }}" target="_blank" rel="noopener" style="font-size:11.5px; color:var(--ink2);">
                                        ต้นฉบับ{{ $row->source_created_at ? ' '.$row->source_created_at->format('d/m H:i') : '' }} ↗
                                    </a>
                                    @if($row->error)
                                        <div style="font-size:11.5px; color:#d9534f; margin-top:2px;">{{ $row->error }}</div>
                                    @endif
                                </td>
                                <td style="padding:8px; white-space:nowrap; color:{{ $statusTone[$row->status] ?? 'var(--ink)' }}; font-weight:600;">{{ $row->status_label }}</td>
                                <td style="padding:8px; text-align:right; white-space:nowrap;">
                                    @if($row->target_post_id && $row->status === 'published')
                                        <a href="https://www.facebook.com/{{ $row->target_post_id }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm">ดูโพส</a>
                                    @endif
                                    @if($row->isCancellable())
                                        <form method="POST" action="{{ route('admin.fortune.reposts.cancel', $row) }}" style="display:inline;"
                                              @submit.prevent="if (confirm(@js('ยกเลิกโพสเวลา '.$row->slot_at->format('d/m H:i').' ?\n\nโพสนี้จะถูกลบออกจากคิวตั้งเวลาของ Facebook และจะไม่ถูกเลือกมาลงสาขานี้อีก'))) $el.submit()"
                                              x-data>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tp-btn tp-btn-sm" style="color:#d9534f;">ยกเลิก</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;">{{ $history->links() }}</div>
        @endif
    </div>
</div>

<script>
    // 🔁 การ์ดรายสาขา — เปิด/ปิดการตั้งค่า + ดูตัวอย่างแผน (ไม่โพสจริง)
    document.addEventListener('alpine:init', () => {
        Alpine.data('tpRepostCard', (previewUrl, startOpen) => ({
            open: startOpen,
            loading: false,
            running: false,
            preview: null,
            mediaLabel(type) {
                return { photo: 'รูปภาพ', video: 'วิดีโอ', text: 'ข้อความ' }[type] || type;
            },
            async loadPreview() {
                if (this.loading) return;   // กันกดรัว
                this.loading = true;
                try {
                    const res = await fetch(previewUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    this.preview = res.ok
                        ? await res.json()
                        : { ok: false, message: 'ดูแผนไม่สำเร็จ (HTTP ' + res.status + ') — ลองใหม่อีกครั้ง', planned: [], skipped: [] };
                } catch (e) {
                    this.preview = { ok: false, message: 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ — ลองใหม่อีกครั้ง', planned: [], skipped: [] };
                } finally {
                    this.loading = false;
                }
            },
        }));
    });
</script>
@endsection
