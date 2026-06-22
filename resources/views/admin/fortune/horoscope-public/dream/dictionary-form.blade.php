{{-- ฟอร์มเพิ่ม/แก้ไขสัญลักษณ์ฝัน — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- คอนเทนเนอร์หลัก จัดเรียงแนวตั้งเว้นระยะ --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ส่วนหัว: ป้ายนำ + ชื่อหน้า + ปุ่มกลับ --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem; letter-spacing:.12em; text-transform:uppercase; margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · พจนานุกรมทำนายฝัน
            </div>
            <h1 class="tp-num" style="font-size:1.5rem; margin:0; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-moon" style="color:var(--accent1);"></i>
                {{ $symbol ? 'แก้ไขสัญลักษณ์ฝัน' : 'เพิ่มสัญลักษณ์ฝัน' }}
            </h1>
            @if($symbol)
                <div class="tp-muted" style="font-size:.85rem; margin-top:6px;">
                    ฝันเห็น{{ $symbol->keyword_th }}
                </div>
            @endif
        </div>
        <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}" class="tp-btn tp-btn-sm">
            <i class="fas fa-arrow-left"></i> กลับรายการ
        </a>
    </div>

    {{-- กล่องแสดงข้อผิดพลาด --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <i class="fas fa-circle-exclamation" style="color:#d9534f;"></i>
                <span style="font-weight:700; color:#d9534f;">พบข้อผิดพลาด</span>
            </div>
            <ul style="margin:0; padding-left:20px; font-size:.85rem; color:var(--ink2);">
                @foreach($errors->all() as $error)
                    <li style="margin-bottom:3px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ฟอร์มหลัก — คง action/method/@csrf/@method เดิม 100% --}}
    <form action="{{ $symbol
            ? route('admin.fortune.horoscope-public.dream.update', $symbol)
            : route('admin.fortune.horoscope-public.dream.store') }}"
          method="POST"
          style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @if($symbol)
            @method('PUT')
        @endif

        {{-- การ์ดที่ 1: ข้อมูลสัญลักษณ์ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <i class="fas fa-cloud-moon" style="color:var(--accent1);"></i>
                <span>ข้อมูลสัญลักษณ์</span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                {{-- หมวดหมู่ --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.8rem; font-weight:600; margin-bottom:8px;">
                        หมวดหมู่ <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="category_id" required
                                style="width:100%; padding:11px 14px; background:transparent; border:0; color:var(--ink); font:inherit; outline:none;">
                            <option value="">— เลือกหมวดหมู่ —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                        {{ old('category_id', $symbol?->category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->icon }} {{ $cat->name_th }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- คำสำคัญ (keyword) --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.8rem; font-weight:600; margin-bottom:8px;">
                        คำสำคัญ (keyword) <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input">
                        <input type="text" name="keyword_th"
                               value="{{ old('keyword_th', $symbol?->keyword_th) }}"
                               placeholder="เช่น งู, น้ำท่วม, ไฟไหม้" required
                               style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font:inherit;">
                    </div>
                    <p class="tp-muted" style="margin:6px 0 0; font-size:.72rem;">
                        แสดงเป็น "ฝันเห็น{keyword}" ในหน้า frontend
                    </p>
                </div>
            </div>

            {{-- ความหมาย --}}
            <div style="margin-top:16px;">
                <label class="tp-muted" style="display:block; font-size:.8rem; font-weight:600; margin-bottom:8px;">
                    ความหมาย <span style="color:#d9534f;">*</span>
                </label>
                <div class="tp-well tp-input">
                    <textarea name="meaning_th" rows="4" required
                              placeholder="อธิบายความหมายของสัญลักษณ์นี้ในฝัน..."
                              style="width:100%; background:transparent; border:none; outline:none; resize:vertical; color:var(--ink); font:inherit; line-height:1.6;">{{ old('meaning_th', $symbol?->meaning_th) }}</textarea>
                </div>
            </div>
        </div>

        {{-- การ์ดที่ 2: เลขเด็ด + ลางบอกเหตุ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <i class="fas fa-clover" style="color:var(--accent1);"></i>
                <span>เลขเด็ด + ลางบอกเหตุ</span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
                {{-- เลขเด็ด --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.8rem; font-weight:600; margin-bottom:8px;">
                        เลขเด็ด
                    </label>
                    <div class="tp-well tp-input">
                        <input type="text" name="lucky_numbers"
                               value="{{ old('lucky_numbers', is_array($symbol?->lucky_numbers) ? implode(', ', $symbol->lucky_numbers) : '') }}"
                               placeholder="เช่น 12, 34, 56"
                               style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font:inherit;">
                    </div>
                    <p class="tp-muted" style="margin:6px 0 0; font-size:.72rem;">
                        คั่นด้วย comma หรือ space
                    </p>
                </div>

                {{-- ระดับความเข้มข้น --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.8rem; font-weight:600; margin-bottom:8px;">
                        ระดับความเข้มข้น <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="intensity" required
                                style="width:100%; padding:11px 14px; background:transparent; border:0; color:var(--ink); font:inherit; outline:none;">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ old('intensity', $symbol?->intensity ?? 3) == $i ? 'selected' : '' }}>
                                    {{ $i }} {{ str_repeat('⭐', $i) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>

                {{-- ลางดี / ลางระวัง — toggle ด้วย Alpine (subtree มี x-data ของตัวเอง) --}}
                <div x-data="{ omen: {{ old('is_good_omen', $symbol?->is_good_omen ?? true) ? 'true' : 'false' }} }">
                    <label class="tp-muted" style="display:block; font-size:.8rem; font-weight:600; margin-bottom:8px;">
                        ลางบอกเหตุ
                    </label>
                    {{-- hidden + checkbox คง name/value เดิม (กลไกฟอร์มไม่เปลี่ยน) --}}
                    <input type="hidden" name="is_good_omen" value="0">
                    <input type="checkbox" name="is_good_omen" value="1"
                           x-model="omen" style="display:none;">
                    <button type="button"
                            @click="omen = !omen"
                            class="tp-well"
                            style="width:100%; display:flex; align-items:center; gap:10px; padding:11px 14px; cursor:pointer; border:0; text-align:left;"
                            :style="omen
                                ? 'box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;'
                                : 'box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;'">
                        <i class="fas" :class="omen ? 'fa-circle-check' : 'fa-triangle-exclamation'"
                           :style="omen ? 'color:#5aa07e;' : 'color:#e0a52e;'"></i>
                        <div>
                            <span style="font-weight:700; color:var(--ink); font-size:.9rem;"
                                  x-text="omen ? 'ลางดี' : 'ลางระวัง'"></span>
                            <div class="tp-muted" style="font-size:.7rem;"
                                 x-text="omen ? 'สัญลักษณ์มงคล' : 'สัญลักษณ์เตือนให้ระวัง'"></div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- การ์ดที่ 3: สถานะการใช้งาน --}}
        <div class="tp-card" x-data="{ active: {{ old('is_active', $symbol?->is_active ?? true) ? 'true' : 'false' }} }">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <i class="fas fa-toggle-on" style="color:var(--accent1);"></i>
                <span>สถานะการใช้งาน</span>
            </div>

            {{-- hidden + checkbox คง name/value เดิม --}}
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   x-model="active" style="display:none;">
            <button type="button"
                    @click="active = !active"
                    class="tp-well"
                    style="width:100%; display:flex; align-items:center; gap:12px; padding:14px 16px; cursor:pointer; border:0; text-align:left;"
                    :style="active
                        ? 'box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;'
                        : 'box-shadow:var(--inset-sm); border-left:4px solid #9a8f7c;'">
                <i class="fas fa-power-off" style="font-size:1.1rem;"
                   :style="active ? 'color:#5aa07e;' : 'color:#9a8f7c;'"></i>
                <div>
                    <span style="font-weight:700; color:var(--ink);"
                          x-text="active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"></span>
                    <div class="tp-muted" style="font-size:.74rem;">
                        แสดงสัญลักษณ์นี้ในหน้า frontend
                    </div>
                </div>
            </button>
        </div>

        {{-- แถวปุ่มดำเนินการ --}}
        <div style="display:flex; justify-content:flex-end; gap:12px; flex-wrap:wrap;">
            <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}" class="tp-btn">
                <i class="fas fa-xmark"></i> ยกเลิก
            </a>
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i>
                {{ $symbol ? 'บันทึกการแก้ไข' : 'เพิ่มสัญลักษณ์' }}
            </button>
        </div>
    </form>
</div>
@endsection
