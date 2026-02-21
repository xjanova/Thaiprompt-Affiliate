@extends('layouts.admin-v3')

@section('title', 'จัดการ AI Providers')

@section('content')
<div x-data="aiProviders()" x-init="init()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                🤖 จัดการ AI Providers
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">จัดการ AI Providers และการเชื่อมต่อ API</p>
        </div>
        <button @click="openAddModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            เพิ่ม Provider
        </button>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-purple-600" x-text="providers.length">0</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</div>
        </div>
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-green-600" x-text="providers.filter(p => p.is_active).length">0</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">ใช้งานอยู่</div>
        </div>
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-blue-600" x-text="providers.filter(p => p.type === 'image' || p.type === 'both').length">0</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">รองรับภาพ</div>
        </div>
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-pink-600" x-text="providers.filter(p => p.type === 'video' || p.type === 'both').length">0</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">รองรับวิดีโอ</div>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-purple-500 border-t-transparent"></div>
    </div>

    {{-- Empty State --}}
    <div x-show="!loading && providers.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-6xl mb-4">🤖</div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">ยังไม่มี Provider</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">คลิก "เพิ่ม Provider" เพื่อเริ่มต้น</p>
    </div>

    {{-- Providers Grid --}}
    <div x-show="!loading && providers.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="provider in providers" :key="provider.id">
            <div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-purple-400 dark:hover:border-purple-500 hover:shadow-xl transition-all duration-300 overflow-hidden"
                 :class="{ 'opacity-50': !provider.is_active }">

                {{-- Card Header --}}
                <div class="p-5 pb-3">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            {{-- Logo --}}
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl"
                                 :class="provider.is_active ? 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/40 dark:to-pink-900/40' : 'bg-gray-100 dark:bg-gray-700'">
                                <template x-if="provider.logo_url">
                                    <img :src="provider.logo_url" class="w-8 h-8 rounded-lg object-contain" :alt="provider.name">
                                </template>
                                <template x-if="!provider.logo_url">
                                    <span>🤖</span>
                                </template>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white" x-text="provider.name"></h3>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="w-2 h-2 rounded-full" :class="provider.is_active ? 'bg-green-500 animate-pulse' : 'bg-red-400'"></span>
                                    <span class="text-xs" :class="provider.is_active ? 'text-green-600 dark:text-green-400' : 'text-red-500'" x-text="provider.is_active ? 'Active' : 'Inactive'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Actions Menu --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-700 rounded-xl shadow-xl border border-gray-200 dark:border-gray-600 py-1 z-50">
                                <button @click="openConfigModal(provider); open = false" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    ⚙️ ตั้งค่า API
                                </button>
                                <button @click="testConnection(provider.id); open = false" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    🔌 ทดสอบเชื่อมต่อ
                                </button>
                                <button @click="toggleActive(provider); open = false" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    <span x-text="provider.is_active ? '⏸️ ปิดใช้งาน' : '▶️ เปิดใช้งาน'"></span>
                                </button>
                                <button @click="openEditModal(provider); open = false" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    ✏️ แก้ไข
                                </button>
                                <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                <button @click="deleteProvider(provider.id); open = false" class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center gap-2">
                                    🗑️ ลบ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="px-5 pb-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2" x-text="provider.description || 'ไม่มีคำอธิบาย'"></p>
                    {{-- API Docs Link --}}
                    <a x-show="provider.api_docs_url" :href="provider.api_docs_url" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1 mt-2 text-xs text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 font-medium hover:underline transition">
                        🔑 ไปเอา API Key
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>

                {{-- Features Tags --}}
                <div class="px-5 pb-3 flex flex-wrap gap-1.5">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="{
                              'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300': provider.type === 'both',
                              'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': provider.type === 'image',
                              'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300': provider.type === 'video'
                          }"
                          x-text="provider.type === 'both' ? '🖼️ ภาพ+วิดีโอ' : (provider.type === 'image' ? '🖼️ ภาพ' : '🎬 วิดีโอ')">
                    </span>
                    <template x-for="feat in (provider.supported_features || [])" :key="feat">
                        <span class="px-2.5 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300" x-text="feat"></span>
                    </template>
                </div>

                {{-- Stats Footer --}}
                <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 gap-4 text-center">
                    <div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="(provider.generations_count || 0).toLocaleString()">0</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">การสร้าง</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold" :class="(provider.success_rate || 0) >= 80 ? 'text-green-600' : (provider.success_rate || 0) >= 50 ? 'text-yellow-600' : 'text-red-600'" x-text="(provider.success_rate || 0) + '%'">0%</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">สำเร็จ</div>
                    </div>
                </div>

                {{-- Wallet Pricing --}}
                <div x-show="provider.wallet_cost_per_image || provider.wallet_cost_per_video" class="px-5 py-2 bg-amber-50 dark:bg-amber-900/20 border-t border-amber-100 dark:border-amber-800/30 flex items-center gap-3 text-xs">
                    <span class="text-amber-700 dark:text-amber-400 font-medium">💰 ราคา:</span>
                    <span x-show="provider.wallet_cost_per_image" class="text-amber-600 dark:text-amber-300" x-text="'ภาพ ' + provider.wallet_cost_per_image + '฿'"></span>
                    <span x-show="provider.wallet_cost_per_video" class="text-amber-600 dark:text-amber-300" x-text="'วิดีโอ ' + provider.wallet_cost_per_video + '฿'"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Add/Edit Provider Modal --}}
    <div x-show="showModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="editingProvider ? '✏️ แก้ไข Provider' : '➕ เพิ่ม Provider ใหม่'"></h3>
                <button @click="showModal = false" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="saveProvider()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ Provider *</label>
                    <input type="text" x-model="form.name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                    <input type="text" x-model="form.slug" placeholder="auto-generated" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท *</label>
                    <select x-model="form.type" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="image">🖼️ ภาพอย่างเดียว</option>
                        <option value="video">🎬 วิดีโออย่างเดียว</option>
                        <option value="both">🖼️🎬 ทั้งภาพและวิดีโอ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea x-model="form.description" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo URL</label>
                    <input type="url" x-model="form.logo_url" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ฟีเจอร์ที่รองรับ</label>
                    <div class="flex flex-wrap gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" value="text-to-image" x-model="form.supported_features" class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Text to Image</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" value="text-to-video" x-model="form.supported_features" class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Text to Video</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" value="image-editing" x-model="form.supported_features" class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Image Editing</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" value="image-to-video" x-model="form.supported_features" class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Image to Video</span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.is_active" class="rounded text-purple-600 focus:ring-purple-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">ยกเลิก</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:from-purple-700 hover:to-pink-700 transition disabled:opacity-50 font-medium">
                        <span x-show="!saving" x-text="editingProvider ? 'บันทึก' : 'สร้าง Provider'"></span>
                        <span x-show="saving">กำลังบันทึก...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Config Modal (Dynamic per provider) --}}
    <div x-show="showConfigModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showConfigModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">⚙️ ตั้งค่า API - <span x-text="configProvider?.name"></span></h3>
            </div>
            <form @submit.prevent="saveConfig()" class="p-6 space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-3 text-sm text-blue-700 dark:text-blue-300">
                    ℹ️ กำหนด API credentials สำหรับเชื่อมต่อกับ provider
                </div>
                {{-- API Docs Link ใน Config Modal --}}
                <div x-show="configProvider?.api_docs_url" class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-3">
                    <a :href="configProvider?.api_docs_url" target="_blank" rel="noopener"
                       class="flex items-center gap-2 text-sm text-purple-700 dark:text-purple-300 hover:text-purple-900 dark:hover:text-purple-100 font-medium">
                        🔑 ไปเอา API Key ที่นี่
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <p class="text-xs text-purple-500 dark:text-purple-400 mt-1" x-text="configProvider?.api_docs_url"></p>
                </div>

                {{-- Dynamic Config Fields --}}
                <template x-for="field in getConfigFields()" :key="field.key">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <span x-text="field.label"></span>
                            <span x-show="field.required" class="text-red-500">*</span>
                        </label>
                        <div class="relative" x-show="field.type === 'password'">
                            <input :type="showApiKey ? 'text' : 'password'" x-model="configForm[field.key]" :required="field.required" :placeholder="field.placeholder || ''" class="w-full px-4 py-2.5 pr-12 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <button type="button" @click="showApiKey = !showApiKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showApiKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showApiKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/></svg>
                            </button>
                        </div>
                        <input x-show="field.type !== 'password'" :type="field.type || 'text'" x-model="configForm[field.key]" :required="field.required" :placeholder="field.placeholder || ''" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <p x-show="field.hint" class="text-xs text-gray-400 mt-1" x-text="field.hint"></p>
                    </div>
                </template>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showConfigModal = false" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">ยกเลิก</button>
                    <button type="button" @click="testConnection(configProvider?.id)" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition flex items-center gap-1">
                        🔌 ทดสอบ
                    </button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:from-purple-700 hover:to-pink-700 transition disabled:opacity-50 font-medium">
                        💾 บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50 max-w-sm">
        <div class="rounded-xl shadow-2xl p-4 flex items-center gap-3" :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
            <span x-text="toast.type === 'success' ? '✅' : '❌'" class="text-xl"></span>
            <span x-text="toast.message" class="text-sm font-medium"></span>
        </div>
    </div>
