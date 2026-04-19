@extends('layouts.admin-v3')

@section('title', 'จัดการนักพัฒนา')

@section('content')
<div class="container-fluid px-6 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                จัดการนักพัฒนา
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                อนุมัติและจัดการบัญชีนักพัฒนาซอฟต์แวร์
            </p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-4 text-white shadow-lg">
                <div class="text-2xl font-bold">{{ $stats['total'] ?? 0 }}</div>
                <div class="text-sm opacity-90">ทั้งหมด</div>
            </div>
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg p-4 text-white shadow-lg">
                <div class="text-2xl font-bold">{{ $stats['pending'] ?? 0 }}</div>
                <div class="text-sm opacity-90">รออนุมัติ</div>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-4 text-white shadow-lg">
                <div class="text-2xl font-bold">{{ $stats['approved'] ?? 0 }}</div>
                <div class="text-sm opacity-90">อนุมัติแล้ว</div>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg p-4 text-white shadow-lg">
                <div class="text-2xl font-bold">{{ $stats['rejected'] ?? 0 }}</div>
                <div class="text-sm opacity-90">ปฏิเสธ</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('admin.developers.index') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ค้นหาด้วยชื่อ, อีเมล, LINE ID..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>

            <select name="status"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="">ทุกสถานะ</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รออนุมัติ</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>ระงับ</option>
            </select>

            <button type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                🔍 ค้นหา
            </button>

            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.developers.index') }}"
                   class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    ล้างตัวกรอง
                </a>
            @endif
        </form>
    </div>

    {{-- Developers Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            นักพัฒนา
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ข้อมูลติดต่อ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Commission
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            วันที่สมัคร
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($developers as $developer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($developer->avatar_url)
                                        <img src="{{ $developer->avatar_url }}"
                                             alt="{{ $developer->display_name }}"
                                             class="w-12 h-12 rounded-full object-cover">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                                            {{ strtoupper(substr($developer->display_name, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $developer->display_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $developer->user->name }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-sm space-y-1">
                                    <div class="text-gray-900 dark:text-gray-100">
                                        📧 {{ $developer->email }}
                                    </div>
                                    @if($developer->line_id)
                                        <div class="text-gray-600 dark:text-gray-400">
                                            💬 {{ $developer->line_id }}
                                        </div>
                                    @endif
                                    @if($developer->phone)
                                        <div class="text-gray-600 dark:text-gray-400">
                                            📱 {{ $developer->phone }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
                                        'approved' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                                        'rejected' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
                                        'suspended' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300',
                                    ];
                                    $statusTexts = [
                                        'pending' => '⏳ รออนุมัติ',
                                        'approved' => '✅ อนุมัติแล้ว',
                                        'rejected' => '❌ ปฏิเสธ',
                                        'suspended' => '⏸️ ระงับ',
                                    ];
                                @endphp

                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClasses[$developer->status] ?? '' }}">
                                    {{ $statusTexts[$developer->status] ?? $developer->status }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ number_format($developer->commission_rate, 2) }}%
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $developer->created_at->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <a href="{{ route('admin.developers.show', $developer) }}"
                                   class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                    👁️ ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่พบข้อมูลนักพัฒนา
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($developers->hasPages())
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                {{ $developers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
