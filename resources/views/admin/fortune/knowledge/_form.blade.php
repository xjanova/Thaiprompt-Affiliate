{{-- ฟอร์มร่วม create/edit องค์ความรู้ ($item = null ตอนสร้าง) --}}
@php
    $inputCls = 'w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500';
    $labelCls = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="{{ $labelCls }}">หมวด (category) <span class="text-red-500">*</span></label>
        <input type="text" name="category" list="categoryOptions" required
               value="{{ old('category', $item->category ?? '') }}" class="{{ $inputCls }}"
               placeholder="health / feng_shui / guardian_spirits / deities / black_magic">
        <datalist id="categoryOptions">
            @foreach($categories as $c)<option value="{{ $c }}">{{ $categoryLabels[$c] ?? $c }}</option>@endforeach
        </datalist>
        <p class="text-xs text-gray-400 mt-1">พิมพ์หมวดใหม่ได้ หรือเลือกจากที่มี</p>
    </div>
    <div>
        <label class="{{ $labelCls }}">ชื่อไพ่ (card_name) — เฉพาะความรู้รายไพ่</label>
        <input type="text" name="card_name" value="{{ old('card_name', $item->card_name ?? '') }}" class="{{ $inputCls }}"
               placeholder="เช่น The Sun / Two of Cups (เว้นว่างถ้าไม่ใช่รายไพ่)">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="{{ $labelCls }}">หัวเรื่องย่อย (subject)</label>
        <input type="text" name="subject" value="{{ old('subject', $item->subject ?? '') }}" class="{{ $inputCls }}"
               placeholder="เช่น พระพิฆเนศ / ทิศอาคเนย์ / The Tower">
    </div>
    <div>
        <label class="{{ $labelCls }}">หัวข้อ (title) <span class="text-red-500">*</span></label>
        <input type="text" name="title" required value="{{ old('title', $item->title ?? '') }}" class="{{ $inputCls }}">
    </div>
</div>

<div>
    <label class="{{ $labelCls }}">เนื้อหาความรู้ (content) <span class="text-red-500">*</span></label>
    <textarea name="content" rows="10" required class="{{ $inputCls }} font-mono text-sm"
              placeholder="องค์ความรู้ที่ AI จะดึงไปทำนาย...">{{ old('content', $item->content ?? '') }}</textarea>
    <p class="text-xs text-gray-400 mt-1">สุขภาพ: เขียนเป็น "อวัยวะ/ระบบ: ... / ตั้งตรง: ... / กลับหัว: ..." · หัวข้ออื่น: เขียนความรู้ได้อิสระ</p>
</div>

<div>
    <label class="{{ $labelCls }}">คำค้น trigger (keywords)</label>
    <input type="text" name="keywords" value="{{ old('keywords', $item->keywords ?? '') }}" class="{{ $inputCls }}"
           placeholder="คั่นด้วย , เช่น ฮวงจุ้ย,จัดบ้าน,ทิศ">
    <p class="text-xs text-gray-400 mt-1">หมายเหตุ: การ detect หมวดหลักใช้คีย์เวิร์ดในระบบ — ช่องนี้ช่วยให้แอดมินค้นหา/อ้างอิง</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="{{ $labelCls }}">ความรุนแรง (severity)</label>
        <select name="severity" class="{{ $inputCls }}">
            @php $sev = old('severity', $item->severity ?? ''); @endphp
            <option value="" @selected($sev === '')>—</option>
            <option value="low" @selected($sev === 'low')>low</option>
            <option value="medium" @selected($sev === 'medium')>medium</option>
            <option value="high" @selected($sev === 'high')>high</option>
        </select>
    </div>
    <div>
        <label class="{{ $labelCls }}">ลำดับ (priority)</label>
        <input type="number" name="priority" min="0" max="9999" value="{{ old('priority', $item->priority ?? 0) }}" class="{{ $inputCls }}">
    </div>
    <div>
        <label class="{{ $labelCls }}">อ้างอิง (source)</label>
        <input type="text" name="source" value="{{ old('source', $item->source ?? '') }}" class="{{ $inputCls }}" placeholder="เช่น Liber 777">
    </div>
</div>

<label class="flex items-center gap-2 cursor-pointer">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))
           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
    <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน (AI จะดึงความรู้นี้ไปใช้)</span>
</label>
