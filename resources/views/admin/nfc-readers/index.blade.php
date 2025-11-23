@extends('layouts.admin-v3')

@section('title', 'จัดการเครื่องอ่านบัตร NFC')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="{
    showFilters: false,
    selectedReaders: [],
    selectAll: false,
    toggleSelectAll() {
        if (this.selectAll) {
            this.selectedReaders = Array.from(document.querySelectorAll('input[name=\'reader_ids[]\']')).map(el => el.value);
        } else {
            this.selectedReaders = [];
        }
    }
}">
    {{-- Header Section พร้อมปุ่มเพิ่มเครื่องอ่านบัตรใหม่ --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-broadcast-tower text-blue-600 dark:text-blue-400"></i>
                จัดการเครื่องอ่านบัตร NFC
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                ระบบจัดการและควบคุมเครื่องอ่านบัตร NFC ทุกเครื่อง
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.nfc-readers.create') }}"
               class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-500 dark:to-blue-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-plus"></i>
                เพิ่มเครื่องอ่านบัตร
            </a>
            <button @click="showFilters = !showFilters"
                    class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-6 py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-filter"></i>
                <span x-show="!showFilters">แสดงตัวกรอง</span>
                <span x-show="showFilters">ซ่อนตัวกรอง</span>
            </button>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-400 px-6 py-4 rounded-lg mb-6 shadow-md"
             x-data="{ show: true }"
             x-show="show"
             x-transition
             x-init="setTimeout(() => show = false, 5000)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-700 dark:text-green-400 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg mb-6 shadow-md"
             x-data="{ show: true }"
             x-show="show"
             x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-700 dark:text-red-400 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Statistics Cards สถิติภาพรวม --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Readers --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">เครื่องอ่านทั้งหมด</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['total_readers']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-broadcast-tower text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Active Readers --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">เปิดใช้งาน</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['active_readers']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Online Readers --}}
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm font-medium">ออนไลน์</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['online_readers']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-wifi text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Offline Readers --}}
        <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">ออฟไลน์</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['offline_readers']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Section ส่วนตัวกรอง --}}
    <div x-show="showFilters"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.nfc-readers.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Search --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-search mr-1"></i>
                        ค้นหา
                    </label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="ชื่อ, Reader ID, Serial, สถานที่..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        สถานะ
                    </label>
                    <select name="status"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                        <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>ปรับปรุง</option>
                    </select>
                </div>

                {{-- Location Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        สถานที่
                    </label>
                    <input type="text"
                           name="location"
                           value="{{ request('location') }}"
                           placeholder="สถานที่ติดตั้ง..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2">
                    <i class="fas fa-filter"></i>
                    กรองข้อมูล
                </button>
                <a href="{{ route('admin.nfc-readers.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    {{-- Readers Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <input type="checkbox"
                                   x-model="selectAll"
                                   @change="toggleSelectAll"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ชื่อเครื่อง
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Reader ID
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Serial Number
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานที่
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Heartbeat
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($readers as $reader)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            {{-- Checkbox --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox"
                                       name="reader_ids[]"
                                       value="{{ $reader->id }}"
                                       x-model="selectedReaders"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>

                            {{-- Name --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-broadcast-tower text-blue-600 dark:text-blue-400"></i>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $reader->name }}
                                        </div>
                                        @if($reader->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ Str::limit($reader->description, 40) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Reader ID --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono font-medium text-gray-900 dark:text-white">
                                    {{ $reader->reader_id }}
                                </span>
                            </td>

                            {{-- Serial Number --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                    {{ $reader->serial_number }}
                                </span>
                            </td>

                            {{-- Location --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-map-marker-alt text-gray-400"></i>
                                    <span>{{ $reader->location }}</span>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    @if($reader->status === 'active')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 w-fit">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            เปิดใช้งาน
                                        </span>
                                    @elseif($reader->status === 'inactive')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 w-fit">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            ปิดใช้งาน
                                        </span>
                                    @elseif($reader->status === 'maintenance')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 w-fit">
                                            <i class="fas fa-wrench mr-1"></i>
                                            ปรับปรุง
                                        </span>
                                    @endif

                                    {{-- Online Status --}}
                                    @if($reader->isOnline())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300 w-fit">
                                            <i class="fas fa-wifi mr-1 text-[10px]"></i>
                                            ออนไลน์
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 w-fit">
                                            <i class="fas fa-wifi-slash mr-1 text-[10px]"></i>
                                            ออฟไลน์
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Last Heartbeat --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($reader->last_heartbeat)
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs">{{ $reader->last_heartbeat->diffForHumans() }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $reader->last_heartbeat->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs italic">ไม่มีข้อมูล</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2" x-data="{ open: false }">
                                    {{-- View --}}
                                    <a href="{{ route('admin.nfc-readers.show', $reader) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye text-lg"></i>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.nfc-readers.edit', $reader) }}"
                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors"
                                       title="แก้ไข">
                                        <i class="fas fa-edit text-lg"></i>
                                    </a>

                                    {{-- More Actions Dropdown --}}
                                    <div class="relative">
                                        <button @click="open = !open"
                                                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
                                                title="เพิ่มเติม">
                                            <i class="fas fa-ellipsis-v text-lg"></i>
                                        </button>

                                        {{-- Dropdown Menu --}}
                                        <div x-show="open"
                                             @click.away="open = false"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50">
                                            <div class="py-1">
                                                @if($reader->status === 'active')
                                                    <form action="{{ route('admin.nfc-readers.deactivate', $reader) }}" method="POST">
                                                        @csrf
                                                        <button type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <i class="fas fa-stop-circle mr-2"></i>
                                                            ปิดการใช้งาน
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.nfc-readers.activate', $reader) }}" method="POST">
                                                        @csrf
                                                        <button type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <i class="fas fa-play-circle mr-2"></i>
                                                            เปิดการใช้งาน
                                                        </button>
                                                    </form>
                                                @endif

                                                <form action="{{ route('admin.nfc-readers.maintenance', $reader) }}" method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <i class="fas fa-wrench mr-2"></i>
                                                        ตั้งค่าปรับปรุง
                                                    </button>
                                                </form>

                                                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                                                <form action="{{ route('admin.nfc-readers.destroy', $reader) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('คุณแน่ใจที่จะลบเครื่องอ่านบัตรนี้? การลบจะไม่สามารถกู้คืนได้')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <i class="fas fa-trash mr-2"></i>
                                                        ลบเครื่องอ่านบัตร
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-broadcast-tower text-6xl mb-4 text-gray-300 dark:text-gray-600"></i>
                                    <p class="text-lg font-medium mb-2">ไม่พบข้อมูลเครื่องอ่านบัตร NFC</p>
                                    <p class="text-sm mb-4">ยังไม่มีเครื่องอ่านบัตรในระบบหรือไม่พบผลลัพธ์ที่ค้นหา</p>
                                    <a href="{{ route('admin.nfc-readers.create') }}"
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                        <i class="fas fa-plus mr-1"></i>
                                        เพิ่มเครื่องอ่านบัตรใหม่เลย
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($readers->hasPages())
            <div class="bg-white dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $readers->links() }}
            </div>
        @endif
    </div>

    {{-- Selected Readers Action Bar --}}
    <div x-show="selectedReaders.length > 0"
         x-transition
         class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center gap-4 z-40">
        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
            เลือกแล้ว <span class="font-bold text-blue-600 dark:text-blue-400" x-text="selectedReaders.length"></span> รายการ
        </div>
        <div class="flex gap-2">
            <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-trash mr-1"></i>
                ลบ
            </button>
            <button @click="selectedReaders = []; selectAll = false"
                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-times mr-1"></i>
                ยกเลิก
            </button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Custom scrollbar for dark mode */
    .dark ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .dark ::-webkit-scrollbar-track {
        background: #1f2937;
    }

    .dark ::-webkit-scrollbar-thumb {
        background: #4b5563;
        border-radius: 4px;
    }

    .dark ::-webkit-scrollbar-thumb:hover {
        background: #6b7280;
    }
</style>
@endpush
