@extends('layouts.admin-v3')

@section('title', 'กำหนด PV สินค้า')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-gem text-purple-600 dark:text-purple-400"></i>
                    กำหนด PV สินค้า
                </h1>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">กำหนดค่า Point Value และค่าคอมมิชชั่นสำหรับสินค้า</p>
            </div>
            <a href="{{ route('admin.mlm.product-pv.create') }}"
               class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-2.5 rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-plus"></i>
                เพิ่มการกำหนด PV
            </a>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 text-xl mt-0.5 mr-3"></i>
            <div>
                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">เกี่ยวกับ PV (Point Value)</h3>
                <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                    PV คือค่าคะแนนที่กำหนดให้กับสินค้า ใช้ในการคำนวณคอมมิชชั่น MLM แต่ละสินค้าสามารถกำหนด PV แยกตามแต่ละแผน MLM ได้
                    หากไม่กำหนด PV จะใช้ค่า Global Rate จากการตั้งค่าแผน MLM
                </p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">แผน MLM</label>
                <select name="plan_id" class="w-full border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ค้นหาสินค้า</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ชื่อสินค้า หรือ SKU"
                       class="w-full border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 py-2 rounded-xl transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Product PV Table -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">สินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">แผน MLM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">PV Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">อัตราคอมมิชชั่น</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider">แสดงในหน้าสินค้า</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($productPvs as $productPv)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($productPv->product->images->count() > 0)
                                    <img src="{{ $productPv->product->images->first()->image_path }}"
                                         alt="{{ $productPv->product->name }}"
                                         class="w-12 h-12 rounded-xl object-cover mr-3 shadow">
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 mr-3 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 dark:text-gray-500 dark:text-gray-400 text-xl"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $productPv->product->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">SKU: {{ $productPv->product->sku }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">ราคา: ฿{{ number_format($productPv->product->price, 2) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                {{ $productPv->plan->display_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($productPv->pv_value, 2) }} PV</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                {{ number_format(($productPv->pv_value / $productPv->product->price) * 100, 1) }}% ของราคา
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($productPv->use_global_rate)
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                    Global Rate
                                </span>
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                                    ฿{{ number_format($productPv->plan->commission_per_pv, 2) }}/PV
                                </div>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                    Custom Rate
                                </span>
                                <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                                    ฿{{ number_format($productPv->custom_commission_per_pv, 2) }}/PV
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex flex-col items-center gap-1">
                                @if($productPv->show_pv_on_product_page)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                        <i class="fas fa-check mr-1"></i>
                                        แสดง PV
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-300">
                                        <i class="fas fa-times mr-1"></i>
                                        ไม่แสดง PV
                                    </span>
                                @endif
                                @if($productPv->show_commission_preview)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        <i class="fas fa-check mr-1"></i>
                                        แสดงตัวอย่างค่าคอม
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.mlm.product-pv.edit', $productPv) }}"
                               class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                <i class="fas fa-edit"></i> แก้ไข
                            </a>
                            <form action="{{ route('admin.mlm.product-pv.destroy', $productPv) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('ยืนยันการลบ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                    <i class="fas fa-trash"></i> ลบ
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-box-open text-6xl text-gray-300 dark:text-gray-600 dark:text-gray-400 mb-4"></i>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">ยังไม่มีการกำหนด PV สินค้า</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400 mt-1">เริ่มต้นด้วยการเพิ่มการกำหนด PV ให้กับสินค้า</p>
                                <a href="{{ route('admin.mlm.product-pv.create') }}"
                                   class="mt-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-6 py-2 rounded-xl inline-flex items-center gap-2 transition-all duration-200">
                                    <i class="fas fa-plus"></i>
                                    เพิ่มการกำหนด PV
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $productPvs->links() }}
    </div>

    <!-- Quick Stats -->
    @if($productPvs->count() > 0)
    <div class="mt-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl shadow-lg p-6 border border-purple-100 dark:border-purple-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-purple-600 dark:text-purple-400"></i>
            สถิติการกำหนด PV
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="glass-fusion dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700 dark:border-gray-700" border border-white/20 dark:border-white/10>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">จำนวนสินค้าที่กำหนด PV</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ number_format($productPvs->total()) }}</p>
            </div>
            <div class="glass-fusion dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700 dark:border-gray-700" border border-white/20 dark:border-white/10>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">PV เฉลี่ย</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                    {{ number_format($productPvs->avg('pv_value'), 2) }} PV
                </p>
            </div>
            <div class="glass-fusion dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700 dark:border-gray-700" border border-white/20 dark:border-white/10>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ใช้ Global Rate</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                    {{ number_format($productPvs->where('use_global_rate', true)->count()) }}
                </p>
            </div>
            <div class="glass-fusion dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700 dark:border-gray-700" border border-white/20 dark:border-white/10>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ใช้ Custom Rate</p>
                <p class="text-2xl font-bold text-pink-600 dark:text-pink-400 mt-1">
                    {{ number_format($productPvs->where('use_global_rate', false)->count()) }}
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
