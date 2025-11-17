@extends('layouts.admin-v3')

@section('title', 'สร้างสมาชิก MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-user-plus text-purple-600 dark:text-purple-400"></i>
                สร้างสมาชิก MLM
            </h1>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">เพิ่มสมาชิกใหม่เข้าสู่ระบบ MLM</p>
        </div>
        <a href="{{ route('admin.mlm.members.index') }}"
           class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            กลับ
        </a>
    </div>

    <div class="max-w-3xl mx-auto">
        <!-- Create Form Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6" border border-white/20 dark:border-white/10>
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-file-alt text-purple-600 dark:text-purple-400"></i>
                    ข้อมูลสมาชิก MLM
                </h2>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">กรอกข้อมูลสำหรับสร้างสมาชิก MLM ใหม่</p>
            </div>

            <!-- Create Form -->
            <form action="{{ route('admin.mlm.members.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- User Selection -->
                <div>
                    <label for="user_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-user text-purple-600 dark:text-purple-400"></i>
                        เลือกผู้ใช้ <span class="text-red-500">*</span>
                    </label>
                    <select id="user_id"
                            name="user_id"
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition @error('user_id') border-red-500 @enderror">
                        <option value="">-- เลือกผู้ใช้ --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 flex items-center gap-1">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        เลือกผู้ใช้ที่ยังไม่เป็นสมาชิก MLM
                    </p>
                    @error('user_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- MLM Plan -->
                <div>
                    <label for="mlm_plan_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-layer-group text-purple-600 dark:text-purple-400"></i>
                        แผน MLM <span class="text-red-500">*</span>
                    </label>
                    <select id="mlm_plan_id"
                            name="mlm_plan_id"
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition @error('mlm_plan_id') border-red-500 @enderror">
                        <option value="">-- เลือกแผน MLM --</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('mlm_plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} - {{ $plan->plan_type }}
                            </option>
                        @endforeach
                    </select>
                    @error('mlm_plan_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Unilevel Sponsor -->
                <div>
                    <label for="unilevel_sponsor_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-network-wired text-blue-600 dark:text-blue-400"></i>
                        ผู้สนับสนุน Unilevel <span class="text-red-500">*</span>
                    </label>
                    <select id="unilevel_sponsor_id"
                            name="unilevel_sponsor_id"
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition @error('unilevel_sponsor_id') border-red-500 @enderror">
                        <option value="">-- เลือกผู้สนับสนุน Unilevel --</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" {{ old('unilevel_sponsor_id') == $member->id ? 'selected' : '' }}>
                                {{ $member->user->name }} ({{ $member->member_code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 flex items-center gap-1">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        เลือกสมาชิกที่จะเป็นผู้สนับสนุนในระบบ Unilevel
                    </p>
                    @error('unilevel_sponsor_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Binary Sponsor (Optional) -->
                <div>
                    <label for="binary_sponsor_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-code-branch text-purple-600 dark:text-purple-400"></i>
                        ผู้สนับสนุน Binary
                        <span class="text-gray-500 dark:text-gray-400 text-xs font-normal">(ไม่จำเป็น)</span>
                    </label>
                    <select id="binary_sponsor_id"
                            name="binary_sponsor_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition @error('binary_sponsor_id') border-red-500 @enderror">
                        <option value="">-- เลือกผู้สนับสนุน Binary (ถ้ามี) --</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" {{ old('binary_sponsor_id') == $member->id ? 'selected' : '' }}>
                                {{ $member->user->name }} ({{ $member->member_code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 flex items-center gap-1">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        เลือกสมาชิกที่จะเป็นผู้สนับสนุนในระบบ Binary (หากไม่เลือก จะใช้ค่าเดียวกับ Unilevel Sponsor)
                    </p>
                    @error('binary_sponsor_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Binary Position (Optional) -->
                <div>
                    <label for="binary_position" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-arrows-alt-h text-purple-600 dark:text-purple-400"></i>
                        ตำแหน่ง Binary
                        <span class="text-gray-500 dark:text-gray-400 text-xs font-normal">(ไม่จำเป็น)</span>
                    </label>
                    <select id="binary_position"
                            name="binary_position"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition @error('binary_position') border-red-500 @enderror">
                        <option value="">-- เลือกตำแหน่ง Binary (ถ้ามี) --</option>
                        <option value="left" {{ old('binary_position') === 'left' ? 'selected' : '' }}>
                            <i class="fas fa-arrow-left"></i> ซ้าย (Left)
                        </option>
                        <option value="right" {{ old('binary_position') === 'right' ? 'selected' : '' }}>
                            <i class="fas fa-arrow-right"></i> ขวา (Right)
                        </option>
                    </select>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 flex items-center gap-1">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        เลือกตำแหน่งในโครงสร้าง Binary (หากไม่เลือก ระบบจะหาตำแหน่งว่างให้อัตโนมัติ)
                    </p>
                    @error('binary_position')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Information Box -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 dark:border-blue-400 p-4 rounded-xl">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">หมายเหตุสำคัญ</h4>
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                การลงทะเบียนสมาชิก MLM จะสร้างโครงสร้างเครือข่ายในระบบ Unilevel และ Binary โปรดตรวจสอบข้อมูลให้ถูกต้องก่อนบันทึก
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <a href="{{ route('admin.mlm.members.index') }}"
                       class="w-full sm:w-auto px-6 py-3 text-center text-gray-700 dark:text-gray-300 dark:text-gray-300 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i>
                        ยกเลิก
                    </a>

                    <button type="submit"
                            class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 dark:from-purple-500 dark:to-pink-500 dark:hover:from-purple-600 dark:hover:to-pink-600 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        สร้างสมาชิก MLM
                    </button>
                </div>
            </form>
        </div>

        <!-- Tips Card -->
        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl shadow-lg p-6">
            <h3 class="font-bold text-yellow-900 dark:text-yellow-300 mb-4 flex items-center gap-2 text-lg">
                <i class="fas fa-lightbulb text-yellow-600 dark:text-yellow-400"></i>
                คำแนะนำในการสร้างสมาชิก
            </h3>
            <ul class="text-sm text-yellow-800 dark:text-yellow-300 space-y-2">
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                    <span>ตรวจสอบว่าผู้ใช้ที่เลือกยังไม่เป็นสมาชิก MLM อยู่แล้ว</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                    <span>เลือกแผน MLM ที่เหมาะสมกับผู้ใช้</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                    <span>ผู้สนับสนุน Unilevel จะเป็นคนที่อยู่เหนือในโครงสร้าง Unilevel</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                    <span>ผู้สนับสนุน Binary และตำแหน่งจะกำหนดตำแหน่งในโครงสร้าง Binary</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                    <span>หากไม่ระบุตำแหน่ง Binary ระบบจะหาตำแหน่งว่างให้อัตโนมัติ</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
