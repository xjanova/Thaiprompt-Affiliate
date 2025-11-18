@extends('layouts.admin-v3')

@section('title', 'รายละเอียดตำแหน่งงาน')

@section('content')
<div class="space-y-6">
    <!-- Header with Gradient -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-briefcase text-3xl"></i>
                    <h1 class="text-3xl font-bold">{{ $job->job_title }}</h1>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-purple-100">
                    <span><i class="fas fa-hashtag"></i> {{ $job->job_code }}</span>
                    <span><i class="fas fa-building"></i> {{ $job->department->name ?? 'N/A' }}</span>
                    <span><i class="fas fa-map-marker-alt"></i> {{ $job->job_location }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.hrm.recruitment.jobs.edit', $job) }}"
                   class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    <span>แก้ไข</span>
                </a>
                <a href="{{ route('admin.hrm.recruitment.jobs.index') }}"
                   class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับ</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">สถานะ</p>
                    @php
                        $statusConfig = [
                            'draft' => ['class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'icon' => 'fa-file-alt', 'label' => 'ฉบับร่าง'],
                            'active' => ['class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', 'icon' => 'fa-check-circle', 'label' => 'เปิดรับสมัคร'],
                            'on_hold' => ['class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', 'icon' => 'fa-pause-circle', 'label' => 'พักการรับ'],
                            'closed' => ['class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', 'icon' => 'fa-times-circle', 'label' => 'ปิดรับ'],
                            'filled' => ['class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300', 'icon' => 'fa-user-check', 'label' => 'จ้างแล้ว'],
                        ];
                        $config = $statusConfig[$job->status] ?? $statusConfig['draft'];
                    @endphp
                    <span class="px-3 py-1 inline-flex items-center gap-1 text-sm font-semibold rounded-full {{ $config['class'] }}">
                        <i class="fas {{ $config['icon'] }}"></i>
                        {{ $config['label'] }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Applications -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">จำนวนผู้สมัคร</p>
                    <p class="text-3xl font-bold">{{ $job->applications->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Openings -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">จำนวนที่รับ</p>
                    <p class="text-3xl font-bold">{{ $job->number_of_openings }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-user-plus text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Employment Type -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">ประเภทการจ้าง</p>
                    <p class="text-lg font-bold">
                        @php
                            $types = [
                                'full_time' => 'Full Time',
                                'part_time' => 'Part Time',
                                'contract' => 'สัญญาจ้าง',
                                'intern' => 'ฝึกงาน',
                                'freelance' => 'อิสระ',
                            ];
                        @endphp
                        {{ $types[$job->employment_type] ?? $job->employment_type }}
                    </p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Job Description -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-purple-500 pl-3">
                    <i class="fas fa-align-left text-purple-500"></i>
                    คำอธิบายงาน
                </h2>
                <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ $job->job_description }}
                </div>
            </div>

            <!-- Requirements -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-blue-500 pl-3">
                    <i class="fas fa-clipboard-list text-blue-500"></i>
                    คุณสมบัติที่ต้องการ
                </h2>
                <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ $job->requirements }}
                </div>
            </div>

            <!-- Responsibilities -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-green-500 pl-3">
                    <i class="fas fa-tasks text-green-500"></i>
                    หน้าที่ความรับผิดชอบ
                </h2>
                <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ $job->responsibilities }}
                </div>
            </div>

            <!-- Benefits -->
            @if($job->benefits)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-yellow-500 pl-3">
                    <i class="fas fa-gift text-yellow-500"></i>
                    สวัสดิการและผลประโยชน์
                </h2>
                <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ $job->benefits }}
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Job Information Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-purple-500"></i>
                    ข้อมูลตำแหน่ง
                </h2>
                <div class="space-y-4">
                    <!-- Department -->
                    <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-building text-purple-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">แผนก</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $job->department->name ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Position -->
                    @if($job->position)
                    <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-user-tie text-blue-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">ตำแหน่ง</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $job->position->title }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Location -->
                    <div class="flex items-start gap-3 p-3 bg-green-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-map-marker-alt text-green-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">สถานที่ทำงาน</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $job->job_location }}</p>
                        </div>
                    </div>

                    <!-- Salary Range -->
                    @if($job->salary_range_min || $job->salary_range_max)
                    <div class="flex items-start gap-3 p-3 bg-yellow-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-money-bill-wave text-yellow-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">เงินเดือน</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                @if($job->salary_range_min && $job->salary_range_max)
                                    ฿{{ number_format($job->salary_range_min) }} - ฿{{ number_format($job->salary_range_max) }}
                                @elseif($job->salary_range_min)
                                    ฿{{ number_format($job->salary_range_min) }}+
                                @else
                                    สูงสุด ฿{{ number_format($job->salary_range_max) }}
                                @endif
                            </p>
                        </div>
                    </div>
                    @endif

                    <!-- Posted Date -->
                    <div class="flex items-start gap-3 p-3 bg-indigo-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-calendar-plus text-indigo-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">วันที่ประกาศ</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $job->posted_date ? \Carbon\Carbon::parse($job->posted_date)->format('d/m/Y') : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <!-- Deadline -->
                    @if($job->application_deadline)
                    <div class="flex items-start gap-3 p-3 bg-red-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-calendar-times text-red-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">ปิดรับสมัคร</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($job->application_deadline)->format('d/m/Y') }}
                            </p>
                            @php
                                $daysLeft = \Carbon\Carbon::now()->diffInDays($job->application_deadline, false);
                            @endphp
                            @if($daysLeft > 0)
                                <p class="text-xs text-green-600 dark:text-green-400">เหลืออีก {{ $daysLeft }} วัน</p>
                            @elseif($daysLeft == 0)
                                <p class="text-xs text-orange-600 dark:text-orange-400">วันสุดท้าย!</p>
                            @else
                                <p class="text-xs text-red-600 dark:text-red-400">หมดเวลาแล้ว</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Posted By -->
                    @if($job->postedBy)
                    <div class="flex items-start gap-3 p-3 bg-pink-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-user text-pink-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">โพสต์โดย</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $job->postedBy->name }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Applications Summary -->
            @if($job->applications->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-blue-500"></i>
                    สถิติการสมัคร
                </h2>
                <div class="space-y-3">
                    @php
                        $statusGroups = $job->applications->groupBy('application_status');
                        $statusLabels = [
                            'new' => ['label' => 'ใหม่', 'color' => 'blue'],
                            'under_review' => ['label' => 'กำลังพิจารณา', 'color' => 'yellow'],
                            'shortlisted' => ['label' => 'ผ่านเข้ารอบ', 'color' => 'green'],
                            'interview_scheduled' => ['label' => 'นัดสัมภาษณ์', 'color' => 'purple'],
                            'interviewed' => ['label' => 'สัมภาษณ์แล้ว', 'color' => 'indigo'],
                            'offer_extended' => ['label' => 'เสนอตำแหน่ง', 'color' => 'pink'],
                            'hired' => ['label' => 'จ้างแล้ว', 'color' => 'green'],
                            'rejected' => ['label' => 'ไม่ผ่าน', 'color' => 'red'],
                        ];
                    @endphp
                    @foreach($statusGroups as $status => $apps)
                        @php
                            $config = $statusLabels[$status] ?? ['label' => $status, 'color' => 'gray'];
                            $count = $apps->count();
                            $percentage = ($count / $job->applications->count()) * 100;
                        @endphp
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $config['label'] }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $count }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-{{ $config['color'] }}-500 h-2 rounded-full transition-all duration-300"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-gray-700 dark:to-gray-700 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h3 class="font-bold text-gray-900 dark:text-white mb-3">
                    <i class="fas fa-bolt text-yellow-500"></i> การกระทำด่วน
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.hrm.recruitment.jobs.edit', $job) }}"
                       class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-edit"></i>
                        แก้ไขตำแหน่งงาน
                    </a>
                    <a href="{{ route('admin.hrm.recruitment.applications.index', ['job_posting_id' => $job->id]) }}"
                       class="w-full px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-users"></i>
                        ดูผู้สมัครทั้งหมด
                    </a>
                    <form action="{{ route('admin.hrm.recruitment.jobs.destroy', $job) }}"
                          method="POST"
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบตำแหน่งงานนี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-trash"></i>
                            ลบตำแหน่งงาน
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
