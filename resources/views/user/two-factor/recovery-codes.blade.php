@extends('layouts.user-arrow-x')

@section('title', 'รหัสกู้คืน 2FA')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">🔑</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">รหัสกู้คืน (Recovery Codes)</h1>
                <p class="text-purple-100 mt-1">เก็บรหัสเหล่านี้ไว้ในที่ปลอดภัย</p>
            </div>
        </div>
    </div>

    <!-- Warning Alert -->
    <div class="bg-red-50 border-2 border-red-300 rounded-2xl p-6">
        <div class="flex gap-4">
            <span class="text-4xl">⚠️</span>
            <div class="flex-1">
                <h3 class="text-xl font-bold text-red-800 mb-2">ข้อควรระวัง - สำคัญมาก!</h3>
                <ul class="space-y-2 text-red-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1">•</span>
                        <span><strong>เก็บรักษารหัสเหล่านี้ให้ปลอดภัย</strong> - อย่าแชร์ให้ใครเห็น</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1">•</span>
                        <span><strong>แต่ละรหัสใช้ได้เพียงครั้งเดียว</strong> - เมื่อใช้แล้วจะใช้ซ้ำไม่ได้</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1">•</span>
                        <span><strong>พิมพ์หรือบันทึกไว้ในที่ปลอดภัย</strong> - เช่น Password Manager</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1">•</span>
                        <span><strong>ใช้เมื่อไม่สามารถเข้าถึงวิธีการยืนยันหลักได้</strong> - กรณีฉุกเฉิน</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @if(!empty($codes) && count($codes) > 0)
        <!-- Recovery Codes Display -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800">รหัสกู้คืนของคุณ</h2>
                <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold">
                    {{ count($codes) }} รหัส
                </span>
            </div>

            <!-- Codes Grid -->
            <div class="grid md:grid-cols-2 gap-4 mb-6" id="codesGrid">
                @foreach($codes as $index => $code)
                    <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-4 hover:border-blue-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 bg-blue-100 text-blue-800 rounded-lg flex items-center justify-center font-bold text-sm">
                                    {{ $index + 1 }}
                                </span>
                                <code class="text-lg font-mono font-bold text-gray-800">{{ $code }}</code>
                            </div>
                            <button type="button"
                                    onclick="copyCode('{{ $code }}')"
                                    class="p-2 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded-lg transition-colors"
                                    title="คัดลอก">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Action Buttons -->
            <div class="grid md:grid-cols-3 gap-4">
                <button type="button"
                        onclick="copyAllCodes()"
                        class="px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition-colors shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    คัดลอกทั้งหมด
                </button>

                <button type="button"
                        onclick="downloadCodes()"
                        class="px-6 py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold transition-colors shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    ดาวน์โหลด
                </button>

                <button type="button"
                        onclick="printCodes()"
                        class="px-6 py-4 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold transition-colors shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    พิมพ์
                </button>
            </div>
        </div>

        <!-- Usage Instructions -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-xl p-6 border border-blue-100">
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span>💡</span> วิธีใช้รหัสกู้คืน
            </h3>
            <div class="space-y-3">
                <div class="flex gap-3">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold flex-shrink-0">1</span>
                    <div class="flex-1">
                        <div class="font-semibold text-gray-800">เมื่อไหร่ควรใช้?</div>
                        <div class="text-sm text-gray-600 mt-1">เมื่อคุณไม่สามารถรับรหัส 2FA ผ่านช่องทางหลักได้ (เช่น เปลี่ยนเบอร์โทรศัพท์, ไม่มีอุปกรณ์)</div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold flex-shrink-0">2</span>
                    <div class="flex-1">
                        <div class="font-semibold text-gray-800">วิธีใช้งาน</div>
                        <div class="text-sm text-gray-600 mt-1">ในหน้ายืนยันตัวตน 2FA ให้คลิก "ใช้รหัสกู้คืนแทน" แล้วกรอกรหัสกู้คืนที่เหลืออยู่</div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold flex-shrink-0">3</span>
                    <div class="flex-1">
                        <div class="font-semibold text-gray-800">หลังใช้งาน</div>
                        <div class="text-sm text-gray-600 mt-1">รหัสที่ใช้แล้วจะใช้ซ้ำไม่ได้ ถ้ารหัสใกล้หมดควรสร้างรหัสใหม่</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- No Codes Available -->
        <div class="bg-white rounded-2xl shadow-2xl p-12 text-center">
            <span class="text-6xl mb-4 block">🔒</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบรหัสกู้คืน</h2>
            <p class="text-gray-600 mb-6">คุณยังไม่มีรหัสกู้คืน หรือรหัสเดิมหมดอายุแล้ว</p>
            <form action="{{ route('user.two-factor.regenerate-codes') }}" method="POST">
                @csrf
                <button type="submit"
                        onclick="return confirm('สร้างรหัสกู้คืนใหม่?')"
                        class="px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-bold text-lg transition-all shadow-lg">
                    สร้างรหัสกู้คืนใหม่
                </button>
            </form>
        </div>
    @endif

    <!-- Back Button -->
    <div class="text-center">
        <a href="{{ route('user.two-factor.setup') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-semibold transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            กลับไปหน้าตั้งค่า 2FA
        </a>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="hidden fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    <span id="toastMessage"></span>
