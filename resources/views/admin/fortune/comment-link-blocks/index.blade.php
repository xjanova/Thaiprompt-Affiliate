{{--
    คอมเมนต์แปะลิงก์ → บอทบล็อกคนโพสต์อัตโนมัติ
    ธีม V4 "นวลทองคำ" (Nuan Gold Clay)

    ตัวแปรจาก FortuneCommentLinkBlockController@index:
    - $blocks : paginator ของ FortuneCommentLinkBlock (with unblockedBy)
    - $stats  : ['total','unread','still_blocked','block_failed','need_delete','today']
    - $filter : 'all'|'unread'|'blocked'|'unblocked'|'need_delete'
    - $search : string

    ทำไมหน้านี้ต้องมี:
    Page token ยังไม่มีสิทธิ์ pages_manage_engagement (ติด App Review)
    → บอทซ่อน/ลบคอมเมนต์เองไม่ได้ ทำได้แค่บล็อกคนโพสต์
    → จึงต้องรวม permalink ไว้ให้แอดมินกดไปลบคอมเมนต์เอง
--}}
@extends('layouts.admin-v4')

@section('title', 'คอมเมนต์แปะลิงก์ — ระบบดูดวง')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · ความปลอดภัย
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                คอมเมนต์แปะลิงก์ 🔗
            </h1>
            <p class="tp-muted" style="font-size:13px; margin:6px 0 0; max-width:640px;">
                บอทเจอลิงก์ภายนอกในคอมเมนต์ → <strong>บล็อกคนโพสต์ทันที</strong> (ห้าม DM + ห้ามคอมเมนต์)
                แล้วเก็บลิงก์ตำแหน่งคอมเมนต์ไว้ให้แอดมินกดไปลบเอง
            </p>
        </div>
        @if($stats['unread'] > 0)
            <form method="POST" action="{{ route('admin.fortune.comment-link-blocks.mark-all-read') }}"
                onsubmit="return confirm('รับทราบทั้งหมด {{ number_format($stats['unread']) }} รายการ ?');">
                @csrf
                <button type="submit" class="tp-btn">
                    <i class="fas fa-check-double"></i> รับทราบทั้งหมด ({{ number_format($stats['unread']) }})
                </button>
            </form>
        @endif
    </div>

    {{-- ===== แจ้งข้อจำกัดที่แอดมินต้องรู้ ===== --}}
    <div class="tp-card" style="padding:16px 18px; border-left:4px solid #e0a52e;">
        <div style="display:flex; align-items:center; gap:9px; font-weight:700; color:#e0a52e; margin-bottom:6px;">
            <i class="fas fa-triangle-exclamation"></i> บอทลบคอมเมนต์เองยังไม่ได้ — ต้องให้คนกดลบ
        </div>
        <p style="margin:0; font-size:13px; color:var(--ink2); line-height:1.65;">
            สิทธิ์ <code style="font-family:ui-monospace,monospace;">pages_manage_engagement</code> ของแอปยังติด App Review ของ Meta
            บอทจึง <strong>บล็อกคนโพสต์ได้</strong> (ใช้สิทธิ์ <code style="font-family:ui-monospace,monospace;">pages_manage_metadata</code> ที่มีอยู่แล้ว)
            แต่ <strong>ซ่อน/ลบคอมเมนต์ไม่ได้</strong> — คอมเมนต์เดิมยังค้างอยู่บนโพสต์
            กดปุ่ม <strong>“ไปลบคอมเมนต์”</strong> เพื่อเปิดไปที่ตำแหน่งคอมเมนต์บน Facebook แล้วลบมือ
            เมื่อ App Review ผ่าน บอทจะเริ่มซ่อนให้เองทันทีโดยไม่ต้องแก้อะไรเพิ่ม
        </p>
    </div>

    {{-- ===== แจ้งเตือนแอดมินทาง Messenger (ฟรี ไม่กินโควต้าแบบ LINE) ===== --}}
    <div class="tp-card" style="padding:18px;" x-data="{ open: {{ $notifyPsid ? 'false' : 'true' }} }">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">
            <div>
                <div style="font-weight:700; display:flex; align-items:center; gap:8px;">
                    <i class="fab fa-facebook-messenger" style="color:#5689b8;"></i>
                    แจ้งเตือนแอดมินทาง Messenger
                    @if($notifyPsid && $notifyEnabled)
                        <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">เปิดอยู่</span>
                    @elseif($notifyPsid)
                        <span class="tp-pill" style="background:rgba(154,143,124,.18); color:#9a8f7c;">ปิดอยู่</span>
                    @else
                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;">ยังไม่ได้ตั้ง</span>
                    @endif
                </div>
                <p class="tp-muted" style="font-size:12.5px; margin:6px 0 0; max-width:620px; line-height:1.6;">
                    วันไหนมีการบล็อก บอทจะทักมาบอก <strong>วันละไม่เกิน 1 ครั้ง</strong> (บล็อกเพิ่มก็ไม่ทักซ้ำ)
                    แล้วพิมพ์ <strong>“สแปม”</strong> กลับไปเพื่อดูรายการ + ลิงก์กดไปลบ —
                    Messenger ส่งฟรีไม่จำกัด ต่างจาก LINE ที่คิดโควต้าทุกข้อความ
                </p>
            </div>
            <button type="button" class="tp-btn" @click="open = !open">
                <i class="fas" :class="open ? 'fa-xmark' : 'fa-gear'"></i>
                <span x-text="open ? 'ปิด' : 'ตั้งค่า'"></span>
            </button>
        </div>

        @if(session('bind_code'))
            <div class="tp-well" style="margin-top:14px; padding:14px 16px;">
                <div style="font-weight:700; margin-bottom:6px;">🔗 รหัสผูก (อายุ 10 นาที ใช้ครั้งเดียว)</div>
                <div class="tp-num" style="font-size:22px; font-weight:800; letter-spacing:1px; font-family:ui-monospace,monospace; color:var(--deep1);">
                    {{ session('bind_code') }}
                </div>
                <p class="tp-muted" style="font-size:12.5px; margin:8px 0 0;">
                    เปิด Messenger ด้วย <strong>บัญชีส่วนตัว</strong> (ไม่ใช่ตอบในนามเพจ) → ทักไปที่เพจแม่หมอ → พิมพ์รหัสนี้ส่งไป
                </p>
            </div>
        @endif

        <div x-show="open" x-cloak style="margin-top:16px; display:flex; flex-direction:column; gap:14px;">
            <form method="POST" action="{{ route('admin.fortune.comment-link-blocks.notify.save') }}">
                @csrf
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:14px; align-items:end;">
                    <div style="min-width:0;">
                        <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                            PSID ของแอดมิน (Facebook) — หลายคนคั่นด้วย ,
                        </label>
                        <input type="text" name="admin_notify_psid" value="{{ $notifyPsid }}"
                            placeholder="26165964502999706, 37818258501105933"
                            class="tp-well tp-input" style="font-family:ui-monospace,monospace;">
                        @if($notifyPsid)
                            <div class="tp-muted" style="font-size:11.5px; margin-top:5px;">
                                ส่งอยู่ {{ count(array_filter(preg_split('/[,\s]+/', $notifyPsid) ?: [])) }} คน
                            </div>
                        @endif
                    </div>
                    <div>
                        <label style="display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer;">
                            <input type="hidden" name="admin_notify_enabled" value="0">
                            <input type="checkbox" name="admin_notify_enabled" value="1" {{ $notifyEnabled ? 'checked' : '' }}>
                            เปิดการแจ้งเตือน
                        </label>
                    </div>
                    <div style="display:flex; gap:9px;">
                        <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">
                            <i class="fas fa-floppy-disk"></i> บันทึก
                        </button>
                    </div>
                </div>
            </form>

            {{-- ตัวเลือก PSID ที่เดาได้จากประวัติดูดวงของแอดมินเอง --}}
            @if(! empty($psidCandidates))
                <div class="tp-well" style="padding:14px 16px;">
                    <div style="font-size:12.5px; font-weight:700; margin-bottom:8px;">
                        🔎 เจอ PSID ที่น่าจะเป็นแอดมิน จากประวัติดูดวง — กดคัดลอกไปวางด้านบนได้เลย
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        @foreach($psidCandidates as $c)
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:12.5px;">
                                <span style="font-family:ui-monospace,monospace; font-weight:700; color:var(--deep1);">{{ $c['psid'] }}</span>
                                <span class="tp-muted">{{ $c['name'] }}</span>
                                @if($c['last_seen'])
                                    <span class="tp-muted" style="font-size:11px;">ล่าสุด {{ \Illuminate\Support\Str::limit($c['last_seen'], 10, '') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div style="display:flex; flex-wrap:wrap; gap:9px;">
                <form method="POST" action="{{ route('admin.fortune.comment-link-blocks.notify.bind-code') }}">
                    @csrf
                    <button type="submit" class="tp-btn">
                        <i class="fas fa-key"></i> ออกรหัสผูก (ถ้าไม่รู้ PSID)
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.fortune.comment-link-blocks.notify.test') }}">
                    @csrf
                    <button type="submit" class="tp-btn" style="background:rgba(86,137,184,.16); color:#5689b8;">
                        <i class="fas fa-paper-plane"></i> ทดสอบส่งเดี๋ยวนี้
                    </button>
                </form>
            </div>

            <p class="tp-muted" style="font-size:12px; margin:0; line-height:1.6;">
                ⚠️ Messenger ส่งได้เฉพาะภายใน <strong>24 ชม.</strong> นับจากที่แอดมินคุยกับเพจครั้งล่าสุด
                ถ้า “ทดสอบส่ง” ไม่ผ่าน ให้ทักเพจจากบัญชีส่วนตัวสัก 1 ข้อความก่อน
                — หลังจากนั้นทุกครั้งที่ตอบ “สแปม” กรอบ 24 ชม. จะต่ออายุเองอัตโนมัติ
            </p>
        </div>
    </div>

    {{-- ===== KPI GRID ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        {{-- รอลบคอมเมนต์ (ตัวเลขที่สำคัญที่สุดของหน้านี้) --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-trash-can"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:#d9534f; line-height:1;">
                    {{ number_format($stats['need_delete']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">รอแอดมินไปลบ</div>
            </div>
        </div>

        {{-- ยังไม่จัดการ --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-bell"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:#e0a52e; line-height:1;">
                    {{ number_format($stats['unread']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ยังไม่รับทราบ</div>
            </div>
        </div>

        {{-- ยังถูกบล็อกอยู่ --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-user-slash"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1); line-height:1;">
                    {{ number_format($stats['still_blocked']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ยังถูกบล็อก</div>
            </div>
        </div>

        {{-- บล็อกไม่สำเร็จ (ต้องรู้ ไม่งั้นคิดว่าปลอดภัยแต่ไม่ปลอดภัย) --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-circle-exclamation"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:#d9534f; line-height:1;">
                    {{ number_format($stats['block_failed']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">บล็อกไม่สำเร็จ</div>
            </div>
        </div>

        {{-- วันนี้ --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1); line-height:1;">
                    {{ number_format($stats['today']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">วันนี้ (ทั้งหมด {{ number_format($stats['total']) }})</div>
            </div>
        </div>
    </div>

    {{-- ===== ฟอร์มกรอง / ค้นหา ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; align-items:end;">
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="filter"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="all" @selected($filter === 'all')>ทั้งหมด</option>
                            <option value="link" @selected($filter === 'link')>🔗 ความผิด: แปะลิงก์</option>
                            <option value="flood" @selected($filter === 'flood')>🌊 ความผิด: คอมเมนต์รัว</option>
                            <option value="need_delete" @selected($filter === 'need_delete')>🗑️ รอแอดมินไปลบ</option>
                            <option value="unread" @selected($filter === 'unread')>🔔 ยังไม่รับทราบ</option>
                            <option value="blocked" @selected($filter === 'blocked')>🚫 ยังถูกบล็อก</option>
                            <option value="detect_only" @selected($filter === 'detect_only')>🔍 เจอจากสแกนย้อนหลัง (ยังไม่บล็อก)</option>
                            <option value="unblocked" @selected($filter === 'unblocked')>✨ ปลดบล็อกแล้ว</option>
                        </select>
                    </div>
                </div>

                <div style="min-width:0;">
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ค้นหา</label>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="ชื่อ, PSID, โดเมน, ข้อความ..."
                        class="tp-well tp-input">
                </div>

                <div style="display:flex; gap:9px;">
                    <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">
                        <i class="fas fa-magnifying-glass"></i> ค้นหา
                    </button>
                    <a href="{{ route('admin.fortune.comment-link-blocks.index') }}" class="tp-btn" style="flex:1; justify-content:center;">
                        <i class="fas fa-rotate-left"></i> รีเซ็ต
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- ===== ตารางเหตุการณ์ ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($blocks->isEmpty())
            <div style="text-align:center; color:var(--ink2); padding:48px 20px;">
                <i class="fas fa-shield-halved" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                ยังไม่มีคอมเมนต์แปะลิงก์ในสถานะนี้
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:920px;">
                    <thead>
                        <tr style="text-align:left;">
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">ผู้โพสต์</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">ลิงก์ที่เจอ / ข้อความ</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">ผลการดำเนินการ</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">เมื่อ</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase; text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($blocks as $block)
                            {{-- แถวที่ยังไม่รับทราบ = เน้นด้วยเส้นซ้าย --}}
                            <tr style="box-shadow:var(--inset-sm); {{ $block->is_read ? '' : 'border-left:3px solid #e0a52e;' }}">
                                {{-- ผู้โพสต์ --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    <div style="font-weight:700; color:var(--ink); font-size:14px;">
                                        {{ $block->display_name ?: '(ไม่มีชื่อ)' }}
                                    </div>
                                    <div class="tp-muted" style="font-size:11.5px; font-family:ui-monospace,monospace; margin-top:3px; word-break:break-all;">
                                        {{ $block->platform_user_id }}
                                    </div>
                                    @if($block->detected_from === 'attachment')
                                        <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#e0a52e; margin-top:6px; display:inline-block;">
                                            📎 ไม่มีข้อความ (แชร์ลิงก์)
                                        </span>
                                    @endif
                                </td>

                                {{-- ลิงก์ + ข้อความ --}}
                                <td style="padding:14px 16px; vertical-align:top; max-width:340px;">
                                    {{-- ระบุความผิดให้ชัด แอดมินจะได้ตัดสินได้เร็ว --}}
                                    @if($block->violation_type === 'flood')
                                        <span class="tp-pill" style="background:rgba(199,138,58,.18); color:#c78a3a; font-weight:700;">
                                            🌊 คอมเมนต์รัว {{ $block->flood_count ?: '?' }} ครั้งติดกัน
                                        </span>
                                    @elseif($block->matched_domain)
                                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f; font-weight:700; font-family:ui-monospace,monospace;">
                                            🔗 {{ $block->matched_domain }}
                                        </span>
                                    @endif
                                    @if($block->message)
                                        <div class="tp-muted" style="font-size:12.5px; margin-top:6px; line-height:1.55; word-break:break-word;">
                                            {{ \Illuminate\Support\Str::limit($block->message, 160) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- ผลการดำเนินการ --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    @if($block->status === 'unblocked')
                                        <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e; font-weight:700;">✨ ปลดบล็อกแล้ว</span>
                                        @if($block->unblockedBy)
                                            <div class="tp-muted" style="font-size:11px; margin-top:5px;">โดย: {{ $block->unblockedBy->name }}</div>
                                        @endif
                                    @elseif($block->status === 'detect_only')
                                        <span class="tp-pill" style="background:rgba(86,137,184,.16); color:#5689b8; font-weight:700;">🔍 เจอจากสแกนย้อนหลัง</span>
                                        <div class="tp-muted" style="font-size:11px; margin-top:5px;">ยังไม่ได้บล็อกใคร</div>
                                    @elseif($block->page_blocked)
                                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f; font-weight:700;">🚫 บล็อกบนเพจแล้ว</span>
                                    @else
                                        <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#e0a52e; font-weight:700;">⚠️ บล็อกไม่สำเร็จ</span>
                                        @if($block->block_error)
                                            <div class="tp-muted" style="font-size:11px; margin-top:5px; word-break:break-word;">
                                                {{ \Illuminate\Support\Str::limit($block->block_error, 90) }}
                                            </div>
                                        @endif
                                    @endif

                                    <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:7px;">
                                        @if($block->hide_succeeded)
                                            <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">🙈 บอทซ่อนคอมเมนต์แล้ว</span>
                                        @elseif($block->comment_deleted)
                                            <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">🗑️ แอดมินลบแล้ว</span>
                                        @else
                                            <span class="tp-pill" style="background:rgba(154,143,124,.18); color:#9a8f7c;">คอมเมนต์ยังอยู่</span>
                                        @endif

                                        @if($block->bot_banned)
                                            <span class="tp-pill" style="background:rgba(154,143,124,.18); color:#9a8f7c;">บอทเลิกคุย</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- เมื่อ --}}
                                <td style="padding:14px 16px; vertical-align:top; color:var(--ink2); font-size:13px; white-space:nowrap;">
                                    <div style="color:var(--ink);">{{ $block->created_at->format('Y-m-d') }}</div>
                                    <div class="tp-muted" style="font-size:11.5px;">{{ $block->created_at->format('H:i') }}</div>
                                </td>

                                {{-- จัดการ --}}
                                <td style="padding:14px 16px; vertical-align:top; text-align:right;">
                                    <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-end;">
                                        {{-- ไปลบคอมเมนต์บน Facebook --}}
                                        @if($block->permalink)
                                            <a href="{{ $block->permalink }}" target="_blank" rel="noopener noreferrer"
                                               class="tp-btn tp-btn-sm" style="background:rgba(86,137,184,.16); color:#5689b8;">
                                                <i class="fas fa-arrow-up-right-from-square"></i> ไปลบคอมเมนต์
                                            </a>
                                        @else
                                            <span class="tp-muted" style="font-size:11px;">ไม่มี permalink</span>
                                        @endif

                                        {{-- ยืนยันว่าลบแล้ว --}}
                                        @if(! $block->comment_deleted && ! $block->hide_succeeded)
                                            <form method="POST" action="{{ route('admin.fortune.comment-link-blocks.mark-deleted', $block) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="tp-btn tp-btn-sm"
                                                    style="background:rgba(90,160,126,.16); color:#5aa07e;">
                                                    <i class="fas fa-check"></i> ลบแล้ว
                                                </button>
                                            </form>
                                        @endif

                                        {{-- ปลดบล็อก (เผื่อบล็อกพลาดคนจริง) --}}
                                        @if($block->status !== 'unblocked')
                                            <form method="POST" action="{{ route('admin.fortune.comment-link-blocks.unblock', $block) }}"
                                                onsubmit="return confirm('ปลดบล็อก {{ $block->display_name ?: $block->platform_user_id }} ?\n\nจะปลดทั้งบนเพจ Facebook และปลดแบนระดับบอท');"
                                                style="display:inline;">
                                                @csrf
                                                <button type="submit" class="tp-btn tp-btn-sm"
                                                    style="background:rgba(224,165,46,.18); color:#e0a52e;">
                                                    <i class="fas fa-wand-magic-sparkles"></i> ปลดบล็อก
                                                </button>
                                            </form>
                                        @endif

                                        {{-- รับทราบ --}}
                                        @if(! $block->is_read)
                                            <form method="POST" action="{{ route('admin.fortune.comment-link-blocks.mark-read', $block) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="tp-btn tp-btn-sm">
                                                    <i class="fas fa-eye"></i> รับทราบ
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Pagination ===== --}}
    @if($blocks->hasPages())
        <div>
            {{ $blocks->links() }}
        </div>
    @endif
</div>
@endsection
