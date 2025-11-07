@extends('layouts.admin')

@section('title', 'ผู้ติดต่อ')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ผู้ติดต่อ</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">จัดการลูกค้าและผู้ขาย</p>
        </div>
        @if(auth()->user()->hasPermission('accounting.create_contacts') || auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.accounting.contacts.create') }}"
           class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>เพิ่มผู้ติดต่อ
        </a>
        @endif
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ลูกค้า</div>
            <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['customers'] ?? 0) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ผู้ขาย</div>
            <div class="text-2xl font-bold text-orange-600">{{ number_format($stats['vendors'] ?? 0) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">ใช้งานอยู่</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($stats['active'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="ค้นหาชื่อ, อีเมล, เบอร์โทร..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <select name="type" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">ประเภททั้งหมด</option>
                <option value="customer" {{ request('type') === 'customer' ? 'selected' : '' }}>ลูกค้า</option>
                <option value="vendor" {{ request('type') === 'vendor' ? 'selected' : '' }}>ผู้ขาย</option>
                <option value="both" {{ request('type') === 'both' ? 'selected' : '' }}>ทั้งสองอย่าง</option>
            </select>

            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">สถานะทั้งหมด</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
            </select>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
        </form>
    </div>

    <!-- Contacts Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ชื่อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">อีเมล</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">โทรศัพท์</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ยอดค้างชำระ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($contacts ?? [] as $contact)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.accounting.contacts.show', $contact) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                {{ $contact->name }}
                            </a>
                            @if($contact->company_name)
                            <div class="text-xs text-gray-500">{{ $contact->company_name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            @if($contact->is_customer && $contact->is_vendor)
                                <span class="inline-flex items-center">
                                    <i class="fas fa-user-tie text-purple-600 mr-1"></i>ทั้งสองอย่าง
                                </span>
                            @elseif($contact->is_customer)
                                <span class="inline-flex items-center">
                                    <i class="fas fa-user text-blue-600 mr-1"></i>ลูกค้า
                                </span>
                            @else
                                <span class="inline-flex items-center">
                                    <i class="fas fa-building text-orange-600 mr-1"></i>ผู้ขาย
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $contact->email ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $contact->phone ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            @if($contact->balance > 0)
                                <span class="text-red-600 font-semibold">฿{{ number_format($contact->balance, 2) }}</span>
                            @else
                                <span class="text-gray-500">฿0.00</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $contact->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $contact->is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="{{ route('admin.accounting.contacts.show', $contact) }}"
                               class="text-blue-600 hover:text-blue-900 mr-3" title="ดูรายละเอียด">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(auth()->user()->hasPermission('accounting.edit_contacts') || auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.accounting.contacts.edit', $contact) }}"
                               class="text-green-600 hover:text-green-900 mr-3" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('accounting.delete_contacts') || auth()->user()->isSuperAdmin())
                            <form action="{{ route('admin.accounting.contacts.destroy', $contact) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('ยืนยันการลบผู้ติดต่อนี้?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่พบข้อมูลผู้ติดต่อ
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if(isset($contacts) && $contacts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $contacts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
