@extends('layouts.admin-v4')

@section('title', $question->exists ? 'แก้ไขข้อสอบ' : 'เพิ่มข้อสอบ')

@push('styles')
    @include('admin.classroom.partials.styles')
@endpush

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: เพิ่ม/แก้ไขข้อสอบ (หลังบ้านห้องเรียน)
     ข้อมูลจาก Admin\ClassroomAdminController@createQuestion / editQuestion: $course, $question
     ฟิลด์: question · choices[0..5] (ช่องว่างถูกตัดทิ้ง) · correct_index (ตำแหน่งในช่องที่กรอก) · explanation · is_active
     ════════════════════════════════════════════════════════════ --}}
@php
    $existing = $question->choiceList();
    $choiceValues = [];
    for ($i = 0; $i < \App\Models\ClassroomQuestion::MAX_CHOICES; $i++) {
        $choiceValues[$i] = old('choices.'.$i, $existing[$i] ?? '');
    }
    $correct = (string) old('correct_index', $question->correct_index ?? 0);
    $questionActive = (bool) old('is_active', $question->is_active ?? true);
    $action = $question->exists ? route('admin.classroom.questions.update', $question) : route('admin.classroom.questions.store', $course);
@endphp
<div class="ac-scope" style="max-width:860px;">
    <div class="ac-row ac-between">
        <div style="flex:1; min-width:240px;">
            <div class="ac-eyebrow">หลังบ้าน · ห้องเรียน · คลังข้อสอบ{{ $course->roleLabel() }}</div>
            <h1 class="ac-h1">{{ $question->exists ? 'แก้ไขข้อสอบ #'.$question->id : 'เพิ่มข้อสอบ' }}</h1>
        </div>
        <a href="{{ route('admin.classroom.questions.index', $course) }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> คลังข้อสอบ</a>
    </div>

    @if(session('success'))
        <div class="tp-card ac-flash">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ $action }}" class="tp-card" style="display:flex; flex-direction:column; gap:14px;">
        @csrf
        @if($question->exists)
            @method('PUT')
        @endif

        <div>
            <label class="ac-label" for="question">คำถาม</label>
            <textarea id="question" name="question" class="tp-input" rows="3" maxlength="1000" required>{{ old('question', $question->question) }}</textarea>
            @error('question') <div class="ac-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <span class="ac-label">ตัวเลือก (กรอก 2–{{ \App\Models\ClassroomQuestion::MAX_CHOICES }} ข้อ แล้วเลือกวงกลมหน้าคำตอบที่ถูก)</span>
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($choiceValues as $i => $value)
                    <label class="ac-choice-row">
                        <input type="radio" name="correct_index" value="{{ $i }}" {{ $correct === (string) $i ? 'checked' : '' }} aria-label="ตัวเลือกที่ {{ $i + 1 }} เป็นคำตอบที่ถูก">
                        <input name="choices[{{ $i }}]" class="tp-input" maxlength="300" value="{{ $value }}" placeholder="ตัวเลือกที่ {{ $i + 1 }}{{ $i >= 2 ? ' (ไม่บังคับ)' : '' }}">
                    </label>
                @endforeach
            </div>
            @error('choices') <div class="ac-error">{{ $message }}</div> @enderror
            @error('choices.*') <div class="ac-error">{{ $message }}</div> @enderror
            @error('correct_index') <div class="ac-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="ac-label" for="explanation">คำอธิบายเฉลย (แสดงหลังส่งคำตอบ)</label>
            <textarea id="explanation" name="explanation" class="tp-input" rows="2" maxlength="1000">{{ old('explanation', $question->explanation) }}</textarea>
            @error('explanation') <div class="ac-error">{{ $message }}</div> @enderror
        </div>

        <label class="ac-row" style="gap:8px; cursor:pointer;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($questionActive) style="width:18px; height:18px;">
            <span style="color:var(--ink); font-weight:600;">เปิดใช้ (ถูกสุ่มออกสอบได้)</span>
        </label>

        <div class="ac-row" style="justify-content:flex-end;">
            @unless($question->exists)
                <button type="submit" name="after" value="new" class="tp-btn"><i class="fas fa-plus"></i> บันทึกแล้วเพิ่มข้อต่อไป</button>
            @endunless
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-floppy-disk"></i> บันทึกข้อสอบ</button>
        </div>
    </form>
</div>
@endsection
