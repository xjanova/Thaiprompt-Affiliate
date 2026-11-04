@extends('layouts.admin-v4')

@section('title', '🎭 ตัวละครเสียงพากย์')

@php
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $voiceName = fn (array $v) => $v['name'].(isset($v['gender']) && $v['gender'] ? ' ('.($v['gender'] === 'female' ? 'หญิง' : ($v['gender'] === 'male' ? 'ชาย' : $v['gender'])).')' : '');
@endphp

@section('content')
<div>
    <div class="mb-5">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide">หลังบ้าน · ระบบดูดวง · เสียงพากย์ตัวละคร</div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-1">🎭 ตัวละครเสียงพากย์</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            ตัวละคร = เสียงต้นแบบของห้องพยากรณ์ · เพิ่มตัวใหม่ไว้ใช้กับห้องใหม่ได้ (ห้องอ้างอิงด้วย <b>รหัส</b>) · เลือกเสียงและโมเดลแยกต่อตัวได้
        </p>
    </div>

    @include('admin.fortune.character-voices._tabs')
    @include('admin.fortune.character-voices._flash')

    {{-- ═══ ตัวละครที่มี ═══ --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        @foreach($characters as $c)
            <form method="POST" action="{{ route('admin.fortune.character-voices.characters.update', $c) }}"
                  class="tp-card p-5 space-y-3 {{ $c->is_active ? '' : 'opacity-70' }}">
                @csrf
                @method('PUT')
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $c->label }}</h2>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1 text-xs">
                            <code class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $c->key }}</code>
                            @if($c->is_builtin)
                                <span class="px-1.5 py-0.5 rounded bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">ห้องเดิมใช้อยู่</span>
                            @endif
                            @unless($c->is_active)
                                <span class="px-1.5 py-0.5 rounded bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">ปิดอยู่</span>
                            @endunless
                        </div>
                    </div>
                    <a href="{{ route('admin.fortune.character-voices.library', ['persona' => $c->key]) }}"
                       class="text-sm text-violet-700 dark:text-violet-300 hover:underline whitespace-nowrap">🗂️ คลังบท ({{ $lineCounts[$c->key] ?? 0 }})</a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}">ชื่อที่แสดง</label>
                        <input type="text" name="label" value="{{ $c->label }}" maxlength="100" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">ลำดับ</label>
                        <input type="number" name="sort_order" value="{{ $c->sort_order }}" min="0" max="9999" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">เสียง</label>
                        <select name="voice_id" class="{{ $input }}">
                            <option value="">— เสียงสำรองของระบบ —</option>
                            @foreach($voices as $v)
                                <option value="{{ $v['id'] }}" @selected($v['id'] === ($c->voice_id ?: ($effectiveVoices[$c->key] ?? null)))>{{ $voiceName($v) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">โมเดล</label>
                        <select name="model" class="{{ $input }}">
                            <option value="">ใช้โมเดลกลาง ({{ $globalModel }})</option>
                            @foreach($models as $m)
                                <option value="{{ $m['id'] }}" @selected($m['id'] === $c->model)>{{ $m['name'] }} — {{ rtrim(rtrim(number_format($m['cost'], 2), '0'), '.') }} เครดิต/ตัวอักษร</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="{{ $label }}">บุคลิกเสียง (ไว้เทียบตอนเลือกเสียง)</label>
                    <input type="text" name="brief" value="{{ $c->brief }}" maxlength="500" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">ประโยคตัวอย่าง (ใช้ฟังเทียบเสียง · ใส่แท็กอารมณ์ เช่น [softly] ได้)</label>
                    <textarea name="sample_text" rows="2" maxlength="{{ \App\Services\Fortune\FortuneCharacterVoiceService::MAX_SAMPLE_CHARS }}" required class="{{ $input }}">{{ $c->sample_text }}</textarea>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    @if($c->is_builtin)
                        <p class="text-xs text-gray-500 dark:text-gray-400">ตัวละครของห้องเดิม — ปิดหรือลบไม่ได้</p>
                    @else
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($c->is_active) class="rounded text-violet-600">
                            เปิดใช้ (ปิด = ห้องที่อ้างอิงตัวนี้จะสร้างเสียงไม่ได้)
                        </label>
                    @endif
                    <div class="flex gap-2 w-full sm:w-auto">
                        @unless($c->is_builtin)
                            <button type="submit" form="del-character-{{ $c->id }}"
                                    class="tp-btn flex-1 sm:flex-none justify-center text-red-700 dark:text-red-300">🗑️ ลบ</button>
                        @endunless
                        <button type="submit" class="tp-btn flex-1 sm:flex-none justify-center">💾 บันทึก</button>
                    </div>
                </div>
            </form>
            @unless($c->is_builtin)
                <form id="del-character-{{ $c->id }}" method="POST" action="{{ route('admin.fortune.character-voices.characters.destroy', $c) }}" class="hidden"
                      onsubmit="return confirm({{ \Illuminate\Support\Js::from('ลบตัวละคร '.$c->label.' และบทในคลังทั้งหมด ('.($lineCounts[$c->key] ?? 0).' บท)?'."\n".'ไฟล์เสียงที่สร้างไว้ยังอยู่ในตารางคลิป') }})">
                    @csrf
                    @method('DELETE')
                </form>
            @endunless
        @endforeach
    </div>

    {{-- ═══ เพิ่มตัวละครใหม่ ═══ --}}
    <form method="POST" action="{{ route('admin.fortune.character-voices.characters.store') }}" class="tp-card p-5 md:p-6 mt-6 space-y-3">
        @csrf
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">➕ เพิ่มตัวละครใหม่</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 -mt-1">สำหรับห้องพยากรณ์ใหม่ — รหัสใช้อ้างอิงจากห้องและตั้งแล้วเปลี่ยนไม่ได้</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="{{ $label }}">รหัส (a-z ตัวเลข _)</label>
                <input type="text" name="key" value="{{ old('key') }}" required pattern="[a-z][a-z0-9_]{1,19}" maxlength="20" placeholder="เช่น tarot_room"
                       class="{{ $input }} font-mono">
            </div>
            <div>
                <label class="{{ $label }}">ชื่อที่แสดง</label>
                <input type="text" name="label" value="{{ old('label') }}" required maxlength="100" placeholder="เช่น ยายทองไพ่ยิปซี" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">คัดลอกคลังบทจาก</label>
                <select name="copy_lines_from" class="{{ $input }}">
                    <option value="">ไม่คัดลอก (เริ่มคลังว่าง)</option>
                    @foreach($characters as $c)
                        <option value="{{ $c->key }}" @selected(old('copy_lines_from') === $c->key)>{{ $c->label }} ({{ $lineCounts[$c->key] ?? 0 }} บท)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">เสียง</label>
                <select name="voice_id" class="{{ $input }}">
                    <option value="">— เลือกทีหลังได้ —</option>
                    @foreach($voices as $v)
                        <option value="{{ $v['id'] }}" @selected(old('voice_id') === $v['id'])>{{ $voiceName($v) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">โมเดล</label>
                <select name="model" class="{{ $input }}">
                    <option value="">ใช้โมเดลกลาง ({{ $globalModel }})</option>
                    @foreach($models as $m)
                        <option value="{{ $m['id'] }}" @selected(old('model') === $m['id'])>{{ $m['name'] }} — {{ rtrim(rtrim(number_format($m['cost'], 2), '0'), '.') }} เครดิต/ตัวอักษร</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">บุคลิกเสียง</label>
                <input type="text" name="brief" value="{{ old('brief') }}" maxlength="500" placeholder="เช่น หญิงวัย 60 เสียงแหบ ขลัง ใจดี" class="{{ $input }}">
            </div>
        </div>
        <div>
            <label class="{{ $label }}">ประโยคตัวอย่าง</label>
            <textarea name="sample_text" rows="2" required maxlength="{{ \App\Services\Fortune\FortuneCharacterVoiceService::MAX_SAMPLE_CHARS }}"
                      placeholder="[softly] สวัสดีจ้ะ … (ใช้ฟังเทียบเสียงในหน้าตั้งค่า)" class="{{ $input }}">{{ old('sample_text') }}</textarea>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="tp-btn w-full md:w-auto justify-center">➕ เพิ่มตัวละคร</button>
        </div>
    </form>
</div>
@endsection
