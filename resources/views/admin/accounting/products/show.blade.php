@extends('layouts.admin')

@section('title', 'รายละเอียดสินค้า/บริการ')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.accounting.products.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">รายละเอียดสินค้า/บริการ</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $product->code }}</p>
            </div>
        </div>
        <div class="flex space-x-3">
            @if(auth()->user()->hasPermission('accounting.edit_products') || auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.accounting.products.edit', $product) }}"
               class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-edit mr-2"></i>แก้ไข
            </a>
            @endif
            @if(auth()->user()->hasPermission('accounting.delete_products') || auth()->user()->isSuperAdmin())
            <form action="{{ route('admin.accounting.products.destroy', $product) }}" method="POST" class="inline"
                  onsubmit="return confirm('ยืนยันการลบสินค้า/บริการนี้?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>ลบ
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Product Details -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ข้อมูลสินค้า/บริการ</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-400">รหัสสินค้า</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $product->code }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-400">ประเภท</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                @if($product->type === 'goods')
                                    <i class="fas fa-box text-blue-600 mr-2"></i>สินค้า
                                @else
                                    <i class="fas fa-concierge-bell text-purple-600 mr-2"></i>บริการ
                                @endif
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">ชื่อสินค้า/บริการ</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $product->name }}</p>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">รายละเอียด</label>
                        <p class="text-gray-900 dark:text-white">{{ $product->description ?? '-' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-400">ราคาขาย</label>
                            <p class="text-2xl font-bold text-green-600">฿{{ number_format($product->sale_price, 2) }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-400">ราคาต้นทุน</label>
                            <p class="text-2xl font-bold text-orange-600">฿{{ number_format($product->cost_price ?? 0, 2) }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">สถานะ</label>
                        <p>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $product->is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Info -->
        <div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ข้อมูลเพิ่มเติม</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">สร้างเมื่อ</label>
                        <p class="text-gray-900 dark:text-white">{{ $product->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">แก้ไขล่าสุด</label>
                        <p class="text-gray-900 dark:text-white">{{ $product->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if(isset($product->creator))
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">ผู้สร้าง</label>
                        <p class="text-gray-900 dark:text-white">{{ $product->creator->name }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
