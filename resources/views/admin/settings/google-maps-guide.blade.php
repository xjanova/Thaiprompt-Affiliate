{{--
/**
 * Google Maps API Setup Guide - คู่มือการตั้งค่า Google Maps API
 *
 * @uses layouts/admin-v3.blade.php
 * @version 3.0
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'คู่มือการตั้งค่า Google Maps API')
@section('page-title', 'Google Maps Setup Guide')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="text-center">
        <h1 class="text-4xl font-bold text-white drop-shadow-lg mb-2">
            <i class="fas fa-map-marked-alt mr-3"></i>
            คู่มือการตั้งค่า Google Maps API
        </h1>
        <p class="text-white/70 text-lg">
            ขั้นตอนการสร้าง API Key และเปิดใช้งาน Google Maps Platform
        </p>
    </div>

    {{-- Step 1: Create Google Cloud Project --}}
    <div class="glass-fusion rounded-2xl p-8 border border-white/30 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white text-xl font-bold">
                1
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-white mb-3">
                    สร้าง Google Cloud Project
                </h2>
                <div class="space-y-3 text-white/80">
                    <p>1.1 เข้าสู่ <a href="https://console.cloud.google.com/" target="_blank" class="text-blue-400 hover:underline">Google Cloud Console</a></p>
                    <p>1.2 คลิก "Select a project" → "New Project"</p>
                    <p>1.3 ตั้งชื่อโปรเจค เช่น "MyApp-Maps" → คลิก "Create"</p>
                </div>
                <div class="mt-4 p-4 bg-yellow-500/20 border border-yellow-500/50 rounded-lg">
                    <p class="text-yellow-300 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>หมายเหตุ:</strong> จำเป็นต้องมี Google Account และอาจต้องยืนยันบัตรเครดิต (แต่จะไม่มีค่าใช้จ่ายหากไม่เกิน Free Tier)
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 2: Enable APIs --}}
    <div class="glass-fusion rounded-2xl p-8 border border-white/30 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white text-xl font-bold">
                2
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-white mb-3">
                    เปิดใช้งาน APIs ที่จำเป็น
                </h2>
                <div class="space-y-3 text-white/80">
                    <p>2.1 ไปที่เมนู "APIs & Services" → "Library"</p>
                    <p>2.2 ค้นหาและเปิดใช้งาน APIs ต่อไปนี้:</p>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="p-3 bg-white/10 rounded-lg">
                        <div class="flex items-center gap-2 text-white font-medium">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>Maps JavaScript API</span>
                        </div>
                        <p class="text-white/60 text-sm mt-1">สำหรับแสดงแผนที่</p>
                    </div>
                    <div class="p-3 bg-white/10 rounded-lg">
                        <div class="flex items-center gap-2 text-white font-medium">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>Geocoding API</span>
                        </div>
                        <p class="text-white/60 text-sm mt-1">แปลงที่อยู่ ⇔ พิกัด</p>
                    </div>
                    <div class="p-3 bg-white/10 rounded-lg">
                        <div class="flex items-center gap-2 text-white font-medium">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>Directions API</span>
                        </div>
                        <p class="text-white/60 text-sm mt-1">คำนวณเส้นทาง</p>
                    </div>
                    <div class="p-3 bg-white/10 rounded-lg">
                        <div class="flex items-center gap-2 text-white font-medium">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>Distance Matrix API</span>
                        </div>
                        <p class="text-white/60 text-sm mt-1">คำนวณระยะทางหลายจุด</p>
                    </div>
                    <div class="p-3 bg-white/10 rounded-lg">
                        <div class="flex items-center gap-2 text-white font-medium">
                            <i class="fas fa-check-circle text-green-400"></i>
                            <span>Places API</span>
                        </div>
                        <p class="text-white/60 text-sm mt-1">ค้นหาสถานที่</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3: Create API Key --}}
    <div class="glass-fusion rounded-2xl p-8 border border-white/30 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center text-white text-xl font-bold">
                3
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-white mb-3">
                    สร้าง API Key
                </h2>
                <div class="space-y-3 text-white/80">
                    <p>3.1 ไปที่ "APIs & Services" → "Credentials"</p>
                    <p>3.2 คลิก "+ CREATE CREDENTIALS" → "API key"</p>
                    <p>3.3 คัดลอก API Key ที่ได้ เช่น: <code class="px-2 py-1 bg-black/30 rounded font-mono text-sm">AIzaSyBxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</code></p>
                </div>
                <div class="mt-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
                    <p class="text-red-300 text-sm">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>⚠️ สำคัญมาก:</strong> อย่าเปิดเผย API Key ให้ผู้อื่น และควรจำกัดการใช้งานด้วย API restrictions
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 4: Secure API Key --}}
    <div class="glass-fusion rounded-2xl p-8 border border-white/30 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-red-500 rounded-full flex items-center justify-center text-white text-xl font-bold">
                4
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-white mb-3">
                    จำกัดการใช้งาน API Key (Recommended)
                </h2>
                <div class="space-y-3 text-white/80">
                    <p>4.1 คลิกที่ API Key ที่สร้างไว้</p>
                    <p>4.2 ในส่วน "API restrictions":</p>
                    <ul class="list-disc list-inside pl-4 space-y-1">
                        <li>เลือก "Restrict key"</li>
                        <li>เลือก APIs ที่ต้องการใช้งาน (ตามขั้นตอนที่ 2)</li>
                    </ul>
                    <p>4.3 ในส่วน "Application restrictions" (ถ้าใช้ในเว็บ):</p>
                    <ul class="list-disc list-inside pl-4 space-y-1">
                        <li>เลือก "HTTP referrers"</li>
                        <li>เพิ่ม domain ของคุณ เช่น <code class="px-1 bg-black/30 rounded text-sm">https://yourdomain.com/*</code></li>
                    </ul>
                    <p>4.4 คลิก "Save"</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 5: Set up in Laravel --}}
    <div class="glass-fusion rounded-2xl p-8 border border-white/30 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center text-white text-xl font-bold">
                5
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-white mb-3">
                    ตั้งค่าใน Laravel Application
                </h2>
                <div class="space-y-4 text-white/80">
                    <div>
                        <p class="mb-2">5.1 วิธีที่ 1: ใช้หน้าตั้งค่าใน Admin Panel (แนะนำ)</p>
                        <ul class="list-disc list-inside pl-4 space-y-1">
                            <li>ไปที่ <a href="{{ route('admin.settings.google-maps') }}" class="text-blue-400 hover:underline">Admin > Settings > Google Maps</a></li>
                            <li>วาง API Key ในช่อง "Google Maps API Key"</li>
                            <li>คลิก "บันทึกการตั้งค่า"</li>
                        </ul>
                    </div>
                    <div>
                        <p class="mb-2">5.2 วิธีที่ 2: แก้ไข .env file โดยตรง</p>
                        <div class="bg-black/50 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                            <pre class="text-green-400">GOOGLE_MAPS_API_KEY=<span class="text-yellow-400">AIzaSyBxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</span>
GOOGLE_MAPS_GEOCODING_ENABLED=<span class="text-blue-400">true</span>
GOOGLE_MAPS_DIRECTIONS_ENABLED=<span class="text-blue-400">true</span>
GOOGLE_MAPS_CACHE_TTL=<span class="text-purple-400">86400</span></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pricing Information --}}
    <div class="glass-fusion rounded-2xl p-8 border border-white/30 shadow-2xl">
        <h2 class="text-2xl font-bold text-white mb-4">
            <i class="fas fa-money-bill-wave mr-2"></i>
            ข้อมูลราคา (Pricing)
        </h2>
        <div class="space-y-3 text-white/80">
            <p>Google Maps Platform มี Free Tier $200/เดือน (~6,800 บาท)</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="p-4 bg-white/10 rounded-lg">
                    <div class="font-bold text-white mb-2">Geocoding API</div>
                    <div class="text-sm">$5 ต่อ 1,000 requests</div>
                    <div class="text-xs text-white/60 mt-1">Free: 40,000 requests/เดือน</div>
                </div>
                <div class="p-4 bg-white/10 rounded-lg">
                    <div class="font-bold text-white mb-2">Directions API</div>
                    <div class="text-sm">$5 ต่อ 1,000 requests</div>
                    <div class="text-xs text-white/60 mt-1">Free: 40,000 requests/เดือน</div>
                </div>
                <div class="p-4 bg-white/10 rounded-lg">
                    <div class="font-bold text-white mb-2">Distance Matrix API</div>
                    <div class="text-sm">$5 ต่อ 1,000 elements</div>
                    <div class="text-xs text-white/60 mt-1">Free: 40,000 elements/เดือน</div>
                </div>
                <div class="p-4 bg-white/10 rounded-lg">
                    <div class="font-bold text-white mb-2">Places API</div>
                    <div class="text-sm">$17 ต่อ 1,000 requests</div>
                    <div class="text-xs text-white/60 mt-1">Free: 11,764 requests/เดือน</div>
                </div>
            </div>
            <div class="mt-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
                <p class="text-green-300 text-sm">
                    <i class="fas fa-lightbulb mr-2"></i>
                    <strong>💡 เทคนิคประหยัดค่าใช้จ่าย:</strong> เปิดใช้งาน Cache เพื่อลดจำนวนครั้งที่เรียก API (แนะนำ TTL: 24 ชั่วโมง)
                </p>
            </div>
        </div>
    </div>

    {{-- Useful Links --}}
    <div class="glass-fusion rounded-2xl p-8 border border-white/30 shadow-2xl">
        <h2 class="text-2xl font-bold text-white mb-4">
            <i class="fas fa-link mr-2"></i>
            ลิงก์ที่เป็นประโยชน์
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <a href="https://console.cloud.google.com/" target="_blank"
               class="p-4 bg-white/10 hover:bg-white/20 rounded-lg transition group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-white font-medium group-hover:text-blue-300">Google Cloud Console</div>
                        <div class="text-white/60 text-sm">จัดการ Project และ API Key</div>
                    </div>
                    <i class="fas fa-external-link-alt text-white/40"></i>
                </div>
            </a>

            <a href="https://developers.google.com/maps/documentation" target="_blank"
               class="p-4 bg-white/10 hover:bg-white/20 rounded-lg transition group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-white font-medium group-hover:text-blue-300">Google Maps Documentation</div>
                        <div class="text-white/60 text-sm">เอกสารการใช้งาน API</div>
                    </div>
                    <i class="fas fa-external-link-alt text-white/40"></i>
                </div>
            </a>

            <a href="https://mapsplatform.google.com/pricing/" target="_blank"
               class="p-4 bg-white/10 hover:bg-white/20 rounded-lg transition group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-white font-medium group-hover:text-blue-300">Pricing Calculator</div>
                        <div class="text-white/60 text-sm">คำนวณค่าใช้จ่าย</div>
                    </div>
                    <i class="fas fa-external-link-alt text-white/40"></i>
                </div>
            </a>

            <a href="{{ route('admin.settings.google-maps') }}"
               class="p-4 bg-blue-500/20 hover:bg-blue-500/30 border border-blue-500/50 rounded-lg transition group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-white font-medium group-hover:text-blue-300">กลับไปหน้าตั้งค่า</div>
                        <div class="text-white/60 text-sm">ตั้งค่า Google Maps API</div>
                    </div>
                    <i class="fas fa-arrow-right text-white/40"></i>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
