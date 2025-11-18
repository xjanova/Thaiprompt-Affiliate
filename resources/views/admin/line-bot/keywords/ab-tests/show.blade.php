@extends('layouts.admin-v3')

@section('title', 'A/B Test: ' . $test->test_name)

@section('content')
<div class="container-fluid px-4 py-8">
    <!-- Header & Controls -->
    <div class="mb-8">
        <a href="{{ route('admin.line-bot.keywords.ab-tests.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline mb-4 inline-block">
            ← กลับ
        </a>
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    🧪 {{ $test->test_name }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{  $test->keyword?->keyword }}
                </p>
            </div>
            <div class="flex gap-2">
                @if($test->status === 'planning')
                    <button onclick="startTest()"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition">
                        ▶️ เริ่ม
                    </button>
                @elseif($test->status === 'active')
                    <button onclick="completeTest()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                        ✅ จบการทดสอบ
                    </button>
                    <button onclick="pauseTest()"
                        class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-semibold transition">
                        ⏸️ หยุด
                    </button>
                @elseif($test->status === 'completed' && $test->winner)
                    <button onclick="applyWinner()"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition">
                        🚀 นำไปใช้
                    </button>
                @endif
                <button onclick="deleteTest()"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition">
                    🗑️ ลบ
                </button>
            </div>
        </div>
    </div>

    <!-- Status Badge -->
    <div class="mb-6">
        @if($test->status === 'active')
            <span class="px-4 py-2 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-semibold">
                🟢 Active - กำลังดำเนินการ
            </span>
        @elseif($test->status === 'completed')
            <span class="px-4 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm font-semibold">
                ✅ Completed - เสร็จสิ้น
            </span>
        @elseif($test->status === 'paused')
            <span class="px-4 py-2 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm font-semibold">
                ⏸️ Paused - หยุดชั่วคราว
            </span>
        @else
            <span class="px-4 py-2 bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-full text-sm font-semibold">
                📋 Planning - ในการวางแผน
            </span>
        @endif
    </div>

    <!-- Test Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">เกณฑ์ชนะ</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                {{ ucfirst(str_replace('_', ' ', $test->winning_criterion)) }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">ตัวอย่างต่ำสุด</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                {{ $test->minimum_samples }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">ทั้งหมด Interactions</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                {{ $summary['total_interactions'] }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">ระยะเวลา</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                @if($summary['duration_days'] !== null)
                    {{ $summary['duration_days'] }} วัน
                @else
                    กำลังดำเนินการ
                @endif
            </p>
        </div>
    </div>

    <!-- Variant Comparison -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Variant A -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    📌 Variant A ({{ $test->variant_a_percentage }}%)
                </h3>
                @if($test->winner === 'variant_a')
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-bold">
                        👑 ผู้ชนะ
                    </span>
                @endif
            </div>

            <div class="space-y-3 mb-4">
                <div>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">ข้อความตอบกลับ</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        {{ $comparison['variant_a']['response_text'] ?? 'N/A' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Impressions</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $comparison['variant_a']['impressions'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Interactions</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $comparison['variant_a']['interactions'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Conversion Rate</p>
                        <p class="text-lg font-bold text-blue-600">{{ round($comparison['variant_a']['conversion_rate'], 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Avg Response Time</p>
                        <p class="text-lg font-bold text-blue-600">{{ round($comparison['variant_a']['avg_response_time'], 0) }}ms</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variant B -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    📌 Variant B ({{ $test->variant_b_percentage }}%)
                </h3>
                @if($test->winner === 'variant_b')
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-bold">
                        👑 ผู้ชนะ
                    </span>
                @endif
            </div>

            <div class="space-y-3 mb-4">
                <div>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">ข้อความตอบกลับ</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        {{ $comparison['variant_b']['response_text'] ?? 'N/A' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Impressions</p>
                        <p class="text-2xl font-bold text-purple-600">{{ $comparison['variant_b']['impressions'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Interactions</p>
                        <p class="text-2xl font-bold text-purple-600">{{ $comparison['variant_b']['interactions'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Conversion Rate</p>
                        <p class="text-lg font-bold text-purple-600">{{ round($comparison['variant_b']['conversion_rate'], 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Avg Response Time</p>
                        <p class="text-lg font-bold text-purple-600">{{ round($comparison['variant_b']['avg_response_time'], 0) }}ms</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section (if completed) -->
    @if($test->status === 'completed')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                📊 ผลลัพธ์ของการทดสอบ
            </h2>

            @if($test->winner)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">ผู้ชนะ</p>
                        <p class="text-2xl font-bold text-green-900 dark:text-green-100 mt-2">
                            {{ ucfirst(str_replace('_', ' ', $test->winner)) }}
                        </p>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Confidence</p>
                        <p class="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-2">
                            {{ round($test->winner_confidence, 1) }}%
                        </p>
                    </div>

                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-purple-800 dark:text-purple-200">สถิติที่มีความหมาย</p>
                        <p class="text-lg font-bold text-purple-900 dark:text-purple-100 mt-2">
                            {{ $test->winner_confidence >= 95 ? '✅ ใช่' : '❌ ไม่' }}
                        </p>
                    </div>
                </div>

                @if($test->results)
                    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $test->results['reason'] ?? '' }}
                        </p>
                    </div>
                @endif
            @endif
        </div>
    @endif

    <!-- Description -->
    @if($test->description)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                คำอธิบาย
            </h3>
            <p class="text-gray-700 dark:text-gray-300">
                {{ $test->description }}
            </p>
        </div>
    @endif
</div>

<!-- JavaScript for Actions -->
<script>
    function startTest() {
        if (!confirm('เริ่มการทดสอบหรือไม่?')) return;
        fetch('{{ route("admin.line-bot.keywords.ab-tests.start", $test) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        }).then(r => r.json()).then(d => {
            if (d.success) {
                alert('เริ่มการทดสอบสำเร็จ');
                location.reload();
            } else {
                alert('ข้อผิดพลาด: ' + d.message);
            }
        });
    }

    function completeTest() {
        if (!confirm('จบการทดสอบหรือไม่? ระบบจะวิเคราะห์ผล')) return;
        fetch('{{ route("admin.line-bot.keywords.ab-tests.complete", $test) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        }).then(r => r.json()).then(d => {
            if (d.success) {
                alert('จบการทดสอบสำเร็จ');
                location.reload();
            } else {
                alert('ข้อผิดพลาด: ' + d.message);
            }
        });
    }

    function pauseTest() {
        const reason = prompt('เหตุผลในการหยุด (ไม่บังคับ):');
        if (reason === null) return;

        fetch('{{ route("admin.line-bot.keywords.ab-tests.pause", $test) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reason })
        }).then(r => r.json()).then(d => {
            if (d.success) {
                alert('หยุดการทดสอบสำเร็จ');
                location.reload();
            } else {
                alert('ข้อผิดพลาด: ' + d.message);
            }
        });
    }

    function applyWinner() {
        if (!confirm('นำ {{ ucfirst(str_replace('_', ' ', $test->winner)) }} ไปใช้กับคีย์เวิร์ดหรือไม่?')) return;

        fetch('{{ route("admin.line-bot.keywords.ab-tests.apply-winner", $test) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        }).then(r => r.json()).then(d => {
            if (d.success) {
                alert('นำไปใช้สำเร็จ - คีย์เวิร์ดอัพเดทแล้ว');
                location.reload();
            } else {
                alert('ข้อผิดพลาด: ' + d.message);
            }
        });
    }

    function deleteTest() {
        if (!confirm('ลบการทดสอบนี้หรือไม่?')) return;

        fetch('{{ route("admin.line-bot.keywords.ab-tests.destroy", $test) }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        }).then(r => r.json()).then(d => {
            if (d.success) {
                alert('ลบสำเร็จ');
                window.location.href = '{{ route("admin.line-bot.keywords.ab-tests.index") }}';
            } else {
                alert('ข้อผิดพลาด: ' + d.message);
            }
        });
    }
</script>
@endsection
