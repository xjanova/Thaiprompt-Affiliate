{{--
    หน้าแสดงว่าบทเรียนถูกล็อค
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-v4')

@section('title', 'บทเรียนถูกล็อค - ศูนย์การเรียนรู้')

@section('content')
<div class="container-fluid px-4 py-6 max-w-3xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('user.academy.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <i class="fas fa-graduation-cap mr-1"></i>ศูนย์การเรียนรู้
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        @if($article->category)
        <a href="{{ route('user.academy.category', $article->category->slug) }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            {{ $article->category->name }}
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        @endif
        <span class="text-gray-900 dark:text-white font-medium">ล็อค</span>
    </nav>

    {{-- Locked Content --}}
    <div class="tp-card rounded-2xl overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-gray-600 to-gray-800 p-8 text-center">
            <div class="w-24 h-24 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lock text-5xl text-white/80"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-black text-white mb-2">บทเรียนนี้ถูกล็อค</h1>
            <p class="text-white/80">คุณต้องทำตามเงื่อนไขก่อนจึงจะเข้าถึงบทเรียนนี้ได้</p>
        </div>

        {{-- Article Info --}}
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full">
                    {{ $article->category->name ?? 'ทั่วไป' }}
                </span>
                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full">
                    {{ $article->difficulty_label ?? 'เริ่มต้น' }}
                </span>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $article->title }}</h2>
            @if($article->excerpt)
            <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $article->excerpt }}</p>
            @endif
        </div>

        {{-- Requirements --}}
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-clipboard-list text-purple-500"></i>
                เงื่อนไขในการปลดล็อค
            </h3>

            @if(isset($accessInfo['reason']))
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4">
                <p class="text-red-700 dark:text-red-400">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    {{ $accessInfo['reason'] }}
                </p>
            </div>
            @endif

            @if(isset($accessInfo['requirements']) && count($accessInfo['requirements']) > 0)
            <div class="space-y-3">
                @foreach($accessInfo['requirements'] as $req)
                <div class="flex items-start gap-3 p-4 {{ $req['completed'] ?? false ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-700/50' }} rounded-xl">
                    @if($req['completed'] ?? false)
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-white"></i>
                    </div>
                    @else
                    <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-circle text-gray-500 dark:text-gray-400 text-xs"></i>
                    </div>
                    @endif
                    <div>
                        <p class="font-medium {{ $req['completed'] ?? false ? 'text-green-800 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $req['label'] ?? $req['description'] ?? 'เงื่อนไข' }}
                        </p>
                        @if(isset($req['description']) && isset($req['label']))
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $req['description'] }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Prerequisites --}}
            @if($article->prerequisites && $article->prerequisites->count() > 0)
            <div class="mt-6">
                <h4 class="font-bold text-gray-900 dark:text-white mb-3">บทเรียนที่ต้องเรียนก่อน:</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($article->prerequisites as $prereq)
                    <a href="{{ route('user.academy.article', $prereq->slug) }}"
                       class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/30 transition">
                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-white"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-purple-900 dark:text-purple-300 truncate">{{ $prereq->title }}</p>
                            <p class="text-sm text-purple-600 dark:text-purple-400">คลิกเพื่อเริ่มเรียน</p>
                        </div>
                        <i class="fas fa-arrow-right text-purple-500"></i>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="p-6 bg-gray-50 dark:bg-gray-900/50">
            <div class="flex flex-col sm:flex-row gap-4">
                @if($article->category)
                <a href="{{ route('user.academy.category', $article->category->slug) }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition">
                    <i class="fas fa-folder"></i>
                    <span>ดูบทเรียนอื่นในหมวดนี้</span>
                </a>
                @endif
                <a href="{{ route('user.academy.index') }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับหน้าหลัก</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
