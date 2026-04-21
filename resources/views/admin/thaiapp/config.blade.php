@extends('layouts.arrow-x-v3')
@section('title', 'App Config · key-value')
@section('content')
<div class="container-fluid px-4 py-6">
    <a href="{{ route('admin.thaiapp.hub') }}" class="text-sm text-gray-500 hover:text-blue-500">← Thaiapp-MANAGER</a>
    <div class="flex items-center justify-between mt-1 mb-6">
        <h1 class="text-3xl font-bold">⚙️ App Config <span class="text-base text-gray-500 font-normal">env = {{ $env }}</span></h1>
        <button onclick="document.getElementById('add').classList.toggle('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg">+ เพิ่ม key</button>
    </div>
    @include('admin.thaiapp._flash')

    <div id="add" class="hidden mb-6 p-6 bg-white dark:bg-gray-800 border rounded-xl">
        <form method="POST" action="{{ route('admin.thaiapp.config.store') }}" class="grid grid-cols-5 gap-3">
            @csrf
            <input type="text" name="key" placeholder="key (เช่น ai_model_id_gemma4_e2b)" required class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg font-mono text-sm">
            <select name="value_type" required class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <option value="string">string</option>
                <option value="int">int</option>
                <option value="bool">bool</option>
                <option value="json">json</option>
            </select>
            <label class="flex items-center gap-2"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" checked> public</label>
            <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg">เพิ่ม</button>
            <input type="text" name="value" placeholder="value" class="col-span-4 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="description" placeholder="คำอธิบาย (optional)" class="col-span-5 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 border rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                <tr>
                    <th class="px-4 py-3">Key</th>
                    <th class="px-4 py-3">Value</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Public</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($configs as $c)
                <tr>
                    <td class="px-4 py-2 font-mono text-xs align-top pt-3">
                        <div class="font-bold">{{ $c->key }}</div>
                        @if($c->description)<div class="text-[10px] text-gray-500 mt-1">{{ $c->description }}</div>@endif
                    </td>
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('admin.thaiapp.config.update', $c->id) }}" class="flex gap-2">
                            @csrf @method('PUT')
                            <input type="text" name="value" value="{{ $c->value }}"
                                class="flex-1 px-2 py-1 bg-gray-50 dark:bg-gray-900 border rounded font-mono text-xs">
                            <button class="px-3 py-1 bg-blue-600 text-white rounded text-xs">💾</button>
                        </form>
                    </td>
                    <td class="px-4 py-2 text-xs">{{ $c->value_type }}</td>
                    <td class="px-4 py-2 text-xs">{{ $c->is_public ? '✓' : '—' }}</td>
                    <td class="px-4 py-2 text-right">
                        <form method="POST" action="{{ route('admin.thaiapp.config.destroy', $c->id) }}" onsubmit="return confirm('ลบ {{ $c->key }}?')">
                            @csrf @method('DELETE')
                            <button class="px-2 py-1 bg-rose-600 text-white rounded text-xs">🗑️</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
