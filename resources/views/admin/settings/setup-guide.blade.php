{{--
/**
 * OCR Setup Guide - คู่มือการตั้งค่า Google Cloud Vision API
 *
 * @version 3.0 - Tailwind CSS
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'คู่มือการตั้งค่า OCR')
@section('page-title', 'OCR Setup Guide')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.settings.ocr') }}" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            กลับ
        </a>
        <div>
            <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                <i class="fas fa-book-open mr-2"></i>
                คู่มือการตั้งค่า Google Cloud Vision API
            </h1>
            <p class="text-white/70 text-sm mt-1">
                สำหรับใช้งานฟีเจอร์ OCR อ่านบัตรประชาชนอัตโนมัติ
            </p>
        </div>
    </div>

    {{-- Important Note --}}
    <div class="bg-yellow-500/20 border border-yellow-500/50 rounded-xl p-6">
        <h3 class="text-yellow-300 font-bold text-lg mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            ก่อนเริ่มต้น
        </h3>
        <ul class="text-white/80 space-y-2 list-disc list-inside">
            <li>คุณต้องมีบัญชี Google Cloud Platform</li>
            <li>ต้องเปิดใช้งาน Billing (มีค่าใช้จ่ายหากใช้เกิน free tier)</li>
            <li>Free tier: 1,000 requests/เดือน สำหรับ Text Detection</li>
        </ul>
    </div>

    {{-- Step 1 --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-2xl font-bold text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 text-white text-lg mr-3">1</span>
            สร้างโปรเจค Google Cloud
        </h2>

        <ol class="space-y-4 text-white/80">
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-sm shrink-0">1.1</span>
                <div>
                    ไปที่ <a href="https://console.cloud.google.com" target="_blank" class="text-blue-400 hover:underline font-medium">Google Cloud Console <i class="fas fa-external-link-alt text-xs ml-1"></i></a>
                </div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-sm shrink-0">1.2</span>
                <div>คลิกที่ dropdown ด้านบน (ถัดจาก "Google Cloud") และเลือก "New Project"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-sm shrink-0">1.3</span>
                <div>ตั้งชื่อโปรเจค (เช่น "thaiprompt-ocr") และคลิก "Create"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-sm shrink-0">1.4</span>
                <div>ไปที่ <a href="https://console.cloud.google.com/billing" target="_blank" class="text-blue-400 hover:underline font-medium">Billing <i class="fas fa-external-link-alt text-xs ml-1"></i></a> และเชื่อมต่อบัญชี billing กับโปรเจค</div>
            </li>
        </ol>
    </div>

    {{-- Step 2 --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h2 class="text-2xl font-bold text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 text-white text-lg mr-3">2</span>
            เปิดใช้งาน Cloud Vision API
        </h2>

        <ol class="space-y-4 text-white/80">
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-sm shrink-0">2.1</span>
                <div>
                    ไปที่ <a href="https://console.cloud.google.com/apis/library/vision.googleapis.com" target="_blank" class="text-blue-400 hover:underline font-medium">Cloud Vision API Library <i class="fas fa-external-link-alt text-xs ml-1"></i></a>
                </div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-sm shrink-0">2.2</span>
                <div>คลิกปุ่ม "Enable" เพื่อเปิดใช้งาน API</div>
            </li>
        </ol>

        <div class="mt-4 p-4 bg-blue-500/20 rounded-lg">
            <p class="text-white/80 text-sm">
                <i class="fas fa-lightbulb text-yellow-400 mr-2"></i>
                <strong>เคล็ดลับ:</strong> หากใช้ Google Maps API อยู่แล้ว สามารถใช้ API Key เดียวกันได้ (แต่ต้องเปิด Vision API ใน Console ด้วย)
            </p>
        </div>
    </div>

    {{-- Step 3A: API Key --}}
    <div class="glass-fusion rounded-2xl p-6 border border-green-500/30 bg-green-500/5">
        <h2 class="text-2xl font-bold text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-500 text-white text-lg mr-3">3A</span>
            สร้าง API Key (วิธีง่าย - แนะนำ)
        </h2>

        <ol class="space-y-4 text-white/80">
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-500/30 flex items-center justify-center text-sm shrink-0">1</span>
                <div>
                    ไปที่ <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-400 hover:underline font-medium">APIs & Services > Credentials <i class="fas fa-external-link-alt text-xs ml-1"></i></a>
                </div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-500/30 flex items-center justify-center text-sm shrink-0">2</span>
                <div>คลิก "+ CREATE CREDENTIALS" > "API Key"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-500/30 flex items-center justify-center text-sm shrink-0">3</span>
                <div>คัดลอก API Key ที่ได้</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-500/30 flex items-center justify-center text-sm shrink-0">4</span>
                <div>
                    <strong class="text-green-300">แนะนำ:</strong> คลิก "Edit API Key" > "API restrictions" > เลือก "Cloud Vision API" เพื่อความปลอดภัย
                </div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-500/30 flex items-center justify-center text-sm shrink-0">5</span>
                <div>นำ API Key ไปใส่ในหน้าตั้งค่า OCR</div>
            </li>
        </ol>
    </div>

    {{-- Step 3B: Service Account --}}
    <div class="glass-fusion rounded-2xl p-6 border border-yellow-500/30 bg-yellow-500/5">
        <h2 class="text-2xl font-bold text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-500 text-white text-lg mr-3">3B</span>
            สร้าง Service Account (วิธีขั้นสูง)
        </h2>

        <ol class="space-y-4 text-white/80">
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-yellow-500/30 flex items-center justify-center text-sm shrink-0">1</span>
                <div>
                    ไปที่ <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" class="text-blue-400 hover:underline font-medium">IAM & Admin > Service Accounts <i class="fas fa-external-link-alt text-xs ml-1"></i></a>
                </div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-yellow-500/30 flex items-center justify-center text-sm shrink-0">2</span>
                <div>คลิก "+ CREATE SERVICE ACCOUNT"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-yellow-500/30 flex items-center justify-center text-sm shrink-0">3</span>
                <div>ตั้งชื่อ (เช่น "vision-api") แล้วคลิก "CREATE AND CONTINUE"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-yellow-500/30 flex items-center justify-center text-sm shrink-0">4</span>
                <div>เลือก Role: "Cloud Vision API User" หรือ "Editor" แล้วคลิก "CONTINUE"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-yellow-500/30 flex items-center justify-center text-sm shrink-0">5</span>
                <div>คลิก "DONE"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-yellow-500/30 flex items-center justify-center text-sm shrink-0">6</span>
                <div>คลิกที่ Service Account ที่สร้างขึ้น > แท็บ "KEYS"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-yellow-500/30 flex items-center justify-center text-sm shrink-0">7</span>
                <div>คลิก "ADD KEY" > "Create new key" > เลือก "JSON" > "CREATE"</div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-yellow-500/30 flex items-center justify-center text-sm shrink-0">8</span>
                <div>ดาวน์โหลดไฟล์ JSON และอัปโหลดในหน้าตั้งค่า OCR</div>
            </li>
        </ol>
    </div>

    {{-- Pricing --}}
    <div class="glass-fusion rounded-2xl p-6 border border-purple-500/30">
        <h2 class="text-2xl font-bold text-white mb-4">
            <i class="fas fa-dollar-sign mr-2 text-purple-400"></i>
            ค่าใช้จ่าย
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-white/80">
                <thead>
                    <tr class="border-b border-white/20">
                        <th class="text-left py-2 px-4">Feature</th>
                        <th class="text-right py-2 px-4">Free Tier</th>
                        <th class="text-right py-2 px-4">Price (หลัง Free Tier)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-white/10">
                        <td class="py-2 px-4">TEXT_DETECTION (อ่านข้อความ)</td>
                        <td class="text-right py-2 px-4 text-green-400">1,000 /เดือน</td>
                        <td class="text-right py-2 px-4">$1.50 / 1,000 requests</td>
                    </tr>
                    <tr class="border-b border-white/10">
                        <td class="py-2 px-4">DOCUMENT_TEXT_DETECTION</td>
                        <td class="text-right py-2 px-4 text-green-400">1,000 /เดือน</td>
                        <td class="text-right py-2 px-4">$1.50 / 1,000 requests</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-4">LABEL_DETECTION</td>
                        <td class="text-right py-2 px-4 text-green-400">1,000 /เดือน</td>
                        <td class="text-right py-2 px-4">$1.50 / 1,000 requests</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-white/60 text-sm mt-4">
            <i class="fas fa-info-circle mr-1"></i>
            ดูรายละเอียดเพิ่มเติมที่ <a href="https://cloud.google.com/vision/pricing" target="_blank" class="text-blue-400 hover:underline">Cloud Vision Pricing</a>
        </p>
    </div>

    {{-- Back Button --}}
    <div class="text-center">
        <a href="{{ route('admin.settings.ocr') }}" class="btn-primary inline-flex">
            <i class="fas fa-cog mr-2"></i>
            ไปหน้าตั้งค่า OCR
        </a>
    </div>
</div>
@endsection
