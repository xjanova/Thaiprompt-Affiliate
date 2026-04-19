@extends('layouts.admin-v3')

@section('title', 'คอร์สถูกล็อค - ' . $article->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-indigo-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        {{-- Back Button --}}
        <a href="{{ route('admin.learning-center.category', $article->category->slug) }}"
           class="inline-flex items-center gap-2 text-gray-300 hover:text-white mb-6 transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปหมวดหมู่ {{ $article->category->name }}</span>
        </a>

        {{-- Lock Card --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 md:p-12 text-center border border-white/20 shadow-2xl">
            {{-- Lock Icon --}}
            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-red-500 to-orange-500 rounded-full flex items-center justify-center animate-pulse">
                <i class="fas fa-lock text-4xl text-white"></i>
            </div>

            {{-- Title --}}
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                คอร์สนี้ยังถูกล็อคอยู่
            </h1>

            {{-- Article Title --}}
            <h2 class="text-xl text-gray-300 mb-6">
                {{ $article->title }}
            </h2>

            {{-- Course Level Badge --}}
            <div class="flex justify-center gap-3 mb-8">
                <span class="px-4 py-2 bg-purple-500/30 rounded-full text-purple-200 text-sm font-medium">
                    <i class="fas fa-layer-group mr-1"></i>
                    {{ $article->course_level_label }}
                </span>
                <span class="px-4 py-2 bg-blue-500/30 rounded-full text-blue-200 text-sm font-medium">
                    <i class="fas fa-clock mr-1"></i>
                    {{ $article->formatted_duration }}
                </span>
            </div>

            {{-- Lock Reason --}}
            <div class="bg-red-500/20 border border-red-500/30 rounded-xl p-6 mb-8">
                <h3 class="text-lg font-semibold text-red-300 mb-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    สาเหตุที่ไม่สามารถเข้าถึงได้
                </h3>

                @switch($accessInfo['reason'])
                    @case('no_permission')
                        <p class="text-red-200">คุณไม่มีสิทธิ์เข้าถึงคอร์สนี้</p>
                        @break

                    @case('prerequisites_incomplete')
                        <p class="text-red-200 mb-4">คุณต้องเรียนจบคอร์สก่อนหน้าก่อน:</p>
                        <ul class="space-y-2">
                            @foreach($accessInfo['requirements'] as $req)
                                <li class="flex items-center gap-3 bg-white/5 rounded-lg px-4 py-3">
                                    <i class="fas fa-book text-orange-400"></i>
                                    <span class="flex-1 text-left text-gray-200">{{ $req['title'] }}</span>
                                    <a href="{{ route('admin.learning-center.article', $req['slug']) }}"
                                       class="px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white text-sm rounded-lg transition">
                                        เรียนเลย
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        @break

                    @case('quiz_not_passed')
                        <p class="text-red-200 mb-4">คุณต้องผ่านแบบทดสอบของคอร์สก่อนหน้า:</p>
                        @if(!empty($accessInfo['requirements']))
                            @php $req = $accessInfo['requirements'][0]; @endphp
                            <div class="bg-white/5 rounded-lg p-4">
                                <p class="text-gray-200 mb-2">
                                    <strong>คอร์ส:</strong> {{ $req['article_title'] ?? 'คอร์สก่อนหน้า' }}
                                </p>
                                <p class="text-gray-200 mb-2">
                                    <strong>คะแนนขั้นต่ำ:</strong> {{ $req['min_score'] ?? 70 }}%
                                </p>
                                <p class="text-gray-200 mb-4">
                                    <strong>คะแนนปัจจุบัน:</strong>
                                    <span class="{{ ($req['best_score'] ?? 0) >= ($req['min_score'] ?? 70) ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $req['best_score'] ?? 0 }}%
                                    </span>
                                </p>
                                @if(isset($req['article_slug']))
                                    <a href="{{ route('admin.learning-center.article', $req['article_slug']) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                                        <i class="fas fa-clipboard-question"></i>
                                        ทำแบบทดสอบ
                                    </a>
                                @endif
                            </div>
                        @endif
                        @break

                    @case('time_locked')
                        <p class="text-red-200 mb-4">คอร์สนี้จะปลดล็อคตามเวลาที่กำหนด:</p>
                        @if(!empty($accessInfo['requirements']))
                            @php $req = $accessInfo['requirements'][0]; @endphp
                            <div class="bg-white/5 rounded-lg p-4">
                                <p class="text-2xl font-bold text-yellow-400 mb-2">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    เหลืออีก {{ $req['days_remaining'] ?? 0 }} วัน
                                </p>
                                <p class="text-gray-300">
                                    ปลดล็อค: {{ \Carbon\Carbon::parse($req['unlock_date'] ?? now())->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endif
                        @break

                    @case('manual_unlock_required')
                        <p class="text-red-200">คอร์สนี้ต้องได้รับการปลดล็อคจากผู้ดูแลระบบ</p>
                        <p class="text-gray-400 mt-2 text-sm">กรุณาติดต่อผู้ดูแลระบบเพื่อขอเข้าถึง</p>
                        @break

                    @default
                        <p class="text-red-200">ไม่สามารถเข้าถึงคอร์สนี้ได้ในขณะนี้</p>
                @endswitch
            </div>

            {{-- Course Info --}}
            <div class="bg-white/5 rounded-xl p-6 mb-8 text-left">
                <h3 class="text-lg font-semibold text-white mb-4">
                    <i class="fas fa-info-circle mr-2 text-blue-400"></i>
                    รายละเอียดคอร์ส
                </h3>
                @if($article->excerpt)
                    <p class="text-gray-300 mb-4">{{ $article->excerpt }}</p>
                @endif

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-2 text-gray-300">
                        <i class="fas fa-folder text-purple-400 w-5"></i>
                        <span>หมวดหมู่: {{ $article->category->name }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-300">
                        <i class="fas fa-signal text-green-400 w-5"></i>
                        <span>ระดับ: {{ $article->difficulty_label }}</span>
                    </div>
                    @if($article->require_quiz_pass)
                        <div class="flex items-center gap-2 text-gray-300">
                            <i class="fas fa-clipboard-check text-yellow-400 w-5"></i>
                            <span>ต้องผ่านแบบทดสอบ ({{ $article->min_quiz_score ?? 70 }}%)</span>
                        </div>
                    @endif
                    @if($article->points_reward)
                        <div class="flex items-center gap-2 text-gray-300">
                            <i class="fas fa-star text-yellow-400 w-5"></i>
                            <span>รางวัล: {{ $article->points_reward }} คะแนน</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('admin.learning-center.category', $article->category->slug) }}"
                   class="px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white font-semibold rounded-xl transition shadow-lg hover:shadow-purple-500/30">
                    <i class="fas fa-th-list mr-2"></i>
                    ดูคอร์สทั้งหมด
                </a>
                <a href="{{ route('admin.learning-center.index') }}"
                   class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl transition border border-white/20">
                    <i class="fas fa-home mr-2"></i>
                    กลับหน้าหลัก
                </a>
            </div>
        </div>

        {{-- Progress Tip --}}
        <div class="mt-8 text-center">
            <p class="text-gray-400 text-sm">
                <i class="fas fa-lightbulb text-yellow-400 mr-1"></i>
                เคล็ดลับ: เรียนตามลำดับจะช่วยให้เข้าใจเนื้อหาได้ดีขึ้น
            </p>
        </div>
    </div>
</div>
@endsection
