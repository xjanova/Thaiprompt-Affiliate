@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบภารกิจดูคลิป')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                ⚙️ ตั้งค่าระบบภารกิจดูคลิป
            </h1>
            <p class="text-gray-600 dark:text-gray-400">กำหนดค่าทั่วไปของระบบ</p>
        </div>
        <a href="{{ route('admin.video-missions.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
            ← กลับ Dashboard
        </a>
    </div>

    <form action="{{ route('admin.video-missions.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            {{-- General Settings --}}
            @if($settings->has('general'))
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎯</span> ตั้งค่าทั่วไป
                </h2>
                <div class="space-y-4">
                    @foreach($settings->get('general') as $setting)
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $setting->label }}</p>
                            @if($setting->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                            @endif
                        </div>
                        <div>
                            @if($setting->type === 'boolean')
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="settings[{{ $setting->key }}]" value="1"
                                       {{ $setting->value === 'true' || $setting->value === '1' ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-pink-600"></div>
                            </label>
                            @elseif($setting->type === 'number')
                            <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                   class="w-32 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-right focus:ring-2 focus:ring-pink-500">
                            @else
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                   class="w-48 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Anti-Cheat Settings --}}
            @if($settings->has('anti_cheat'))
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🛡️</span> Anti-Cheat
                </h2>
                <div class="space-y-4">
                    @foreach($settings->get('anti_cheat') as $setting)
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $setting->label }}</p>
                            @if($setting->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                            @endif
                        </div>
                        <div>
                            @if($setting->type === 'boolean')
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="settings[{{ $setting->key }}]" value="1"
                                       {{ $setting->value === 'true' || $setting->value === '1' ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-pink-600"></div>
                            </label>
                            @elseif($setting->type === 'number')
                            <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                   class="w-32 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-right focus:ring-2 focus:ring-pink-500">
                            @else
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                   class="w-48 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Limits Settings --}}
            @if($settings->has('limits'))
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>📊</span> Limits
                </h2>
                <div class="space-y-4">
                    @foreach($settings->get('limits') as $setting)
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $setting->label }}</p>
                            @if($setting->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                            @endif
                        </div>
                        <div>
                            @if($setting->type === 'number')
                            <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                   class="w-32 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-right focus:ring-2 focus:ring-pink-500">
                            @else
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                   class="w-48 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- If no settings exist yet --}}
            @if($settings->isEmpty())
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-12 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 text-center">
                <div class="text-5xl mb-4">⚙️</div>
                <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีการตั้งค่าในระบบ</p>
                <p class="text-sm text-gray-400 dark:text-gray-500">การตั้งค่าจะถูกสร้างอัตโนมัติเมื่อรัน Seeder</p>
            </div>
            @else
            {{-- Submit --}}
            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all shadow-lg hover:shadow-pink-500/25">
                    💾 บันทึกการตั้งค่า
                </button>
            </div>
            @endif
        </div>
    </form>
</div>
@endsection