</div>

<script>
function aiProviders() {
    return {
        providers: [],
        loading: true,
        saving: false,
        showModal: false,
        showConfigModal: false,
        showApiKey: false,
        editingProvider: null,
        configProvider: null,
        toast: { show: false, message: '', type: 'success' },
        form: { name: '', slug: '', type: 'image', description: '', logo_url: '', supported_features: [], is_active: true },
        configForm: {},

        // กำหนด config fields เฉพาะ provider
        providerConfigDefs: {
            'together-ai': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' },
                { key: 'api_endpoint', label: 'API Endpoint', type: 'url', required: false, encrypted: false, hint: 'ค่าเริ่มต้น: https://api.together.xyz/v1', placeholder: 'https://api.together.xyz/v1' }
            ],
            'cloudflare-ai': [
                { key: 'api_key', label: 'API Token', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' },
                { key: 'account_id', label: 'Account ID', type: 'text', required: true, encrypted: false, hint: 'อยู่ในหน้า Dashboard ของ Cloudflare', placeholder: 'เช่น 1a2b3c4d5e...' },
                { key: 'api_endpoint', label: 'API Endpoint', type: 'url', required: false, encrypted: false, hint: 'ค่าเริ่มต้น: https://api.cloudflare.com/client/v4', placeholder: 'https://api.cloudflare.com/client/v4' }
            ],
            'grok': [
                { key: 'api_key', label: 'API Key (xAI)', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xai-xxx...' },
                { key: 'api_endpoint', label: 'API Endpoint', type: 'url', required: false, encrypted: false, hint: 'ค่าเริ่มต้น: https://api.x.ai/v1', placeholder: 'https://api.x.ai/v1' }
            ],
            'openai': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'sk-xxx...' },
                { key: 'api_endpoint', label: 'API Endpoint', type: 'url', required: false, encrypted: false, hint: 'ค่าเริ่มต้น: https://api.openai.com/v1', placeholder: 'https://api.openai.com/v1' }
            ],
            'stability-ai': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'sk-xxx...' },
                { key: 'api_endpoint', label: 'API Endpoint', type: 'url', required: false, encrypted: false, placeholder: 'https://api.stability.ai/v2beta' }
            ],
            'fal-ai': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' },
                { key: 'api_endpoint', label: 'API Endpoint', type: 'url', required: false, encrypted: false, placeholder: 'https://fal.run' }
            ],
            'bfl': [
                { key: 'api_key', label: 'API Key (X-Key)', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' }
            ],
            'replicate': [
                { key: 'api_key', label: 'API Token', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'r8_xxx...' },
                { key: 'api_endpoint', label: 'API Endpoint', type: 'url', required: false, encrypted: false, placeholder: 'https://api.replicate.com/v1' }
            ],
            'ideogram': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' }
            ],
            'leonardo-ai': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' }
            ],
            'runway-ml': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' }
            ],
            'luma-ai': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' }
            ],
            'kling-ai': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' }
            ],
            'minimax': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล', placeholder: 'xxx...' }
            ],
            'pollinations': [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 สมัครฟรีที่ enter.pollinations.ai แล้วนำ key มาใส่', placeholder: 'sk_...' }
            ],
            'huggingface': [
                { key: 'api_key', label: 'API Token', type: 'password', required: true, encrypted: true, hint: '🔒 สมัครฟรีที่ huggingface.co → Settings → Access Tokens → สร้าง token ใหม่', placeholder: 'hf_...' }
            ],
        },

        async init() {
            await this.loadProviders();
        },

        async loadProviders() {
            this.loading = true;
            try {
                const res = await fetch('/admin/ai-gen/providers', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) this.providers = data.data;
            } catch (e) {
                this.showToast('โหลดข้อมูลไม่สำเร็จ', 'error');
            }
            this.loading = false;
        },

        openAddModal() {
            this.editingProvider = null;
            this.form = { name: '', slug: '', type: 'image', description: '', logo_url: '', supported_features: [], is_active: true };
            this.showModal = true;
        },

        openEditModal(provider) {
            this.editingProvider = provider;
            this.form = {
                name: provider.name,
                slug: provider.slug,
                type: provider.type,
                description: provider.description || '',
                logo_url: provider.logo_url || '',
                supported_features: provider.supported_features || [],
                is_active: provider.is_active
            };
            this.showModal = true;
        },

        async saveProvider() {
            this.saving = true;
            try {
                const url = this.editingProvider
                    ? `/admin/ai-gen/providers/${this.editingProvider.id}`
                    : '/admin/ai-gen/providers';
                const method = this.editingProvider ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    this.showModal = false;
                    await this.loadProviders();
                    this.showToast(this.editingProvider ? 'อัพเดทสำเร็จ' : 'สร้าง Provider สำเร็จ', 'success');
                } else {
                    this.showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.saving = false;
        },

        // ดึง config fields ตาม provider slug
        getConfigFields() {
            if (!this.configProvider) return [];
            const slug = this.configProvider.slug;
            return this.providerConfigDefs[slug] || [
                { key: 'api_key', label: 'API Key', type: 'password', required: true, encrypted: true, hint: '🔒 จะถูกเข้ารหัสในฐานข้อมูล' },
                { key: 'api_endpoint', label: 'API Endpoint', type: 'url', required: false, encrypted: false, placeholder: 'https://api.example.com/v1' }
            ];
        },

        openConfigModal(provider) {
            this.configProvider = provider;
            this.showApiKey = false;

            // เตรียม form ตาม config fields ของ provider
            const fields = this.providerConfigDefs[provider.slug] || [
                { key: 'api_key' }, { key: 'api_endpoint' }
            ];
            const form = {};
            fields.forEach(f => { form[f.key] = ''; });

            // โหลดค่า config ที่มีอยู่ (ใช้ config_key + config_value)
            if (provider.configs && Array.isArray(provider.configs)) {
                provider.configs.forEach(c => {
                    const key = c.config_key || c.key;
                    const val = c.config_value || c.value;
                    if (form.hasOwnProperty(key)) {
                        // ถ้าเป็น encrypted ไม่แสดงค่าเดิม (เพราะเข้ารหัสอยู่)
                        form[key] = c.is_encrypted ? '' : (val || '');
                    }
                });
            }

            this.configForm = form;
            this.showConfigModal = true;
        },

        async saveConfig() {
            this.saving = true;
            try {
                // สร้าง configs array จาก dynamic fields
                const fields = this.getConfigFields();
                const configs = fields
                    .filter(f => this.configForm[f.key] !== '' && this.configForm[f.key] !== undefined)
                    .map(f => ({
                        key: f.key,
                        value: this.configForm[f.key],
                        is_encrypted: f.encrypted || false
                    }));

                if (configs.length === 0) {
                    this.showToast('กรุณากรอก API Key', 'error');
                    this.saving = false;
                    return;
                }

                const res = await fetch(`/admin/ai-gen/providers/${this.configProvider.id}/config`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ configs })
                });
                const data = await res.json();
                if (data.success) {
                    this.showConfigModal = false;
                    await this.loadProviders();
                    this.showToast('บันทึกตั้งค่าสำเร็จ', 'success');
                } else {
                    this.showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.saving = false;
        },

        async testConnection(providerId) {
            try {
                // บันทึก config ก่อนทดสอบ (ถ้ากรอกไว้)
                const fields = this.getConfigFields();
                const configs = fields
                    .filter(f => this.configForm[f.key] !== '' && this.configForm[f.key] !== undefined)
                    .map(f => ({
                        key: f.key,
                        value: this.configForm[f.key],
                        is_encrypted: f.encrypted || false
                    }));

                if (configs.length > 0) {
                    this.showToast('💾 กำลังบันทึกแล้วทดสอบ...', 'success');
                    await fetch(`/admin/ai-gen/providers/${providerId}/config`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ configs })
                    });
                }

                this.showToast('🔌 กำลังทดสอบเชื่อมต่อ...', 'success');
                const res = await fetch(`/admin/ai-gen/providers/${providerId}/test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await res.json();
                this.showToast(data.success ? '✅ เชื่อมต่อสำเร็จ!' : (data.message || data.error || 'เชื่อมต่อไม่สำเร็จ'), data.success ? 'success' : 'error');
            } catch (e) {
                this.showToast('ไม่สามารถทดสอบได้', 'error');
            }
        },

        async toggleActive(provider) {
            try {
                const res = await fetch(`/admin/ai-gen/providers/${provider.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ is_active: !provider.is_active })
                });
                const data = await res.json();
                if (data.success) {
                    await this.loadProviders();
                    this.showToast(provider.is_active ? 'ปิดใช้งานแล้ว' : 'เปิดใช้งานแล้ว', 'success');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async deleteProvider(id) {
            if (!confirm('ต้องการลบ Provider นี้?')) return;
            try {
                const res = await fetch(`/admin/ai-gen/providers/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await res.json();
                if (data.success) {
                    await this.loadProviders();
                    this.showToast('ลบ Provider สำเร็จ', 'success');
                } else {
                    this.showToast(data.error || 'ลบไม่สำเร็จ', 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => this.toast.show = false, 3000);
        }
    }
}
</script>
@endsection
