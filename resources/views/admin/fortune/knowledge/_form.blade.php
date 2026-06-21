{{-- ฟอร์มร่วม create/edit องค์ความรู้ ($item = null ตอนสร้าง) — ธีม V4 นวลทองคำ (PARTIAL: ห้ามใส่ @extends/@section) --}}
@php
    // คลาสร่วมสำหรับ label และ helper text ทุกช่อง (อ้างตัวแปรธีม V4 ไม่ hardcode สีเทา/ขาว)
    $lblStyle  = 'display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:7px;';
    $hintStyle = 'font-size:12px;color:var(--ink2);opacity:.85;margin-top:6px;line-height:1.5;';
    $reqStyle  = 'color:#d9534f;font-weight:700;';
    // กล่องอินพุตธีม V4 — .tp-well ครอบ + input โปร่งใสด้านใน
    $wellStyle = 'padding:0;';
    $inStyle   = 'width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;';
    $selStyle  = 'width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;';
@endphp

{{-- แถวที่ 1: หมวด + ชื่อไพ่ --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;">
    <div>
        <label style="{{ $lblStyle }}">หมวด (category) <span style="{{ $reqStyle }}">*</span></label>
        <div class="tp-well tp-input" style="{{ $wellStyle }}">
            <input type="text" name="category" list="categoryOptions" required
                   value="{{ old('category', $item->category ?? '') }}" style="{{ $inStyle }}"
                   placeholder="health / feng_shui / guardian_spirits / deities / black_magic">
        </div>
        <datalist id="categoryOptions">
            @foreach($categories as $c)<option value="{{ $c }}">{{ $categoryLabels[$c] ?? $c }}</option>@endforeach
        </datalist>
        <p style="{{ $hintStyle }}">พิมพ์หมวดใหม่ได้ หรือเลือกจากที่มี</p>
    </div>
    <div>
        <label style="{{ $lblStyle }}">ชื่อไพ่ (card_name) — เฉพาะความรู้รายไพ่</label>
        <div class="tp-well tp-input" style="{{ $wellStyle }}">
            <input type="text" name="card_name" value="{{ old('card_name', $item->card_name ?? '') }}" style="{{ $inStyle }}"
                   placeholder="เช่น The Sun / Two of Cups (เว้นว่างถ้าไม่ใช่รายไพ่)">
        </div>
    </div>
</div>

{{-- การ์ดไพ่ที่ผูกกับความรู้ (โชว์เฉพาะตอนแก้ไขที่มี card_name) --}}
@if(($card ?? null))
    <div class="tp-inset" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:14px;margin-top:16px;">
        @if($card->image_url)
            <img src="{{ $card->image_url }}" alt="{{ $card->name_en }}"
                 style="width:56px;height:auto;border-radius:8px;flex-shrink:0;box-shadow:var(--raise);">
        @endif
        <div>
            <div style="font-size:11px;color:var(--ink2);margin-bottom:3px;letter-spacing:.3px;">ไพ่ที่ผูกกับความรู้นี้</div>
            <div style="font-weight:700;color:var(--ink);font-size:15px;">🃏 {{ $card->name_th }}</div>
            <div style="font-size:12px;color:var(--ink2);">{{ $card->name_en }}</div>
        </div>
    </div>
@endif

{{-- แถวที่ 2: หัวเรื่องย่อย + หัวข้อ --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin-top:16px;">
    <div>
        <label style="{{ $lblStyle }}">หัวเรื่องย่อย (subject)</label>
        <div class="tp-well tp-input" style="{{ $wellStyle }}">
            <input type="text" name="subject" value="{{ old('subject', $item->subject ?? '') }}" style="{{ $inStyle }}"
                   placeholder="เช่น พระพิฆเนศ / ทิศอาคเนย์ / The Tower">
        </div>
    </div>
    <div>
        <label style="{{ $lblStyle }}">หัวข้อ (title) <span style="{{ $reqStyle }}">*</span></label>
        <div class="tp-well tp-input" style="{{ $wellStyle }}">
            <input type="text" name="title" required value="{{ old('title', $item->title ?? '') }}" style="{{ $inStyle }}">
        </div>
    </div>
</div>

{{-- เนื้อหาความรู้ --}}
<div style="margin-top:16px;">
    <label style="{{ $lblStyle }}">เนื้อหาความรู้ (content) <span style="{{ $reqStyle }}">*</span></label>
    <div class="tp-well tp-input" style="{{ $wellStyle }}">
        <textarea name="content" rows="10" required
                  style="{{ $inStyle }}font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;line-height:1.6;resize:vertical;"
                  placeholder="องค์ความรู้ที่ AI จะดึงไปทำนาย...">{{ old('content', $item->content ?? '') }}</textarea>
    </div>
    <p style="{{ $hintStyle }}">สุขภาพ: เขียนเป็น "อวัยวะ/ระบบ: ... / ตั้งตรง: ... / กลับหัว: ..." · หัวข้ออื่น: เขียนความรู้ได้อิสระ</p>
</div>

{{-- คำค้น trigger --}}
<div style="margin-top:16px;">
    <label style="{{ $lblStyle }}">คำค้น trigger (keywords)</label>
    <div class="tp-well tp-input" style="{{ $wellStyle }}">
        <input type="text" name="keywords" value="{{ old('keywords', $item->keywords ?? '') }}" style="{{ $inStyle }}"
               placeholder="คั่นด้วย , เช่น ฮวงจุ้ย,จัดบ้าน,ทิศ">
    </div>
    <p style="{{ $hintStyle }}">หมายเหตุ: การ detect หมวดหลักใช้คีย์เวิร์ดในระบบ — ช่องนี้ช่วยให้แอดมินค้นหา/อ้างอิง</p>
</div>

{{-- แถวที่ 3: ความรุนแรง + ลำดับ + อ้างอิง --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin-top:16px;">
    <div>
        <label style="{{ $lblStyle }}">ความรุนแรง (severity)</label>
        <div class="tp-well tp-input" style="{{ $wellStyle }}">
            @php $sev = old('severity', $item->severity ?? ''); @endphp
            <select name="severity" style="{{ $selStyle }}">
                <option value="" @selected($sev === '')>—</option>
                <option value="low" @selected($sev === 'low')>low</option>
                <option value="medium" @selected($sev === 'medium')>medium</option>
                <option value="high" @selected($sev === 'high')>high</option>
            </select>
        </div>
    </div>
    <div>
        <label style="{{ $lblStyle }}">ลำดับ (priority)</label>
        <div class="tp-well tp-input" style="{{ $wellStyle }}">
            <input type="number" name="priority" min="0" max="9999"
                   value="{{ old('priority', $item->priority ?? 0) }}" style="{{ $inStyle }}">
        </div>
    </div>
    <div>
        <label style="{{ $lblStyle }}">อ้างอิง (source)</label>
        <div class="tp-well tp-input" style="{{ $wellStyle }}">
            <input type="text" name="source" value="{{ old('source', $item->source ?? '') }}" style="{{ $inStyle }}"
                   placeholder="เช่น Liber 777">
        </div>
    </div>
</div>

{{-- เปิด/ปิดใช้งาน --}}
<label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-top:18px;">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))
           style="width:18px;height:18px;border-radius:5px;accent-color:var(--accent1);cursor:pointer;">
    <span style="font-size:14px;color:var(--ink);">เปิดใช้งาน (AI จะดึงความรู้นี้ไปใช้)</span>
</label>
