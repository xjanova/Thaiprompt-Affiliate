@extends('layouts.admin')

@section('title', $pageTitle ?? 'เพิ่มผู้ให้บริการใหม่')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
            <i class="fas fa-user-plus mr-3"></i>{{ $pageTitle ?? 'เพิ่มผู้ให้บริการใหม่' }}
        </h1>
    </div>

    <form action="{{ route('admin.service-providers.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl space-y-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลือกผู้ใช้ <span class="text-red-500">*</span></label>
                <select name="user_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 @error('user_id') border-red-500 @enderror" required>
                    <option value="">-- เลือกผู้ใช้ --</option>
                    @foreach(\App\Models\User::whereDoesntHave('serviceProvider')->orderBy('name')->get() as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                @error('user_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อที่แสดง <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 @error('name') border-red-500 @enderror" required>
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เบอร์โทร <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 @error('phone') border-red-500 @enderror" required>
                    @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อีเมล</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ที่อยู่</label>
                <textarea name="address" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ old('address') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รายละเอียด/แนะนำตัว</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขบัตรประชาชน</label>
                <input type="text" name="id_card_number" value="{{ old('id_card_number') }}" maxlength="13" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 font-mono">
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl hover:shadow-lg transition">
                <i class="fas fa-save mr-2"></i>บันทึก
            </button>
            <a href="{{ route('admin.service-providers.index') }}" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-xl hover:bg-gray-300 transition text-center">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
