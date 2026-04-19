{{--
    User Blocks Index - รายการการ Block ระหว่างผู้ใช้
    แสดงรายการการ Block ทั้งหมด
--}}
@extends('layouts.admin-v3')

@section('title', 'จัดการการ Block')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="blockManager()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-user-slash text-purple-500 mr-3"></i>
                    จัดการการ Block
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    รายการการ Block ระหว่างผู้ใช้และผู้ให้บริการ
                </p>
            </div>
            <a href="{{ route('admin.anti-abuse.dashboard') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับไป Dashboard
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @php
            $blockTypes = \App\Models\UserBlock::BLOCK_TYPES;
        @endphp
        @foreach($blockTypes as $type => $label)
            @php
                $typeColors = [
                    'personal' => ['bg' => 'blue', 'icon' => 'fa-user-friends'],
                    'safety' => ['bg' => 'red', 'icon' => 'fa-shield-alt'],
                    'system_auto' => ['bg' => 'yellow', 'icon' => 'fa-robot'],
                    'admin' => ['bg' => 'purple', 'icon' => 'fa-user-shield'],
                ];
                $config = $typeColors[$type] ?? ['bg' => 'gray', 'icon' => 'fa-ban'];
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="text-3xl font-bold text-{{ $config['bg'] }}-600 dark:text-{{ $config['bg'] }}-400">
                            {{ $blocks->where('block_type', $type)->count() }}
                        </p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-{{ $config['bg'] }}-100 dark:bg-{{ $config['bg'] }}-900 flex items-center justify-center">
                        <i class="fas {{ $config['icon'] }} text-{{ $config['bg'] }}-600 dark:text-{{ $config['bg'] }}-400"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" action="{{ route('admin.anti-abuse.blocks') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Block Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท Block</label>
                <select name="block_type"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($blockTypes as $type => $label)
                        <option value="{{ $type }}" {{ request('block_type') === $type ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Blocker Type Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ผู้ Block</label>
                <select name="blocker_type"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                    <option value="">ทั้งหมด</option>
                    <option value="user" {{ request('blocker_type') === 'user' ? 'selected' : '' }}>ผู้ใช้</option>
                    <option value="provider" {{ request('blocker_type') === 'provider' ? 'selected' : '' }}>ผู้ให้บริการ</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ยังมีผล</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                </select>
            </div>

            {{-- Search & Buttons --}}
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-search mr-2"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.anti-abuse.blocks') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Blocks Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ Block
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-arrow-right"></i>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ถูก Block
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            เหตุผล
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($blocks as $block)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            {{-- Blocker --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        @if($block->blocker_type === 'user' && $block->blockerUser)
                                            {{-- ใช้ profile_picture_url ซึ่งเป็น accessor ที่ถูกต้องของ User model --}}
                                            <x-user-avatar :user="$block->blockerUser" size="md" :ring="false" />
                                        @elseif($block->blocker_type === 'provider' && $block->blockerProvider)
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                 src="{{ $block->blockerProvider->logo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($block->blockerProvider->name ?? 'P') }}"
                                                 alt="{{ $block->blockerProvider->name ?? 'Provider' }}">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-500 dark:text-gray-400"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            @if($block->blocker_type === 'user' && $block->blockerUser)
                                                {{ $block->blockerUser->name }}
                                            @elseif($block->blocker_type === 'provider' && $block->blockerProvider)
                                                {{ $block->blockerProvider->name ?? 'Provider #' . $block->blocker_provider_id }}
                                            @else
                                                ไม่พบข้อมูล
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $block->blocker_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Arrow --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <i class="fas fa-long-arrow-alt-right text-2xl text-red-500"></i>
                            </td>

                            {{-- Blocked --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        @if($block->blocked_type === 'user' && $block->blockedUser)
                                            {{-- ใช้ profile_picture_url ซึ่งเป็น accessor ที่ถูกต้องของ User model --}}
                                            <x-user-avatar :user="$block->blockedUser" size="md" :ring="false" />
                                        @elseif($block->blocked_type === 'provider' && $block->blockedProvider)
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                 src="{{ $block->blockedProvider->logo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($block->blockedProvider->name ?? 'P') }}"
                                                 alt="{{ $block->blockedProvider->name ?? 'Provider' }}">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-500 dark:text-gray-400"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            @if($block->blocked_type === 'user' && $block->blockedUser)
                                                {{ $block->blockedUser->name }}
                                            @elseif($block->blocked_type === 'provider' && $block->blockedProvider)
                                                {{ $block->blockedProvider->name ?? 'Provider #' . $block->blocked_provider_id }}
                                            @else
                                                ไม่พบข้อมูล
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $block->blocked_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Block Type --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php
                                    $typeConfig = [
                                        'personal' => ['class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200', 'icon' => 'fa-user-friends'],
                                        'safety' => ['class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200', 'icon' => 'fa-shield-alt'],
                                        'system_auto' => ['class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200', 'icon' => 'fa-robot'],
                                        'admin' => ['class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200', 'icon' => 'fa-user-shield'],
                                    ];
                                    $config = $typeConfig[$block->block_type] ?? ['class' => 'bg-gray-100 text-gray-800', 'icon' => 'fa-ban'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $config['class'] }}">
                                    <i class="fas {{ $config['icon'] }} mr-1"></i>
                                    {{ $blockTypes[$block->block_type] ?? $block->block_type }}
                                </span>
                            </td>

                            {{-- Reason --}}
                            <td class="px-6 py-4">
                                @if($block->reason)
                                    <p class="text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate" title="{{ $block->reason }}">
                                        {{ Str::limit($block->reason, 40) }}
                                    </p>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($block->isActive())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        <i class="fas fa-ban mr-1"></i>
                                        ยังมีผล
                                    </span>
                                    @if($block->expires_at)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            หมดอายุ {{ $block->expires_at->format('d/m/Y') }}
                                        </p>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fas fa-clock mr-1"></i>
                                        หมดอายุแล้ว
                                    </span>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $block->created_at->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $block->created_at->format('H:i') }}
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    {{-- View Details --}}
                                    <button @click="showBlockDetail({{ json_encode([
                                        'blocker_type' => $block->blocker_type,
                                        'blocker_name' => $block->blocker_type === 'user' ? ($block->blockerUser->name ?? 'N/A') : ($block->blockerProvider->name ?? 'N/A'),
                                        'blocked_type' => $block->blocked_type,
                                        'blocked_name' => $block->blocked_type === 'user' ? ($block->blockedUser->name ?? 'N/A') : ($block->blockedProvider->name ?? 'N/A'),
                                        'block_type' => $blockTypes[$block->block_type] ?? $block->block_type,
                                        'reason' => $block->reason,
                                        'expires_at' => $block->expires_at ? $block->expires_at->format('d/m/Y H:i') : 'ถาวร',
                                        'created_at' => $block->created_at->format('d/m/Y H:i'),
                                    ]) }})"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                            title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    @if($block->isActive())
                                        {{-- Unblock --}}
                                        <form action="{{ route('admin.anti-abuse.blocks.remove', $block) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                    title="ยกเลิก Block"
                                                    onclick="return confirm('ยืนยันการยกเลิก Block นี้?')">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-user-friends text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                    <p class="text-gray-500 dark:text-gray-400">ไม่พบรายการ Block</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($blocks->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $blocks->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    <div x-show="showDetailModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="detail-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDetailModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showDetailModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDetailModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
                        <i class="fas fa-user-slash text-purple-500 mr-2"></i>
                        รายละเอียดการ Block
                    </h3>

                    <div class="space-y-6">
                        {{-- Visual Block Representation --}}
                        <div class="flex items-center justify-center space-x-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-center">
                                <div class="h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-user text-2xl text-blue-600 dark:text-blue-400"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="blockDetail.blocker_name"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="blockDetail.blocker_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ'"></p>
                            </div>
                            <div class="flex flex-col items-center">
                                <i class="fas fa-ban text-3xl text-red-500 mb-1"></i>
                                <span class="text-xs text-red-500 font-medium">BLOCK</span>
                            </div>
                            <div class="text-center">
                                <div class="h-16 w-16 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-user text-2xl text-red-600 dark:text-red-400"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="blockDetail.blocked_name"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="blockDetail.blocked_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ'"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">ประเภท Block</label>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="blockDetail.block_type"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">หมดอายุ</label>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="blockDetail.expires_at"></p>
                            </div>
                        </div>

                        <div x-show="blockDetail.reason">
                            <label class="text-xs text-gray-500 dark:text-gray-400">เหตุผล</label>
                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="blockDetail.reason"></p>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">Block เมื่อ</label>
                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="blockDetail.created_at"></p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button"
                            @click="showDetailModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:w-auto sm:text-sm">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function blockManager() {
    return {
        showDetailModal: false,
        blockDetail: {},

        showBlockDetail(data) {
            this.blockDetail = data;
            this.showDetailModal = true;
        }
    }
}
</script>
@endpush
@endsection
