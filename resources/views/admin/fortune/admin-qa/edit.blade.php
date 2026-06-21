@extends('layouts.admin-v4')

@section('title', 'แก้ไข Admin Q&A #' . $qa->id)

@section('content')
{{-- หน้าแก้ไขคลังคำตอบแอดมิน (RAG Admin Q&A) — ธีม V4 นวลทองคำ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== ส่วนหัว ===== --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12.5px;letter-spacing:.04em;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · RAG คลังคำตอบแอดมิน
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;color:var(--ink);margin:0;">
                <i class="fas fa-pen-to-square" style="color:var(--accent1);margin-inline-end:8px;"></i>
                แก้ไข Q&amp;A #{{ $qa->id }}
            </h1>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if (Route::has('admin.fortune.admin-qa.index'))
                <a href="{{ route('admin.fortune.admin-qa.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-arrow-left"></i> กลับรายการ
                </a>
            @endif
        </div>
    </div>

    {{-- ===== แจ้ง error การ validate ===== --}}
    @if ($errors->any())
        <div class="tp-card tp-inset" style="padding:16px 18px;border-inline-start:3px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;color:#d9534f;font-weight:700;font-size:14px;">
                <i class="fas fa-triangle-exclamation"></i> พบข้อผิดพลาด
            </div>
            <ul style="margin:0;padding-inline-start:20px;color:var(--ink2);font-size:13.5px;line-height:1.7;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start;">

        {{-- ===== คอลัมน์ซ้าย: ฟอร์มแก้ไข ===== --}}
        <form action="{{ route('admin.fortune.admin-qa.update', $qa) }}" method="POST"
              class="tp-card tp-raise"
              style="padding:24px;display:flex;flex-direction:column;gap:20px;min-width:0;">
            @csrf
            @method('PUT')

            {{-- คำถามลูกค้า --}}
            <div>
                <div class="tp-section-h" style="margin-bottom:8px;">
                    <i class="fas fa-comment-dots" style="color:var(--accent1);"></i>
                    <span>คำถามลูกค้า (Q)</span>
                </div>
                <p class="tp-muted" style="font-size:12px;margin:0 0 8px 0;">
                    ถ้าแก้ข้อความนี้ ระบบจะ re-embed ใหม่อัตโนมัติ
                </p>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="q_text" rows="3" required
                              style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;line-height:1.7;resize:vertical;font-family:inherit;">{{ old('q_text', $qa->q_text) }}</textarea>
                </div>
            </div>

            {{-- คำตอบแอดมิน --}}
            <div>
                <div class="tp-section-h" style="margin-bottom:8px;">
                    <i class="fas fa-reply" style="color:#5aa07e;"></i>
                    <span>คำตอบแอดมิน (A)</span>
                </div>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="a_text" rows="7" required
                              style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;line-height:1.7;resize:vertical;font-family:inherit;">{{ old('a_text', $qa->a_text) }}</textarea>
                </div>
            </div>

            {{-- หมวด --}}
            <div>
                <div class="tp-section-h" style="margin-bottom:8px;">
                    <i class="fas fa-tag" style="color:#e0a52e;"></i>
                    <span>หมวด (category)</span>
                </div>
                <p class="tp-muted" style="font-size:12px;margin:0 0 8px 0;">
                    ระบบจัดให้อัตโนมัติจาก state ตอน capture — แอดมินแก้ได้ถ้าผิด
                </p>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="category"
                            style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;font-family:inherit;">
                        <option value="">— ไม่ระบุ (legacy / ทั่วไป) —</option>
                        @foreach ($categoryOptions as $catKey => $catLabel)
                            <option value="{{ $catKey }}" @selected(old('category', $qa->category) === $catKey)>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="tp-divider"></div>

            {{-- สถานะ + threshold --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                {{-- เปิดใช้งาน --}}
                <div>
                    <div class="tp-section-h" style="margin-bottom:8px;">
                        <i class="fas fa-toggle-on" style="color:#5aa07e;"></i>
                        <span>การใช้งาน</span>
                    </div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="tp-well tp-inset-sm"
                           style="display:flex;align-items:center;gap:10px;padding:11px 14px;cursor:pointer;border-radius:12px;">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $qa->is_active))
                               style="width:18px;height:18px;accent-color:var(--accent1);cursor:pointer;">
                        <span style="font-size:13.5px;color:var(--ink);">เปิดใช้งาน (allow retrieve)</span>
                    </label>
                </div>

                {{-- threshold override --}}
                <div>
                    <div class="tp-section-h" style="margin-bottom:8px;">
                        <i class="fas fa-bullseye" style="color:#5689b8;"></i>
                        <span>Threshold override</span>
                    </div>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" step="0.01" min="0" max="1" name="similarity_threshold"
                               value="{{ old('similarity_threshold', $qa->similarity_threshold) }}"
                               placeholder="เว้นว่าง = ใช้ค่า global"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;font-family:inherit;">
                    </div>
                </div>
            </div>

            <div class="tp-divider"></div>

            {{-- ปุ่ม action ของฟอร์ม --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-floppy-disk"></i> บันทึก
                </button>
                @if (Route::has('admin.fortune.admin-qa.index'))
                    <a href="{{ route('admin.fortune.admin-qa.index') }}" class="tp-btn">
                        ยกเลิก
                    </a>
                @endif
            </div>
        </form>

        {{-- ===== คอลัมน์ขวา: ข้อมูลเมตา + เครื่องมือ ===== --}}
        <div style="display:flex;flex-direction:column;gap:18px;">

            {{-- การ์ดเมตาดาต้า --}}
            <div class="tp-card tp-inset" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:14px;">
                    <i class="fas fa-circle-info" style="color:var(--accent2);"></i>
                    <span>ข้อมูลระเบียน</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:11px;font-size:13px;">

                    @php
                        // สถานะ embedding — มี/ไม่มี เพื่อแสดง pill สี
                        $embDim = $qa->q_embedding ? count($qa->q_embedding) : 0;
                    @endphp

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span class="tp-muted">Embedding</span>
                        @if ($embDim > 0)
                            <span class="tp-pill tp-pill-gold">{{ $embDim }}d</span>
                        @else
                            <span class="tp-pill tp-pill-soft" style="color:#d9534f;">ว่าง</span>
                        @endif
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span class="tp-muted">Platform</span>
                        <span class="tp-pill tp-pill-soft" style="font-family:monospace;">{{ $qa->source_platform }}</span>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span class="tp-muted">Customer</span>
                        <span style="font-family:monospace;color:var(--ink);font-size:12px;word-break:break-all;text-align:end;">{{ $qa->source_user_id ?? '-' }}</span>
                    </div>

                    @if ($qa->page_id)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                            <span class="tp-muted">Page ID</span>
                            <span style="font-family:monospace;color:var(--ink);font-size:12px;word-break:break-all;text-align:end;">{{ $qa->page_id }}</span>
                        </div>
                    @endif

                    @if ($qa->reading_id)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                            <span class="tp-muted">Reading</span>
                            <span style="font-family:monospace;color:var(--ink);font-size:12px;text-align:end;">#{{ $qa->reading_id }} ({{ $qa->reading_type ?? '?' }})</span>
                        </div>
                    @endif

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span class="tp-muted">Hits</span>
                        <span class="tp-num" style="color:var(--ink);font-weight:700;">{{ $qa->hit_count }}</span>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span class="tp-muted">Last hit</span>
                        <span style="color:var(--ink2);font-size:12px;text-align:end;">{{ $qa->last_hit_at?->diffForHumans() ?? 'never' }}</span>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span class="tp-muted">Created</span>
                        <span style="color:var(--ink2);font-size:12px;text-align:end;">{{ $qa->created_at }}</span>
                    </div>
                </div>
            </div>

            {{-- บริบทย้อนหลัง (ถ้ามี) — collapsible ด้วย Alpine --}}
            @if (! empty($qa->context_json['prev_turns']))
                <div class="tp-card tp-inset" style="padding:20px;" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            style="width:100%;display:flex;justify-content:space-between;align-items:center;gap:10px;background:transparent;border:0;cursor:pointer;padding:0;color:var(--ink);">
                        <span class="tp-section-h" style="margin:0;">
                            <i class="fas fa-clock-rotate-left" style="color:var(--accent1);"></i>
                            <span>บริบทย้อนหลัง ({{ count($qa->context_json['prev_turns']) }} turn)</span>
                        </span>
                        <i class="fas fa-chevron-down" style="transition:transform .2s;color:var(--ink2);" :style="open ? 'transform:rotate(180deg)' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse style="margin-top:14px;display:flex;flex-direction:column;gap:8px;">
                        @foreach ($qa->context_json['prev_turns'] as $turn)
                            <div class="tp-well tp-inset-sm" style="padding:9px 12px;font-size:12.5px;line-height:1.6;">
                                <span style="font-weight:700;color:{{ ($turn['role'] ?? '') === 'user' ? '#5689b8' : '#5aa07e' }};">
                                    {{ $turn['role'] ?? '?' }}:
                                </span>
                                <span style="color:var(--ink2);">{{ $turn['content'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- เครื่องมือ: re-embed / ลบ — ใช้ route ที่ยืนยันแล้ว --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:14px;">
                    <i class="fas fa-screwdriver-wrench" style="color:var(--ink2);"></i>
                    <span>เครื่องมือ</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    @if (Route::has('admin.fortune.admin-qa.reembed'))
                        <form action="{{ route('admin.fortune.admin-qa.reembed', $qa) }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit" class="tp-btn tp-btn-sm" style="width:100%;justify-content:center;">
                                <i class="fas fa-rotate"></i> Re-embed ใหม่
                            </button>
                        </form>
                    @endif

                    @if (Route::has('admin.fortune.admin-qa.destroy'))
                        <form action="{{ route('admin.fortune.admin-qa.destroy', $qa) }}" method="POST" style="margin:0;"
                              x-data
                              @submit.prevent="if (confirm('ยืนยันลบระเบียนนี้? การลบไม่สามารถกู้คืนได้')) $el.submit()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tp-btn tp-btn-sm" style="width:100%;justify-content:center;color:#d9534f;">
                                <i class="fas fa-trash-can"></i> ลบระเบียน
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
