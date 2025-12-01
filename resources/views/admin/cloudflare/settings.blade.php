@extends('layouts.admin-v3')

@section('title', 'Cloudflare Settings')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-gray-600 via-gray-700 to-slate-800 dark:from-gray-700 dark:via-gray-800 dark:to-slate-900 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center">
            <a href="{{ route('admin.cloudflare.index') }}" class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="w-14 h-14 rounded-xl backdrop-blur-sm flex items-center justify-center bg-white/20 mr-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Settings</h1>
                <p class="text-gray-300">ตั้งค่า Cloudflare API</p>
            </div>
        </div>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">API Configuration</h3>

            <div class="space-y-6">
                <!-- Zone ID -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zone ID</label>
                    <div class="flex items-center">
                        <input type="text"
                               value="{{ $zoneId ? str_repeat('*', 8) . substr($zoneId, -8) : 'ไม่ได้ตั้งค่า' }}"
                               readonly
                               class="flex-1 rounded-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-4 py-2 text-gray-900 dark:text-gray-100">
                        @if($zoneId)
                        <span class="ml-2 px-3 py-1 text-xs font-medium text-green-800 bg-green-100 dark:bg-green-900/50 dark:text-green-300 rounded-full">
                            ตั้งค่าแล้ว
                        </span>
                        @else
                        <span class="ml-2 px-3 py-1 text-xs font-medium text-red-800 bg-red-100 dark:bg-red-900/50 dark:text-red-300 rounded-full">
                            ยังไม่ได้ตั้งค่า
                        </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตั้งค่าใน .env: CLOUDFLARE_ZONE_ID</p>
                </div>

                <!-- API Token -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Token</label>
                    <div class="flex items-center">
                        <input type="text"
                               value="{{ $hasApiToken ? '••••••••••••••••••••' : 'ไม่ได้ตั้งค่า' }}"
                               readonly
                               class="flex-1 rounded-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-4 py-2 text-gray-900 dark:text-gray-100">
                        @if($hasApiToken)
                        <span class="ml-2 px-3 py-1 text-xs font-medium text-green-800 bg-green-100 dark:bg-green-900/50 dark:text-green-300 rounded-full">
                            ตั้งค่าแล้ว
                        </span>
                        @else
                        <span class="ml-2 px-3 py-1 text-xs font-medium text-red-800 bg-red-100 dark:bg-red-900/50 dark:text-red-300 rounded-full">
                            ยังไม่ได้ตั้งค่า
                        </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตั้งค่าใน .env: CLOUDFLARE_API_TOKEN</p>
                </div>

                <!-- Status -->
                <div class="pt-4 border-t border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">สถานะการเชื่อมต่อ</span>
                        @if($isConfigured)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            พร้อมใช้งาน
                        </span>
                        @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            ยังไม่พร้อม
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Help -->
            <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">วิธีตั้งค่า Cloudflare API</h4>
                <ol class="text-sm text-blue-800 dark:text-blue-300 space-y-2 list-decimal list-inside">
                    <li>ไปที่ <a href="https://dash.cloudflare.com" target="_blank" class="underline">Cloudflare Dashboard</a></li>
                    <li>เลือก domain ของคุณ</li>
                    <li>Zone ID อยู่ที่ sidebar ขวามือ ใต้ "API"</li>
                    <li>สำหรับ API Token: ไปที่ My Profile → API Tokens → Create Token</li>
                    <li>ใช้ template "Edit zone DNS" หรือสร้าง custom token ที่มี permissions:
                        <ul class="ml-4 mt-1 list-disc">
                            <li>Zone - Zone - Read</li>
                            <li>Zone - Cache Purge - Purge</li>
                            <li>Zone - DNS - Edit (ถ้าต้องการจัดการ DNS)</li>
                            <li>Zone - Zone Settings - Edit (ถ้าต้องการเปลี่ยน Security Level)</li>
                        </ul>
                    </li>
                    <li>เพิ่มใน .env file:
                        <pre class="mt-2 p-2 bg-blue-100 dark:bg-blue-800 rounded text-xs">CLOUDFLARE_ZONE_ID=your_zone_id
CLOUDFLARE_API_TOKEN=your_api_token</pre>
                    </li>
                    <li>รัน <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">php artisan config:clear</code></li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
