@extends('layouts.admin-v3')

@section('title', 'สร้างหนี้ใหม่')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-red-900 to-gray-900 py-8 px-4">
    <div class="max-w-2xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.platform-revenue.debts.index') }}"
               class="text-red-400 hover:text-red-300 mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>กลับรายการหนี้
            </a>
            <h1 class="text-4xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-plus-circle text-red-400"></i>
                สร้างหนี้ใหม่
            </h1>
        </div>

        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
            <form method="POST" action="{{ route('admin.platform-revenue.debts.store') }}">
                @csrf

                <div class="mb-6">
                    <label class="text-white/60 text-sm block mb-2">ผู้ใช้ *</label>
                    <div x-data="{ search: '', users: [], selected: null }" class="relative">
                        <input type="hidden" name="user_id" x-model="selected?.id">
                        <input type="text"
                               x-model="search"
                               @input.debounce.300ms="searchUsers()"
                               :value="selected ? selected.name : search"
                               @focus="if(selected) { search = ''; selected = null; }"
                               placeholder="ค้นหาผู้ใช้..."
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400">

                        <div x-show="users.length > 0 && !selected"
                             class="absolute z-10 w-full mt-2 bg-gray-800 rounded-xl border border-white/20 shadow-xl max-h-60 overflow-y-auto">
                            <template x-for="user in users" :key="user.id">
                                <div @click="selected = user; search = user.text; users = []"
                                     class="p-3 hover:bg-white/10 cursor-pointer text-white border-b border-white/10 last:border-0">
                                    <span x-text="user.text"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    @error('user_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="text-white/60 text-sm block mb-2">จำนวนเงิน (บาท) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           value="{{ old('amount') }}"
                           class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400"
                           placeholder="0.00">
                    @error('amount')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="text-white/60 text-sm block mb-2">เหตุผล *</label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400"
                              placeholder="ระบุเหตุผลในการสร้างหนี้...">{{ old('reason') }}</textarea>
                    @error('reason')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="text-white/60 text-sm block mb-2">ลำดับความสำคัญ</label>
                    <select name="priority" class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-400">
                        <option value="1">1 - สูงสุด</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5" selected>5 - ปกติ</option>
                    </select>
                    <p class="text-white/40 text-sm mt-1">หนี้ที่มีลำดับต่ำกว่าจะถูกหักก่อน</p>
                </div>

                <div class="flex gap-4">
                    <a href="{{ route('admin.platform-revenue.debts.index') }}"
                       class="px-6 py-3 bg-white/10 rounded-xl text-white hover:bg-white/20 transition flex-1 text-center">
                        ยกเลิก
                    </a>
                    <button type="submit" class="px-6 py-3 bg-red-500 rounded-xl text-white hover:bg-red-600 transition flex-1">
                        <i class="fas fa-plus mr-2"></i>สร้างหนี้
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function searchUsers() {
    const search = document.querySelector('[x-model="search"]').value;
    if (search.length < 2) return;

    try {
        const response = await fetch(`{{ route('admin.platform-revenue.debts.api.search-users') }}?q=${encodeURIComponent(search)}`);
        const data = await response.json();

        // Update Alpine.js data
        const el = document.querySelector('[x-data]');
        if (el.__x) {
            el.__x.$data.users = data.data;
        }
    } catch (e) {
        console.error('Search failed:', e);
    }
}
</script>
@endpush
