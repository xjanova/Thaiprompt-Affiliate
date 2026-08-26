@extends('layouts.admin-v4')

@section('title', 'จัดการแบนเนอร์ DM')

@section('content')
{{-- ══════════════════════════════════════════════════════════════
     หน้าจัดการแบนเนอร์ DM — ธีม V4 "นวลทองคำ"
     ตัวแปรจาก FortuneBannerController@index:
       $banners  = FortuneBanner::ordered()->get()  (collection)
       $settings = FortuneTellingSetting::getSettings()
     คงฟังก์ชันเดิม 100%: ฟอร์มตั้งค่า (POST settings), การ์ดแบนเนอร์
     + รูป preview ($banner->image_url), ปุ่มแก้ไข/เปิด-ปิด/ลบ
     ══════════════════════════════════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ───── Header ───── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.4px;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · แบนเนอร์ DM
            </div>
            <h1 class="tp-num" style="font-size:26px;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-images" style="color:var(--accent1);"></i>
                จัดการแบนเนอร์ DM
            </h1>
        </div>
        <a href="{{ route('admin.fortune.banners.create') }}" class="tp-btn tp-btn-primary">
            <i class="fas fa-plus"></i>
            อัพโหลดแบนเนอร์ใหม่
        </a>
    </div>

    {{-- ───── ⚙️ ตั้งค่าระบบแบนเนอร์ ───── --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:9px;margin-bottom:18px;">
            <i class="fas fa-sliders-h" style="color:var(--accent2);"></i>
            <span>ตั้งค่าระบบแบนเนอร์</span>
        </div>

        <form action="{{ route('admin.fortune.banners.settings') }}" method="POST">
            @csrf

            {{-- แถวบน: master toggle + กลยุทธ์ --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-bottom:16px;">
                {{-- Master toggle --}}
                <label class="tp-inset" style="display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:14px;cursor:pointer;">
                    <input type="checkbox" name="enable_dm_banner" value="1"
                           @checked($settings->enable_dm_banner ?? false)
                           style="width:18px;height:18px;accent-color:var(--accent1);cursor:pointer;flex-shrink:0;">
                    <span>
                        <span style="display:block;font-weight:600;color:var(--ink);">เปิดใช้งานระบบแบนเนอร์</span>
                        <span class="tp-muted" style="font-size:12px;">Master switch — ปิดทั้งระบบ</span>
                    </span>
                </label>

                {{-- กลยุทธ์ --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:7px;">
                        กลยุทธ์การเลือกแบนเนอร์
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="banner_pick_strategy"
                                style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                            <option value="rotation" @selected(($settings->banner_pick_strategy ?? 'rotation') === 'rotation')>
                                🔄 วนรอบ (rotation) — ใช้ทุกแบนเนอร์เท่าๆ กัน
                            </option>
                            <option value="random" @selected(($settings->banner_pick_strategy ?? '') === 'random')>
                                🎲 สุ่ม (random) — สุ่มจริงทุกครั้ง
                            </option>
                            <option value="sequential" @selected(($settings->banner_pick_strategy ?? '') === 'sequential')>
                                📋 ตามลำดับ (sequential) — ใช้แบนเนอร์แรกตาม sort
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- แถวล่าง: trigger toggles --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:18px;">
                <label class="tp-inset-sm" style="display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;cursor:pointer;">
                    <input type="checkbox" name="banner_send_on_reaction" value="1"
                           @checked($settings->banner_send_on_reaction ?? true)
                           style="width:16px;height:16px;accent-color:var(--accent1);cursor:pointer;flex-shrink:0;">
                    <span style="font-size:13px;color:var(--ink);">👍 ส่งเมื่อกด reaction</span>
                </label>

                <label class="tp-inset-sm" style="display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;cursor:pointer;">
                    <input type="checkbox" name="banner_send_on_comment" value="1"
                           @checked($settings->banner_send_on_comment ?? true)
                           style="width:16px;height:16px;accent-color:var(--accent1);cursor:pointer;flex-shrink:0;">
                    <span style="font-size:13px;color:var(--ink);">💬 ส่งเมื่อ comment</span>
                </label>

                <label class="tp-inset-sm" style="display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;cursor:pointer;">
                    <input type="checkbox" name="banner_send_on_welcome" value="1"
                           @checked($settings->banner_send_on_welcome ?? true)
                           style="width:16px;height:16px;accent-color:var(--accent1);cursor:pointer;flex-shrink:0;">
                    <span style="font-size:13px;color:var(--ink);">🤝 ส่งเมื่อทักครั้งแรก</span>
                </label>
            </div>

            {{-- ───── 🃏 การ์ดทางเข้า (2026-08-26) ─────
                 อยู่ในฟอร์มเดียวกับแบนเนอร์เพราะคุมเรื่องเดียวกัน = "DM ขาออกหน้าตาแบบไหน"
                 กดบันทึกครั้งเดียวคุมทั้งสองเรื่อง (ค่าที่ไม่ได้แตะถูก pre-fill ไว้แล้ว) --}}
            <div class="tp-inset" style="padding:16px;border-radius:14px;margin-bottom:18px;">
                <div style="display:flex;align-items:center;gap:9px;margin-bottom:6px;">
                    <i class="fas fa-id-card" style="color:var(--accent2);"></i>
                    <span style="font-weight:600;color:var(--ink);">การ์ดทางเข้า (Facebook)</span>
                </div>

                <p class="tp-muted" style="font-size:12px;line-height:1.7;margin-bottom:14px;">
                    เปลี่ยน DM จาก "ข้อความล้วน" เป็น <strong>การ์ดมีรูป + ปุ่ม</strong>
                    — Facebook ให้ 1 ข้อความมีได้แค่ตัวหนังสือ<em>หรือ</em>รูป อย่างใดอย่างหนึ่ง
                    และตอบคอมเมนต์ได้ครั้งเดียวต่อ 1 คอมเมนต์
                    การ์ดจึงเป็นทางเดียวที่ยัดรูป + คำ + ปุ่ม ลงกล่องเดียวได้
                    <br>
                    <span style="color:var(--accent2);">⚠️ ถ้าราคา/รูปหาย ระบบจะตกกลับไปข้อความเดิมให้เองอัตโนมัติ</span>
                </p>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;">
                    <label class="tp-inset-sm" style="display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border-radius:12px;cursor:pointer;">
                        <input type="checkbox" name="entry_cards_on_dm" value="1"
                               @checked($settings->entry_cards_on_dm ?? false)
                               style="width:16px;height:16px;accent-color:var(--accent1);cursor:pointer;flex-shrink:0;margin-top:2px;">
                        <span>
                            <span style="display:block;font-size:13px;color:var(--ink);">🃏 DM ตอบคอมเมนต์เป็นการ์ด 2 ใบ</span>
                            <span class="tp-muted" style="font-size:11.5px;">[🎁 รับดวงฟรี] + [👑 VIP มีค่าครู] · <strong>แตะ funnel หลัก เปิดแล้วเทียบยอดด้วย</strong></span>
                        </span>
                    </label>

                    <label class="tp-inset-sm" style="display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border-radius:12px;cursor:pointer;">
                        <input type="checkbox" name="birth_day_cards_enabled" value="1"
                               @checked($settings->birth_day_cards_enabled ?? false)
                               style="width:16px;height:16px;accent-color:var(--accent1);cursor:pointer;flex-shrink:0;margin-top:2px;">
                        <span>
                            <span style="display:block;font-size:13px;color:var(--ink);">📅 เลือกวันเกิดเป็นการ์ด 7 ใบ</span>
                            <span class="tp-muted" style="font-size:11.5px;">ภาพเทพพาหนะประจำวัน แทนปุ่มข้อความ · เสี่ยงน้อยกว่า ลองตัวนี้ก่อนได้</span>
                        </span>
                    </label>
                </div>
            </div>

            <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm">
                <i class="fas fa-floppy-disk"></i>
                บันทึกการตั้งค่า
            </button>
        </form>
    </div>

    {{-- ───── 🖼️ แบนเนอร์ทั้งหมด ───── --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:9px;margin-bottom:18px;">
            <i class="fas fa-images" style="color:var(--accent1);"></i>
            <span>แบนเนอร์ทั้งหมด</span>
            <span class="tp-pill tp-pill-gold" style="margin-left:4px;">{{ $banners->count() }}</span>
        </div>

        @if($banners->isEmpty())
            {{-- Empty state --}}
            <div style="text-align:center;padding:48px 16px;">
                <i class="fas fa-inbox" style="font-size:46px;color:var(--ink2);opacity:.45;"></i>
                <p class="tp-muted" style="margin:16px 0 18px;">ยังไม่มีแบนเนอร์</p>
                <a href="{{ route('admin.fortune.banners.create') }}" class="tp-btn tp-btn-primary tp-btn-sm">
                    <i class="fas fa-plus"></i>
                    อัพโหลดแบนเนอร์แรก
                </a>
            </div>
        @else
            {{-- Card grid --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                @foreach($banners as $banner)
                    {{-- แต่ละการ์ดมี x-data เอง (Alpine subtree สำหรับปุ่ม @click) --}}
                    <div x-data="{}" class="tp-tile" style="padding:0;overflow:hidden;display:flex;flex-direction:column;">

                        {{-- รูป preview --}}
                        <div style="position:relative;aspect-ratio:16/9;background:var(--inset);">
                            <img src="{{ $banner->image_url }}"
                                 alt="{{ $banner->name }}"
                                 loading="lazy"
                                 style="width:100%;height:100%;object-fit:cover;display:block;">

                            {{-- overlay เมื่อปิดใช้งาน --}}
                            @if(! $banner->is_active)
                                <div style="position:absolute;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;">
                                    <span class="tp-pill" style="background:#d9534f;color:#fff;font-weight:700;">
                                        <i class="fas fa-pause"></i> ปิดใช้งาน
                                    </span>
                                </div>
                            @endif

                            {{-- ลำดับ sort_order --}}
                            <span style="position:absolute;top:9px;left:9px;padding:3px 9px;border-radius:8px;background:rgba(0,0,0,.6);color:#fff;font-size:12px;font-weight:600;">
                                #{{ $banner->sort_order }}
                            </span>

                            {{-- สถานะ badge มุมขวา --}}
                            @if($banner->is_active)
                                <span class="tp-pill" style="position:absolute;top:9px;right:9px;background:#5aa07e;color:#fff;">
                                    <i class="fas fa-circle" style="font-size:7px;"></i> เปิดอยู่
                                </span>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div style="padding:15px 16px;flex:1;display:flex;flex-direction:column;">
                            <div style="font-weight:600;color:var(--ink);margin-bottom:4px;font-size:15px;">
                                {{ $banner->name }}
                            </div>

                            @if($banner->description)
                                <div class="tp-muted" style="font-size:12.5px;margin-bottom:10px;line-height:1.5;">
                                    {{ Str::limit($banner->description, 60) }}
                                </div>
                            @endif

                            {{-- meta: ขนาด / file size / ส่งไปแล้ว --}}
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;">
                                <span class="tp-pill tp-pill-soft" style="font-size:11.5px;">
                                    <i class="fas fa-up-right-and-down-left-from-center" style="color:var(--accent2);"></i>
                                    {{ $banner->width }}×{{ $banner->height }}
                                </span>
                                <span class="tp-pill tp-pill-soft" style="font-size:11.5px;">
                                    <i class="fas fa-database" style="color:var(--accent2);"></i>
                                    {{ round(($banner->file_size ?? 0) / 1024) }} KB
                                </span>
                                <span class="tp-pill tp-pill-soft" style="font-size:11.5px;">
                                    <i class="fas fa-paper-plane" style="color:#5689b8;"></i>
                                    ส่งแล้ว {{ number_format($banner->send_count) }}
                                </span>
                            </div>

                            {{-- ปุ่ม action --}}
                            <div style="display:flex;gap:8px;margin-top:auto;">
                                <a href="{{ route('admin.fortune.banners.edit', $banner) }}"
                                   class="tp-btn tp-btn-sm"
                                   style="flex:1;justify-content:center;">
                                    <i class="fas fa-pen"></i> แก้ไข
                                </a>

                                {{-- เปิด/ปิด --}}
                                <form action="{{ route('admin.fortune.banners.toggle', $banner) }}" method="POST" style="flex:1;">
                                    @csrf
                                    <button type="submit" class="tp-btn tp-btn-sm" style="width:100%;justify-content:center;
                                            color:{{ $banner->is_active ? '#e0a52e' : '#5aa07e' }};">
                                        @if($banner->is_active)
                                            <i class="fas fa-pause"></i> ปิด
                                        @else
                                            <i class="fas fa-play"></i> เปิด
                                        @endif
                                    </button>
                                </form>

                                {{-- ลบ (ยืนยันก่อน) --}}
                                <form action="{{ route('admin.fortune.banners.destroy', $banner) }}" method="POST"
                                      @submit="if(!confirm('ลบแบนเนอร์นี้?')){ $event.preventDefault(); }">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tp-icon-btn" title="ลบแบนเนอร์" style="color:#d9534f;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
