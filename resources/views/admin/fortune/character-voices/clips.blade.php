@extends('layouts.admin-v4')

@section('title', '🎧 คลิปเสียงทั้งหมด')

@php
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm';
    $purposeNames = [
        'chat' => 'ข้อความห้องแชท', 'sample' => 'ฟังเทียบเสียง', 'library' => 'คลังบทพูด',
        'greeting' => 'ทักลูกค้าคุ้นเคย', 'narration' => 'เล่าคำทำนาย',
    ];
    $statusBadge = [
        'completed' => ['สำเร็จ', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'],
        'pending' => ['กำลังสร้าง', 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'],
        'failed' => ['ล้มเหลว', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'],
    ];
@endphp

@section('content')
<div x-data="{ selected: [] }">
    <div class="mb-5">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide">หลังบ้าน · ระบบดูดวง · เสียงพากย์ตัวละคร</div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-1">🎧 คลิปเสียงทั้งหมด</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            ทุกไฟล์ที่สร้างจาก ElevenLabs — ข้อความในห้องแชท เสียงตัวอย่าง และคลังบทพูด · ไฟล์ที่ถูกเล่นซ้ำ (ครั้งที่เล่นจากแคช) = ประหยัดเครดิต
        </p>
    </div>

    @include('admin.fortune.character-voices._tabs')
    @include('admin.fortune.character-voices._flash')

    {{-- ═══ ตัวกรอง ═══ --}}
    <form method="GET" action="{{ route('admin.fortune.character-voices.clips.index') }}" class="tp-card p-4 mb-4 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">ตัวละคร</label>
            <select name="persona" class="{{ $input }}">
                <option value="">ทั้งหมด</option>
                @foreach($catalog as $key => $c)
                    <option value="{{ $key }}" @selected(($filters['persona'] ?? '') === $key)>{{ $c['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">ใช้ทำอะไร</label>
            <select name="purpose" class="{{ $input }}">
                <option value="">ทั้งหมด</option>
                @foreach($purposes as $p)
                    <option value="{{ $p }}" @selected(($filters['purpose'] ?? '') === $p)>{{ $purposeNames[$p] ?? $p }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">สถานะ</label>
            <select name="status" class="{{ $input }}">
                <option value="">ทั้งหมด</option>
                @foreach($statusBadge as $key => [$name])
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">โมเดล</label>
            <select name="model" class="{{ $input }}">
                <option value="">ทั้งหมด</option>
                @foreach($modelsUsed as $m)
                    <option value="{{ $m }}" @selected(($filters['model'] ?? '') === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-span-2 md:col-span-1">
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">ค้นข้อความ</label>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" class="{{ $input }}">
        </div>
        <div class="col-span-2 md:col-span-1 flex gap-2">
            <button type="submit" class="tp-btn flex-1 justify-center">🔎 กรอง</button>
            <a href="{{ route('admin.fortune.character-voices.clips.index') }}" class="tp-btn justify-center">ล้าง</a>
        </div>
    </form>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            {{ number_format($stats->n ?? 0) }} ไฟล์ · สร้างไป {{ number_format($stats->chars ?? 0) }} ตัวอักษร · เล่นซ้ำจากแคช {{ number_format($stats->hits ?? 0) }} ครั้ง
        </p>
        <form method="POST" action="{{ route('admin.fortune.character-voices.clips.destroy') }}"
              @submit="if (!selected.length || !confirm('ลบคลิปเสียง ' + selected.length + ' ไฟล์? ลบแล้วกู้คืนไม่ได้ (ข้อความเดิมจะสร้างใหม่และเสียเครดิตเมื่อมีคนขอ)')) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
            <button type="submit" class="tp-btn text-red-700 dark:text-red-300" :disabled="!selected.length">🗑️ ลบที่เลือก (<span x-text="selected.length"></span>)</button>
        </form>
    </div>

    {{-- ═══ ตาราง ═══ --}}
    <div class="tp-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/60 text-xs text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2 text-left">
                            <input type="checkbox" class="rounded text-violet-600" aria-label="เลือกทั้งหน้า"
                                   @change="selected = $event.target.checked ? {{ \Illuminate\Support\Js::from($clips->pluck('id')) }} : []">
                        </th>
                        <th class="px-3 py-2 text-left font-medium">ตัวละคร / ใช้ทำอะไร</th>
                        <th class="px-3 py-2 text-left font-medium min-w-[18rem]">ข้อความ</th>
                        <th class="px-3 py-2 text-left font-medium">เสียง / โมเดล</th>
                        <th class="px-3 py-2 text-right font-medium">ตัวอักษร</th>
                        <th class="px-3 py-2 text-right font-medium">เล่นซ้ำ</th>
                        <th class="px-3 py-2 text-left font-medium min-w-[14rem]">ไฟล์</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($clips as $clip)
                        @php [$statusName, $statusClass] = $statusBadge[$clip->status] ?? [$clip->status, 'bg-gray-100 text-gray-700']; @endphp
                        <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-3 py-3"><input type="checkbox" class="rounded text-violet-600" value="{{ $clip->id }}" x-model.number="selected" aria-label="เลือกคลิป {{ $clip->id }}"></td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="text-gray-900 dark:text-gray-100">{{ $catalog[$clip->persona]['label'] ?? $clip->persona }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $purposeNames[$clip->purpose] ?? $clip->purpose }}</div>
                                @foreach($linesByClip[$clip->id] ?? [] as $line)
                                    <code class="text-[11px] text-violet-700 dark:text-violet-300">{{ $line->line_key }}</code>
                                @endforeach
                            </td>
                            <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
                                <div class="line-clamp-3">{{ $clip->text }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">#{{ $clip->id }} · {{ $clip->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400">
                                <div>{{ $voiceNames[$clip->voice_id] ?? $clip->voice_id }}</div>
                                <div><code>{{ $clip->model }}</code></div>
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-gray-700 dark:text-gray-300">{{ number_format($clip->chars) }}</td>
                            <td class="px-3 py-3 text-right font-mono text-gray-700 dark:text-gray-300">{{ number_format($clip->hits) }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-block mb-1 px-1.5 py-0.5 rounded text-[11px] {{ $statusClass }}">{{ $statusName }}</span>
                                @if($clip->isCompleted())
                                    <audio controls preload="none" class="w-full h-9" src="{{ $clip->audio_url }}"></audio>
                                @elseif($clip->status === 'failed')
                                    <div class="text-xs text-red-700 dark:text-red-300">{{ \Illuminate\Support\Str::limit($clip->error, 120) }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-12 text-center text-gray-500 dark:text-gray-400">ไม่มีคลิปเสียงตามตัวกรองนี้</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $clips->links() }}</div>
</div>
@endsection
