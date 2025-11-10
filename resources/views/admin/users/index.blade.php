@extends('layouts.admin')

@section('title', 'จัดการผู้ใช้')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">จัดการผู้ใช้</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">จัดการผู้ใช้งานและสิทธิ์การเข้าถึง</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.roles.index') }}"
               class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-gray-600 to-gray-700 text-white font-semibold rounded-xl hover:from-gray-700 hover:to-gray-800 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                จัดการบทบาท
            </a>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                เพิ่มผู้ใช้ใหม่
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อ, อีเมล หรือเลขสมาชิก"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">บทบาท</label>
                <select name="role" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                    <option value="">ทั้งหมด</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">จำนวนต่อหน้า</label>
                <select name="per_page" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="flex-1 px-5 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    ค้นหา
                </button>
                <a href="{{ route('admin.users.index') }}" class="px-5 py-3 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-200 shadow-md hover:shadow-lg">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Bar (Hidden by default, shown when items selected) -->
    <div id="bulk-actions-bar" class="hidden bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl shadow-lg border-2 border-indigo-200 dark:border-indigo-800 p-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 bg-indigo-600 rounded-full">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    เลือกแล้ว <span id="selected-count" class="font-bold text-indigo-600 dark:text-indigo-400 text-lg">0</span> รายการ
                </span>
            </div>
            <div class="flex gap-3">
                <button onclick="bulkAction('delete')" class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-sm font-semibold rounded-xl hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    ลบที่เลือก
                </button>
                <button onclick="clearSelection()" class="px-5 py-2.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-200 shadow-md">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800">
                    <tr>
                        <th class="px-6 py-4 text-left">
                            <input type="checkbox" id="select-all"
                                   class="rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5 cursor-pointer"
                                   onchange="toggleSelectAll(this)">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เลขสมาชิก</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">อีเมล</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">บทบาท</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สร้างเมื่อ</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 dark:hover:from-slate-700 dark:hover:to-slate-700/80 transition-all duration-200">
                            <td class="px-6 py-4">
                                <input type="checkbox"
                                       class="user-checkbox rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5 cursor-pointer"
                                       value="{{ $user->id }}"
                                       data-user-id="{{ $user->id }}"
                                       data-super-admin="{{ $user->is_super_admin ? '1' : '0' }}"
                                       onchange="updateBulkActions()"
                                       {{ $user->is_super_admin ? 'disabled' : '' }}>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->member_number)
                                    <div class="flex items-center">
                                        <span class="px-3 py-1.5 bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-bold rounded-lg border border-indigo-200 dark:border-indigo-800">
                                            {{ $user->member_number }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">ไม่มี</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <img class="h-12 w-12 rounded-xl ring-2 ring-indigo-100 dark:ring-slate-600"
                                             src="{{ $user->profile_picture_url }}"
                                             alt="{{ $user->name }}">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $user->name }}
                                        </div>
                                        @if($user->is_super_admin)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Super Admin
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</div>
                                @if($user->email_verified_at)
                                    <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400 font-medium">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        ยืนยันแล้ว
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-xs text-amber-600 dark:text-amber-400 font-medium">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        รอยืนยัน
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->roleModel)
                                    <span class="px-4 py-2 inline-flex text-xs leading-5 font-bold rounded-xl shadow-sm
                                        {{ $user->roleModel->name === 'super_admin' ? 'bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : '' }}
                                        {{ $user->roleModel->name === 'admin' ? 'bg-gradient-to-r from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800' : '' }}
                                        {{ $user->roleModel->name === 'seller' ? 'bg-gradient-to-r from-yellow-100 to-orange-100 dark:from-yellow-900/30 dark:to-orange-900/30 text-yellow-800 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800' : '' }}
                                        {{ $user->roleModel->name === 'user' ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800' : '' }}">
                                        {{ $user->roleModel->display_name }}
                                    </span>
                                @else
                                    <span class="px-4 py-2 inline-flex text-xs leading-5 font-bold rounded-xl shadow-sm bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-900/30 dark:to-gray-800/30 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                        {{ $user->getRoleDisplayName() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->created_at->format('H:i') }} น.</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="group relative inline-flex items-center justify-center p-2.5 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-xl hover:from-indigo-600 hover:to-purple-600 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                       title="ดูรายละเอียด">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <span class="absolute bottom-full mb-2 hidden group-hover:block px-2 py-1 text-xs bg-gray-900 text-white rounded whitespace-nowrap">ดูรายละเอียด</span>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="group relative inline-flex items-center justify-center p-2.5 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-xl hover:from-blue-600 hover:to-cyan-600 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                       title="แก้ไข">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        <span class="absolute bottom-full mb-2 hidden group-hover:block px-2 py-1 text-xs bg-gray-900 text-white rounded whitespace-nowrap">แก้ไข</span>
                                    </a>
                                    @if(!$user->is_super_admin)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="group relative inline-flex items-center justify-center p-2.5 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-xl hover:from-red-600 hover:to-rose-600 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                                    title="ลบ">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                <span class="absolute bottom-full mb-2 hidden group-hover:block px-2 py-1 text-xs bg-gray-900 text-white rounded whitespace-nowrap">ลบ</span>
                                            </button>
                                        </form>
                                    @else
                                        <div class="inline-flex items-center justify-center p-2.5 bg-gray-200 dark:bg-slate-700 text-gray-400 dark:text-gray-500 rounded-xl cursor-not-allowed opacity-50">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">ไม่พบข้อมูลผู้ใช้</span>
                                    <span class="text-sm text-gray-400 dark:text-gray-500 mt-2">ลองปรับเปลี่ยนตัวกรองหรือเพิ่มผู้ใช้ใหม่</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/50">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        แสดง <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $users->firstItem() ?? 0 }}</span>
                        ถึง <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $users->lastItem() ?? 0 }}</span>
                        จากทั้งหมด <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $users->total() }}</span> รายการ
                    </div>
                    <div>
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Bulk selection functionality
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

function updateBulkActions() {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');

    if (selectedCount > 0) {
        bulkActionsBar.classList.remove('hidden');
        selectedCountSpan.textContent = selectedCount;
    } else {
        bulkActionsBar.classList.add('hidden');
    }

    // Update select-all checkbox state
    const allCheckboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
    const selectAllCheckbox = document.getElementById('select-all');
    selectAllCheckbox.checked = selectedCount === allCheckboxes.length && allCheckboxes.length > 0;
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('select-all').checked = false;
    updateBulkActions();
}

function bulkAction(action) {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

    if (selectedIds.length === 0) {
        alert('กรุณาเลือกผู้ใช้อย่างน้อย 1 คน');
        return;
    }

    if (action === 'delete') {
        if (!confirm(`คุณแน่ใจหรือไม่ที่จะลบผู้ใช้ ${selectedIds.length} คน?`)) {
            return;
        }

        // Create form to submit bulk delete
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.users.index") }}/bulk-delete';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection
