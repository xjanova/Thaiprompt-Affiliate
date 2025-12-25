@extends('layouts.admin-v3')

@section('title', 'แก้ไขบทบาท')

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายการบทบาท
        </a>
    </div>

    {{-- Premium Hero Header (Orange-Amber-Yellow for Edit) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-500 dark:from-orange-700 dark:via-amber-700 dark:to-yellow-700 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-user-edit"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-user-edit text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">แก้ไขบทบาท</h1>
                    <p class="text-orange-100 text-lg mt-1">แก้ไขข้อมูลและสิทธิ์ของบทบาท: {{ $role->display_name }}</p>
                </div>
            </div>
        </div>
    </div>

<div class="max-w-4xl mx-auto">

    {{-- Form Card --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-edit text-orange-600 dark:text-orange-400"></i>
            แก้ไขข้อมูลบทบาท
        </h2>

        <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            {{-- Role Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อบทบาท (ภาษาอังกฤษ) <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name', $role->name) }}"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('name') border-red-500 @enderror"
                       placeholder="เช่น manager, supervisor"
                       required>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใช้ตัวพิมพ์เล็ก ไม่มีช่องว่าง สำหรับระบบภายใน</p>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Display Name --}}
            <div>
                <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อที่แสดง <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="display_name"
                       name="display_name"
                       value="{{ old('display_name', $role->display_name) }}"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('display_name') border-red-500 @enderror"
                       placeholder="เช่น ผู้จัดการ, ผู้ดูแลระบบ"
                       required>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ชื่อที่จะแสดงให้ผู้ใช้เห็น</p>
                @error('display_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    คำอธิบาย
                </label>
                <textarea id="description"
                          name="description"
                          rows="3"
                          class="w-full px-4 py-3 border-2 border-gray-200 dark:border-white/20 bg-white/90 dark:bg-white/10 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-xl focus:outline-none transition-all duration-200 @error('description') border-red-500 @enderror"
                          placeholder="อธิบายหน้าที่และความรับผิดชอบของบทบาทนี้">{{ old('description', $role->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- System Role Notice --}}
            @if($role->is_system_role)
            <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 backdrop-blur-sm border-l-4 border-yellow-400 dark:border-yellow-500 rounded-xl p-6 shadow-lg">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-yellow-400/20 dark:bg-yellow-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-yellow-900 dark:text-yellow-300 mb-2 text-lg">⚠️ บทบาทระบบ</h3>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            นี่คือบทบาทระบบที่สำคัญต่อการทำงานของแอปพลิเคชัน กรุณาระมัดระวังในการแก้ไข
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Permissions --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fas fa-shield-alt text-indigo-600 dark:text-indigo-400 mr-2"></i>
                    สิทธิ์การเข้าถึง
                </label>

                @if($permissions->count() > 0)
                    <div class="space-y-4">
                        @foreach($permissions as $category => $categoryPermissions)
                            <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm border-2 border-gray-200 dark:border-white/20 rounded-xl p-6 shadow-sm">
                                <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    {{ \App\Models\Permission::getCategoryDisplayName($category) }}
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($categoryPermissions as $permission)
                                        <label class="flex items-start cursor-pointer hover:bg-white/50 dark:hover:bg-white/10 p-3 rounded-lg transition-all">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission->id }}"
                                                   {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}
                                                   class="mt-1 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $permission->display_name }}
                                                </div>
                                                @if($permission->description)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $permission->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm">ยังไม่มีสิทธิ์ในระบบ</p>
                @endif

                @error('permissions')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-white/10">
                <a href="{{ route('admin.roles.index') }}"
                   class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-white/60 dark:bg-white/10 backdrop-blur-sm border border-gray-200 dark:border-white/20 rounded-xl hover:bg-gray-100 dark:hover:bg-white/20 transition-all duration-200">
                    <i class="fas fa-times mr-2"></i>
                    ยกเลิก
                </a>

                <button type="submit"
                        style="background: var(--arrow-x-primary-gradient)"
                        class="px-6 py-2.5 text-white font-semibold rounded-xl focus:outline-none shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                    <i class="fas fa-save mr-2"></i>
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>

    @if(session('error'))
    <div class="mt-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl">
        {{ session('error') }}
    </div>
    @endif
</div>
</div>
@endsection
