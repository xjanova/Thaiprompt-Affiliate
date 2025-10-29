@extends('layouts.admin')

@section('title', 'การตั้งค่ากระเป๋าเงิน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">การตั้งค่ากระเป๋าเงิน</h2>
                <p class="text-gray-600 mt-1">จัดการความปลอดภัยและการตั้งค่าต่างๆ</p>
            </div>
            <a href="{{ route('admin.wallet.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Wallet Info -->
    <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-2">ข้อมูลกระเป๋าเงิน</h3>
                <div class="space-y-1">
                    <p class="text-sm text-indigo-100">ที่อยู่กระเป๋าเงิน</p>
                    <p class="text-xl font-bold font-mono">{{ $wallet->wallet_address }}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    <span class="text-sm font-medium">สถานะ:</span>
                    <span class="ml-2 text-sm font-bold">
                        @if($wallet->isActive())
                            ✅ ใช้งานได้
                        @elseif($wallet->isLocked())
                            🔒 ถูกล็อก
                        @else
                            ⏸️ ระงับ
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- PIN Settings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-6">
                <span class="text-3xl mr-3">🔑</span>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">ตั้งค่า PIN</h3>
                    <p class="text-sm text-gray-600">
                        @if($wallet->hasPIN())
                            PIN ของคุณถูกตั้งค่าแล้ว
                        @else
                            <span class="text-red-600 font-semibold">คุณยังไม่ได้ตั้งค่า PIN</span>
                        @endif
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.wallet.set-pin') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PIN (4-6 หลัก)</label>
                    <input type="password" name="pin" maxlength="6" pattern="[0-9]{4,6}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="กรอก PIN ตัวเลข 4-6 หลัก">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ยืนยัน PIN</label>
                    <input type="password" name="pin_confirmation" maxlength="6" pattern="[0-9]{4,6}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="กรอก PIN อีกครั้ง">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                    {{ $wallet->hasPIN() ? 'เปลี่ยน PIN' : 'ตั้งค่า PIN' }}
                </button>
            </form>

            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex">
                    <span class="text-yellow-600 mr-2">⚠️</span>
                    <div class="text-xs text-yellow-800">
                        <p class="font-semibold mb-1">คำเตือนด้านความปลอดภัย:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>PIN จะใช้สำหรับยืนยันทุกครั้งที่ถอนเงินหรือโอนเงิน</li>
                            <li>อย่าแชร์ PIN ให้ผู้อื่นทราบ</li>
                            <li>ตั้ง PIN ที่ไม่ซ้ำกับรหัสผ่านอื่นๆ</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallet Lock/Unlock -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-6">
                <span class="text-3xl mr-3">🔒</span>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">ล็อกกระเป๋าเงิน</h3>
                    <p class="text-sm text-gray-600">ป้องกันการทำธุรกรรมโดยไม่ได้รับอนุญาต</p>
                </div>
            </div>

            @if($wallet->isLocked())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="text-red-600 text-2xl mr-3">🚫</span>
                        <div>
                            <p class="text-sm font-semibold text-red-800">กระเป๋าเงินของคุณถูกล็อกอยู่</p>
                            <p class="text-xs text-red-600 mt-1">
                                @if($wallet->locked_until)
                                    ล็อกถึง: {{ $wallet->locked_until->format('d/m/Y H:i') }}
                                @else
                                    ล็อกถาวร
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.wallet.unlock') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                        🔓 ปลดล็อกกระเป๋าเงิน
                    </button>
                </form>
            @else
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="text-green-600 text-2xl mr-3">✅</span>
                        <div>
                            <p class="text-sm font-semibold text-green-800">กระเป๋าเงินของคุณใช้งานได้ปกติ</p>
                            <p class="text-xs text-green-600 mt-1">คุณสามารถทำธุรกรรมได้ตามปกติ</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.wallet.lock') }}" method="POST"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะล็อกกระเป๋าเงิน? การล็อกจะป้องกันการทำธุรกรรมทั้งหมด')">
                    @csrf
                    <button type="submit" class="w-full bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                        🔒 ล็อกกระเป๋าเงิน
                    </button>
                </form>
            @endif

            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex">
                    <span class="text-blue-600 mr-2">ℹ️</span>
                    <div class="text-xs text-blue-800">
                        <p class="font-semibold mb-1">เกี่ยวกับการล็อกกระเป๋าเงิน:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>เมื่อล็อก จะไม่สามารถทำธุรกรรมใดๆ ได้</li>
                            <li>ยอดเงินจะยังคงอยู่ปลอดภัย</li>
                            <li>คุณสามารถปลดล็อกได้ตลอดเวลา</li>
                            <li>ระบบจะล็อกอัตโนมัติหลังพยายามใส่ PIN ผิด 5 ครั้ง</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Statistics -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-6">📊 สถิติความปลอดภัย</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <span class="text-3xl block mb-2">🔐</span>
                <p class="text-2xl font-bold text-blue-600">{{ $wallet->hasPIN() ? 'เปิด' : 'ปิด' }}</p>
                <p class="text-sm text-gray-600">PIN Protection</p>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <span class="text-3xl block mb-2">🛡️</span>
                <p class="text-2xl font-bold text-purple-600">{{ $wallet->two_factor_enabled ? 'เปิด' : 'ปิด' }}</p>
                <p class="text-sm text-gray-600">2FA (เร็วๆ นี้)</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <span class="text-3xl block mb-2">✅</span>
                <p class="text-2xl font-bold text-green-600">{{ $wallet->transactions()->where('status', 'completed')->count() }}</p>
                <p class="text-sm text-gray-600">ธุรกรรมสำเร็จ</p>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <span class="text-3xl block mb-2">❌</span>
                <p class="text-2xl font-bold text-red-600">{{ $wallet->failed_attempts }}</p>
                <p class="text-sm text-gray-600">ความพยายามที่ล้มเหลว</p>
            </div>
        </div>
    </div>
</div>
@endsection
