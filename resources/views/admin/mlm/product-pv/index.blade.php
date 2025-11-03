@extends('layouts.admin')

@section('title', 'กำหนด PV สินค้า')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">กำหนด PV สินค้า</h1>
            <p class="text-gray-600 mt-1">กำหนดค่า Point Value และค่าคอมมิชชั่นสำหรับสินค้า</p>
        </div>
        <a href="{{ route('admin.mlm.product-pv.create') }}"
           class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-2.5 rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            เพิ่มการกำหนด PV
        </a>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800">เกี่ยวกับ PV (Point Value)</h3>
                <p class="text-sm text-blue-700 mt-1">
                    PV คือค่าคะแนนที่กำหนดให้กับสินค้า ใช้ในการคำนวณคอมมิชชั่น MLM แต่ละสินค้าสามารถกำหนด PV แยกตามแต่ละแผน MLM ได้
                    หากไม่กำหนด PV จะใช้ค่า Global Rate จากการตั้งค่าแผน MLM
                </p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">แผน MLM</label>
                <select name="plan_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">ทั้งหมด</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ค้นหาสินค้า</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ชื่อสินค้า หรือ SKU"
                       class="w-full border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Product PV Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">สินค้า</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">แผน MLM</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">PV Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">อัตราคอมมิชชั่น</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider">แสดงในหน้าสินค้า</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($productPvs as $productPv)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($productPv->product->images->count() > 0)
                                <img src="{{ $productPv->product->images->first()->image_path }}"
                                     alt="{{ $productPv->product->name }}"
                                     class="w-12 h-12 rounded-lg object-cover mr-3">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-gray-200 mr-3 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $productPv->product->name }}</div>
                                <div class="text-sm text-gray-500">SKU: {{ $productPv->product->sku }}</div>
                                <div class="text-xs text-gray-500">ราคา: ฿{{ number_format($productPv->product->price, 2) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                            {{ $productPv->plan->display_name }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-blue-600">{{ number_format($productPv->pv_value, 2) }} PV</div>
                        <div class="text-xs text-gray-500">
                            {{ number_format(($productPv->pv_value / $productPv->product->price) * 100, 1) }}% ของราคา
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($productPv->use_global_rate)
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
                                Global Rate
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                ฿{{ number_format($productPv->plan->commission_per_pv, 2) }}/PV
                            </div>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                Custom Rate
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                ฿{{ number_format($productPv->custom_commission_per_pv, 2) }}/PV
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex flex-col items-center gap-1">
                            @if($productPv->show_pv_on_product_page)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    แสดง PV
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    ไม่แสดง PV
                                </span>
                            @endif
                            @if($productPv->show_commission_preview)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    แสดงตัวอย่างค่าคอม
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.mlm.product-pv.edit', $productPv) }}"
                           class="text-blue-600 hover:text-blue-900 mr-3">แก้ไข</a>
                        <form action="{{ route('admin.mlm.product-pv.destroy', $productPv) }}"
                              method="POST"
                              class="inline-block"
                              onsubmit="return confirm('ยืนยันการลบ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">ลบ</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="text-lg font-medium">ยังไม่มีการกำหนด PV สินค้า</p>
                            <p class="text-sm text-gray-400 mt-1">เริ่มต้นด้วยการเพิ่มการกำหนด PV ให้กับสินค้า</p>
                            <a href="{{ route('admin.mlm.product-pv.create') }}"
                               class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg inline-flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                เพิ่มการกำหนด PV
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $productPvs->links() }}
    </div>

    <!-- Quick Stats -->
    @if($productPvs->count() > 0)
    <div class="mt-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">สถิติการกำหนด PV</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg p-4 shadow">
                <p class="text-sm text-gray-600">จำนวนสินค้าที่กำหนด PV</p>
                <p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($productPvs->total()) }}</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow">
                <p class="text-sm text-gray-600">PV เฉลี่ย</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">
                    {{ number_format($productPvs->avg('pv_value'), 2) }} PV
                </p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow">
                <p class="text-sm text-gray-600">ใช้ Global Rate</p>
                <p class="text-2xl font-bold text-green-600 mt-1">
                    {{ number_format($productPvs->where('use_global_rate', true)->count()) }}
                </p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow">
                <p class="text-sm text-gray-600">ใช้ Custom Rate</p>
                <p class="text-2xl font-bold text-pink-600 mt-1">
                    {{ number_format($productPvs->where('use_global_rate', false)->count()) }}
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
