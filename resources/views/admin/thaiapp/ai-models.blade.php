@extends('layouts.arrow-x-v3')
@section('title', 'AI Models · on-device Gemma')
@section('content')
<div class="container-fluid px-4 py-6 max-w-5xl mx-auto">
    <a href="{{ route('admin.thaiapp.hub') }}" class="text-sm text-gray-500 hover:text-blue-500">← Thaiapp-MANAGER</a>
    <h1 class="text-3xl font-bold mt-1 mb-1">🧠 AI Models (Gemma .task)</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        ไฟล์ .task ที่ user โหลดไปติดตั้งบนเครื่อง · host บน server เรา (Nginx serve ตรง) · sync จาก HuggingFace
    </p>
    @include('admin.thaiapp._flash')

    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 border rounded-xl text-sm flex gap-6">
        <div>HF Token: <strong class="{{ $hfTokenSet ? 'text-emerald-500' : 'text-rose-500' }}">{{ $hfTokenSet ? '✓ SET' : '✗ MISSING' }}</strong></div>
        @if($diskFree)
            <div>Disk free: <strong>{{ number_format($diskFree / (1024*1024*1024), 1) }} GB</strong></div>
        @endif
        <div>Storage: <code class="text-xs">public/ai-models/</code></div>
    </div>

    <div class="space-y-4">
        @foreach($items as $item)
        <div class="p-6 bg-white dark:bg-gray-800 border rounded-xl">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="text-xs uppercase text-gray-500 font-mono">{{ $item['tier'] }}</div>
                    <h3 class="text-xl font-bold">{{ $item['label'] }}</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 font-mono mt-1">{{ $item['filename'] }}</div>
                </div>
                @if($item['exists'])
                    <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full text-sm font-bold">✓ Synced</span>
                @else
                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm">Not synced</span>
                @endif
            </div>
            @if($item['exists'])
                <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                    <div><span class="text-gray-500">Size:</span> <strong>{{ number_format($item['size'] / (1024*1024), 0) }} MB</strong></div>
                    <div><span class="text-gray-500">Modified:</span> {{ $item['modified'] }}</div>
                </div>
            @endif
            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.thaiapp.ai-models.sync', $item['tier']) }}">
                    @csrf
                    <button type="submit" onclick="this.innerText='⏳ กำลัง sync...'; this.disabled=true;"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold">
                        🔄 {{ $item['exists'] ? 'Re-sync' : 'Sync now' }}
                    </button>
                </form>
                @if($item['exists'])
                    <form method="POST" action="{{ route('admin.thaiapp.ai-models.destroy', $item['tier']) }}"
                        onsubmit="return confirm('ลบ {{ $item['filename'] }} ({{ number_format($item['size'] / (1024*1024), 0) }} MB)?')">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg">🗑️ ลบ</button>
                    </form>
                @endif
                <a href="https://main.thaiprompt.online/api/v1/ai/models/{{ $item['tier'] }}/info" target="_blank"
                   class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">Info JSON ↗</a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-900 dark:text-amber-200">
        ⚠️ <strong>Sync ใช้เวลา 1-3 นาที</strong> (HF → server) · อย่าปิด browser จนเสร็จ · ถ้า timeout เปิด tab ใหม่แล้วลองอีกที
    </div>
</div>
@endsection
