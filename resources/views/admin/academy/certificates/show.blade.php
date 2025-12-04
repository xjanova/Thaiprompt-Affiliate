{{--
    หน้ารายละเอียดใบประกาศนียบัตร
    ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
--}}
@extends('layouts.admin-v3')

@section('title', 'รายละเอียดใบประกาศนียบัตร')

@section('content')
<div x-data="certificateDetail()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 sm:p-8 text-white mb-8 shadow-xl">
        <div class="flex flex-col lg:flex-row justify-between items-start gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold mb-2">📜 รายละเอียดใบประกาศนียบัตร</h1>
                <p class="font-mono text-lg text-indigo-100">{{ $certificate->certificate_number }}</p>
            </div>
            <div>
                @if($certificate->is_revoked)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-red-500/20 text-red-100">
                        ❌ ถูกเพิกถอนแล้ว
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-green-500/20 text-green-100">
                        ✅ ใช้งานได้
                    </span>
                @endif
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.academy.certificates.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-lg transition">
                ← กลับ
            </a>
            <a href="{{ route('admin.academy.certificates.edit', $certificate->id) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-500 hover:bg-indigo-400 text-white font-semibold rounded-lg transition">
                ✏️ แก้ไข
            </a>
            <a href="{{ route('admin.certificates.download', $certificate->id) }}" target="_blank"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 hover:bg-green-400 text-white font-semibold rounded-lg transition">
                📥 ดาวน์โหลด
            </a>
            @if(!$certificate->is_revoked)
                <button @click="revokeCertificate()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-400 text-white font-semibold rounded-lg transition">
                    🚫 เพิกถอน
                </button>
            @else
                <button @click="restoreCertificate()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 hover:bg-green-400 text-white font-semibold rounded-lg transition">
                    ♻️ กู้คืน
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Certificate Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-5 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลใบประกาศ
                </h2>

                <div class="space-y-4">
                    <div class="flex justify-between items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">นักเรียน</span>
                        <span class="font-semibold text-gray-900 dark:text-white text-right">{{ $certificate->student_name }}</span>
                    </div>

                    <div class="flex justify-between items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">อีเมล</span>
                        <span class="font-semibold text-gray-900 dark:text-white text-right">{{ $certificate->student_email }}</span>
                    </div>

                    <div class="flex justify-between items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">คอร์ส</span>
                        <span class="font-semibold text-gray-900 dark:text-white text-right">{{ $certificate->course_title }}</span>
                    </div>

                    <div class="flex justify-between items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">ครูผู้สอน</span>
                        <span class="font-semibold text-gray-900 dark:text-white text-right">{{ $certificate->instructor_name }}</span>
                    </div>

                    @if($certificate->quiz_score)
                        <div class="flex justify-between items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">คะแนน Quiz</span>
                            <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($certificate->quiz_score, 2) }}%</span>
                        </div>
                    @endif

                    @if($certificate->total_hours)
                        <div class="flex justify-between items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">ชั่วโมงเรียน</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($certificate->total_hours, 1) }} ชม.</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">วันที่ออกใบประกาศ</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $certificate->issued_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="flex justify-between items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">รหัสยืนยัน</span>
                        <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $certificate->verification_code }}</span>
                    </div>

                    @if($certificate->custom_text)
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide block mb-2">ข้อความพิเศษ</span>
                            <p class="text-gray-700 dark:text-gray-300">{{ $certificate->custom_text }}</p>
                        </div>
                    @endif
                </div>

                @if($certificate->is_revoked)
                    <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="font-bold text-red-800 dark:text-red-200 mb-2">ใบประกาศนี้ถูกเพิกถอนแล้ว</div>
                        <p class="text-sm text-red-700 dark:text-red-300">
                            <strong>วันที่เพิกถอน:</strong> {{ $certificate->revoked_at->format('d/m/Y H:i') }}
                        </p>
                        @if($certificate->revoked_reason)
                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                <strong>เหตุผล:</strong> {{ $certificate->revoked_reason }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Certificate Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-5 border-b-2 border-gray-100 dark:border-gray-700">
                    ตัวอย่างใบประกาศ
                </h2>

                @if($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path))
                    <div class="border-2 border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                        <iframe src="{{ Storage::disk('public')->url($certificate->pdf_path) }}"
                                class="w-full h-[600px] border-0"></iframe>
                    </div>
                @else
                    <div class="text-center py-16 px-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-5xl mb-4">📄</div>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">ไม่พบไฟล์ใบประกาศ</p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">อาจจะยังไม่ได้สร้างหรือถูกลบไปแล้ว</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-5 border-b-2 border-gray-100 dark:border-gray-700">
                    การกระทำ
                </h2>

                <div class="space-y-3">
                    <a href="{{ route('certificate.verify', $certificate->verification_code) }}" target="_blank"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                        🔍 ตรวจสอบใบประกาศ (Public)
                    </a>

                    <a href="{{ route('certificate.share', $certificate->id) }}" target="_blank"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                        🔗 หน้าแชร์
                    </a>

                    <button @click="copyVerificationLink()"
                            class="flex items-center gap-3 w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                        📋 คัดลอกลิงก์ตรวจสอบ
                    </button>

                    <a href="mailto:{{ $certificate->student_email }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                        ✉️ ส่งอีเมลถึงนักเรียน
                    </a>
                </div>
            </div>

            <!-- User Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-5 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลผู้ใช้
                </h2>

                <div class="text-center mb-4">
                    <div class="w-20 h-20 mx-auto mb-3 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold">
                        {{ strtoupper(substr($certificate->user->name, 0, 1)) }}
                    </div>
                    <div class="font-bold text-lg text-gray-900 dark:text-white">{{ $certificate->user->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $certificate->user->email }}</div>
                </div>

                <a href="{{ route('admin.users.show', $certificate->user->id) }}"
                   class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                    👤 ดูโปรไฟล์ผู้ใช้
                </a>
            </div>

            <!-- Course Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-5 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลคอร์ส
                </h2>

                <div class="mb-4">
                    @if($certificate->article->thumbnail_url)
                        <img src="{{ $certificate->article->thumbnail_url }}"
                             class="w-full rounded-lg mb-3" alt="Course thumbnail">
                    @endif

                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">{{ $certificate->article->title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ Str::limit($certificate->article->description ?? 'ไม่มีคำอธิบาย', 100) }}
                    </p>
                </div>

                <a href="{{ route('admin.articles.edit', $certificate->article->id) }}"
                   class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                    📝 จัดการคอร์ส
                </a>
            </div>

            <!-- System Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-5 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลระบบ
                </h2>

                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <div class="flex justify-between">
                        <span>Created:</span>
                        <span>{{ $certificate->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Updated:</span>
                        <span>{{ $certificate->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>ID:</span>
                        <span class="font-mono">#{{ $certificate->id }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับแสดงรายละเอียดใบประกาศ
 * ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
 */
function certificateDetail() {
    return {
        /**
         * เพิกถอนใบประกาศ
         */
        async revokeCertificate() {
            const reason = prompt('กรุณาระบุเหตุผลในการเพิกถอน (ถ้ามี):');
            if (reason === null) return;

            try {
                const response = await fetch('{{ route("admin.academy.certificates.revoke", $certificate->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ revoked_reason: reason })
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ เพิกถอนใบประกาศเรียบร้อยแล้ว');
                    location.reload();
                } else {
                    alert('❌ เกิดข้อผิดพลาด');
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด');
                console.error(error);
            }
        },

        /**
         * กู้คืนใบประกาศ
         */
        async restoreCertificate() {
            if (!confirm('ต้องการกู้คืนใบประกาศนี้?')) return;

            try {
                const response = await fetch('{{ route("admin.academy.certificates.restore", $certificate->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ กู้คืนใบประกาศเรียบร้อยแล้ว');
                    location.reload();
                } else {
                    alert('❌ เกิดข้อผิดพลาด');
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด');
                console.error(error);
            }
        },

        /**
         * คัดลอกลิงก์ตรวจสอบ
         */
        copyVerificationLink() {
            const link = '{{ route("certificate.verify", $certificate->verification_code) }}';
            navigator.clipboard.writeText(link).then(() => {
                alert('✅ คัดลอกลิงก์เรียบร้อยแล้ว!');
            }).catch(() => {
                prompt('คัดลอกลิงก์นี้:', link);
            });
        }
    }
}
</script>
@endpush
@endsection
