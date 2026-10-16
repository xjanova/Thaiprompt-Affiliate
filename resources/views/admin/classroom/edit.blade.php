@extends('layouts.admin-v4')

@section('title', 'แก้ไขคอร์ส'.$course->roleLabel())

@push('styles')
    @include('admin.classroom.partials.styles')
@endpush

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: แก้ไขคอร์ส + จัดการบทเรียน (หลังบ้านห้องเรียน)
     ข้อมูลจาก Admin\ClassroomAdminController@editCourse:
       $course (ClassroomCourse + lessons เรียงตาม sort_order), $completedByLesson (lesson_id → จำนวนคนดูจบ),
       $certifiedCount (ผู้ผ่านการอบรมของบทบาทนี้ทุกรุ่น)
     error bag: default (คอร์ส) · lesson (เพิ่มบท) · lesson_{id} (แก้บท)
     ════════════════════════════════════════════════════════════ --}}
@php
    $newLessonErrors = $errors->getBag('lesson');
    $lessonCount = $course->lessons->count();
    $versionNow = (string) old('rules_version', $course->rules_version);
    $courseActive = (bool) old('is_active', $course->is_active);
@endphp
<div class="ac-scope">
    <div class="ac-row ac-between">
        <div style="flex:1; min-width:240px;">
            <div class="ac-eyebrow">หลังบ้าน · ห้องเรียน · คอร์ส{{ $course->roleLabel() }}</div>
            <h1 class="ac-h1">{{ $course->title }}</h1>
        </div>
        <div class="ac-row">
            <a href="{{ route('admin.classroom.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> ทุกคอร์ส</a>
            <a href="{{ route('admin.classroom.questions.index', $course) }}" class="tp-btn tp-btn-sm"><i class="fas fa-list-check"></i> คลังข้อสอบ</a>
            <a href="{{ route('admin.classroom.stats', $course) }}" class="tp-btn tp-btn-sm"><i class="fas fa-chart-simple"></i> สถิติ</a>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card ac-flash">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="tp-card ac-flash-bad">{{ $errors->first() }}</div>
    @endif

    {{-- ── ตั้งค่าคอร์ส ── --}}
    <form method="POST" action="{{ route('admin.classroom.courses.update', $course) }}" class="tp-card" style="display:flex; flex-direction:column; gap:14px;"
          x-data="{ version: {{ \Illuminate\Support\Js::from($versionNow) }}, original: {{ \Illuminate\Support\Js::from((string) $course->rules_version) }} }">
        @csrf
        @method('PUT')
        <h2 class="ac-h2"><i class="fas fa-sliders" style="color:var(--deep1);"></i> ตั้งค่าคอร์ส</h2>

        <div>
            <label class="ac-label" for="title">ชื่อคอร์ส</label>
            <input id="title" name="title" class="tp-input" maxlength="150" required value="{{ old('title', $course->title) }}">
            @error('title') <div class="ac-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="ac-label" for="description">คำอธิบาย</label>
            <textarea id="description" name="description" class="tp-input" rows="3" maxlength="3000">{{ old('description', $course->description) }}</textarea>
            @error('description') <div class="ac-error">{{ $message }}</div> @enderror
        </div>

        <div class="ac-form-grid">
            <div>
                <label class="ac-label" for="rules_version">รุ่นกติกา</label>
                <input id="rules_version" name="rules_version" class="tp-input" maxlength="20" required x-model="version">
                <div class="ac-help">เปลี่ยนเมื่อกติกาตลาดเปลี่ยน — ผู้ผ่านรุ่นเก่าจะขึ้น "ควรทบทวน" (คะแนนไม่หาย)</div>
                @error('rules_version') <div class="ac-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="ac-label" for="question_count">จำนวนข้อที่สุ่มต่อการสอบ</label>
                <input id="question_count" name="question_count" type="number" min="1" max="100" class="tp-input" required value="{{ old('question_count', $course->question_count) }}">
                @error('question_count') <div class="ac-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="ac-label" for="pass_score">เกณฑ์ผ่าน (ตอบถูกอย่างน้อย)</label>
                <input id="pass_score" name="pass_score" type="number" min="1" max="100" class="tp-input" required value="{{ old('pass_score', $course->pass_score) }}">
                @error('pass_score') <div class="ac-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="ac-label" for="points_reward">คะแนนความประพฤติเมื่อผ่านครั้งแรก</label>
                <input id="points_reward" name="points_reward" type="number" min="0" max="1000" class="tp-input" required value="{{ old('points_reward', $course->points_reward) }}">
                @error('points_reward') <div class="ac-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="ac-label" for="retry_cooldown_seconds">รอสอบใหม่หลังไม่ผ่าน (วินาที)</label>
                <input id="retry_cooldown_seconds" name="retry_cooldown_seconds" type="number" min="0" max="86400" class="tp-input" required value="{{ old('retry_cooldown_seconds', $course->retry_cooldown_seconds) }}">
                @error('retry_cooldown_seconds') <div class="ac-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <span class="ac-label">สถานะคอร์ส</span>
                <label class="ac-row" style="gap:8px; min-height:44px; cursor:pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($courseActive) style="width:18px; height:18px;">
                    <span style="color:var(--ink); font-weight:600;">เปิดสอน (ปิดคอร์สอื่นของบทบาทนี้อัตโนมัติ)</span>
                </label>
            </div>
        </div>

        <div x-show="version.trim() !== original" x-cloak class="tp-card ac-flash-bad" style="box-shadow:none; padding:12px 14px;">
            ⚠️ กำลังเปลี่ยนรุ่นกติกาจาก <strong x-text="original"></strong> เป็น <strong x-text="version"></strong> —
            ผู้ผ่านการอบรม{{ $course->roleLabel() }} {{ number_format($certifiedCount) }} คนจะขึ้นว่า "ควรทบทวน" จนกว่าจะสอบผ่านรุ่นใหม่ (ป้ายและคะแนนเดิมยังอยู่)
        </div>

        <div class="ac-row" style="justify-content:flex-end;">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-floppy-disk"></i> บันทึกคอร์ส</button>
        </div>
    </form>

    {{-- ── บทเรียน ── --}}
    <div class="tp-card" style="display:flex; flex-direction:column; gap:12px;">
        <div class="ac-row ac-between">
            <h2 class="ac-h2"><i class="fas fa-circle-play" style="color:var(--deep1);"></i> บทเรียน ({{ $lessonCount }})</h2>
            <span class="tp-muted" style="font-size:.78rem;">ผู้เรียนต้องดูทุกบทที่ "เปิด" อย่างน้อย 90% จึงจะสอบได้</span>
        </div>

        @forelse($course->lessons as $i => $lesson)
            @php
                $bag = $errors->getBag('lesson_'.$lesson->id);
                $editing = $bag->any();
                $deleteConfirm = 'ลบบทเรียน "'.$lesson->title.'" ? ความคืบหน้าการดูของผู้เรียนในบทนี้จะหายด้วย ย้อนกลับไม่ได้';
            @endphp
            <div x-data="{ edit: {{ $editing ? 'true' : 'false' }} }" style="display:flex; flex-direction:column; gap:10px;">
                <div class="ac-lesson" @if(! $lesson->is_active) style="opacity:.6;" @endif>
                    <div class="ac-order">
                        <form method="POST" action="{{ route('admin.classroom.lessons.move', $lesson) }}">
                            @csrf
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" class="tp-icon-btn" style="width:32px; height:28px;" aria-label="เลื่อนขึ้น" @disabled($i === 0)><i class="fas fa-chevron-up"></i></button>
                        </form>
                        <form method="POST" action="{{ route('admin.classroom.lessons.move', $lesson) }}">
                            @csrf
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" class="tp-icon-btn" style="width:32px; height:28px;" aria-label="เลื่อนลง" @disabled($i === $lessonCount - 1)><i class="fas fa-chevron-down"></i></button>
                        </form>
                    </div>
                    <a href="{{ \App\Services\Classroom\YouTubeId::watchUrl($lesson->youtube_id) }}" target="_blank" rel="noopener">
                        <img class="ac-thumb" src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy">
                    </a>
                    <div style="min-width:0;">
                        <div class="tp-muted" style="font-size:.75rem;">บทที่ {{ $i + 1 }} · {{ $lesson->durationLabel() }} · YouTube <code>{{ $lesson->youtube_id }}</code></div>
                        <div style="font-weight:700; color:var(--ink); line-height:1.4;">{{ $lesson->title }}</div>
                        <div class="ac-row" style="gap:6px; margin-top:4px;">
                            <span class="tp-pill {{ $lesson->is_active ? 'ac-pill-ok' : 'ac-pill-off' }}">{{ $lesson->is_active ? 'เปิด' : 'ปิด' }}</span>
                            <span class="tp-muted" style="font-size:.75rem;">ดูจบแล้ว {{ number_format((int) ($completedByLesson[$lesson->id] ?? 0)) }} คน</span>
                        </div>
                    </div>
                    <div class="ac-row ac-actions" style="gap:6px;">
                        <button type="button" class="tp-btn tp-btn-sm" x-on:click="edit = !edit"><i class="fas fa-pen"></i> แก้ไข</button>
                        <form method="POST" action="{{ route('admin.classroom.lessons.toggle', $lesson) }}">
                            @csrf
                            <button type="submit" class="tp-btn tp-btn-sm">{{ $lesson->is_active ? 'ปิด' : 'เปิด' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.classroom.lessons.destroy', $lesson) }}"
                              x-on:submit="if (!confirm({{ \Illuminate\Support\Js::from($deleteConfirm) }})) $event.preventDefault()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tp-btn tp-btn-sm ac-danger"><i class="fas fa-trash"></i> ลบ</button>
                        </form>
                    </div>
                </div>

                <form x-show="edit" x-cloak method="POST" action="{{ route('admin.classroom.lessons.update', $lesson) }}" class="tp-inset-sm" style="padding:14px; border-radius:16px; display:flex; flex-direction:column; gap:10px;">
                    @csrf
                    @method('PUT')
                    <div class="ac-form-grid">
                        <div>
                            <label class="ac-label">ชื่อบทเรียน</label>
                            <input name="title" class="tp-input" maxlength="200" required value="{{ $editing ? old('title') : $lesson->title }}">
                            @if($bag->has('title')) <div class="ac-error">{{ $bag->first('title') }}</div> @endif
                        </div>
                        <div>
                            <label class="ac-label">ลิงก์ YouTube</label>
                            <input name="youtube_url" class="tp-input" required value="{{ $editing ? old('youtube_url') : \App\Services\Classroom\YouTubeId::watchUrl($lesson->youtube_id) }}">
                            @if($bag->has('youtube_url')) <div class="ac-error">{{ $bag->first('youtube_url') }}</div> @endif
                        </div>
                        <div>
                            <label class="ac-label">ความยาว (นาที:วินาที)</label>
                            <input name="duration" class="tp-input" required placeholder="5:30" value="{{ $editing ? old('duration') : $lesson->durationLabel() }}">
                            @if($bag->has('duration')) <div class="ac-error">{{ $bag->first('duration') }}</div> @endif
                        </div>
                    </div>
                    <input type="hidden" name="is_active" value="{{ $lesson->is_active ? 1 : 0 }}">
                    <div class="ac-row" style="justify-content:flex-end;">
                        <button type="button" class="tp-btn tp-btn-sm" x-on:click="edit = false">ยกเลิก</button>
                        <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm"><i class="fas fa-floppy-disk"></i> บันทึกบทเรียน</button>
                    </div>
                </form>
            </div>
        @empty
            <p class="tp-muted" style="margin:0;">ยังไม่มีบทเรียน — เพิ่มวิดีโอแรกด้านล่าง</p>
        @endforelse
    </div>

    {{-- ── เพิ่มบทเรียน ── --}}
    <form method="POST" action="{{ route('admin.classroom.lessons.store', $course) }}" class="tp-card" style="display:flex; flex-direction:column; gap:12px;">
        @csrf
        <h2 class="ac-h2"><i class="fas fa-plus" style="color:var(--deep1);"></i> เพิ่มบทเรียน</h2>
        <div class="ac-form-grid">
            <div>
                <label class="ac-label" for="new_title">ชื่อบทเรียน</label>
                <input id="new_title" name="title" class="tp-input" maxlength="200" required value="{{ $newLessonErrors->any() ? old('title') : '' }}" placeholder="เช่น บทที่ 1 รับออเดอร์ให้ไว">
                @if($newLessonErrors->has('title')) <div class="ac-error">{{ $newLessonErrors->first('title') }}</div> @endif
            </div>
            <div>
                <label class="ac-label" for="new_youtube">ลิงก์ YouTube (วางลิงก์แบบไหนก็ได้)</label>
                <input id="new_youtube" name="youtube_url" class="tp-input" required value="{{ $newLessonErrors->any() ? old('youtube_url') : '' }}" placeholder="https://youtu.be/xxxxxxxxxxx">
                <div class="ac-help">ใช้วิดีโอแบบ "ไม่เป็นสาธารณะ (unlisted)" ได้ · ระบบแยกรหัสวิดีโอให้เอง</div>
                @if($newLessonErrors->has('youtube_url')) <div class="ac-error">{{ $newLessonErrors->first('youtube_url') }}</div> @endif
            </div>
            <div>
                <label class="ac-label" for="new_duration">ความยาววิดีโอ (นาที:วินาที)</label>
                <input id="new_duration" name="duration" class="tp-input" required value="{{ $newLessonErrors->any() ? old('duration') : '' }}" placeholder="5:30">
                <div class="ac-help">ใส่ให้ตรงกับความยาวจริง — ผู้เรียนต้องดูอย่างน้อย 90% ของค่านี้</div>
                @if($newLessonErrors->has('duration')) <div class="ac-error">{{ $newLessonErrors->first('duration') }}</div> @endif
            </div>
        </div>
        <input type="hidden" name="is_active" value="1">
        <div class="ac-row" style="justify-content:flex-end;">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> เพิ่มบทเรียน</button>
        </div>
    </form>
</div>
@endsection
