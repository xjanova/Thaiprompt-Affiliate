<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Trading Accounts') }}
            </h2>
            <button onclick="document.getElementById('addAccountModal').classList.remove('hidden')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold">
                + เพิ่มบัญชีเทรด
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Info Card -->
            <div class="bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">ℹ️</span>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            เกี่ยวกับบัญชีเทรด
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <p>เชื่อมต่อบัญชีเทรดของคุณจาก Exchange ต่างๆ เพื่อให้บอทสามารถทำการเทรดได้ ข้อมูล API Key และ Secret จะถูกเข้ารหัสอย่างปลอดภัย</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts Grid -->
            @if($accounts->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($accounts as $account)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 hover:shadow-2xl transition-shadow">
                    <!-- Exchange Logo & Name -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            @if($account->exchange->logo_url)
                                <img src="{{ $account->exchange->logo_url }}" alt="{{ $account->exchange->name }}" class="w-10 h-10 rounded-full">
                            @else
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($account->exchange->name, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ $account->account_name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $account->exchange->name }}</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($account->status === 'active') bg-green-100 text-green-800
                            @elseif($account->status === 'inactive') bg-gray-100 text-gray-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ strtoupper($account->status) }}
                        </span>
                    </div>

                    <!-- Account Details -->
                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ประเภท:</span>
                            <span class="font-medium">{{ ucfirst($account->account_type) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ตลาด:</span>
                            <span class="font-medium">{{ ucfirst($account->market_type) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ยอดเริ่มต้น:</span>
                            <span class="font-medium">{{ $account->balance_currency }} {{ number_format($account->initial_balance, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ยอดปัจจุบัน:</span>
                            <span class="font-bold {{ $account->current_balance >= $account->initial_balance ? 'text-green-600' : 'text-red-600' }}">
                                {{ $account->balance_currency }} {{ number_format($account->current_balance, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- P&L -->
                    @php
                        $pnl = $account->current_balance - $account->initial_balance;
                        $pnlPercentage = $account->initial_balance > 0 ? ($pnl / $account->initial_balance) * 100 : 0;
                    @endphp
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg mb-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">กำไร/ขาดทุน</p>
                        <p class="text-lg font-bold {{ $pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $pnl >= 0 ? '+' : '' }}{{ $account->balance_currency }} {{ number_format($pnl, 2) }}
                            <span class="text-sm">({{ $pnl >= 0 ? '+' : '' }}{{ number_format($pnlPercentage, 2) }}%)</span>
                        </p>
                    </div>

                    <!-- Bots Using This Account -->
                    @php
                        $botsCount = $account->bots()->count();
                    @endphp
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        🤖 {{ $botsCount }} บอทกำลังใช้บัญชีนี้
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button class="flex-1 px-3 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-lg text-sm font-semibold">
                            ✏️ แก้ไข
                        </button>
                        @if($botsCount === 0)
                            <form action="#" method="POST" class="flex-1" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบบัญชีนี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-3 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900 dark:hover:bg-red-800 text-red-800 dark:text-red-200 rounded-lg text-sm font-semibold">
                                    🗑️ ลบ
                                </button>
                            </form>
                        @else
                            <button disabled title="ไม่สามารถลบบัญชีที่มีบอทกำลังใช้งาน" class="flex-1 px-3 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-semibold cursor-not-allowed">
                                🗑️ ลบ
                            </button>
                        @endif
                    </div>

                    <!-- Last Updated -->
                    <div class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">
                        อัปเดตล่าสุด: {{ $account->updated_at->diffForHumans() }}
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-12 text-center">
                <div class="text-6xl mb-4">🏦</div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีบัญชีเทรด</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    เพิ่มบัญชีเทรดจาก Exchange เพื่อเริ่มต้นใช้งานบอท
                </p>
                <button onclick="document.getElementById('addAccountModal').classList.remove('hidden')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                    + เพิ่มบัญชีเทรดแรก
                </button>
            </div>
            @endif

            <!-- Supported Exchanges -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 Exchange ที่รองรับ</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($exchanges as $exchange)
                    <div class="text-center p-4 rounded-lg {{ $exchange->is_active ? 'bg-gray-50 dark:bg-gray-700' : 'bg-gray-100 dark:bg-gray-800 opacity-50' }}">
                        @if($exchange->logo_url)
                            <img src="{{ $exchange->logo_url }}" alt="{{ $exchange->name }}" class="w-12 h-12 mx-auto mb-2 rounded-full">
                        @else
                            <div class="w-12 h-12 mx-auto mb-2 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($exchange->name, 0, 1) }}
                            </div>
                        @endif
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $exchange->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Fee: {{ $exchange->trading_fee_percentage }}%</p>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    <!-- Add Account Modal -->
    <div id="addAccountModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">เพิ่มบัญชีเทรด</h3>
                <button onclick="document.getElementById('addAccountModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl">
                    ×
                </button>
            </div>

            <form action="{{ route('trading-bot.accounts.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Exchange Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Exchange <span class="text-red-500">*</span>
                    </label>
                    <select name="exchange_id" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">เลือก Exchange</option>
                        @foreach($exchanges->where('is_active', true) as $exchange)
                            <option value="{{ $exchange->id }}">{{ $exchange->name }} (Fee: {{ $exchange->trading_fee_percentage }}%)</option>
                        @endforeach
                    </select>
                </div>

                <!-- Account Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อบัญชี <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="account_name" required placeholder="เช่น: My Binance Account"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <!-- Account Type & Market Type -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ประเภทบัญชี <span class="text-red-500">*</span>
                        </label>
                        <select name="account_type" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="demo">Demo (ทดสอบ)</option>
                            <option value="paper">Paper Trading</option>
                            <option value="live">Live (เงินจริง)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ประเภทตลาด <span class="text-red-500">*</span>
                        </label>
                        <select name="market_type" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="spot">Spot</option>
                            <option value="margin">Margin</option>
                            <option value="futures">Futures</option>
                        </select>
                    </div>
                </div>

                <!-- API Credentials -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="api_key" required placeholder="API Key จาก Exchange"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API Secret <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="api_secret" required placeholder="API Secret จาก Exchange"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        🔒 API Secret จะถูกเข้ารหัสอย่างปลอดภัย
                    </p>
                </div>

                <!-- Initial Balance -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ยอดเงินเริ่มต้น <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="initial_balance" step="0.01" min="0" required placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สกุลเงิน <span class="text-red-500">*</span>
                        </label>
                        <select name="balance_currency" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="THB">THB (บาท)</option>
                            <option value="USDT">USDT</option>
                            <option value="USD">USD</option>
                            <option value="BTC">BTC</option>
                        </select>
                    </div>
                </div>

                <!-- Warning -->
                <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <span class="text-2xl mr-3">⚠️</span>
                        <div class="text-sm text-yellow-700 dark:text-yellow-300">
                            <p class="font-medium mb-1">คำเตือนด้านความปลอดภัย:</p>
                            <ul class="list-disc list-inside space-y-1 text-xs">
                                <li>อย่าแชร์ API Key และ Secret กับใครเป็นอันขาด</li>
                                <li>แนะนำให้ใช้ API Key ที่จำกัดสิทธิ์เฉพาะการเทรดเท่านั้น (ไม่ต้องให้สิทธิ์ถอนเงิน)</li>
                                <li>ตรวจสอบให้แน่ใจว่า API มี IP Whitelist ถ้าเป็นไปได้</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-3 pt-4">
                    <button type="button"
                            onclick="document.getElementById('addAccountModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                        เชื่อมต่อบัญชี
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
