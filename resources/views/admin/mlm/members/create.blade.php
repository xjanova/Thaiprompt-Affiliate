@extends('layouts.admin')

@section('title', 'สร้างสมาชิก MLM')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.mlm.members.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
        ← กลับไปรายการสมาชิก MLM
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">สร้างสมาชิก MLM</h2>

        <!-- Create Form -->
        <form action="{{ route('admin.mlm.members.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- User Selection -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    เลือกผู้ใช้ <span class="text-red-500">*</span>
                </label>
                <select id="user_id"
                        name="user_id"
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('user_id') border-red-500 @enderror">
                    <option value="">-- เลือกผู้ใช้ --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    เลือกผู้ใช้ที่ยังไม่เป็นสมาชิก MLM
                </p>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- MLM Plan -->
            <div>
                <label for="mlm_plan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    แผน MLM <span class="text-red-500">*</span>
                </label>
                <select id="mlm_plan_id"
                        name="mlm_plan_id"
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('mlm_plan_id') border-red-500 @enderror">
                    <option value="">-- เลือกแผน MLM --</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ old('mlm_plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }} - {{ $plan->plan_type }}
                        </option>
                    @endforeach
                </select>
                @error('mlm_plan_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Unilevel Sponsor -->
            <div>
                <label for="unilevel_sponsor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ผู้สนับสนุน Unilevel <span class="text-red-500">*</span>
                </label>
                <select id="unilevel_sponsor_id"
                        name="unilevel_sponsor_id"
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('unilevel_sponsor_id') border-red-500 @enderror">
                    <option value="">-- เลือกผู้สนับสนุน Unilevel --</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" {{ old('unilevel_sponsor_id') == $member->id ? 'selected' : '' }}>
                            {{ $member->user->name }} ({{ $member->member_code }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    เลือกสมาชิกที่จะเป็นผู้สนับสนุนในระบบ Unilevel
                </p>
                @error('unilevel_sponsor_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Binary Sponsor (Optional) -->
            <div>
                <label for="binary_sponsor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ผู้สนับสนุน Binary (ไม่จำเป็น)
                </label>
                <select id="binary_sponsor_id"
                        name="binary_sponsor_id"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('binary_sponsor_id') border-red-500 @enderror">
                    <option value="">-- เลือกผู้สนับสนุน Binary (ถ้ามี) --</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" {{ old('binary_sponsor_id') == $member->id ? 'selected' : '' }}>
                            {{ $member->user->name }} ({{ $member->member_code }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    เลือกสมาชิกที่จะเป็นผู้สนับสนุนในระบบ Binary (หากไม่เลือก จะใช้ค่าเดียวกับ Unilevel Sponsor)
                </p>
                @error('binary_sponsor_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Binary Position (Optional) -->
            <div>
                <label for="binary_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ตำแหน่ง Binary (ไม่จำเป็น)
                </label>
                <select id="binary_position"
                        name="binary_position"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('binary_position') border-red-500 @enderror">
                    <option value="">-- เลือกตำแหน่ง Binary (ถ้ามี) --</option>
                    <option value="left" {{ old('binary_position') === 'left' ? 'selected' : '' }}>
                        ซ้าย (Left)
                    </option>
                    <option value="right" {{ old('binary_position') === 'right' ? 'selected' : '' }}>
                        ขวา (Right)
                    </option>
                </select>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    เลือกตำแหน่งในโครงสร้าง Binary (หากไม่เลือก ระบบจะหาตำแหน่งว่างให้อัตโนมัติ)
                </p>
                @error('binary_position')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Information Box -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 dark:border-blue-500 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>หมายเหตุ:</strong> การลงทะเบียนสมาชิก MLM จะสร้างโครงสร้างเครือข่ายในระบบ Unilevel และ Binary โปรดตรวจสอบข้อมูลให้ถูกต้องก่อนบันทึก
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-slate-700">
                <a href="{{ route('admin.mlm.members.index') }}"
                   class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 rounded-md hover:bg-gray-200 dark:hover:bg-slate-600">
                    ยกเลิก
                </a>

                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    สร้างสมาชิก MLM
                </button>
            </div>
        </form>
    </div>

    <!-- Tips Card -->
    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
        <h3 class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">💡 คำแนะนำ</h3>
        <ul class="text-sm text-yellow-800 dark:text-yellow-300 space-y-1">
            <li>• ตรวจสอบว่าผู้ใช้ที่เลือกยังไม่เป็นสมาชิก MLM อยู่แล้ว</li>
            <li>• เลือกแผน MLM ที่เหมาะสมกับผู้ใช้</li>
            <li>• ผู้สนับสนุน Unilevel จะเป็นคนที่อยู่เหนือในโครงสร้าง Unilevel</li>
            <li>• ผู้สนับสนุน Binary และตำแหน่งจะกำหนดตำแหน่งในโครงสร้าง Binary</li>
            <li>• หากไม่ระบุตำแหน่ง Binary ระบบจะหาตำแหน่งว่างให้อัตโนมัติ</li>
        </ul>
    </div>
</div>
@endsection
