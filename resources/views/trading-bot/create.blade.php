@extends('layouts.app')

@section('title', 'Create Trading Bot')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('trading-bot.index') }}" class="text-blue-600 hover:text-blue-800">
            ← กลับ Dashboard
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">สร้างบอทเทรดใหม่</h1>

        <form action="{{ route('trading-bot.store') }}" method="POST">
            @csrf

            <!-- Bot Name -->
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">
                    ชื่อบอท <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    placeholder="เช่น BTC Moon Bot">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Trading Account -->
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">
                    บัญชีเทรด <span class="text-red-500">*</span>
                </label>
                <select name="account_id" required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">-- เลือกบัญชี --</option>
                    @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                        {{ $account->account_name }} ({{ $account->exchange->name }})
                    </option>
                    @endforeach
                </select>
                @if($accounts->count() === 0)
                <p class="text-sm text-gray-500 mt-2">
                    คุณยังไม่มีบัญชีเทรด <a href="{{ route('trading-bot.accounts') }}" class="text-blue-600 underline">เพิ่มบัญชีเทรด</a>
                </p>
                @endif
                @error('account_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Strategy -->
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">
                    กลยุทธ์ <span class="text-red-500">*</span>
                </label>
                <select name="strategy_id" required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">-- เลือกกลยุทธ์ --</option>
                    @foreach($allStrategies as $strategy)
                    <option value="{{ $strategy->id }}" {{ old('strategy_id') == $strategy->id ? 'selected' : '' }}>
                        {{ $strategy->name }} ({{ ucfirst($strategy->strategy_type) }})
                    </option>
                    @endforeach
                </select>
                @if($allStrategies->count() === 0)
                <p class="text-sm text-gray-500 mt-2">
                    คุณยังไม่มีกลยุทธ์ <a href="{{ route('trading-bot.strategies.create') }}" class="text-blue-600 underline">สร้างกลยุทธ์</a>
                </p>
                @endif
                @error('strategy_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Trading Pair -->
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">
                    Trading Pair <span class="text-red-500">*</span>
                </label>
                <input type="text" name="trading_pair" value="{{ old('trading_pair', 'BTC/USDT') }}" required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    placeholder="BTC/USDT">
                <p class="text-sm text-gray-500 mt-1">รูปแบบ: BASE/QUOTE (เช่น BTC/USDT, ETH/THB)</p>
                @error('trading_pair')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Timeframe -->
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">
                    Timeframe <span class="text-red-500">*</span>
                </label>
                <select name="timeframe" required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="1m" {{ old('timeframe') == '1m' ? 'selected' : '' }}>1 นาที</option>
                    <option value="5m" {{ old('timeframe') == '5m' ? 'selected' : '' }}>5 นาที</option>
                    <option value="15m" {{ old('timeframe') == '15m' ? 'selected' : '' }}>15 นาที</option>
                    <option value="30m" {{ old('timeframe') == '30m' ? 'selected' : '' }}>30 นาที</option>
                    <option value="1h" {{ old('timeframe', '1h') == '1h' ? 'selected' : '' }}>1 ชั่วโมง</option>
                    <option value="4h" {{ old('timeframe') == '4h' ? 'selected' : '' }}>4 ชั่วโมง</option>
                    <option value="1d" {{ old('timeframe') == '1d' ? 'selected' : '' }}>1 วัน</option>
                    <option value="1w" {{ old('timeframe') == '1w' ? 'selected' : '' }}>1 สัปดาห์</option>
                </select>
                @error('timeframe')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Allocated Capital -->
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">
                    เงินทุนที่จัดสรร <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="allocated_capital" value="{{ old('allocated_capital', '100') }}" required step="0.01" min="10"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="100">
                    <span class="absolute right-4 top-3 text-gray-500">USDT</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">ขั้นต่ำ 10 USDT</p>
                @error('allocated_capital')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Position Size -->
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">
                    ขนาดการเทรด (% ของเงินทุน) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="position_size_percentage" value="{{ old('position_size_percentage', '10') }}" required step="1" min="1" max="100"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    placeholder="10">
                <p class="text-sm text-gray-500 mt-1">แนะนำ 5-20% ต่อการเทรด</p>
                @error('position_size_percentage')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Dry Run -->
            <div class="mb-8">
                <label class="flex items-center">
                    <input type="checkbox" name="dry_run" value="1" {{ old('dry_run', true) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3 text-gray-700 dark:text-gray-300">
                        <span class="font-bold">Paper Trading</span> (ทดสอบด้วยเงินเสมือน - แนะนำสำหรับมือใหม่)
                    </span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('trading-bot.index') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors">
                    สร้างบอท
                </button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-bold text-blue-800 dark:text-blue-300 mb-1">💡 เคล็ดลับสำหรับมือใหม่</h3>
                <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
                    <li>เริ่มต้นด้วย Paper Trading เพื่อทดสอบกลยุทธ์</li>
                    <li>ใช้เงินทุนเพียง 5-10% ของพอร์ตโฟลิโอในแต่ละบอท</li>
                    <li>เลือก Timeframe ตามสไตล์การเทรดของคุณ (1h-4h เหมาะสำหรับมือใหม่)</li>
                    <li>ติดตามและปรับปรุงกลยุทธ์อย่างสม่ำเสมอ</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
