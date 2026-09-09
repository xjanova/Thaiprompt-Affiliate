@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'Flash Deals')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     Lazada Hub — ตั้งค่า Flash Deals หน้าแรก
     "ระบบหยิบของที่ Lazada ลดราคาจริงมาขึ้นหน้าแรกเอง"

     🚨 หน้านี้เขียนลง marketplace_settings (คีย์ขึ้นต้น lazada_deals_) เท่านั้น
        และถูกอ่านกลับผ่าน App\Support\LazadaDealSettings ที่เดียว
     ════════════════════════════════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                <a href="{{ route('admin.lazada-hub.dashboard') }}" style="color:var(--accent2);text-decoration:none;">Lazada Hub</a> · Flash Deals
            </div>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-bolt" style="color:var(--accent1);"></i> Flash Deals หน้าแรก
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">
                ระบบไปอ่าน <b>ราคาก่อนลด</b> จากหน้ารายการของ Lazada เอง แล้วยืนยันกับฟีดว่ากินค่าคอมได้ ก่อนเอาขึ้นหน้าแรก
            </p>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <form method="POST" action="{{ route('admin.lazada-hub.flash-deals.scan') }}">
                @csrf
                <button type="submit" class="tp-btn tp-btn-primary" @disabled($isRunning)>
                    <i class="fas {{ $isRunning ? 'fa-spinner fa-spin' : 'fa-bolt' }}"></i>
                    <span>{{ $isRunning ? 'กำลังกวาด…' : 'กวาดเดี๋ยวนี้' }}</span>
                </button>
            </form>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="tp-btn">
                <i class="fas fa-up-right-from-square"></i> <span>ดูหน้าแรก</span>
            </a>
        </div>
    </div>

    @if(session('success'))<div class="tp-card" style="border-left:4px solid #5aa07e;font-size:.88rem;color:var(--ink);"><i class="fas fa-circle-check" style="color:#5aa07e;"></i> {{ session('success') }}</div>@endif
    @if(session('error'))<div class="tp-card" style="border-left:4px solid #e0a52e;font-size:.88rem;color:var(--ink);"><i class="fas fa-triangle-exclamation" style="color:#e0a52e;"></i> {{ session('error') }}</div>@endif
    @if($errors->any())<div class="tp-card" style="border-left:4px solid #d9534f;font-size:.88rem;color:var(--ink);"><i class="fas fa-circle-xmark" style="color:#d9534f;"></i> {{ $errors->first() }}</div>@endif

    {{-- ── สถานะตอนนี้ ── --}}
    <div class="tp-card" style="display:flex;align-items:center;gap:22px;flex-wrap:wrap;{{ $live->isEmpty() ? 'border-left:4px solid #e0a52e;' : 'border-left:4px solid #5aa07e;' }}">
        <div>
            <div class="tp-muted" style="font-size:.72rem;font-weight:600;">ดีลที่โชว์อยู่หน้าแรกตอนนี้</div>
            <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);">
                {{ number_format($live->count()) }} <span class="tp-muted" style="font-size:.8rem;font-weight:600;">ชิ้น</span>
            </div>
        </div>

        @if($lastRun)
            <div>
                <div class="tp-muted" style="font-size:.72rem;font-weight:600;">กวาดล่าสุด</div>
                <div style="font-size:.9rem;color:var(--ink);font-weight:700;">
                    {{ \Carbon\Carbon::parse($lastRun['at'] ?? now())->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} น.
                </div>
                <div class="tp-muted" style="font-size:.74rem;">
                    เห็น {{ number_format($lastRun['seen'] ?? 0) }} · เข้าเกณฑ์ {{ number_format($lastRun['candidates'] ?? 0) }}
                    · ขึ้นหน้าแรก {{ number_format($lastRun['published'] ?? 0) }}
                    @if(($lastRun['expired'] ?? 0) > 0) · หมดอายุ {{ number_format($lastRun['expired']) }} @endif
                </div>
            </div>
        @endif

        <div>
            <div class="tp-muted" style="font-size:.72rem;font-weight:600;">กวาดอัตโนมัติ</div>
            <span class="tp-pill" style="background:{{ $enabled ? '#5aa07e' : 'var(--ink2)' }};color:#fff;font-size:11px;">
                {{ $enabled ? 'เปิด · ทุก '.$rescanHours.' ชม.' : 'ปิดอยู่' }}
            </span>
        </div>

        @if(!empty($lastRun['note']))
            <div style="color:#e0a52e;font-size:.8rem;flex:1;min-width:240px;">
                <i class="fas fa-triangle-exclamation"></i> {{ $lastRun['note'] }}
            </div>
        @elseif($live->isEmpty())
            <div style="color:#e0a52e;font-size:.8rem;flex:1;min-width:240px;">
                <i class="fas fa-triangle-exclamation"></i> ยังไม่มีดีล — แถบ Flash Deals หน้าแรกจะไม่แสดงจนกว่าจะกวาดเจอของ
            </div>
        @endif

        @if($live->isNotEmpty())
            <form method="POST" action="{{ route('admin.lazada-hub.flash-deals.clear') }}" style="margin-left:auto;"
                  onsubmit="return confirm('ล้างดีลทั้งหมดออกจากหน้าแรก?\n\nสินค้ายังอยู่ในร้านและยังกดซื้อได้ตามเดิม — ล้างแค่ป้ายลดราคาเท่านั้น');">
                @csrf
                <button type="submit" class="tp-btn tp-btn-sm">
                    <i class="fas fa-eraser"></i> <span>ล้างดีลออกจากหน้าแรก</span>
                </button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.lazada-hub.flash-deals.update') }}" style="display:flex;flex-direction:column;gap:18px;">
        @csrf
        @method('PUT')

        {{-- ── สวิตช์ใหญ่ ── --}}
        <div class="tp-card" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;border-left:4px solid {{ $enabled ? '#5aa07e' : 'var(--ink2)' }};">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $enabled)) style="width:20px;height:20px;accent-color:#5aa07e;cursor:pointer;">
                <span style="font-weight:800;color:var(--ink);font-size:1rem;">กวาดดีลอัตโนมัติ</span>
            </label>
            <span class="tp-muted" style="font-size:.82rem;">
                ปิดตัวนี้ = หยุดกวาดตามรอบ (ปุ่ม “กวาดเดี๋ยวนี้” ยังใช้ได้เสมอ) · ดีลที่ขึ้นอยู่จะค่อย ๆ หมดอายุไปเอง
            </span>
        </div>

        {{-- ── เกณฑ์คัดของ ── --}}
        <div class="tp-card">
            <div style="font-weight:700;color:var(--ink);margin-bottom:2px;"><i class="fas fa-filter" style="color:var(--accent1);"></i> เกณฑ์คัดของ</div>
            <p class="tp-muted" style="font-size:.8rem;margin:0 0 14px;">
                ยิ่งเข้มยิ่งได้ของดี แต่ถ้าเข้มเกินไปจะไม่เหลือของขึ้นหน้าแรกเลย
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ส่วนลดขั้นต่ำ (%)</label>
                    <input type="number" name="min_discount_percent" value="{{ old('min_discount_percent', $filters['min_discount_percent']) }}" min="1" max="95" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">ต่ำกว่านี้ไม่เรียกว่าดีล</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ส่วนลดสูงสุด (%)</label>
                    <input type="number" name="max_discount_percent" value="{{ old('max_discount_percent', $filters['max_discount_percent']) }}" min="1" max="99" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">ลดเกินนี้ = ราคาก่อนลดไม่น่าเชื่อถือ (ผู้ขายตั้งเองได้)</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ค่าคอมขั้นต่ำ (%)</label>
                    <input type="number" step="0.1" name="min_commission_percent" value="{{ old('min_commission_percent', $filters['min_commission_percent']) }}" min="0" max="100" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">ต่ำกว่านี้ไม่คุ้มพื้นที่หน้าแรก</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ราคาต่ำสุด (บาท)</label>
                    <input type="number" step="1" name="min_price" value="{{ old('min_price', (int) $filters['min_price']) }}" min="0" max="1000000" class="tp-input tp-num" style="width:120px;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ราคาสูงสุด (บาท)</label>
                    <input type="number" step="1" name="max_price" value="{{ old('max_price', (int) $filters['max_price']) }}" min="1" max="1000000" class="tp-input tp-num" style="width:120px;">
                </div>
            </div>

            <div style="border-top:1px solid var(--line, rgba(0,0,0,.08));margin:16px 0 14px;"></div>

            <div style="font-weight:700;color:var(--ink);margin-bottom:2px;font-size:.92rem;"><i class="fas fa-shield-halved" style="color:var(--accent1);"></i> ด่านคุณภาพ</div>
            <p class="tp-muted" style="font-size:.8rem;margin:0 0 14px;">
                ส่วนลดอย่างเดียวเชื่อไม่ได้ — ผู้ขายตั้งราคาก่อนลดเองได้ แต่ปลอมรีวิว/ยอดขายยากกว่ามาก
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">เรตติ้งขั้นต่ำ (0-5)</label>
                    <input type="number" step="0.1" name="min_rating" value="{{ old('min_rating', $filters['min_rating']) }}" min="0" max="5" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">0 = ไม่ตรวจ</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">รีวิวขั้นต่ำ (ชิ้น)</label>
                    <input type="number" name="min_reviews" value="{{ old('min_reviews', $filters['min_reviews']) }}" min="0" max="100000" class="tp-input tp-num" style="width:110px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">ของไม่มีรีวิวจะถูกตัดออกด้วยช่องนี้</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ยอดขายขั้นต่ำ (ชิ้น)</label>
                    <input type="number" name="min_sold" value="{{ old('min_sold', $filters['min_sold']) }}" min="0" max="1000000" class="tp-input tp-num" style="width:110px;">
                </div>
            </div>
        </div>

        {{-- ── เพดาน + จังหวะ ── --}}
        <div class="tp-card">
            <div style="font-weight:700;color:var(--ink);margin-bottom:2px;"><i class="fas fa-gauge-high" style="color:var(--accent1);"></i> เพดานการยิง + จังหวะเวลา</div>
            <p class="tp-muted" style="font-size:.8rem;margin:0 0 14px;">
                ยิ่งกวาดเยอะยิ่งใช้เวลานานและเสี่ยงโดน Lazada กันบอท — ค่าที่ตั้งไว้ใช้เวลา 1-5 นาทีต่อรอบ
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;">
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">กวาดกี่หน้าต่อคำค้น</label>
                    <input type="number" name="pages_per_keyword" value="{{ old('pages_per_keyword', $limits['pages_per_keyword']) }}" min="1" max="5" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">หน้าละ ~40 ชิ้น</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">เอาขึ้นหน้าแรกสูงสุด (ชิ้น)</label>
                    <input type="number" name="publish_limit" value="{{ old('publish_limit', $limits['publish_limit']) }}" min="1" max="60" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">แถบหน้าแรกโชว์ 12 ชิ้นแรก ที่เหลืออยู่ในหน้า “ดูทั้งหมด”</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">คำค้นละไม่เกิน (ชิ้น)</label>
                    <input type="number" name="max_per_seed" value="{{ old('max_per_seed', $limits['max_per_seed']) }}" min="1" max="20" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">กันแถบดีลกลายเป็นของหมวดเดียวเรียงกันทั้งแถว</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ขอลิงก์ค่าคอมสูงสุด (ชิ้น/รอบ)</label>
                    <input type="number" name="link_budget" value="{{ old('link_budget', $limits['link_budget']) }}" min="1" max="120" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">ตัวนี้คือคอขวด (~1.2 วิ/ชิ้น)</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ดีลสดได้กี่ชั่วโมง</label>
                    <input type="number" name="fresh_hours" value="{{ old('fresh_hours', $freshHours) }}" min="1" max="168" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">เกินนี้แล้วยังไม่ได้ตรวจซ้ำ = ร่วงจากหน้าแรกเอง</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">รอบตรวจราคาซ้ำ (ชม.)</label>
                    <input type="number" name="rescan_hours" value="{{ old('rescan_hours', $rescanHours) }}" min="1" max="24" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">ตัวเลขบนนาฬิกานับถอยหลังหน้าแรก</div>
                </div>
            </div>

            <div class="tp-card" style="margin-top:14px;background:var(--surf);border-left:3px solid #e0a52e;font-size:.8rem;color:var(--ink2);line-height:1.7;">
                <i class="fas fa-circle-info" style="color:#e0a52e;"></i>
                <b>“ดีลสดได้กี่ชั่วโมง” ต้องมากกว่า “รอบตรวจราคาซ้ำ” เสมอ</b> — แนะนำอย่างน้อย 2 เท่า
                เผื่อรอบกวาดพลาดไป 1 ครั้ง หน้าแรกจะได้ไม่ว่างเป็นช่วง ๆ
                <br>
                <span class="tp-muted">หมายเหตุ: การเปลี่ยน “รอบตรวจราคาซ้ำ” ที่นี่มีผลกับนาฬิกาที่ลูกค้าเห็นเท่านั้น
                ตารางเวลาจริงของ cron ตั้งอยู่ในโค้ด (ทุก 3 ชม.) — ถ้าจะเปลี่ยนของจริงต้องแจ้งทีมพัฒนา</span>
            </div>
        </div>

        {{-- ── คำค้น ── --}}
        <div class="tp-card" x-data="dealSeeds()">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700;color:var(--ink);"><i class="fas fa-magnifying-glass" style="color:var(--accent1);"></i> คำค้นที่ใช้กวาด</div>
                    <p class="tp-muted" style="font-size:.8rem;margin:4px 0 0;">
                        ระบบเอาคำเหล่านี้ไปค้นบน Lazada แล้วคัดเฉพาะของที่ลดราคาจริง ·
                        เลือกหมวดไว้เพื่อให้สินค้าไปอยู่ถูกหมวดบนหน้าร้าน
                    </p>
                </div>
                <button type="button" class="tp-btn tp-btn-sm" @click="add()">
                    <i class="fas fa-plus"></i> <span>เพิ่มคำค้น</span>
                </button>
            </div>

            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px;">
                <template x-for="(row, i) in rows" :key="row.uid">
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="text" :name="`seeds[${i}][keyword]`" x-model="row.keyword"
                               placeholder="เช่น หม้อทอดไร้น้ำมัน" maxlength="120"
                               class="tp-input" style="flex:1;min-width:200px;">
                        <select :name="`seeds[${i}][category]`" x-model="row.category" class="tp-input" style="width:230px;">
                            <option value="">— ไม่ระบุหมวด (ลงหมวดสินค้า Lazada) —</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="tp-btn tp-btn-sm" @click="remove(i)" title="ลบคำค้นนี้">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>

                <p x-show="rows.length === 0" x-cloak class="tp-muted" style="font-size:.82rem;margin:6px 0 0;">
                    ยังไม่มีคำค้น — กด “เพิ่มคำค้น” ก่อน ไม่งั้นบันทึกแล้วระบบจะปิดการกวาดอัตโนมัติให้
                </p>
            </div>

            <div class="tp-muted" style="font-size:.75rem;margin-top:10px;">
                สูงสุด 40 คำ · คำซ้ำจะถูกตัดออกให้อัตโนมัติ · ยิ่งเยอะยิ่งใช้เวลากวาดนาน
            </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> <span>บันทึกการตั้งค่า</span>
            </button>
            <span class="tp-muted" style="font-size:.8rem;align-self:center;">
                บันทึกแล้วมีผลกับรอบกวาดถัดไป · กด “กวาดเดี๋ยวนี้” ถ้าอยากเห็นผลทันที
            </span>
        </div>
    </form>

    {{-- ── ดีลที่โชว์อยู่ตอนนี้ ── --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="padding:14px 16px;">
            <div style="font-weight:700;color:var(--ink);"><i class="fas fa-list" style="color:var(--accent1);"></i> ดีลที่โชว์อยู่ตอนนี้</div>
            <p class="tp-muted" style="font-size:.8rem;margin:4px 0 0;">เรียงตามส่วนลด · 12 ชิ้นแรกคือที่โผล่บนแถบหน้าแรก</p>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;min-width:760px;">
                <thead>
                    <tr style="background:var(--surf);color:var(--ink2);text-align:left;">
                        <th style="padding:10px 14px;font-weight:600;">#</th>
                        <th style="padding:10px 14px;font-weight:600;">สินค้า</th>
                        <th style="padding:10px 14px;font-weight:600;text-align:right;">ราคา</th>
                        <th style="padding:10px 14px;font-weight:600;text-align:center;">ลด</th>
                        <th style="padding:10px 14px;font-weight:600;text-align:center;">เรตติ้ง</th>
                        <th style="padding:10px 14px;font-weight:600;text-align:right;">ขายแล้ว</th>
                        <th style="padding:10px 14px;font-weight:600;text-align:right;">PV</th>
                        <th style="padding:10px 14px;font-weight:600;">ตรวจล่าสุด</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($live as $index => $product)
                        <tr style="border-top:1px solid var(--line, rgba(0,0,0,.06));{{ $index === 12 ? 'border-top:2px dashed var(--ink2);' : '' }}">
                            <td style="padding:10px 14px;color:var(--ink2);">{{ $index + 1 }}</td>
                            <td style="padding:10px 14px;color:var(--ink);max-width:340px;">
                                <a href="{{ route('shop.show', $product->slug ?: $product->id) }}" target="_blank" rel="noopener"
                                   style="color:var(--ink);text-decoration:none;">
                                    {{ \Illuminate\Support\Str::limit($product->name, 68) }}
                                </a>
                            </td>
                            <td style="padding:10px 14px;text-align:right;white-space:nowrap;" class="tp-num">
                                <b style="color:var(--ink);">฿{{ number_format($product->price, 0) }}</b>
                                <span class="tp-muted" style="text-decoration:line-through;font-size:.78rem;">฿{{ number_format($product->compare_at_price, 0) }}</span>
                            </td>
                            <td style="padding:10px 14px;text-align:center;">
                                <span class="tp-pill" style="background:#d9534f;color:#fff;font-size:11px;">-{{ $product->deal_discount_percent }}%</span>
                            </td>
                            <td style="padding:10px 14px;text-align:center;" class="tp-num">
                                {{ $product->rating_average > 0 ? number_format((float) $product->rating_average, 1) : '—' }}
                            </td>
                            <td style="padding:10px 14px;text-align:right;" class="tp-num">{{ number_format($product->sales_count) }}</td>
                            <td style="padding:10px 14px;text-align:right;" class="tp-num">{{ number_format((float) $product->pv_value, 2) }}</td>
                            <td style="padding:10px 14px;" class="tp-muted">
                                {{ optional($product->deal_verified_at)->timezone('Asia/Bangkok')->format('d/m H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:26px 14px;text-align:center;" class="tp-muted">
                                ยังไม่มีดีล — กด “กวาดเดี๋ยวนี้” เพื่อให้ระบบไปหาของที่ Lazada ลดราคาจริงมาให้
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * ตัวจัดการรายการคำค้น (เพิ่ม/ลบแถว)
 *
 * ⚠️ ชื่อ input ต้องเป็น seeds[i][keyword] โดย i เรียงต่อกันไม่ข้าม
 *    จึงผูก :name กับดัชนีของ x-for ไม่ใช่กับ uid ของแถว
 *    (uid มีไว้ให้ :key เท่านั้น — ถ้าใช้ index เป็น key แถวจะสลับค่ากันตอนลบกลางลิสต์)
 */
function dealSeeds() {
    return {
        uid: 0,
        rows: [],

        init() {
            const initial = @json(array_values($seeds));
            this.rows = initial.map((row) => ({
                uid: ++this.uid,
                keyword: row.keyword ?? '',
                category: row.category ?? '',
            }));
        },

        add() {
            this.rows.push({ uid: ++this.uid, keyword: '', category: '' });
        },

        remove(index) {
            this.rows.splice(index, 1);
        },
    };
}
</script>
@endpush
@endsection
