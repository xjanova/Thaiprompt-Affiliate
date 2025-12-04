{{--
    หน้าจัดการใบประกาศนียบัตร
    ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
--}}
@extends('layouts.admin-v3')

@section('title', 'จัดการใบประกาศนียบัตร - Admin')

@section('content')
<div x-data="certificatesManager()" class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 sm:p-8 text-white mb-8 shadow-xl">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold">📜 จัดการใบประกาศนียบัตร</h1>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.academy.certificates.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg transition shadow-lg">
                    ➕ สร้างใบประกาศใหม่
                </a>
                <a href="{{ route('admin.academy.certificates.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-lg transition">
                    📥 Export CSV
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-5 text-center">
                <div class="text-3xl sm:text-4xl font-bold mb-1">{{ number_format($stats['total']) }}</div>
                <div class="text-sm text-indigo-100">ทั้งหมด</div>
            </div>
            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-5 text-center">
                <div class="text-3xl sm:text-4xl font-bold mb-1">{{ number_format($stats['active']) }}</div>
                <div class="text-sm text-indigo-100">ใช้งานได้</div>
            </div>
            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-5 text-center">
                <div class="text-3xl sm:text-4xl font-bold mb-1">{{ number_format($stats['revoked']) }}</div>
                <div class="text-sm text-indigo-100">ถูกเพิกถอน</div>
            </div>
            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-5 text-center">
                <div class="text-3xl sm:text-4xl font-bold mb-1">{{ number_format($stats['this_month']) }}</div>
                <div class="text-sm text-indigo-100">เดือนนี้</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('admin.academy.certificates.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">ค้นหา</label>
                    <input type="text" name="search"
                           placeholder="เลขที่, ชื่อ, อีเมล..."
                           value="{{ request('search') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">คอร์ส</label>
                    <select name="course_id"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <option value="">ทั้งหมด</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">สถานะ</label>
                    <select name="status"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานได้</option>
                        <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>ถูกเพิกถอน</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">วันที่ออก (จาก)</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">วันที่ออก (ถึง)</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.academy.certificates.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                ใบประกาศนียบัตรทั้งหมด ({{ $certificates->total() }})
            </h2>
        </div>

        @if($certificates->isEmpty())
            <div class="text-center py-20 px-6">
                <div class="text-6xl mb-4">📜</div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบใบประกาศนียบัตร</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">ยังไม่มีใบประกาศนียบัตรในระบบ หรือลองปรับตัวกรองใหม่</p>
                <a href="{{ route('admin.academy.certificates.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                    ➕ สร้างใบประกาศใหม่
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เลขที่</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">นักเรียน</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">คอร์ส</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">คะแนน</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่ออก</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($certificates as $cert)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-4">
                                    <span class="font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ $cert->certificate_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $cert->student_name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $cert->student_email }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="max-w-[250px] truncate text-gray-900 dark:text-white">{{ $cert->course_title }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">ครู: {{ $cert->instructor_name }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    @if($cert->quiz_score)
                                        <span class="font-bold text-green-600 dark:text-green-400">
                                            {{ number_format($cert->quiz_score, 0) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-gray-900 dark:text-white">{{ $cert->issued_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $cert->issued_at->format('H:i') }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    @if($cert->is_revoked)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                            ❌ เพิกถอนแล้ว
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                            ✅ ใช้งานได้
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" @click.outside="open = false"
                                                class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition">
                                            ⋮
                                        </button>
                                        <div x-show="open" x-transition
                                             class="absolute right-0 top-full mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-10 overflow-hidden">
                                            <a href="{{ route('admin.academy.certificates.show', $cert->id) }}"
                                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                👁️ ดูรายละเอียด
                                            </a>
                                            <a href="{{ route('admin.academy.certificates.edit', $cert->id) }}"
                                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                ✏️ แก้ไข
                                            </a>
                                            <a href="{{ route('admin.certificates.download', $cert->id) }}" target="_blank"
                                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                📥 ดาวน์โหลด
                                            </a>
                                            @if(!$cert->is_revoked)
                                                <button @click="revokeCertificate({{ $cert->id }})"
                                                        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                    🚫 เพิกถอน
                                                </button>
                                            @else
                                                <button @click="restoreCertificate({{ $cert->id }})"
                                                        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                    ♻️ กู้คืน
                                                </button>
                                            @endif
                                            <button @click="deleteCertificate({{ $cert->id }})"
                                                    class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                                🗑️ ลบ
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $certificates->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับจัดการ Certificates
 * ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
 */
function certificatesManager() {
    return {
        /**
         * เพิกถอนใบประกาศ
         */
        async revokeCertificate(id) {
            const reason = prompt('กรุณาระบุเหตุผลในการเพิกถอน (ถ้ามี):');
            if (reason === null) return;

            try {
                const response = await fetch(`{{ url('admin/academy/certificates') }}/${id}/revoke`, {
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
        async restoreCertificate(id) {
            if (!confirm('ต้องการกู้คืนใบประกาศนี้?')) return;

            try {
                const response = await fetch(`{{ url('admin/academy/certificates') }}/${id}/restore`, {
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
         * ลบใบประกาศ
         */
        async deleteCertificate(id) {
            if (!confirm('ต้องการลบใบประกาศนี้? การกระทำนี้ไม่สามารถย้อนกลับได้!')) return;

            try {
                const response = await fetch(`{{ url('admin/academy/certificates') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ ลบใบประกาศเรียบร้อยแล้ว');
                    location.reload();
                } else {
                    alert('❌ เกิดข้อผิดพลาด');
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด');
                console.error(error);
            }
        }
    }
}
</script>
@endpush
@endsection
