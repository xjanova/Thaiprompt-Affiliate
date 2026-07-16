@extends('layouts.user-v4')

@section('title', 'สร้าง Ticket ใหม่')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:900px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5689b8 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px; background:#5689b8;"><i class="fas fa-edit" style="color:#fff;"></i></span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">สร้าง Ticket ใหม่</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">แจ้งปัญหาหรือขอความช่วยเหลือจากทีมงาน</div>
                    </div>
                </div>
                <a href="{{ route('user.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fa-solid fa-arrow-left"></i> กลับ</a>
            </div>
        </div>
    </div>

    {{-- ── ฟอร์ม ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;" x-data="{ cat: '{{ old('category_id') }}', pri: '{{ old('priority', 'medium') }}' }">
        <form method="POST" action="{{ route('user.tickets.store') }}" style="display:flex; flex-direction:column; gap:22px;">
            @csrf

            {{-- หมวดหมู่ --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:12px;"><i class="fa-solid fa-folder" style="margin-right:6px;"></i>หมวดหมู่ *</label>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
                    @foreach($categories as $category)
                        <label style="cursor:pointer;">
                            <input type="radio" name="category_id" value="{{ $category->id }}" x-model="cat" required style="position:absolute; opacity:0; pointer-events:none;">
                            <div class="tp-card" style="padding:16px; text-align:center;"
                                 :style="cat == '{{ $category->id }}' ? 'box-shadow:0 0 0 2px {{ $category->color }}, var(--raise);' : ''">
                                <div style="width:64px; height:64px; margin:0 auto 10px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; background:linear-gradient(135deg, {{ $category->color }}15, {{ $category->color }}30); color:{{ $category->color }}; border:2px solid {{ $category->color }}40;">
                                    <i class="{{ $category->icon ?: 'fa-solid fa-folder' }}"></i>
                                </div>
                                <div style="font-weight:800; font-size:14px; color:var(--ink); margin-bottom:2px;">{{ $category->name }}</div>
                                <div style="font-size:11px; color:var(--ink2);">{{ $category->description }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('category_id')
                    <p style="margin-top:8px; font-size:13px; color:#d9534f;"><i class="fa-solid fa-exclamation-circle" style="margin-right:4px;"></i>{{ $message }}</p>
                @enderror
                <p style="margin-top:10px; font-size:11px; color:var(--ink2);"><i class="fa-solid fa-lightbulb" style="color:#d9a441; margin-right:4px;"></i>เลือกหมวดหมู่ที่ตรงกับปัญหาของคุณมากที่สุดเพื่อให้ทีมงานสามารถช่วยเหลือได้อย่างรวดเร็ว</p>
            </div>

            {{-- หัวข้อ --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:8px;"><i class="fa-solid fa-heading" style="margin-right:6px;"></i>หัวข้อ *</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="255"
                       placeholder="สรุปปัญหาหรือคำถามของคุณในหัวข้อสั้นๆ" class="tp-input">
                @error('subject')<p style="margin-top:8px; font-size:13px; color:#d9534f;">{{ $message }}</p>@enderror
            </div>

            {{-- รายละเอียด --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:8px;"><i class="fa-solid fa-align-left" style="margin-right:6px;"></i>รายละเอียด *</label>
                <textarea name="description" required rows="8"
                          placeholder="โปรดอธิบายปัญหาหรือคำถามของคุณให้ละเอียดที่สุด เพื่อให้เราสามารถช่วยเหลือคุณได้อย่างรวดเร็ว" class="tp-input" style="resize:vertical;">{{ old('description') }}</textarea>
                @error('description')<p style="margin-top:8px; font-size:13px; color:#d9534f;">{{ $message }}</p>@enderror
                <p style="margin-top:8px; font-size:11px; color:var(--ink2);"><i class="fa-solid fa-info-circle" style="margin-right:4px;"></i>ควรระบุ: ขั้นตอนที่ทำให้เกิดปัญหา, ข้อความ error (ถ้ามี), และสิ่งที่คุณคาดหวัง</p>
            </div>

            {{-- ความสำคัญ --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:12px;"><i class="fa-solid fa-exclamation-circle" style="margin-right:6px;"></i>ความสำคัญ</label>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
                    @foreach(['low' => ['😌','ต่ำ','ไม่เร่งด่วน','#8a8a8a'], 'medium' => ['🤔','ปานกลาง','ปกติ (แนะนำ)','#5689b8'], 'high' => ['😰','สูง','เร่งด่วน','#e08a3c']] as $val => $p)
                        <label style="cursor:pointer;">
                            <input type="radio" name="priority" value="{{ $val }}" x-model="pri" style="position:absolute; opacity:0; pointer-events:none;">
                            <div class="tp-card" style="padding:16px; text-align:center;"
                                 :style="pri == '{{ $val }}' ? 'box-shadow:0 0 0 2px {{ $p[3] }}, var(--raise);' : ''">
                                <div style="font-size:28px; margin-bottom:6px;">{{ $p[0] }}</div>
                                <div style="font-weight:800; color:var(--ink);">{{ $p[1] }}</div>
                                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $p[2] }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('priority')<p style="margin-top:8px; font-size:13px; color:#d9534f;">{{ $message }}</p>@enderror
            </div>

            {{-- เคล็ดลับ --}}
            <div class="tp-card" style="padding:18px; border-left:4px solid #5689b8;">
                <div style="display:flex; gap:12px;">
                    <i class="fa-solid fa-lightbulb" style="font-size:20px; color:#5689b8; margin-top:2px;"></i>
                    <div>
                        <div style="font-weight:800; color:var(--ink); margin-bottom:8px;">เคล็ดลับสำหรับการสร้าง Ticket ที่ดี</div>
                        <ul style="margin:0; padding:0; list-style:none; font-size:13px; color:var(--ink2); display:flex; flex-direction:column; gap:4px;">
                            <li>• เลือกหมวดหมู่ที่ตรงกับปัญหาของคุณมากที่สุด</li>
                            <li>• เขียนหัวข้อที่กระชับและชัดเจน</li>
                            <li>• อธิบายรายละเอียดให้ละเอียดเพื่อให้ทีมงานช่วยเหลือได้เร็วขึ้น</li>
                            <li>• หากเป็นปัญหาเทคนิค ควรระบุ browser, อุปกรณ์, และขั้นตอนการเกิดปัญหา</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- ปุ่ม --}}
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <a href="{{ route('user.tickets.index') }}" class="tp-btn"><i class="fa-solid fa-times"></i> ยกเลิก</a>
                <button type="submit" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fa-solid fa-paper-plane"></i> สร้าง Ticket</button>
            </div>
        </form>
    </div>
</div>
@endsection
