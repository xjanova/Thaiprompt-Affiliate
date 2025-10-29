<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>เข้าสู่ระบบ - TP-Affiliate</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-indigo-600 to-purple-600">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-lg shadow-2xl p-8">
                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-indigo-600 mb-2">TP-Affiliate</h1>
                    <p class="text-gray-600">เข้าสู่ระบบ</p>
                </div>

                @if (session('info'))
                    <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                        {{ session('info') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่าน</label>
                        <input type="password" name="password" id="password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               required>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">จดจำฉัน</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300">
                        เข้าสู่ระบบ
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
                        ยังไม่มีบัญชี? สมัครสมาชิก
                    </a>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('home') }}" class="text-white hover:text-gray-200 text-sm">
                    ← กลับหน้าแรก
                </a>
            </div>
        </div>
    </div>
</body>
</html>
