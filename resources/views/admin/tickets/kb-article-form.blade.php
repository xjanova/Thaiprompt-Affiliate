@extends('layouts.admin-v4')

@section('title', isset($article) ? 'แก้ไขบทความ' : 'เขียนบทความใหม่')

@section('content')
{{--
    📝 ฟอร์มบทความฐานความรู้ (ธีม V4 นวลทองคำ)
    ใช้ร่วมกันทั้งสร้างและแก้ไข — คง route/ฟิลด์/old() เดิม 100%
--}}
@php $isEdit = isset($article); @endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.tickets.kb-articles.index') }}" class="tp-icon-btn" title="กลับไปรายการบทความ">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · ฐานความรู้</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                    {{ $isEdit ? 'แก้ไขบทความ' : 'เขียนบทความใหม่' }} 📝
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">บทความช่วยเหลือที่ผู้ใช้ค้นหาอ่านเองได้</div>
            </div>
        </div>
        <button type="submit" form="kbArticleForm" class="tp-btn tp-btn-sm tp-btn-primary" style="font-weight:700;">
            <i class="fas fa-save"></i> {{ $isEdit ? 'บันทึกการแก้ไข' : 'เผยแพร่บทความ' }}
        </button>
    </div>

    {{-- ===== ข้อผิดพลาด ===== --}}
    @if($errors->any())
        <div class="tp-card" style="padding:16px 18px; border-left:4px solid #d9534f;">
            <div style="font-size:13.5px; font-weight:700; color:#d9534f; margin-bottom:6px;">
                <i class="fas fa-circle-exclamation"></i> ตรวจสอบข้อมูลอีกครั้ง
            </div>
            <ul style="margin:0; padding-left:20px; font-size:12.5px; color:var(--ink2);">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== ฟอร์ม ===== --}}
    <form id="kbArticleForm" method="POST"
          action="{{ $isEdit ? route('admin.tickets.kb-articles.update', $article->id) : route('admin.tickets.kb-articles.store') }}"
          style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- เนื้อหาบทความ --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-file-lines"></i> เนื้อหาบทความ</div>

            <div style="margin-bottom:16px;">
                <label for="title" style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    หัวข้อ <span style="color:#d9534f;">*</span>
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="title" id="title" required
                           value="{{ old('title', $article->title ?? '') }}"
                           placeholder="เช่น วิธีรีเซ็ตรหัสผ่าน"
                           style="width:100%; background:transparent; border:none; outline:none; padding:11px 13px; color:var(--ink); font-size:15px; font-weight:600;">
                </div>
                @error('title')
                    <div style="font-size:12px; color:#d9534f; margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="content" style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    เนื้อหา <span style="color:#d9534f;">*</span>
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="content" id="content" rows="15" required
                              placeholder="เขียนขั้นตอนให้ผู้ใช้ทำตามได้เอง..."
                              style="width:100%; background:transparent; border:none; outline:none; padding:13px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit; line-height:1.75;">{{ old('content', $article->content ?? '') }}</textarea>
                </div>
                <div style="font-size:11px; color:var(--ink2); margin-top:6px;">รองรับ Markdown พื้นฐาน</div>
                @error('content')
                    <div style="font-size:12px; color:#d9534f; margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- การจัดหมวดหมู่ --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-folder-tree"></i> การจัดหมวดหมู่</div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px; margin-bottom:16px;">
                <div>
                    <label for="category_id" style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมวดหมู่</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="category_id" id="category_id"
                                style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                            <option value="">เลือกหมวดหมู่</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $article->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="tags" style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">แท็ก (คั่นด้วยคอมม่า)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="tags" id="tags"
                               value="{{ old('tags', $isEdit && $article->tags ? implode(', ', $article->tags) : '') }}"
                               placeholder="password, login, account"
                               style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                    </div>
                </div>
            </div>

            <label class="tp-well" style="display:flex; align-items:center; gap:10px; padding:13px 15px; cursor:pointer;">
                {{-- hidden 0 นำหน้า: ไม่ติ๊กแล้วต้องบันทึกเป็น "ส่วนตัว" ได้จริง --}}
                <input type="hidden" name="is_public" value="0">
                <input type="checkbox" name="is_public" id="is_public" value="1"
                       {{ old('is_public', $article->is_public ?? true) ? 'checked' : '' }}
                       style="accent-color:#5aa07e; width:16px; height:16px; cursor:pointer;">
                <span style="font-size:13px; font-weight:600; color:var(--ink);">
                    <i class="fas fa-globe" style="color:#5aa07e; margin-right:6px;"></i>เผยแพร่สาธารณะ (ผู้ใช้ทั่วไปอ่านได้)
                </span>
            </label>
        </div>

        {{-- ปุ่มล่าง --}}
        <div style="display:flex; flex-wrap:wrap; justify-content:flex-end; gap:10px;">
            <a href="{{ route('admin.tickets.kb-articles.index') }}" class="tp-btn">ยกเลิก</a>
            <button type="submit" class="tp-btn tp-btn-primary" style="font-weight:700; padding:11px 22px;">
                <i class="fas fa-save"></i> {{ $isEdit ? 'บันทึกการแก้ไข' : 'เผยแพร่บทความ' }}
            </button>
        </div>
    </form>
</div>
@endsection
