@extends('layouts.admin-v3')

@section('title', 'จับคู่บัตร NFC กับผู้ใช้')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.nfc-cards.show', $nfcCard) }}"
           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-link text-purple-600 dark:text-purple-400"></i>
                จับคู่บัตร NFC กับผู้ใช้
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                เชื่อมโยงบัตร <span class="font-mono font-semibold">{{ $nfcCard->card_number }}</span> กับผู้ใช้ในระบบ
            </p>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg mb-6 shadow-md">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                <div class="flex-1">
                    <h3 class="font-semibold mb-2">พบข้อผิดพลาด:</h3>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <form action="{{ route('admin.nfc-cards.pair', $nfcCard) }}" method="POST" x-data="{
                    selectedUser: null,
                    searchQuery: '',
                    users: {{ $users->toJson() }},
                    get filteredUsers() {
                        if (!this.searchQuery) return this.users;
                        const query = this.searchQuery.toLowerCase();
                        return this.users.filter(user =>
                            user.name.toLowerCase().includes(query) ||
                            user.email.toLowerCase().includes(query) ||
                            (user.id + '').includes(query)
                        );
                    },
                    selectUser(user) {
                        this.selectedUser = user;
                    }
                }">
                    @csrf

                    <div class="p-6 space-y-6">
                        {{-- Search Box --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-search mr-1"></i>
                                ค้นหาผู้ใช้
                            </label>
                            <input type="text"
                                   x-model="searchQuery"
                                   placeholder="ค้นหาด้วย ชื่อ, อีเมล, หรือรหัสผู้ใช้..."
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        {{-- Selected User Display --}}
                        <div x-show="selectedUser" x-transition class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                                        <span x-text="selectedUser ? selectedUser.name.substring(0, 2).toUpperCase() : ''"></span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white" x-text="selectedUser ? selectedUser.name : ''"></div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedUser ? selectedUser.email : ''"></div>
                                    </div>
                                </div>
                                <button type="button"
                                        @click="selectedUser = null"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                            <input type="hidden" name="user_id" x-bind:value="selectedUser ? selectedUser.id : ''">
                        </div>

                        {{-- Users List --}}
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden max-h-96 overflow-y-auto">
                            <template x-for="user in filteredUsers" :key="user.id">
                                <div @click="selectUser(user)"
                                     :class="selectedUser && selectedUser.id === user.id ? 'bg-purple-100 dark:bg-purple-900/30 border-l-4 border-purple-600' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                                     class="p-4 border-b border-gray-200 dark:border-gray-700 cursor-pointer transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            <span x-text="user.name.substring(0, 2).toUpperCase()"></span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white" x-text="user.name"></div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400" x-text="user.email"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            ID: <span x-text="user.id"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- No Results --}}
                            <div x-show="filteredUsers.length === 0"
                                 class="p-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-search text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                <p>ไม่พบผู้ใช้ที่ตรงกับคำค้นหา</p>
                            </div>
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 rounded-b-xl flex justify-between items-center gap-4">
                        <a href="{{ route('admin.nfc-cards.show', $nfcCard) }}"
                           class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-times"></i>
                            ยกเลิก
                        </a>

                        <button type="submit"
                                x-bind:disabled="!selectedUser"
                                class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-700 hover:from-purple-700 hover:to-indigo-800 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-link"></i>
                            จับคู่บัตร
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Card Info Sidebar --}}
        <div class="space-y-6">
            {{-- Card Preview --}}
            <div class="bg-gradient-to-br from-purple-500 to-indigo-600 dark:from-purple-600 dark:to-indigo-700 rounded-xl shadow-lg p-6 text-white">
                <h3 class="font-semibold text-purple-100 mb-4">บัตรที่จะจับคู่</h3>

                <div class="space-y-3">
                    <div>
                        <div class="text-xs text-purple-200 mb-1">เลขบัตร</div>
                        <div class="font-mono font-bold text-lg">{{ $nfcCard->card_number }}</div>
                    </div>

                    @if($nfcCard->card_name)
                        <div>
                            <div class="text-xs text-purple-200 mb-1">ชื่อบัตร</div>
                            <div class="font-semibold">{{ $nfcCard->card_name }}</div>
                        </div>
                    @endif

                    <div>
                        <div class="text-xs text-purple-200 mb-1">ประเภท</div>
                        <div class="font-semibold">
                            @if($nfcCard->card_type === 'standard')
                                มาตรฐาน
                            @elseif($nfcCard->card_type === 'premium')
                                พรีเมียม
                            @elseif($nfcCard->card_type === 'vip')
                                วีไอพี
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-purple-200 mb-1">ยอดเงินคงเหลือ</div>
                        <div class="text-2xl font-bold">฿{{ number_format($nfcCard->balance, 2) }}</div>
                    </div>
                </div>
            </div>

            {{-- Info Card --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-yellow-500"></i>
                    ข้อมูลการจับคู่
                </h3>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-0.5"></i>
                        <span>เมื่อจับคู่แล้ว ผู้ใช้จะสามารถใช้บัตรนี้สำหรับทำธุรกรรมได้</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-0.5"></i>
                        <span>บัตร 1 ใบสามารถจับคู่กับผู้ใช้ได้เพียง 1 คนเท่านั้น</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-0.5"></i>
                        <span>สามารถยกเลิกการจับคู่และจับคู่ใหม่ได้ภายหลัง</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-0.5"></i>
                        <span>ผู้ใช้จะได้รับการแจ้งเตือนเมื่อบัตรถูกจับคู่</span>
                    </li>
                </ul>
            </div>

            {{-- Statistics --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-users text-blue-600"></i>
                    สถิติผู้ใช้
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ผู้ใช้ทั้งหมด:</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $users->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ผู้ใช้ที่ใช้งานอยู่:</span>
                        <span class="font-bold text-green-600 dark:text-green-400">
                            {{ $users->where('status', 'active')->count() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Custom scrollbar for users list */
    .overflow-y-auto::-webkit-scrollbar {
        width: 8px;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .dark .overflow-y-auto::-webkit-scrollbar-track {
        background: #1f2937;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #9ca3af;
        border-radius: 4px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #6b7280;
    }
</style>
@endpush
