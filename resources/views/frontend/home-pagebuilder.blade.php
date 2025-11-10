@extends('layouts.windows')

@section('title', $renderData['page']['meta']['title'] ?? 'หน้าแรก')

@section('meta')
@if(!empty($renderData['page']['meta']['description']))
<meta name="description" content="{{ $renderData['page']['meta']['description'] }}">
@endif
@endsection

@section('content')

<!-- Page Builder Sections -->
@foreach($renderData['sections'] as $section)
    @php
        $componentName = str_replace('_', '-', $section['type']);
        $viewPath = "page-builder.sections.{$componentName}";
    @endphp

    @if(view()->exists($viewPath))
        @include($viewPath, ['section' => $section])
    @else
        <!-- Fallback for undefined section types -->
        <div class="py-20 bg-gray-100">
            <div class="container mx-auto px-4 text-center">
                <p class="text-gray-500">Section type "{{ $section['type'] }}" not found</p>
                <p class="text-sm text-gray-400">View: {{ $viewPath }}</p>
            </div>
        </div>
    @endif
@endforeach

@if(empty($renderData['sections']))
<!-- Empty State -->
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900">
    <div class="text-center text-white">
        <svg class="mx-auto h-24 w-24 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
        </svg>
        <h3 class="mt-4 text-2xl font-medium">ยังไม่มีเนื้อหา</h3>
        <p class="mt-2 text-white/70">กรุณาใช้ Page Builder เพื่อสร้างหน้าแรก</p>
        @auth
            @if(Auth::user()->is_admin)
            <a href="{{ route('admin.page-builder.index') }}" class="mt-6 inline-block px-6 py-3 bg-white text-purple-900 font-semibold rounded-lg hover:bg-gray-100 transition">
                ไปที่ Page Builder
            </a>
            @endif
        @endauth
    </div>
</div>
@endif

@endsection
