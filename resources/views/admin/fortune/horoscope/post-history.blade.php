{{-- resources/views/admin/fortune/horoscope/post-history.blade.php --}}
{{-- ประวัติการโพสดวงรายวัน --}}

@extends('layouts.admin')

@section('title', 'ประวัติโพส - ' . $campaign->name)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="mb-6 p-4 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700 rounded-xl text-yellow-800 dark:text-yellow-200">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.fortune.horoscope.index') }}"
                   class="px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition text-sm">
                    ← กลับ
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    📤 ประวัติการโพส
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                แคมเปญ: <span class="font-semibold text-purple-600 dark:text-purple-400">{{ $campaign->name }}</span>
                |
                แพลตฟอร์ม:
                @if($campaign->post_to_facebook) <span class="text-blue-600">📘 Facebook</span> @endif
                @if($campaign->post_to_line) <span class="text-green-600">💚 LINE</span> @endif
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.fortune.horoscope.content-history', $campaign) }}"
               class="px-4 py-2 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-800/50 text-purple-700 dark:text-purple-300 rounded-lg transition text-sm">
                🤖 ดูเนื้อหา
            </a>
            <form action="{{ route('admin.fortune.horoscope.publish-now', $campaign) }}" method="POST" class="inline"
                  onsubmit="return confirm('ต้องการโพสเนื้อหาทันทีหรือไม่?')">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition text-sm">
                    🚀 โพสทันที
                </button>
            </form>
        </div>
    </div>

    {{-- สรุปสถิติ --}}
    @php
        $totalPosts = $posts->total();
        $postedCount = $campaign->posts()->posted()->count();
        $failedCount = $campaign->posts()->where('status', 'failed')->count();
        $pendingCount = $campaign->posts()->where('status', 'pending')->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 text-white">
            <div class="text-blue-100 text-sm">ทั้งหมด</div>
            <div class="text-2xl font-bold">{{ number_format($totalPosts) }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-4 text-white">
            <div class="text-green-100 text-sm">สำเร็จ</div>
            <div class="text-2xl font-bold">{{ number_format($postedCount) }}</div>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-4 text-white">
            <div class="text-red-100 text-sm">ล้มเหลว</div>
            <div class="text-2xl font-bold">{{ number_format($failedCount) }}</div>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-4 text-white">
            <div class="text-yellow-100 text-sm">รอดำเนินการ</div>
            <div class="text-2xl font-bold">{{ number_format($pendingCount) }}</div>
        </div>
    </div>

    {{-- Post Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            วันที่
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            แพลตฟอร์ม
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            สถานะ
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            เนื้อหา
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            โพสเมื่อ
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            ลิงก์
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($posts as $post)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            {{-- วันที่เป้าหมาย --}}
                            <td class="px-4 py-4">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $post->target_date ? $post->target_date->format('d/m/Y') : '-' }}
                                </div>
                            </td>

                            {{-- แพลตฟอร์ม --}}
                            <td class="px-4 py-4 text-center">
                                @switch($post->platform)
                                    @case('facebook')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                            📘 Facebook
                                        </span>
                                        @break
                                    @case('line')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                            💚 LINE
                                        </span>
                                        @break
                                    @default
                                        <span class="text-gray-400">{{ $post->platform }}</span>
                                @endswitch
                            </td>

                            {{-- สถานะ --}}
                            <td class="px-4 py-4 text-center">
                                @switch($post->status)
                                    @case('posted')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                            ✅ โพสแล้ว
                                        </span>
                                        @break
                                    @case('posting')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 animate-pulse">
                                            ⏳ กำลังโพส
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                                            ⏸ รอ
                                        </span>
                                        @break
                                    @case('failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300"
                                              title="{{ $post->error_message }}">
                                            ❌ ล้มเหลว
                                        </span>
                                        @break
                                @endswitch

                                @if($post->error_message)
                                    <div class="mt-1 text-xs text-red-500 dark:text-red-400 max-w-[200px] truncate" title="{{ $post->error_message }}">
                                        {{ Str::limit($post->error_message, 40) }}
                                    </div>
                                @endif
                            </td>

                            {{-- เนื้อหา --}}
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate" title="{{ $post->post_content }}">
                                    {{ Str::limit($post->post_content, 80) }}
                                </div>
                                @if(!empty($post->image_urls))
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        🖼 {{ count($post->image_urls) }} รูป
                                    </span>
                                @endif
                            </td>

                            {{-- เวลาโพส --}}
                            <td class="px-4 py-4 text-center text-sm">
                                @if($post->published_at)
                                    <span class="text-gray-900 dark:text-white">
                                        {{ $post->published_at->format('d/m/Y') }}
                                    </span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $post->published_at->format('H:i:s') }}
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- ลิงก์โพส --}}
                            <td class="px-4 py-4 text-center">
                                @if($post->platform_post_url)
                                    <a href="{{ $post->platform_post_url }}" target="_blank" rel="noopener"
                                       class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                        🔗 ดูโพส
                                    </a>
                                @elseif($post->platform_post_id)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ Str::limit($post->platform_post_id, 15) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400 dark:text-gray-500">
                                    <div class="text-4xl mb-3">📤</div>
                                    <p class="text-lg font-medium">ยังไม่มีประวัติการโพส</p>
                                    <p class="text-sm mt-1">สร้างเนื้อหาแล้วกด "โพสทันที" หรือรอ scheduler ทำงาน</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($posts->hasPages())
        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @endif
</div>
@endsection
