@extends('layouts.arrow-x-v3')
@section('title', 'AI Pool · keys')
@section('content')
<div class="container-fluid px-4 py-6">
    <a href="{{ route('admin.thaiapp.hub') }}" class="text-sm text-gray-500 hover:text-blue-500">← Thaiapp-MANAGER</a>
    <div class="flex items-center justify-between mt-1 mb-6">
        <h1 class="text-3xl font-bold">🔑 AI Pool (API keys)</h1>
        <button onclick="document.getElementById('addKey').classList.toggle('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">+ เพิ่ม key</button>
    </div>
    @include('admin.thaiapp._flash')

    <div id="addKey" class="hidden mb-6 p-6 bg-white dark:bg-gray-800 border rounded-xl">
        <form method="POST" action="{{ route('admin.thaiapp.ai-pool.keys.store') }}" class="grid grid-cols-4 gap-3">
            @csrf
            <select name="provider" required class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                @foreach($providers as $p) <option value="{{ $p }}">{{ $p }}</option> @endforeach
            </select>
            <input type="text" name="name" placeholder="ชื่อ key (เช่น 'Prod Gemini #1')" required class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="api_key" placeholder="API key (sk-... หรือ AIza...)" required class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg font-mono text-sm">
            <div class="flex gap-2">
                <input type="number" name="priority" placeholder="priority (0-100)" value="50" class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg">เพิ่ม</button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 border rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                <tr>
                    <th class="px-4 py-3">Provider</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Key (mask)</th>
                    <th class="px-4 py-3 text-right">Priority</th>
                    <th class="px-4 py-3 text-right">Used today</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($keys as $k)
                <tr>
                    <td class="px-4 py-3"><span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">{{ $k->provider }}</span></td>
                    <td class="px-4 py-3">{{ $k->name }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ substr($k->api_key, 0, 8) }}…{{ substr($k->api_key, -4) }}</td>
                    <td class="px-4 py-3 text-right">{{ $k->priority }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($k->tokens_used_today ?? 0) }}</td>
                    <td class="px-4 py-3">
                        @if($k->is_active)
                            <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded text-xs">active</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">disabled</span>
                        @endif
                        @if(($k->consecutive_errors ?? 0) > 0)
                            <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs ml-1">{{ $k->consecutive_errors }} errors</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-1">
                        <form method="POST" action="{{ route('admin.thaiapp.ai-pool.keys.update', $k->id) }}" class="inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="is_active" value="{{ $k->is_active ? 0 : 1 }}">
                            <button class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">{{ $k->is_active ? 'Disable' : 'Enable' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.thaiapp.ai-pool.keys.destroy', $k->id) }}" class="inline" onsubmit="return confirm('ลบ key {{ $k->name }}?')">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1 text-xs bg-rose-600 text-white rounded hover:bg-rose-700">ลบ</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">ยังไม่มี key ในระบบ · กด "+ เพิ่ม key"</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