</div>

@push('scripts')
<script>
const codes = @json($codes ?? []);

function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s';
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.classList.add('hidden');
            toast.style.opacity = '1';
        }, 500);
    }, 3000);
}

function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        showToast('✅ คัดลอกรหัสแล้ว');
    }).catch(() => {
        alert('ไม่สามารถคัดลอกได้');
    });
}

function copyAllCodes() {
    const allCodes = codes.join('\n');
    navigator.clipboard.writeText(allCodes).then(() => {
        showToast('✅ คัดลอกรหัสทั้งหมดแล้ว');
    }).catch(() => {
        alert('ไม่สามารถคัดลอกได้');
    });
}

function downloadCodes() {
    const content = '=== รหัสกู้คืน 2FA ===\n' +
                    'วันที่สร้าง: ' + new Date().toLocaleString('th-TH') + '\n\n' +
                    '⚠️ เก็บรักษารหัสเหล่านี้ให้ปลอดภัย\n' +
                    '⚠️ แต่ละรหัสใช้ได้เพียงครั้งเดียว\n\n' +
                    'รหัสกู้คืน:\n' +
                    codes.map((code, index) => `${index + 1}. ${code}`).join('\n') +
                    '\n\n===================';

    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '2FA-Recovery-Codes-' + new Date().getTime() + '.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showToast('✅ ดาวน์โหลดไฟล์แล้ว');
}

function printCodes() {
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>รหัสกู้คืน 2FA</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; }
                h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 8px; }
                .code { font-family: monospace; font-size: 18px; padding: 10px; background: #f5f5f5; margin: 10px 0; border-radius: 4px; }
                .meta { color: #666; font-size: 12px; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <h1>🔑 รหัสกู้คืน 2FA</h1>
            <p><strong>วันที่สร้าง:</strong> ${new Date().toLocaleString('th-TH')}</p>

            <div class="warning">
                <strong>⚠️ ข้อควรระวัง:</strong>
                <ul>
                    <li>เก็บรักษารหัสเหล่านี้ให้ปลอดภัย - อย่าแชร์ให้ใครเห็น</li>
                    <li>แต่ละรหัสใช้ได้เพียงครั้งเดียว</li>
                    <li>ใช้เมื่อไม่สามารถเข้าถึงวิธีการยืนยันหลักได้</li>
                </ul>
            </div>

            <h2>รหัสกู้คืน (${codes.length} รหัส):</h2>
            ${codes.map((code, index) => `<div class="code">${index + 1}. ${code}</div>`).join('')}

            <div class="meta">
                <p>พิมพ์โดย: ThaiPrompt Affiliate System</p>
                <p>หมายเหตุ: เก็บเอกสารนี้ในที่ปลอดภัย</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);

    showToast('✅ เตรียมพิมพ์เอกสาร');
}
</script>
@endpush
@endsection
