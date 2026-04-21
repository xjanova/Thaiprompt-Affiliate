@extends('layouts.arrow-x-v3')
@section('title', 'Releases · APK history')
@section('content')
<div class="container-fluid px-4 py-6">
    <a href="{{ route('admin.thaiapp.hub') }}" class="text-sm text-gray-500 hover:text-blue-500">← Thaiapp-MANAGER</a>
    <h1 class="text-3xl font-bold mt-1 mb-1">📦 Releases</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">APK auto-sync จาก GitHub Releases · (read-only) · recent 30</p>
    @include('admin.thaiapp._flash')

    <div class="bg-white dark:bg-gray-800 border rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                <tr>
                    <th class="px-4 py-3">Version</th>
                    <th class="px-4 py-3">Platform / Channel</th>
                    <th class="px-4 py-3">Min supported</th>
                    <th class="px-4 py-3 text-right">Size</th>
                    <th class="px-4 py-3">Link</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($releases as $r)
                <tr>
                    <td class="px-4 py-3 font-bold">{{ $r->version }} <span class="text-xs text-gray-500">({{ $r->build_number }})</span></td>
                    <td class="px-4 py-3"><span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">{{ $r->platform }}</span> · {{ $r->channel }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $r->min_supported_version }}</td>
                    <td class="px-4 py-3 text-right">{{ $r->apk_size_bytes ? number_format($r->apk_size_bytes / (1024*1024), 0) . ' MB' : '—' }}</td>
                    <td class="px-4 py-3">
                        @if($r->apk_url)
                            <a href="{{ $r->apk_url }}" target="_blank" class="text-blue-500 hover:underline text-xs">APK ↗</a>
                        @else — @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
