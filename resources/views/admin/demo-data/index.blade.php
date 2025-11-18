@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="text-4xl">🧹</span>
                    {{ $pageTitle }}
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    ลบข้อมูลทดสอบเพื่อเตรียมระบบสำหรับการใช้งานจริง (Production-Ready)
                </p>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-4 py-3 rounded relative" role="alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-4 py-3 rounded relative" role="alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Production Warning --}}
    @if($isProduction)
        <div class="mb-6 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-400 dark:border-yellow-600 text-yellow-800 dark:text-yellow-400 px-6 py-4 rounded-lg" role="alert">
            <div class="flex items-start">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="font-bold text-lg mb-1">⚠️ Production Environment Detected!</h3>
                    <p class="text-sm">
                        คุณกำลังอยู่ใน Production environment - กรุณาระมัดระวังในการลบข้อมูล!
                        ระบบจะถามยืนยันอีกครั้งก่อนดำเนินการ
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- คำแนะนำ --}}
    <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-r-lg">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-700 dark:text-blue-400">
                <p class="font-bold mb-1">💡 คำแนะนำ:</p>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>ข้อมูลจำเป็น (Settings, MLM Plans, Ranks, etc.) จะ<strong>ไม่ถูกลบ</strong></li>
                    <li>Super Admin ที่สร้างตอนติดตั้งจะ<strong>ปลอดภัย</strong></li>
                    <li>ควร<strong>สำรองข้อมูล</strong>ก่อนลบ</li>
                    <li>สามารถเลือกลบทีละหมวดหมู่หรือลบทั้งหมดได้</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Demo Data Categories Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        @foreach($categories as $key => $category)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-shadow">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-{{ $category['color'] }}-500 to-{{ $category['color'] }}-600 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ $category['icon'] }}</span>
                            <h3 class="text-lg font-bold text-white">
                                {{ $category['label'] }}
                            </h3>
                        </div>
                        @if(isset($stats[$key]) && $stats[$key] > 0)
                            <span class="bg-white/30 text-white text-xs font-bold px-3 py-1 rounded-full">
                                {{ number_format($stats[$key]) }} records
                            </span>
                        @else
                            <span class="bg-white/30 text-white text-xs font-bold px-3 py-1 rounded-full">
                                ไม่มีข้อมูล
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ $category['description'] }}
                    </p>

                    {{-- Tables List --}}
                    <div class="mb-4">
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">ตารางที่เกี่ยวข้อง:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($category['tables'] as $table)
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded">
                                    {{ $table }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Action Button --}}
                    @if(isset($stats[$key]) && $stats[$key] > 0)
                        <form action="{{ route('admin.demo-data.clean') }}" method="POST"
                              onsubmit="return confirm('⚠️ ต้องการลบ{{ $category['label'] }}หรือไม่?\n\nข้อมูลจำนวน {{ number_format($stats[$key]) }} records จะถูกลบถาวร!\n\nยืนยันการดำเนินการ?');">
                            @csrf
                            <input type="hidden" name="category" value="{{ $key }}">
                            <button type="submit"
                                    class="w-full bg-{{ $category['color'] }}-600 hover:bg-{{ $category['color'] }}-700 text-white font-bold py-2 px-4 rounded transition-colors">
                                🗑️ ลบข้อมูล
                            </button>
                        </form>
                    @else
                        <button type="button" disabled
                                class="w-full bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-bold py-2 px-4 rounded cursor-not-allowed">
                            ✓ ไม่มีข้อมูลที่ต้องลบ
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Delete All Section --}}
    @php
        $totalRecords = array_sum($stats);
    @endphp

    @if($totalRecords > 0)
        <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <span class="text-5xl">🚨</span>
                    <div>
                        <h3 class="text-2xl font-bold mb-1">ลบข้อมูลทดสอบทั้งหมด</h3>
                        <p class="text-red-100">
                            ลบข้อมูลทดสอบทุกหมวดหมู่พร้อมกัน ({{ number_format($totalRecords) }} records)
                        </p>
                    </div>
                </div>
                <form action="{{ route('admin.demo-data.clean') }}" method="POST"
                      onsubmit="return confirm('⚠️⚠️⚠️ คำเตือนสำคัญ! ⚠️⚠️⚠️\n\nคุณกำลังจะลบข้อมูลทดสอบทั้งหมด!\n\nจำนวน {{ number_format($totalRecords) }} records จะถูกลบถาวร!\n\n✅ ข้อมูลจำเป็นจะไม่ถูกลบ (Settings, MLM, etc.)\n✅ Super Admin ของคุณปลอดภัย\n\nแนะนำให้สำรองข้อมูลก่อน!\n\nต้องการดำเนินการจริงหรือไม่?');">
                    @csrf
                    <input type="hidden" name="category" value="all">
                    <button type="submit"
                            class="bg-white hover:bg-gray-100 text-red-600 font-bold py-3 px-8 rounded-lg transition-colors shadow-lg">
                        🗑️ ลบทั้งหมด
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-6 rounded-r-lg text-center">
            <div class="flex flex-col items-center gap-3">
                <span class="text-6xl">✅</span>
                <h3 class="text-2xl font-bold text-green-700 dark:text-green-400">
                    ระบบสะอาดเรียบร้อยแล้ว!
                </h3>
                <p class="text-green-600 dark:text-green-500">
                    ไม่มีข้อมูลทดสอบในระบบ - พร้อมสำหรับการใช้งานจริง (Production)
                </p>
            </div>
        </div>
    @endif

    {{-- Documentation Link --}}
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-start gap-4">
            <span class="text-4xl">📚</span>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                    เอกสารประกอบ
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    อ่านคู่มือการจัดการข้อมูลทดสอบฉบับเต็มและคำถามที่พบบ่อย (FAQ)
                </p>
                <div class="flex gap-3">
                    <a href="{{ asset('DEMO_DATA_GUIDE.md') }}" target="_blank"
                       class="inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:underline">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                        </svg>
                        คู่มือการใช้งาน (DEMO_DATA_GUIDE.md)
                    </a>
                    <span class="text-gray-400">|</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        💻 Command Line: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">php artisan demo:reset</code>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-refresh stats every 30 seconds
setInterval(function() {
    fetch('{{ route('admin.demo-data.stats') }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Stats updated:', data.data);
                // อัพเดท UI ถ้าต้องการ
            }
        })
        .catch(error => console.error('Error fetching stats:', error));
}, 30000);
</script>
@endpush
@endsection
