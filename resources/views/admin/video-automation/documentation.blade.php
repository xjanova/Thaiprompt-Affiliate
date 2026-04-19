@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'คู่มือการใช้งาน Video Automation')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.video-automation.dashboard') }}"
           class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left text-gray-600 dark:text-gray-400"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-book mr-3 text-purple-500"></i>
                คู่มือการใช้งาน Video Automation
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                วิธีตั้งค่าและใช้งานระบบสร้างวีดีโออัตโนมัติ
            </p>
        </div>
    </div>

    {{-- Table of Contents --}}
    <div class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-list mr-2 text-purple-500"></i>
            สารบัญ
        </h2>
        <ul class="space-y-2">
            <li><a href="#overview" class="text-purple-600 dark:text-purple-400 hover:underline">1. ภาพรวมระบบ</a></li>
            <li><a href="#suno" class="text-purple-600 dark:text-purple-400 hover:underline">2. วิธีหา Suno AI API Key</a></li>
            <li><a href="#freepik" class="text-purple-600 dark:text-purple-400 hover:underline">3. วิธีหา Freepik API Key</a></li>
            <li><a href="#youtube" class="text-purple-600 dark:text-purple-400 hover:underline">4. วิธีเชื่อมต่อ YouTube</a></li>
            <li><a href="#facebook" class="text-purple-600 dark:text-purple-400 hover:underline">5. วิธีเชื่อมต่อ Facebook</a></li>
            <li><a href="#tiktok" class="text-purple-600 dark:text-purple-400 hover:underline">6. วิธีเชื่อมต่อ TikTok</a></li>
            <li><a href="#instagram" class="text-purple-600 dark:text-purple-400 hover:underline">7. วิธีเชื่อมต่อ Instagram</a></li>
            <li><a href="#usage" class="text-purple-600 dark:text-purple-400 hover:underline">8. วิธีใช้งานระบบ</a></li>
        </ul>
    </div>

    {{-- Section 1: Overview --}}
    <div id="overview" class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-600 text-white text-sm mr-3">1</span>
            ภาพรวมระบบ Video Automation
        </h2>

        <div class="prose dark:prose-invert max-w-none">
            <p>ระบบ Video Automation ช่วยให้คุณสร้างวีดีโอได้อัตโนมัติโดยใช้ AI โดยมีขั้นตอนดังนี้:</p>

            <div class="grid md:grid-cols-4 gap-4 my-6">
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-purple-600 text-white flex items-center justify-center mb-3">
                        <i class="fas fa-music"></i>
                    </div>
                    <h4 class="font-semibold text-purple-900 dark:text-purple-100">Suno AI</h4>
                    <p class="text-sm text-purple-700 dark:text-purple-300">สร้างเพลงจาก AI</p>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-blue-600 text-white flex items-center justify-center mb-3">
                        <i class="fas fa-images"></i>
                    </div>
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100">Freepik AI</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300">สร้างภาพจาก AI</p>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-green-600 text-white flex items-center justify-center mb-3">
                        <i class="fas fa-film"></i>
                    </div>
                    <h4 class="font-semibold text-green-900 dark:text-green-100">FFmpeg</h4>
                    <p class="text-sm text-green-700 dark:text-green-300">รวมเป็นวีดีโอ</p>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-red-600 text-white flex items-center justify-center mb-3">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h4 class="font-semibold text-red-900 dark:text-red-100">Social Media</h4>
                    <p class="text-sm text-red-700 dark:text-red-300">โพสอัตโนมัติ</p>
                </div>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-lightbulb text-yellow-500 mt-1"></i>
                    <div>
                        <h4 class="font-semibold text-yellow-800 dark:text-yellow-200">ข้อกำหนดเบื้องต้น</h4>
                        <ul class="mt-2 text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside">
                            <li>Server ต้องติดตั้ง FFmpeg (สำหรับสร้างวีดีโอ)</li>
                            <li>มี API Keys ของ Suno AI และ Freepik</li>
                            <li>เชื่อมต่อบัญชี YouTube/Facebook/TikTok ที่ต้องการโพส</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 2: Suno API --}}
    <div id="suno" class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-600 text-white text-sm mr-3">2</span>
            วิธีหา Suno AI API Key
        </h2>

        <div class="prose dark:prose-invert max-w-none">
            <p>Suno AI เป็นบริการสร้างเพลงด้วย AI คุณภาพสูง สามารถสร้างเพลงได้หลากหลายสไตล์</p>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 my-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-shoe-prints mr-2 text-purple-500"></i>
                    ขั้นตอนการหา API Key
                </h4>

                <ol class="space-y-4">
                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">1</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ไปที่เว็บไซต์ Suno AI</p>
                            <a href="https://suno.ai" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">
                                https://suno.ai <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">2</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">สมัครสมาชิกหรือเข้าสู่ระบบ</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">สามารถสมัครด้วย Google หรือ Discord</p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">3</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ใช้ Suno API (Unofficial)</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">เนื่องจาก Suno ยังไม่มี Official API คุณสามารถใช้:</p>
                            <ul class="mt-2 text-sm list-disc list-inside text-gray-600 dark:text-gray-400">
                                <li><a href="https://github.com/gcui-art/suno-api" target="_blank" class="text-purple-600 dark:text-purple-400">Suno API (GitHub)</a> - ต้อง self-host</li>
                                <li>หรือใช้ Cookie จาก browser</li>
                            </ul>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">4</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">การหา Cookie (วิธีง่าย)</p>
                            <ol class="mt-2 text-sm list-decimal list-inside text-gray-600 dark:text-gray-400 space-y-1">
                                <li>Login เข้า suno.ai</li>
                                <li>กด F12 เปิด Developer Tools</li>
                                <li>ไปที่ Tab "Application" > "Cookies"</li>
                                <li>Copy ค่า "__client" cookie</li>
                                <li>นำมาใส่ในการตั้งค่า</li>
                            </ol>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 rounded-r-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                    <div>
                        <h4 class="font-semibold text-blue-800 dark:text-blue-200">แพ็กเกจและราคา</h4>
                        <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 list-disc list-inside">
                            <li><strong>Free:</strong> 50 credits/วัน (ประมาณ 5-10 เพลง)</li>
                            <li><strong>Pro ($8/เดือน):</strong> 2,500 credits/เดือน</li>
                            <li><strong>Premier ($24/เดือน):</strong> 10,000 credits/เดือน</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3: Freepik API --}}
    <div id="freepik" class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm mr-3">3</span>
            วิธีหา Freepik API Key
        </h2>

        <div class="prose dark:prose-invert max-w-none">
            <p>Freepik มี AI Image Generator ที่สามารถสร้างภาพจากข้อความได้</p>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 my-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-shoe-prints mr-2 text-blue-500"></i>
                    ขั้นตอนการหา API Key
                </h4>

                <ol class="space-y-4">
                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">1</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ไปที่ Freepik API</p>
                            <a href="https://www.freepik.com/api" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                https://www.freepik.com/api <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">2</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">สมัครบัญชี Developer</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">กด "Get Started" และสร้างบัญชี</p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">3</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">สร้าง API Key</p>
                            <ol class="mt-2 text-sm list-decimal list-inside text-gray-600 dark:text-gray-400 space-y-1">
                                <li>ไปที่ Dashboard</li>
                                <li>คลิก "Create API Key"</li>
                                <li>ตั้งชื่อและเลือก permissions</li>
                                <li>Copy API Key มาใช้งาน</li>
                            </ol>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-r-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-dollar-sign text-green-500 mt-1"></i>
                    <div>
                        <h4 class="font-semibold text-green-800 dark:text-green-200">ราคา API</h4>
                        <ul class="mt-2 text-sm text-green-700 dark:text-green-300 list-disc list-inside">
                            <li><strong>Free Tier:</strong> 100 requests/เดือน</li>
                            <li><strong>Starter ($9.99/เดือน):</strong> 5,000 requests</li>
                            <li><strong>Business:</strong> ติดต่อทีมขาย</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 4: YouTube --}}
    <div id="youtube" class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-600 text-white text-sm mr-3">4</span>
            วิธีเชื่อมต่อ YouTube
        </h2>

        <div class="prose dark:prose-invert max-w-none">
            <p>ใช้ YouTube Data API v3 สำหรับอัปโหลดวีดีโออัตโนมัติ</p>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 my-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-shoe-prints mr-2 text-red-500"></i>
                    ขั้นตอนการตั้งค่า
                </h4>

                <ol class="space-y-4">
                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center font-bold">1</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ไปที่ Google Cloud Console</p>
                            <a href="https://console.cloud.google.com" target="_blank" class="text-red-600 dark:text-red-400 hover:underline">
                                https://console.cloud.google.com <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center font-bold">2</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">สร้าง Project ใหม่</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">หรือใช้ Project ที่มีอยู่แล้ว</p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center font-bold">3</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">เปิดใช้งาน YouTube Data API v3</p>
                            <ol class="mt-2 text-sm list-decimal list-inside text-gray-600 dark:text-gray-400 space-y-1">
                                <li>ไปที่ "APIs & Services" > "Library"</li>
                                <li>ค้นหา "YouTube Data API v3"</li>
                                <li>คลิก "Enable"</li>
                            </ol>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center font-bold">4</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">สร้าง OAuth 2.0 Credentials</p>
                            <ol class="mt-2 text-sm list-decimal list-inside text-gray-600 dark:text-gray-400 space-y-1">
                                <li>ไปที่ "APIs & Services" > "Credentials"</li>
                                <li>คลิก "Create Credentials" > "OAuth client ID"</li>
                                <li>เลือก "Web application"</li>
                                <li>เพิ่ม Redirect URI: <code class="bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">{{ route('admin.video-automation.youtube.callback') }}</code></li>
                                <li>Copy Client ID และ Client Secret</li>
                            </ol>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center font-bold">5</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ตั้งค่า OAuth Consent Screen</p>
                            <ol class="mt-2 text-sm list-decimal list-inside text-gray-600 dark:text-gray-400 space-y-1">
                                <li>ไปที่ "OAuth consent screen"</li>
                                <li>กรอกข้อมูลแอป</li>
                                <li>เพิ่ม Scopes: youtube.upload, youtube</li>
                                <li>เพิ่ม Test users (ถ้าเป็น External)</li>
                            </ol>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center font-bold">6</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">นำค่ามาใส่ในระบบ</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ไปที่ <a href="{{ route('admin.video-automation.settings') }}" class="text-red-600 dark:text-red-400 underline">หน้าตั้งค่า</a>
                                และใส่ Client ID, Client Secret
                            </p>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-r-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-red-500 mt-1"></i>
                    <div>
                        <h4 class="font-semibold text-red-800 dark:text-red-200">ข้อควรระวัง</h4>
                        <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                            <li>YouTube มี Daily Quota จำกัด (10,000 units/วัน)</li>
                            <li>การอัปโหลดวีดีโอใช้ประมาณ 1,600 units/วีดีโอ</li>
                            <li>แนะนำให้ขอ Quota เพิ่มถ้าต้องการอัปโหลดมาก</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 5: Facebook --}}
    <div id="facebook" class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm mr-3">5</span>
            วิธีเชื่อมต่อ Facebook
        </h2>

        <div class="prose dark:prose-invert max-w-none">
            <p>ใช้ Facebook Graph API สำหรับโพสวีดีโอไปยัง Facebook Page</p>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 my-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-shoe-prints mr-2 text-blue-500"></i>
                    ขั้นตอนการตั้งค่า
                </h4>

                <ol class="space-y-4">
                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">1</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ไปที่ Meta for Developers</p>
                            <a href="https://developers.facebook.com" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                https://developers.facebook.com <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">2</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">สร้าง App</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">เลือก "Business" type</p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">3</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">เพิ่ม Products</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">เพิ่ม "Facebook Login" และ "Pages API"</p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">4</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ขอ Permissions</p>
                            <ul class="mt-2 text-sm list-disc list-inside text-gray-600 dark:text-gray-400">
                                <li>pages_manage_posts</li>
                                <li>pages_read_engagement</li>
                                <li>publish_video</li>
                            </ul>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">5</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">สร้าง Page Access Token</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">ใช้ Graph API Explorer หรือ Access Token Debugger</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Section 6: TikTok --}}
    <div id="tiktok" class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-black text-white text-sm mr-3">6</span>
            วิธีเชื่อมต่อ TikTok
        </h2>

        <div class="prose dark:prose-invert max-w-none">
            <p>ใช้ TikTok Content Posting API สำหรับโพสวีดีโอ</p>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 my-4">
                <ol class="space-y-4">
                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-bold">1</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ไปที่ TikTok for Developers</p>
                            <a href="https://developers.tiktok.com" target="_blank" class="text-gray-600 dark:text-gray-400 hover:underline">
                                https://developers.tiktok.com <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-bold">2</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">สร้าง App และขอ Content Posting API Access</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">ต้องผ่านการ Review จาก TikTok</p>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-clock text-yellow-500 mt-1"></i>
                    <div>
                        <h4 class="font-semibold text-yellow-800 dark:text-yellow-200">หมายเหตุ</h4>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            TikTok Content Posting API ต้องผ่านการ Review ซึ่งอาจใช้เวลา 1-2 สัปดาห์
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 7: Instagram --}}
    <div id="instagram" class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600 text-white text-sm mr-3">7</span>
            วิธีเชื่อมต่อ Instagram
        </h2>

        <div class="prose dark:prose-invert max-w-none">
            <p>Instagram ใช้ Facebook Graph API สำหรับ Business Accounts</p>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 my-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">ข้อกำหนด</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                    <li>ต้องเป็น Instagram Business หรือ Creator Account</li>
                    <li>ต้องเชื่อมต่อกับ Facebook Page</li>
                    <li>ใช้ Instagram Graph API ผ่าน Facebook App</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Section 8: Usage --}}
    <div id="usage" class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-600 text-white text-sm mr-3">8</span>
            วิธีใช้งานระบบ
        </h2>

        <div class="prose dark:prose-invert max-w-none">
            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-wand-magic-sparkles mr-2 text-purple-500"></i>
                        สร้างวีดีโอด้วยตนเอง
                    </h4>
                    <ol class="text-sm text-gray-600 dark:text-gray-400 list-decimal list-inside space-y-2">
                        <li>ไปที่ "สร้างโปรเจกต์ใหม่"</li>
                        <li>เลือกเทมเพลตหรือตั้งค่าเอง</li>
                        <li>กำหนดแนวเพลงและสไตล์ภาพ</li>
                        <li>เลือก Platforms ที่จะโพส</li>
                        <li>กด "สร้างและโพส"</li>
                    </ol>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-5">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-clock mr-2 text-blue-500"></i>
                        ตั้งเวลาอัตโนมัติ
                    </h4>
                    <ol class="text-sm text-gray-600 dark:text-gray-400 list-decimal list-inside space-y-2">
                        <li>ไปที่ "ตั้งเวลาอัตโนมัติ"</li>
                        <li>สร้าง Schedule ใหม่</li>
                        <li>เลือกเทมเพลต</li>
                        <li>กำหนดความถี่ (ทุกวัน/ทุกสัปดาห์)</li>
                        <li>ระบบจะสร้างและโพสอัตโนมัติ</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="flex justify-center">
        <a href="{{ route('admin.video-automation.dashboard') }}"
           class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl transition">
            <i class="fas fa-arrow-left mr-2"></i>
            กลับไปหน้า Dashboard
        </a>
    </div>
</div>
@endsection
