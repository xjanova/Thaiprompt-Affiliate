@extends('layouts.admin')

@section('title', 'รายละเอียดเทมเพลตการประเมินผล')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">{{ $template->name ?? 'เทมเพลตการประเมินผล' }}</h1>
                <p class="text-purple-100">รายละเอียดเทมเพลตการประเมินผลการปฏิบัติงาน</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.hrm.performance.templates.edit', $template) }}"
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    แก้ไข
                </a>
                <a href="{{ route('admin.hrm.performance.templates.index') }}"
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Basic Information -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อเทมเพลต</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">{{ $template->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">สถานะ</p>
                <p class="text-base font-medium">
                    <span class="px-3 py-1 text-sm rounded-full {{ $template->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $template->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                    </span>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ประเภทการประเมิน</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">
                    @if(isset($template->review_type))
                        @switch($template->review_type)
                            @case('annual') ประจำปี @break
                            @case('quarterly') รายไตรมาส @break
                            @case('monthly') รายเดือน @break
                            @case('probation') ทดลองงาน @break
                            @case('project') ตามโครงการ @break
                            @default {{ $template->review_type }}
                        @endswitch
                    @else
                        -
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">คะแนนเต็ม</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">{{ $template->max_score ?? 100 }} คะแนน</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ระบบการให้คะแนน</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">
                    @if(isset($template->rating_scale))
                        มาตรวัด {{ $template->rating_scale }}
                        @if($template->rating_scale == 5)
                            ระดับ (1-5)
                        @elseif($template->rating_scale == 10)
                            ระดับ (1-10)
                        @elseif($template->rating_scale == 100)
                            คะแนน (0-100)
                        @endif
                    @else
                        -
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">วันที่สร้าง</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">{{ $template->created_at ? $template->created_at->format('d/m/Y H:i') : '-' }}</p>
            </div>
        </div>

        @if(isset($template->description) && $template->description)
        <div class="mt-6">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">คำอธิบาย</p>
            <p class="text-base text-gray-900 dark:text-white">{{ $template->description }}</p>
        </div>
        @endif
    </div>

    <!-- Evaluation Criteria -->
    @if(isset($template->criteria) && count($template->criteria) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">หัวข้อการประเมิน ({{ count($template->criteria) }} หัวข้อ)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ลำดับ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หัวข้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คำอธิบาย</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">น้ำหนัก</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($template->criteria as $index => $criteria)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $criteria->name ?? $criteria['name'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $criteria->description ?? $criteria['description'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $criteria->weight ?? $criteria['weight'] ?? 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white text-right">รวมน้ำหนัก:</td>
                        <td class="px-6 py-3 text-sm font-bold text-gray-900 dark:text-white">
                            {{ collect($template->criteria)->sum(function($c) { return $c->weight ?? $c['weight'] ?? 0; }) }}%
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    <!-- Rating Guidelines -->
    @if(isset($template->rating_guidelines) && $template->rating_guidelines)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">คำแนะนำการประเมิน</h3>
        <div class="text-base text-gray-900 dark:text-white whitespace-pre-line">{{ $template->rating_guidelines }}</div>
    </div>
    @endif

    <!-- Usage Statistics -->
    @if(isset($template->reviews_count))
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถิติการใช้งาน</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-4 bg-purple-50 dark:bg-slate-700 rounded-lg">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $template->reviews_count ?? 0 }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">จำนวนครั้งที่ใช้งาน</p>
            </div>
            <div class="text-center p-4 bg-green-50 dark:bg-slate-700 rounded-lg">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $template->completed_reviews_count ?? 0 }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">ประเมินเสร็จสิ้น</p>
            </div>
            <div class="text-center p-4 bg-blue-50 dark:bg-slate-700 rounded-lg">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ isset($template->avg_score) ? number_format($template->avg_score, 2) : '-' }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">คะแนนเฉลี่ย</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Button -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-red-600 mb-2">ลบเทมเพลต</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            การลบเทมเพลตนี้จะไม่สามารถกู้คืนได้
            @if(isset($template->reviews_count) && $template->reviews_count > 0)
                <span class="text-red-600 font-medium">ไม่สามารถลบเทมเพลตที่มีการใช้งานแล้ว</span>
            @endif
        </p>
        @if(!isset($template->reviews_count) || $template->reviews_count === 0)
        <form method="POST" action="{{ route('admin.hrm.performance.templates.destroy', $template) }}"
              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบเทมเพลตนี้?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                ลบเทมเพลต
            </button>
        </form>
        @else
        <button type="button" disabled class="px-6 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
            ลบเทมเพลต
        </button>
        @endif
    </div>
</div>
@endsection
