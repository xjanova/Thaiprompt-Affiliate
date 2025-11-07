@extends('layouts.admin')

@section('title', 'รายละเอียดผู้ติดต่อ')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.accounting.contacts.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $contact->name }}</h1>
                @if($contact->company_name)
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $contact->company_name }}</p>
                @endif
            </div>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.accounting.contacts.statement', $contact) }}"
               class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                <i class="fas fa-file-invoice mr-2"></i>ใบแจ้งยอด
            </a>
            @if(auth()->user()->hasPermission('accounting.edit_contacts') || auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.accounting.contacts.edit', $contact) }}"
               class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-edit mr-2"></i>แก้ไข
            </a>
            @endif
            @if(auth()->user()->hasPermission('accounting.delete_contacts') || auth()->user()->isSuperAdmin())
            <form action="{{ route('admin.accounting.contacts.destroy', $contact) }}" method="POST" class="inline"
                  onsubmit="return confirm('ยืนยันการลบผู้ติดต่อนี้?');">
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
        <!-- Contact Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-400">ชื่อผู้ติดต่อ</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $contact->name }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-400">ประเภท</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                @if($contact->is_customer && $contact->is_vendor)
                                    <i class="fas fa-user-tie text-purple-600 mr-2"></i>ทั้งสองอย่าง
                                @elseif($contact->is_customer)
                                    <i class="fas fa-user text-blue-600 mr-2"></i>ลูกค้า
                                @else
                                    <i class="fas fa-building text-orange-600 mr-2"></i>ผู้ขาย
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($contact->company_name)
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">ชื่อบริษัท</label>
                        <p class="text-gray-900 dark:text-white">{{ $contact->company_name }}</p>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-400">อีเมล</label>
                            <p class="text-gray-900 dark:text-white">{{ $contact->email ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-400">โทรศัพท์</label>
                            <p class="text-gray-900 dark:text-white">{{ $contact->phone ?? '-' }}</p>
                        </div>
                    </div>

                    @if($contact->tax_id)
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">เลขประจำตัวผู้เสียภาษี</label>
                        <p class="text-gray-900 dark:text-white">{{ $contact->tax_id }}</p>
                    </div>
                    @endif

                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">สถานะ</label>
                        <p>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                {{ $contact->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $contact->is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ที่อยู่</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 dark:text-white">
                        {{ $contact->address ?? '-' }}<br>
                        @if($contact->district || $contact->province || $contact->postal_code)
                            {{ $contact->district }} {{ $contact->province }} {{ $contact->postal_code }}
                        @endif
                    </p>
                </div>
            </div>

            @if($contact->notes)
            <!-- Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">หมายเหตุ</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-900 dark:text-white">{{ $contact->notes }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Stats & Recent Activity -->
        <div class="space-y-6">
            <!-- Balance Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ยอดค้างชำระ</h2>
                </div>
                <div class="p-6">
                    <div class="text-4xl font-bold {{ $contact->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                        ฿{{ number_format($contact->balance ?? 0, 2) }}
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">สถิติ</h2>
                </div>
                <div class="p-6 space-y-4">
                    @if($contact->is_customer)
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">ใบแจ้งหนี้ทั้งหมด</label>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['invoices'] ?? 0) }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">ยอดขายทั้งหมด</label>
                        <p class="text-2xl font-bold text-green-600">฿{{ number_format($stats['total_sales'] ?? 0, 2) }}</p>
                    </div>
                    @endif

                    @if($contact->is_vendor)
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">รายจ่ายทั้งหมด</label>
                        <p class="text-2xl font-bold text-orange-600">฿{{ number_format($stats['total_purchases'] ?? 0, 2) }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Additional Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ข้อมูลเพิ่มเติม</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">สร้างเมื่อ</label>
                        <p class="text-gray-900 dark:text-white">{{ $contact->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">แก้ไขล่าสุด</label>
                        <p class="text-gray-900 dark:text-white">{{ $contact->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
