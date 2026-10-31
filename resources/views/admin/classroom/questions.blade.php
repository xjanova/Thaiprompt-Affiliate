@extends('layouts.admin-v4')

@section('title', 'คลังข้อสอบ'.$course->roleLabel())

@push('styles')
    @include('admin.classroom.partials.styles')
@endpush

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: คลังข้อสอบของคอร์ส (หลังบ้านห้องเรียน)
     ข้อมูลจาก Admin\ClassroomAdminController@questions:
       $course, $questions (paginate 30), $status (active|inactive|null), $search, $activeCount
     ════════════════════════════════════════════════════════════ --}}
<div class="ac-scope">
    <div class="ac-row ac-between">
        <div style="flex:1; min-width:240px;">
            <div class="ac-eyebrow">หลังบ้าน · ห้องเรียน · คอร์ส{{ $course->roleLabel() }}</div>
            <h1 class="ac-h1">คลังข้อสอบ</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:.85rem;">
                เปิดใช้ {{ number_format($activeCount) }} ข้อ · สุ่มออกสอบครั้งละ {{ $course->question_count }} ข้อ (ผ่าน {{ $course->pass_score }}) · ตอนสอบระบบสลับลำดับตัวเลือกให้เอง
            </p>
        </div>
        <div class="ac-row">
            <a href="{{ route('admin.classroom.courses.edit', $course) }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> คอร์ส</a>
            <a href="{{ route('admin.classroom.questions.create', $course) }}" class="tp-btn tp-btn-primary tp-btn-sm"><i class="fas fa-plus"></i> เพิ่มข้อสอบ</a>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card ac-flash">{{ session('success') }}</div>
    @endif
    @if($activeCount < $course->question_count)
        <div class="tp-card ac-flash-bad">⚠️ ข้อสอบที่เปิดใช้ ({{ $activeCount }}) น้อยกว่าจำนวนที่สุ่มต่อครั้ง ({{ $course->question_count }}) — ระบบจะสุ่มเท่าที่มีและลดเกณฑ์ผ่านตามสัดส่วน</div>
    @endif

    <form method="GET" class="tp-card ac-row">
        <input name="q" value="{{ $search }}" class="tp-input" style="flex:1; min-width:200px;" placeholder="ค้นหาคำถาม">
        <select name="status" class="tp-input" style="width:auto; min-width:150px;">
            <option value="">ทุกสถานะ</option>
            <option value="active" @selected($status === 'active')>เปิดใช้</option>
            <option value="inactive" @selected($status === 'inactive')>ปิดอยู่</option>
        </select>
        <button type="submit" class="tp-btn"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
    </form>

    <div style="display:flex; flex-direction:column; gap:12px;">
        @forelse($questions as $question)
            <div class="tp-card" style="display:flex; flex-direction:column; gap:10px; @if(! $question->is_active) opacity:.62; @endif">
                <div class="ac-row ac-between" style="align-items:flex-start;">
                    <div style="flex:1; min-width:0; font-weight:700; color:var(--ink); line-height:1.5;">
                        <span class="tp-muted" style="font-size:.75rem;">#{{ $question->id }}</span> {{ $question->question }}
                    </div>
                    <span class="tp-pill {{ $question->is_active ? 'ac-pill-ok' : 'ac-pill-off' }}">{{ $question->is_active ? 'เปิดใช้' : 'ปิดอยู่' }}</span>
                </div>
                <ol style="margin:0; padding-left:20px; font-size:.86rem; color:var(--ink); line-height:1.7;">
                    @foreach($question->choiceList() as $ci => $choice)
                        <li @if($ci === (int) $question->correct_index) style="font-weight:800; color:#2f855a;" @endif>
                            {{ $choice }} @if($ci === (int) $question->correct_index) <i class="fas fa-circle-check" aria-label="คำตอบที่ถูก"></i> @endif
                        </li>
                    @endforeach
                </ol>
                @if($question->explanation)
                    <div class="tp-muted" style="font-size:.8rem;"><i class="fas fa-lightbulb"></i> {{ $question->explanation }}</div>
                @endif
                <div class="ac-row" style="justify-content:flex-end; gap:6px;">
                    <a href="{{ route('admin.classroom.questions.edit', $question) }}" class="tp-btn tp-btn-sm"><i class="fas fa-pen"></i> แก้ไข</a>
                    <form method="POST" action="{{ route('admin.classroom.questions.toggle', $question) }}">
                        @csrf
                        <button type="submit" class="tp-btn tp-btn-sm">{{ $question->is_active ? 'ปิดข้อนี้' : 'เปิดใช้' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.classroom.questions.destroy', $question) }}"
                          x-data x-on:submit="if (!confirm('ลบข้อสอบ #{{ $question->id }} ถาวร? (ถ้าแค่ไม่อยากให้ออกสอบ ใช้ปุ่มปิดข้อนี้แทน)')) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-btn tp-btn-sm ac-danger"><i class="fas fa-trash"></i> ลบ</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="tp-card" style="text-align:center; padding:28px;">
                <p class="tp-muted" style="margin:0 0 12px;">{{ $search !== '' || $status ? 'ไม่พบข้อสอบตามเงื่อนไข' : 'ยังไม่มีข้อสอบในคอร์สนี้' }}</p>
                <a href="{{ route('admin.classroom.questions.create', $course) }}" class="tp-btn tp-btn-primary tp-btn-sm"><i class="fas fa-plus"></i> เพิ่มข้อสอบ</a>
            </div>
        @endforelse
    </div>

    @if($questions->hasPages())
        <div>{{ $questions->links() }}</div>
    @endif
</div>
@endsection
