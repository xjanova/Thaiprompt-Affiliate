@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 dark:from-gray-900 dark:via-purple-900 dark:to-blue-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12 animate-fade-in">
            <h1 class="text-5xl md:text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 mb-4">
                <i class="fas fa-qrcode mr-3"></i>QR Code & Barcode Generator
            </h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                สร้าง QR Code และ Barcode ฟรี! รองรับทุกรูปแบบ ใช้งานง่าย ดาวน์โหลดได้ทันที
            </p>
        </div>

        <!-- Main Generator Section -->
        <div x-data="qrBarcodeGenerator()" class="space-y-8">

            <!-- Type Selection -->
            <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8">
                <div class="flex flex-col md:flex-row gap-4">
                    <button @click="type = 'qrcode'"
                            :class="type === 'qrcode' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg shadow-purple-500/50' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                            class="flex-1 py-6 rounded-2xl font-bold text-xl transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-qrcode text-3xl mb-2"></i>
                        <div>QR Code</div>
                        <div class="text-sm font-normal mt-1">สร้าง QR Code</div>
                    </button>

                    <button @click="type = 'barcode'"
                            :class="type === 'barcode' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg shadow-blue-500/50' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                            class="flex-1 py-6 rounded-2xl font-bold text-xl transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-barcode text-3xl mb-2"></i>
                        <div>Barcode</div>
                        <div class="text-sm font-normal mt-1">สร้าง Barcode</div>
                    </button>
                </div>
            </div>

            <!-- QR Code Generator -->
            <div x-show="type === 'qrcode'" x-transition class="grid md:grid-cols-2 gap-8">
                <!-- Input Section -->
                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8 space-y-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <i class="fas fa-sliders-h mr-3 text-purple-400"></i>ตั้งค่า QR Code
                    </h2>

                    <!-- Format Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">รูปแบบข้อมูล</label>
                        <select x-model="qrFormat" @change="updatePlaceholder()" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="text">ข้อความทั่วไป (Text)</option>
                            <option value="url">ลิงก์เว็บไซต์ (URL)</option>
                            <option value="email">อีเมล (Email)</option>
                            <option value="phone">เบอร์โทรศัพท์ (Phone)</option>
                            <option value="sms">ส่ง SMS</option>
                            <option value="wifi">WiFi</option>
                            <option value="vcard">นามบัตร (vCard)</option>
                        </select>
                    </div>

                    <!-- Dynamic Input Fields -->
                    <div x-show="qrFormat === 'text' || qrFormat === 'url'">
                        <label class="block text-sm font-medium text-gray-300 mb-2">เนื้อหา</label>
                        <textarea x-model="qrContent" @input="generateQR()" :placeholder="placeholder"
                                  class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent h-32 resize-none"></textarea>
                    </div>

                    <div x-show="qrFormat === 'email'">
                        <label class="block text-sm font-medium text-gray-300 mb-2">อีเมล</label>
                        <input type="email" x-model="emailData.email" @input="generateQR()" placeholder="example@email.com"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent mb-3">
                        <input type="text" x-model="emailData.subject" @input="generateQR()" placeholder="หัวข้อ (ถ้ามี)"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <div x-show="qrFormat === 'phone' || qrFormat === 'sms'">
                        <label class="block text-sm font-medium text-gray-300 mb-2">เบอร์โทรศัพท์</label>
                        <input type="tel" x-model="phoneData.number" @input="generateQR()" placeholder="+66812345678"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <div x-show="qrFormat === 'sms'" class="mt-3">
                            <textarea x-model="phoneData.message" @input="generateQR()" placeholder="ข้อความ SMS"
                                      class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent h-24 resize-none"></textarea>
                        </div>
                    </div>

                    <div x-show="qrFormat === 'wifi'">
                        <label class="block text-sm font-medium text-gray-300 mb-2">ชื่อ WiFi (SSID)</label>
                        <input type="text" x-model="wifiData.ssid" @input="generateQR()" placeholder="MyWiFiNetwork"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent mb-3">
                        <label class="block text-sm font-medium text-gray-300 mb-2">รหัsผ่าน</label>
                        <input type="text" x-model="wifiData.password" @input="generateQR()" placeholder="password123"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent mb-3">
                        <label class="block text-sm font-medium text-gray-300 mb-2">ประเภทการเข้ารหัส</label>
                        <select x-model="wifiData.encryption" @change="generateQR()" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="WPA">WPA/WPA2</option>
                            <option value="WEP">WEP</option>
                            <option value="nopass">ไม่มีรหัสผ่าน</option>
                        </select>
                    </div>

                    <div x-show="qrFormat === 'vcard'">
                        <label class="block text-sm font-medium text-gray-300 mb-2">ชื่อ</label>
                        <input type="text" x-model="vcardData.name" @input="generateQR()" placeholder="ชื่อ นามสกุล"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent mb-3">
                        <label class="block text-sm font-medium text-gray-300 mb-2">เบอร์โทร</label>
                        <input type="tel" x-model="vcardData.phone" @input="generateQR()" placeholder="+66812345678"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent mb-3">
                        <label class="block text-sm font-medium text-gray-300 mb-2">อีเมล</label>
                        <input type="email" x-model="vcardData.email" @input="generateQR()" placeholder="example@email.com"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent mb-3">
                        <label class="block text-sm font-medium text-gray-300 mb-2">องค์กร/บริษัท</label>
                        <input type="text" x-model="vcardData.organization" @input="generateQR()" placeholder="ชื่อบริษัท"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Color Customization -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">สีโค้ด</label>
                            <input type="color" x-model="qrColor" @input="generateQR()"
                                   class="w-full h-12 bg-white/10 border border-white/20 rounded-xl cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">สีพื้นหลัง</label>
                            <input type="color" x-model="qrBgColor" @input="generateQR()"
                                   class="w-full h-12 bg-white/10 border border-white/20 rounded-xl cursor-pointer">
                        </div>
                    </div>

                    <!-- Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">ขนาด: <span x-text="qrSize"></span>px</label>
                        <input type="range" x-model="qrSize" @input="generateQR()" min="128" max="512" step="32"
                               class="w-full h-2 bg-white/20 rounded-lg appearance-none cursor-pointer">
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8">
                    <h2 class="text-2xl font-bold text-white flex items-center mb-6">
                        <i class="fas fa-eye mr-3 text-cyan-400"></i>ตัวอย่าง
                    </h2>

                    <div class="flex flex-col items-center justify-center space-y-6">
                        <!-- QR Code Display -->
                        <div class="bg-white p-6 rounded-2xl shadow-2xl">
                            <canvas id="qrCanvas" class="mx-auto"></canvas>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap gap-3 justify-center">
                            <button @click="downloadQR()" class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-green-500/50 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-download mr-2"></i>ดาวน์โหลด PNG
                            </button>
                            <button @click="downloadQRSVG()" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-vector-square mr-2"></i>ดาวน์โหลด SVG
                            </button>
                            <button @click="copyQRToClipboard()" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-copy mr-2"></i>คัดลอก
                            </button>
                        </div>

                        <!-- Info Text -->
                        <p class="text-gray-300 text-sm text-center max-w-md">
                            QR Code ของคุณพร้อมใช้งาน! สามารถดาวน์โหลดหรือคัดลอกไปใช้ได้ทันที
                        </p>
                    </div>
                </div>
            </div>

            <!-- Barcode Generator -->
            <div x-show="type === 'barcode'" x-transition class="grid md:grid-cols-2 gap-8">
                <!-- Input Section -->
                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8 space-y-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <i class="fas fa-sliders-h mr-3 text-blue-400"></i>ตั้งค่า Barcode
                    </h2>

                    <!-- Format Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">รูปแบบ Barcode</label>
                        <select x-model="barcodeFormat" @change="generateBarcode()" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="CODE128">CODE128 (ทั่วไป)</option>
                            <option value="EAN13">EAN13 (สินค้า 13 หลัก)</option>
                            <option value="EAN8">EAN8 (สินค้า 8 หลัก)</option>
                            <option value="UPC">UPC (สินค้า 12 หลัก)</option>
                            <option value="CODE39">CODE39</option>
                            <option value="ITF14">ITF14 (14 หลัก)</option>
                            <option value="MSI">MSI</option>
                            <option value="pharmacode">Pharmacode (ยา)</option>
                        </select>
                    </div>

                    <!-- Content Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">เนื้อหา</label>
                        <input type="text" x-model="barcodeContent" @input="generateBarcode()" :placeholder="getBarcodePlaceholder()"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-400 mt-2" x-text="getBarcodeHelp()"></p>
                    </div>

                    <!-- Display Options -->
                    <div>
                        <label class="flex items-center space-x-3 text-gray-300 cursor-pointer">
                            <input type="checkbox" x-model="barcodeShowText" @change="generateBarcode()" class="w-5 h-5 rounded bg-white/10 border-white/20 text-blue-500 focus:ring-2 focus:ring-blue-500">
                            <span>แสดงข้อความใต้ Barcode</span>
                        </label>
                    </div>

                    <!-- Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">ความกว้าง: <span x-text="barcodeWidth"></span></label>
                        <input type="range" x-model="barcodeWidth" @input="generateBarcode()" min="1" max="4" step="0.5"
                               class="w-full h-2 bg-white/20 rounded-lg appearance-none cursor-pointer">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">ความสูง: <span x-text="barcodeHeight"></span>px</label>
                        <input type="range" x-model="barcodeHeight" @input="generateBarcode()" min="30" max="150" step="10"
                               class="w-full h-2 bg-white/20 rounded-lg appearance-none cursor-pointer">
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-3xl shadow-2xl border border-white/20 p-8">
                    <h2 class="text-2xl font-bold text-white flex items-center mb-6">
                        <i class="fas fa-eye mr-3 text-cyan-400"></i>ตัวอย่าง
                    </h2>

                    <div class="flex flex-col items-center justify-center space-y-6">
                        <!-- Barcode Display -->
                        <div class="bg-white p-6 rounded-2xl shadow-2xl">
                            <svg id="barcodeSVG"></svg>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap gap-3 justify-center">
                            <button @click="downloadBarcode()" class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-green-500/50 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-download mr-2"></i>ดาวน์โหลด PNG
                            </button>
                            <button @click="downloadBarcodeSVG()" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-vector-square mr-2"></i>ดาวน์โหลด SVG
                            </button>
                        </div>

                        <!-- Info Text -->
                        <p class="text-gray-300 text-sm text-center max-w-md">
                            Barcode ของคุณพร้อมใช้งาน! สามารถดาวน์โหลดไปใช้ได้ทันที
                        </p>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="grid md:grid-cols-3 gap-6 mt-12">
                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-2xl border border-white/20 p-6 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="text-4xl mb-4">🎨</div>
                    <h3 class="text-xl font-bold text-white mb-2">ปรับแต่งได้เต็มที่</h3>
                    <p class="text-gray-300">เปลี่ยนสี ขนาด และรูปแบบตามต้องการ</p>
                </div>

                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-2xl border border-white/20 p-6 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="text-4xl mb-4">⚡</div>
                    <h3 class="text-xl font-bold text-white mb-2">สร้างทันที</h3>
                    <p class="text-gray-300">ไม่ต้องรอ สร้างและดาวน์โหลดได้ทันที</p>
                </div>

                <div class="backdrop-blur-lg bg-white/10 dark:bg-gray-800/30 rounded-2xl border border-white/20 p-6 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="text-4xl mb-4">🔒</div>
                    <h3 class="text-xl font-bold text-white mb-2">ปลอดภัย 100%</h3>
                    <p class="text-gray-300">ประมวลผลบนเครื่องของคุณ ไม่ส่งข้อมูลไปเซิร์ฟเวอร์</p>
                </div>
            </div>

        </div>
    </div>
</div>

@push('styles')
<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.8s ease-out;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(147, 51, 234, 0.5);
        border-radius: 5px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(147, 51, 234, 0.8);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="{{ asset('js/qr-barcode-generator.js') }}"></script>
@endpush
@endsection
