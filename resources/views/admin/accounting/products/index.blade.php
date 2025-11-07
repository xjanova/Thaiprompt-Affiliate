@extends('layouts.admin')

@section('title', 'สินค้าและบริการ')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">สินค้าและบริการ</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">จัดการสินค้าและบริการในระบบบัญชี</p>
        </div>
        @if(auth()->user()->hasPermission('accounting.create_products') || auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.accounting.products.create') }}"
           class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>เพิ่มสินค้า/บริการ
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
            <div class="text-gray-600 dark:text-gray-400 text-sm">สินค้า</div>
            <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['goods'] ?? 0) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-gray-600 dark:text-gray-400 text-sm">บริการ</div>
            <div class="text-2xl font-bold text-purple-600">{{ number_format($stats['services'] ?? 0) }}</div>
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
                   placeholder="ค้นหาชื่อสินค้า, รหัส..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <select name="type" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">ประเภททั้งหมด</option>
                <option value="goods" {{ request('type') === 'goods' ? 'selected' : '' }}>สินค้า</option>
                <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>บริการ</option>
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

    <!-- Products Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">รหัส</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ชื่อสินค้า/บริการ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ราคาขาย</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ราคาต้นทุน</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($products ?? [] as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.accounting.products.show', $product) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                {{ $product->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $product->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            @if($product->type === 'goods')
                                <i class="fas fa-box text-blue-600 mr-2"></i>สินค้า
                            @else
                                <i class="fas fa-concierge-bell text-purple-600 mr-2"></i>บริการ
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($product->sale_price, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($product->cost_price ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $product->is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="{{ route('admin.accounting.products.show', $product) }}"
                               class="text-blue-600 hover:text-blue-900 mr-3" title="ดูรายละเอียด">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(auth()->user()->hasPermission('accounting.edit_products') || auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.accounting.products.edit', $product) }}"
                               class="text-green-600 hover:text-green-900 mr-3" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('accounting.delete_products') || auth()->user()->isSuperAdmin())
                            <form action="{{ route('admin.accounting.products.destroy', $product) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('ยืนยันการลบสินค้า/บริการนี้?');">
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
                            ไม่พบข้อมูลสินค้าและบริการ
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if(isset($products) && $products->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
