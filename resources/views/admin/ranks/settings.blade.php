@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า Rank: ' . ($rank->name_th ?? $rank->name))

@section('content')
{{--
/**
 * Admin Rank Settings
 *
 * จัดการเงื่อนไข (Requirements) และรางวัล (Bonuses) สำหรับแต่ละ Rank
 * - เพิ่ม/แก้ไข/ลบเงื่อนไขในการเลื่อนระดับ
 * - เพิ่ม/แก้ไข/ลบโบนัสและรางวัล
 * - ตั้งค่าสิทธิพิเศษ (Privileges)
 *
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

@php
    $rankBadges = [
        1 => '🥉', 2 => '🥈', 3 => '🥇', 4 => '💎',
        5 => '💠', 6 => '👑', 7 => '🏆', 8 => '⭐',
    ];
    $badge = $rankBadges[$rank->level] ?? '🏅';
@endphp

<div class="space-y-6" x-data="rankSettings()">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white/10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="text-6xl">{{ $badge }}</div>
                    <div>
                        <h1 class="text-3xl font-bold">ตั้งค่า {{ $rank->name_th ?? $rank->name }}</h1>
                        <p class="text-purple-100 mt-1">จัดการเงื่อนไข รางวัล และสิทธิพิเศษสำหรับ Level {{ $rank->level }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.ranks.index') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-lg rounded-xl hover:bg-white/30 transition flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับไปหน้า Ranks</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">📋</span>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rank->requirements->count() }}</div>
                    <div class="text-sm text-gray-500">เงื่อนไข</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">🎁</span>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rank->bonuses->count() }}</div>
                    <div class="text-sm text-gray-500">โบนัส/รางวัล</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">⭐</span>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($rank->privileges ?? []) }}</div>
                    <div class="text-sm text-gray-500">สิทธิพิเศษ</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rank->users_count ?? $rank->users()->count() }}</div>
                    <div class="text-sm text-gray-500">สมาชิก</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'requirements'"
                        :class="activeTab === 'requirements' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm transition">
                    <span class="text-xl mr-2">📋</span> เงื่อนไข (Requirements)
                </button>
                <button @click="activeTab = 'bonuses'"
                        :class="activeTab === 'bonuses' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm transition">
                    <span class="text-xl mr-2">🎁</span> โบนัส/รางวัล (Bonuses)
                </button>
                <button @click="activeTab = 'privileges'"
                        :class="activeTab === 'privileges' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm transition">
                    <span class="text-xl mr-2">⭐</span> สิทธิพิเศษ (Privileges)
                </button>
            </nav>
        </div>

        {{-- Requirements Tab --}}
        <div x-show="activeTab === 'requirements'" class="p-6">
            {{-- Add New Requirement --}}
            <div class="mb-6">
                <button @click="showAddRequirement = !showAddRequirement"
                        class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span>เพิ่มเงื่อนไขใหม่</span>
                </button>
            </div>

            {{-- Add Requirement Form --}}
            <div x-show="showAddRequirement" x-collapse class="mb-6">
                <form action="{{ route('admin.ranks.requirements.store', $rank) }}" method="POST"
                      class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6">
                    @csrf
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">เพิ่มเงื่อนไขใหม่</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทเงื่อนไข</label>
                            <select name="requirement_type" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                @foreach($requirementTypes as $key => $type)
                                    <option value="{{ $key }}">{{ $type['icon'] }} {{ $type['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค่าเป้าหมาย</label>
                            <input type="number" name="target_value" required step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ (EN)</label>
                            <input type="text" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ (TH)</label>
                            <input type="text" name="name_th"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตัวดำเนินการ</label>
                            <select name="operator" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value=">=">>= (มากกว่าหรือเท่ากับ)</option>
                                <option value=">">> (มากกว่า)</option>
                                <option value="=">= (เท่ากับ)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หน่วย</label>
                            <input type="text" name="unit" placeholder="เช่น คะแนน, คน, บาท"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (TH)</label>
                            <textarea name="description_th" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                        </div>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_required" value="1" checked
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">เป็นเงื่อนไขบังคับ</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลำดับความสำคัญ (Weight)</label>
                            <input type="number" name="weight" value="0" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" @click="showAddRequirement = false"
                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                            <i class="fas fa-save mr-2"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>

            {{-- Requirements List --}}
            <div class="space-y-4">
                @forelse($rank->requirements as $requirement)
                    <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl p-4"
                         x-data="{ editing: false }">
                        <div x-show="!editing">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center text-2xl">
                                        {{ $requirementTypes[$requirement->requirement_type]['icon'] ?? '📌' }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">
                                            {{ $requirement->name_th ?? $requirement->name }}
                                            @if($requirement->is_required)
                                                <span class="text-red-500 text-sm">*บังคับ</span>
                                            @endif
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $requirementTypes[$requirement->requirement_type]['name'] ?? $requirement->requirement_type }}
                                            {{ $requirement->operator }} {{ number_format($requirement->target_value) }}
                                            {{ $requirement->unit }}
                                        </p>
                                        @if($requirement->description_th)
                                            <p class="text-xs text-gray-500 mt-1">{{ $requirement->description_th }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $requirement->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $requirement->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <button @click="editing = true"
                                            class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.ranks.requirements.destroy', $requirement) }}" method="POST"
                                          onsubmit="return confirm('ยืนยันการลบเงื่อนไขนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Edit Form --}}
                        <div x-show="editing" x-cloak>
                            <form action="{{ route('admin.ranks.requirements.update', $requirement) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                                        <select name="requirement_type" required
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                            @foreach($requirementTypes as $key => $type)
                                                <option value="{{ $key }}" {{ $requirement->requirement_type === $key ? 'selected' : '' }}>
                                                    {{ $type['icon'] }} {{ $type['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค่าเป้าหมาย</label>
                                        <input type="number" name="target_value" value="{{ $requirement->target_value }}" required step="0.01"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (EN)</label>
                                        <input type="text" name="name" value="{{ $requirement->name }}" required
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (TH)</label>
                                        <input type="text" name="name_th" value="{{ $requirement->name_th }}"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตัวดำเนินการ</label>
                                        <select name="operator" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                            <option value=">=" {{ $requirement->operator === '>=' ? 'selected' : '' }}>>= (มากกว่าหรือเท่ากับ)</option>
                                            <option value=">" {{ $requirement->operator === '>' ? 'selected' : '' }}>> (มากกว่า)</option>
                                            <option value="=" {{ $requirement->operator === '=' ? 'selected' : '' }}>= (เท่ากับ)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หน่วย</label>
                                        <input type="text" name="unit" value="{{ $requirement->unit }}"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="is_required" value="1" {{ $requirement->is_required ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-sm">บังคับ</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="is_active" value="1" {{ $requirement->is_active ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-sm">เปิดใช้งาน</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weight</label>
                                        <input type="number" name="weight" value="{{ $requirement->weight }}" min="0"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end gap-2">
                                    <button type="button" @click="editing = false" class="px-3 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg text-sm">ยกเลิก</button>
                                    <button type="submit" class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm">บันทึก</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <div class="text-5xl mb-4">📋</div>
                        <p class="text-lg">ยังไม่มีเงื่อนไขสำหรับระดับนี้</p>
                        <p class="text-sm mt-1">คลิกปุ่ม "เพิ่มเงื่อนไขใหม่" เพื่อเริ่มต้น</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Bonuses Tab --}}
        <div x-show="activeTab === 'bonuses'" x-cloak class="p-6">
            {{-- Add New Bonus --}}
            <div class="mb-6">
                <button @click="showAddBonus = !showAddBonus"
                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span>เพิ่มโบนัส/รางวัลใหม่</span>
                </button>
            </div>

            {{-- Add Bonus Form --}}
            <div x-show="showAddBonus" x-collapse class="mb-6">
                <form action="{{ route('admin.ranks.bonuses.store', $rank) }}" method="POST"
                      class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6">
                    @csrf
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">เพิ่มโบนัส/รางวัลใหม่</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทโบนัส</label>
                            <select name="bonus_type" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                @foreach($bonusTypes as $key => $type)
                                    <option value="{{ $key }}">{{ $type['icon'] }} {{ $type['name'] }} - {{ $type['description'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (บาท)</label>
                            <input type="number" name="amount" step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ (EN)</label>
                            <input type="text" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ (TH)</label>
                            <input type="text" name="name_th"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เปอร์เซ็นต์ (%)</label>
                            <input type="number" name="percentage" step="0.01" min="0" max="100"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลำดับความสำคัญ</label>
                            <input type="number" name="priority" value="0" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (TH)</label>
                            <textarea name="description_th" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                        </div>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="auto_apply" value="1" checked
                                       class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">ให้โดยอัตโนมัติ</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" @click="showAddBonus = false"
                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                            <i class="fas fa-save mr-2"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>

            {{-- Bonuses List --}}
            <div class="space-y-4">
                @forelse($rank->bonuses as $bonus)
                    <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl p-4"
                         x-data="{ editing: false }">
                        <div x-show="!editing">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center text-2xl">
                                        {{ $bonusTypes[$bonus->bonus_type]['icon'] ?? '🎁' }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 dark:text-white">
                                            {{ $bonus->name_th ?? $bonus->name }}
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $bonusTypes[$bonus->bonus_type]['name'] ?? $bonus->bonus_type }}
                                            @if($bonus->amount > 0)
                                                - ฿{{ number_format($bonus->amount, 2) }}
                                            @endif
                                            @if($bonus->percentage > 0)
                                                - {{ $bonus->percentage }}%
                                            @endif
                                        </p>
                                        @if($bonus->description_th)
                                            <p class="text-xs text-gray-500 mt-1">{{ $bonus->description_th }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $bonus->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $bonus->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if($bonus->auto_apply)
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Auto</span>
                                    @endif
                                    <button @click="editing = true"
                                            class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.ranks.bonuses.destroy', $bonus) }}" method="POST"
                                          onsubmit="return confirm('ยืนยันการลบโบนัสนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Edit Form --}}
                        <div x-show="editing" x-cloak>
                            <form action="{{ route('admin.ranks.bonuses.update', $bonus) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                                        <select name="bonus_type" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                            @foreach($bonusTypes as $key => $type)
                                                <option value="{{ $key }}" {{ $bonus->bonus_type === $key ? 'selected' : '' }}>
                                                    {{ $type['icon'] }} {{ $type['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนเงิน</label>
                                        <input type="number" name="amount" value="{{ $bonus->amount }}" step="0.01"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (EN)</label>
                                        <input type="text" name="name" value="{{ $bonus->name }}" required
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (TH)</label>
                                        <input type="text" name="name_th" value="{{ $bonus->name_th }}"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เปอร์เซ็นต์</label>
                                        <input type="number" name="percentage" value="{{ $bonus->percentage }}" step="0.01"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ลำดับ</label>
                                        <input type="number" name="priority" value="{{ $bonus->priority }}" min="0"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="auto_apply" value="1" {{ $bonus->auto_apply ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                            <span class="text-sm">Auto Apply</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="is_active" value="1" {{ $bonus->is_active ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                            <span class="text-sm">เปิดใช้งาน</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end gap-2">
                                    <button type="button" @click="editing = false" class="px-3 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg text-sm">ยกเลิก</button>
                                    <button type="submit" class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm">บันทึก</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <div class="text-5xl mb-4">🎁</div>
                        <p class="text-lg">ยังไม่มีโบนัส/รางวัลสำหรับระดับนี้</p>
                        <p class="text-sm mt-1">คลิกปุ่ม "เพิ่มโบนัส/รางวัลใหม่" เพื่อเริ่มต้น</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Privileges Tab --}}
        <div x-show="activeTab === 'privileges'" x-cloak class="p-6">
            <form action="{{ route('admin.ranks.update-privileges', $rank) }}" method="POST">
                @csrf

                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">เลือกสิทธิพิเศษสำหรับระดับนี้</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สิทธิพิเศษที่เลือกจะแสดงให้สมาชิกในระดับนี้เห็น</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($availablePrivileges as $key => $privilege)
                        <label class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <input type="checkbox" name="privileges[]" value="{{ $key }}"
                                   {{ in_array($key, $rank->privileges ?? []) ? 'checked' : '' }}
                                   class="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <div class="flex items-start gap-3 flex-1">
                                <div class="text-3xl">{{ $privilege['icon'] }}</div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ $privilege['name_th'] ?? $privilege['name'] }}
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $privilege['description_th'] ?? $privilege['description'] }}
                                    </p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        <span>บันทึกสิทธิพิเศษ</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Link to other Rank settings --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.ranks.edit', $rank) }}"
           class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center text-2xl">
                ⚙️
            </div>
            <div>
                <div class="font-bold text-gray-900 dark:text-white">ตั้งค่าทั่วไป</div>
                <div class="text-sm text-gray-500">แก้ไขชื่อ, Commission Rate, ฯลฯ</div>
            </div>
        </a>
        <a href="{{ route('admin.ranks.avatar-frames') }}"
           class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center text-2xl">
                🖼️
            </div>
            <div>
                <div class="font-bold text-gray-900 dark:text-white">กรอบอวาต้าร์</div>
                <div class="text-sm text-gray-500">จัดการกรอบรูป Avatar</div>
            </div>
        </a>
        <a href="{{ route('admin.ranks.promotions.index') }}"
           class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center text-2xl">
                📈
            </div>
            <div>
                <div class="font-bold text-gray-900 dark:text-white">การเลื่อนระดับ</div>
                <div class="text-sm text-gray-500">ดูและอนุมัติการเลื่อนระดับ</div>
            </div>
        </a>
    </div>
</div>

@push('scripts')
<script>
    function rankSettings() {
        return {
            activeTab: 'requirements',
            showAddRequirement: false,
            showAddBonus: false,
        }
    }
</script>
@endpush
@endsection
