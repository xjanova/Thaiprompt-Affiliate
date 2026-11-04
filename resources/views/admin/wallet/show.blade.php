@extends('layouts.admin-v3')

@section('title', 'รายละเอียดกระเป๋าเงิน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">รายละเอียดกระเป๋าเงิน</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1 font-mono">{{ $wallet->wallet_address }}</p>
        </div>
        <a href="{{ route('admin.wallet.all') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    {{-- ผลการทำรายการเงิน — แสดงค้างไว้ (toast หายใน 3 วิ แต่เรื่องเงินแอดมินต้องเห็นชัด) --}}
    @if(session('success'))
        <div class="rounded-xl border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-800 dark:text-green-200" role="status">✅ {{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="rounded-xl border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/30 px-4 py-3 text-sm text-blue-800 dark:text-blue-200" role="status">ℹ️ {{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-800 dark:text-red-200" role="alert">⚠️ {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-800 dark:text-red-200" role="alert">
            <p class="font-semibold mb-1">ยังไม่ได้ทำรายการ — กรุณาแก้ไข:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <!-- Wallet Overview -->
    <div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-2xl font-bold mb-2">{{ $wallet->user->name }}</h3>
                <p class="opacity-80 text-sm">{{ $wallet->user->email }}</p>
            </div>
            <div class="text-right">
                <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    <span class="text-sm font-medium">สถานะ:</span>
                    <span class="ml-2 text-sm font-bold">
                        @if($wallet->isActive())
                            ✅ ใช้งานได้
                        @elseif($wallet->isLocked())
                            🔒 ถูกล็อก
                        @elseif($wallet->status == 'suspended')
                            ⏸️ ระงับชั่วคราว
                        @else
                            ❓ {{ $wallet->status }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Balance -->
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">ยอดคงเหลือ</span>
                    <span class="text-2xl">💰</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($wallet->balance, 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">{{ $wallet->currency }}</p>
            </div>

            <!-- Total Income -->
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">รายรับทั้งหมด</span>
                    <span class="text-2xl">📈</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($statistics['total_income'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">30 วันล่าสุด: {{ number_format($statistics['last_30_days_income'], 2) }}</p>
            </div>

            <!-- Total Expense -->
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">รายจ่ายทั้งหมด</span>
                    <span class="text-2xl">📉</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($statistics['total_expense'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">30 วันล่าสุด: {{ number_format($statistics['last_30_days_expense'], 2) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recent Transactions -->
            {{-- x-data ว่างเพื่อให้ปุ่ม "ย้อน" ใช้ $dispatch ได้ → ส่งไปหน้าต่างยืนยันในคอมโพเนนต์จัดการเงิน --}}
            <div x-data class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ธุรกรรมล่าสุด</h3>
                    <a href="{{ route('admin.wallet.all-transactions', ['wallet_id' => $wallet->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
                        ดูทั้งหมด →
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รายละเอียด</th>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                                <th class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($recentTransactions as $transaction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-xl mr-2">{{ $transaction->type_icon }}</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $transaction->type_label }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'bonus', 'refund']) ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->formatted_amount }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($transaction->description, 30) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    @php
                                        $canRollback = $transaction->status === 'completed'
                                            && ! in_array($transaction->type, ['transfer_in', 'transfer_out'], true)
                                            && $transaction->reference_type !== 'rollback'
                                            && ! in_array($transaction->id, $rolledBackIds ?? [], true);
                                    @endphp
                                    @if($canRollback)
                                        <button type="button"
                                            @click="$dispatch('wallet-rollback', { id: {{ (int) $transaction->id }}, txid: @js($transaction->transaction_id), amount: {{ json_encode(round((float) $transaction->amount, 2)) }}, isCredit: {{ in_array($transaction->type, ['deposit', 'commission', 'refund', 'bonus', 'cashback'], true) ? 'true' : 'false' }} })"
                                            class="text-xs px-3 py-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 hover:bg-amber-200 dark:hover:bg-amber-900/60 transition">
                                            ↩ ย้อน
                                        </button>
                                    @elseif(in_array($transaction->id, $rolledBackIds ?? [], true))
                                        <span class="text-xs text-gray-400 dark:text-gray-500">ย้อนแล้ว</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">ไม่มีธุรกรรม</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ประวัติการปรับยอดโดยแอดมิน (audit trail) -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ประวัติการปรับยอดโดยแอดมิน</h3>
                    <a href="{{ route('admin.wallet.adjustments', ['wallet_id' => $wallet->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm shrink-0">ดูทั้งหมด →</a>
                </div>
                <div class="space-y-2">
                    @forelse($adminAdjustments as $adj)
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <span class="text-sm font-bold {{ $adj->is_credit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} sm:w-28 shrink-0">
                                {{ $adj->is_credit ? '+' : '−' }}฿{{ number_format((float) $adj->amount, 2) }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-white truncate">
                                    <span class="font-semibold">{{ $adj->action_label }}</span> · {{ $adj->reason }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $adj->admin_name ?? 'ไม่ทราบ' }} · {{ $adj->created_at->format('d/m/Y H:i:s') }}
                                    · ฿{{ number_format((float) $adj->balance_before, 2) }} → ฿{{ number_format((float) $adj->balance_after, 2) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">ยังไม่มีการปรับยอดโดยแอดมิน</p>
                    @endforelse
                </div>
            </div>

            <!-- Activity Logs -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Activity Logs</h3>
                    <a href="{{ route('admin.wallet.all-logs', ['wallet_id' => $wallet->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
                        ดูทั้งหมด →
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($recentLogs as $log)
                    <div class="flex items-start p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="flex-shrink-0 mr-3">
                            @if($log->severity == 'critical') 🚨
                            @elseif($log->severity == 'warning') ⚠️
                            @else ℹ️
                            @endif
                        </span>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $log->description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">ไม่มี logs</p>
                    @endforelse
                </div>
            </div>

            <!-- Wallet Info -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลกระเป๋าเงิน</h3>
                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">Wallet Address</span>
                        <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $wallet->wallet_address }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">สกุลเงิน</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $wallet->currency }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">มี PIN</span>
                        <span class="font-semibold {{ $wallet->hasPIN() ? 'text-green-600' : 'text-red-600' }}">
                            {{ $wallet->hasPIN() ? '✅ ตั้งแล้ว' : '❌ ยังไม่ตั้ง' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $wallet->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">อัปเดตล่าสุด</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $wallet->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($wallet->two_factor_enabled)
                    <div class="flex justify-between py-2">
                        <span class="text-gray-600 dark:text-gray-400">Two-Factor Authentication</span>
                        <span class="font-semibold text-green-600">✅ เปิดใช้งาน</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="space-y-6">
            <script>
                // 🛡️ คอมโพเนนต์จัดการเงินโดยแอดมิน — ประกาศก่อน element (สคริปต์ inline รันก่อน Alpine เริ่ม)
                window.walletAdminActions = window.walletAdminActions || function (opts) {
                    return {
                        balance: Number(opts.balance) || 0,
                        maxAmount: Number(opts.maxAmount) || 1000000,
                        panel: ['adjust', 'refund', 'transfer'].includes(opts.oldPanel) ? opts.oldPanel : null,
                        submitting: false,
                        errors: { adjust: '', refund: '', transfer: '', confirm: '' },
                        adjust: { direction: 'credit', amount: '', reason: '' },
                        refund: { amount: '', reason: '' },
                        transfer: { q: '', results: [], to: null, loading: false, searched: false, amount: '', reason: '', seq: 0 },
                        rollback: { tx: null, reason: '' },
                        confirm: { open: false, form: null, title: '', amount: 0, after: 0, isCredit: true, reason: '', target: '', checked: false },

                        init() {
                            // validation ไม่ผ่าน → เปิดแผงเดิมพร้อมค่าที่กรอกไว้ (ไม่ต้องพิมพ์ใหม่)
                            if (this.panel && opts.old) {
                                this[this.panel].amount = opts.old.amount || '';
                                this[this.panel].reason = opts.old.reason || '';
                                if (this.panel === 'adjust' && ['credit', 'debit'].includes(opts.old.direction)) this.adjust.direction = opts.old.direction;
                            }
                            // กลับมาจากหน้าเดิม (bfcache) → ปลดล็อกปุ่ม ไม่งั้นค้าง "กำลังบันทึก..."
                            window.addEventListener('pageshow', () => { this.submitting = false; this.confirm.open = false; });
                        },
                        toggle(name) { this.panel = this.panel === name ? null : name; },
                        fmt(n) { return Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

                        // ตรวจจำนวนเงิน: ตัวเลข > 0 ทศนิยมไม่เกิน 2 ตำแหน่ง ไม่เกินเพดาน
                        parseAmount(raw) {
                            const s = String(raw || '').replace(/,/g, '').trim();
                            if (!/^\d+(\.\d{1,2})?$/.test(s)) return { err: 'กรุณากรอกจำนวนเงินเป็นตัวเลข ทศนิยมไม่เกิน 2 ตำแหน่ง' };
                            const v = Math.round(parseFloat(s) * 100) / 100;
                            if (!(v > 0)) return { err: 'จำนวนเงินต้องมากกว่า 0' };
                            if (v > this.maxAmount) return { err: 'จำนวนเงินเกินเพดานต่อครั้ง (฿' + this.fmt(this.maxAmount) + ')' };
                            return { v };
                        },
                        checkReason(r) { return String(r || '').trim().length >= 3 ? '' : 'กรุณาระบุเหตุผลอย่างน้อย 3 ตัวอักษร'; },

                        review(form) {
                            this.errors[form] = '';
                            const data = this[form];
                            const a = this.parseAmount(data.amount);
                            if (a.err) { this.errors[form] = a.err; return; }
                            const rErr = this.checkReason(data.reason);
                            if (rErr) { this.errors[form] = rErr; return; }

                            let isCredit = true, title = '', target = '';
                            if (form === 'adjust') {
                                isCredit = data.direction === 'credit';
                                title = isCredit ? 'ยืนยันเติมเงิน' : 'ยืนยันหักเงิน';
                            } else if (form === 'refund') {
                                title = 'ยืนยันคืนเงิน';
                            } else if (form === 'transfer') {
                                if (!data.to) { this.errors.transfer = 'กรุณาเลือกกระเป๋าปลายทาง'; return; }
                                isCredit = false;
                                title = 'ยืนยันโอนเงินโดยแอดมิน';
                                target = data.to.user_name + ' (' + data.to.wallet_address + ')';
                            }
                            const after = Math.round((isCredit ? this.balance + a.v : this.balance - a.v) * 100) / 100;
                            if (after < 0) { this.errors[form] = 'ยอดเงินไม่พอ (คงเหลือ ฿' + this.fmt(this.balance) + ')'; return; }

                            this.confirm = { open: true, form, title, amount: a.v, after, isCredit, reason: data.reason.trim(), target, checked: false };
                            this.errors.confirm = '';
                        },

                        openRollback(tx) {
                            this.rollback = { tx, reason: '' };
                            const isCredit = !tx.isCredit; // ย้อนรายการเงินเข้า = หัก
                            const after = Math.round((isCredit ? this.balance + tx.amount : this.balance - tx.amount) * 100) / 100;
                            this.confirm = { open: true, form: 'rollback', title: 'ยืนยันย้อนรายการ ' + tx.txid, amount: tx.amount, after, isCredit, reason: '', target: '', checked: false };
                            this.errors.confirm = after < 0 ? 'ยอดเงินคงเหลือไม่พอสำหรับย้อนรายการนี้' : '';
                        },

                        submitConfirmed() {
                            if (this.submitting || !this.confirm.checked) return; // กันกดซ้ำ
                            if (this.confirm.form === 'rollback') {
                                const rErr = this.checkReason(this.rollback.reason);
                                if (rErr) { this.errors.confirm = rErr; return; }
                                if (this.confirm.after < 0) return;
                            }
                            this.submitting = true;
                            // ส่งจำนวนเงินที่ตรวจแล้ว (ตัดคอมมา "1,000" → "1000.00") ให้ตรงกับที่แสดงในหน้าต่างยืนยัน
                            if (this.confirm.form !== 'rollback') this[this.confirm.form].amount = Number(this.confirm.amount).toFixed(2);
                            // รอ Alpine อัปเดตค่าใน hidden input ก่อนส่ง
                            this.$nextTick(() => this.$refs[this.confirm.form + 'Form'].submit());
                        },

                        async searchWallets() {
                            const q = this.transfer.q.trim();
                            const seq = ++this.transfer.seq;
                            if (q.length < 2) { this.transfer.results = []; this.transfer.searched = false; return; }
                            this.transfer.loading = true;
                            try {
                                const url = opts.searchUrl + '?q=' + encodeURIComponent(q) + '&exclude=' + opts.walletId;
                                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                                const json = await res.json();
                                if (seq !== this.transfer.seq) return; // ผลเก่ามาถึงทีหลัง → ทิ้ง
                                this.transfer.results = (json && json.data) ? json.data : [];
                            } catch (e) {
                                if (seq === this.transfer.seq) { this.transfer.results = []; this.errors.transfer = 'ค้นหากระเป๋าไม่สำเร็จ กรุณาลองใหม่'; }
                            } finally {
                                if (seq === this.transfer.seq) { this.transfer.loading = false; this.transfer.searched = true; }
                            }
                        },
                    };
                };
            </script>
            <!-- Admin Actions -->
            {{--
                🛡️ (2026-11-04) จัดการเงินโดยแอดมิน — ทุกฟอร์มต้องผ่าน "หน้าต่างยืนยัน" ที่แสดงยอดก่อน → หลัง
                - ทิศทาง (เติม/หัก) ส่งแยกจากจำนวนเงิน (ฟอร์มเดิมเลือก "หัก" แล้วพิมพ์จำนวนทีหลัง = เติมเงินแทน)
                - idempotency_key ต่อฟอร์ม: กดซ้ำ/ย้อนกลับแล้วส่งเดิม ระบบไม่ทำซ้ำ
                - expected_balance: ยอดเปลี่ยนระหว่างยืนยัน → ระบบปฏิเสธให้ยืนยันใหม่
            --}}
            <div x-data="walletAdminActions({
                    balance: {{ json_encode(round((float) $wallet->balance, 2)) }},
                    maxAmount: {{ (int) \App\Services\WalletService::ADMIN_MAX_AMOUNT }},
                    walletId: {{ (int) $wallet->id }},
                    searchUrl: @js(route('admin.wallet.wallets-dropdown')),
                    oldPanel: @js(old('_panel')),
                    old: { direction: @js(old('direction', 'credit')), amount: @js((string) old('amount', '')), reason: @js((string) old('reason', '')) },
                })"
                @wallet-rollback.window="openRollback($event.detail)"
                class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">การจัดการเงิน (Admin)</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">ทุกรายการถูกบันทึกชื่อแอดมิน เวลา ยอดก่อน/หลัง และเหตุผล</p>

                @if(! $wallet->isActive())
                    <div class="mb-4 rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                        ⚠️ กระเป๋านี้ไม่ได้อยู่ในสถานะใช้งาน ({{ $wallet->status }}) — แอดมินยังปรับยอดได้ แต่เจ้าของใช้เงินไม่ได้จนกว่าจะปลดล็อก
                    </div>
                @endif

                <div class="space-y-3">
                    <!-- เติม / หัก -->
                    <button type="button" @click="toggle('adjust')" :aria-expanded="panel === 'adjust'"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl transition flex items-center justify-center min-h-[44px]">
                        💵 เติมเงิน / หักเงิน
                    </button>

                    <div x-show="panel === 'adjust'" x-cloak x-transition class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <form x-ref="adjustForm" action="{{ route('admin.wallet.adjust-balance', $wallet->id) }}" method="POST" class="space-y-4" @submit.prevent="review('adjust')" novalidate>
                            @csrf
                            <input type="hidden" name="_panel" value="adjust">
                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <input type="hidden" name="expected_balance" :value="balance.toFixed(2)">
                            <input type="hidden" name="confirmed" value="1">
                            <input type="hidden" name="direction" :value="adjust.direction">

                            <div>
                                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</span>
                                <div class="grid grid-cols-2 gap-2" role="radiogroup">
                                    <button type="button" role="radio" :aria-checked="adjust.direction === 'credit'" @click="adjust.direction = 'credit'"
                                        :class="adjust.direction === 'credit' ? 'bg-green-600 text-white border-green-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600'"
                                        class="py-2 rounded-xl border font-semibold transition min-h-[44px]">➕ เติมเงิน</button>
                                    <button type="button" role="radio" :aria-checked="adjust.direction === 'debit'" @click="adjust.direction = 'debit'"
                                        :class="adjust.direction === 'debit' ? 'bg-red-600 text-white border-red-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600'"
                                        class="py-2 rounded-xl border font-semibold transition min-h-[44px]">➖ หักเงิน</button>
                                </div>
                            </div>
                            <div>
                                <label for="adjust_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (บาท)</label>
                                <input id="adjust_amount" type="text" inputmode="decimal" name="amount" x-model.trim="adjust.amount" autocomplete="off"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="0.00">
                            </div>
                            <div>
                                <label for="adjust_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล / หมายเหตุ <span class="text-red-500">*</span></label>
                                <textarea id="adjust_reason" name="reason" rows="2" maxlength="500" x-model="adjust.reason"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="เช่น ชดเชยออเดอร์ #1234 ที่ระบบตัดเงินซ้ำ"></textarea>
                            </div>
                            <p x-show="errors.adjust" x-text="errors.adjust" class="text-sm text-red-600 dark:text-red-400" role="alert"></p>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl transition min-h-[44px]">
                                ตรวจสอบก่อนยืนยัน →
                            </button>
                        </form>
                    </div>

                    <!-- คืนเงิน -->
                    <button type="button" @click="toggle('refund')" :aria-expanded="panel === 'refund'"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition flex items-center justify-center min-h-[44px]">
                        🔄 คืนเงิน (Refund)
                    </button>

                    <div x-show="panel === 'refund'" x-cloak x-transition class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <form x-ref="refundForm" action="{{ route('admin.wallet.refund', $wallet->id) }}" method="POST" class="space-y-4" @submit.prevent="review('refund')" novalidate>
                            @csrf
                            <input type="hidden" name="_panel" value="refund">
                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <input type="hidden" name="expected_balance" :value="balance.toFixed(2)">
                            <input type="hidden" name="confirmed" value="1">
                            <div>
                                <label for="refund_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงินคืน (บาท)</label>
                                <input id="refund_amount" type="text" inputmode="decimal" name="amount" x-model.trim="refund.amount" autocomplete="off"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="0.00">
                            </div>
                            <div>
                                <label for="refund_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                                <textarea id="refund_reason" name="reason" rows="2" maxlength="500" x-model="refund.reason"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ระบุเหตุผลในการคืนเงิน..."></textarea>
                            </div>
                            <p x-show="errors.refund" x-text="errors.refund" class="text-sm text-red-600 dark:text-red-400" role="alert"></p>
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-xl transition min-h-[44px]">
                                ตรวจสอบก่อนยืนยัน →
                            </button>
                        </form>
                    </div>

                    <!-- โอนโดยแอดมิน -->
                    <button type="button" @click="toggle('transfer')" :aria-expanded="panel === 'transfer'"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition flex items-center justify-center min-h-[44px]">
                        ↔️ โอนเงิน (Admin)
                    </button>

                    <div x-show="panel === 'transfer'" x-cloak x-transition class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <form x-ref="transferForm" action="{{ route('admin.wallet.admin-transfer') }}" method="POST" class="space-y-4" @submit.prevent="review('transfer')" novalidate>
                            @csrf
                            <input type="hidden" name="_panel" value="transfer">
                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <input type="hidden" name="expected_balance" :value="balance.toFixed(2)">
                            <input type="hidden" name="confirmed" value="1">
                            <input type="hidden" name="from_wallet_id" value="{{ $wallet->id }}">
                            <input type="hidden" name="to_wallet_id" :value="transfer.to ? transfer.to.id : ''">
                            <div>
                                <label for="transfer_q" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โอนไปยังกระเป๋า</label>
                                <template x-if="transfer.to">
                                    <div class="flex items-center justify-between gap-2 px-4 py-2 rounded-xl border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/30">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="transfer.to.user_name"></p>
                                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400 truncate" x-text="transfer.to.wallet_address"></p>
                                        </div>
                                        <button type="button" @click="transfer.to = null" class="text-sm text-blue-700 dark:text-blue-300 hover:underline shrink-0">เปลี่ยน</button>
                                    </div>
                                </template>
                                <div x-show="!transfer.to" class="relative">
                                    <input id="transfer_q" type="search" x-model="transfer.q" @input.debounce.350ms="searchWallets()" autocomplete="off"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="พิมพ์ชื่อ อีเมล หรือ Wallet Address">
                                    <p x-show="transfer.loading" class="text-xs text-gray-500 dark:text-gray-400 mt-1">กำลังค้นหา...</p>
                                    <p x-show="transfer.searched && !transfer.loading && transfer.results.length === 0" class="text-xs text-gray-500 dark:text-gray-400 mt-1">ไม่พบกระเป๋าที่ตรงกับคำค้น</p>
                                    <ul x-show="transfer.results.length" class="mt-1 max-h-56 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                        <template x-for="w in transfer.results" :key="w.id">
                                            <li>
                                                <button type="button" @click="transfer.to = w; transfer.results = []" class="w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white" x-text="w.user_name"></span>
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="w.wallet_address + ' · ฿' + w.balance + (w.status !== 'active' ? ' · ' + w.status : '')"></span>
                                                </button>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                            <div>
                                <label for="transfer_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (บาท)</label>
                                <input id="transfer_amount" type="text" inputmode="decimal" name="amount" x-model.trim="transfer.amount" autocomplete="off"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="0.00">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ยอดคงเหลือ: ฿<span x-text="fmt(balance)"></span></p>
                            </div>
                            <div>
                                <label for="transfer_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                                <textarea id="transfer_reason" name="reason" rows="2" maxlength="500" x-model="transfer.reason"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ระบุเหตุผลในการโอน..."></textarea>
                            </div>
                            <p x-show="errors.transfer" x-text="errors.transfer" class="text-sm text-red-600 dark:text-red-400" role="alert"></p>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl transition min-h-[44px]">
                                ตรวจสอบก่อนยืนยัน →
                            </button>
                        </form>
                    </div>

                    <!-- ฟอร์มย้อนรายการ (เปิดจากปุ่มในตารางธุรกรรม) -->
                    <form x-ref="rollbackForm" action="{{ route('admin.wallet.rollback-transaction', $wallet->id) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="_panel" value="rollback">
                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <input type="hidden" name="confirmed" value="1">
                        <input type="hidden" name="transaction_id" :value="rollback.tx ? rollback.tx.id : ''">
                        <input type="hidden" name="reason" :value="rollback.reason">
                    </form>

                    <!-- หน้าต่างยืนยัน (teleport ไป body — กัน position:fixed ติดอยู่ใน card ที่มี backdrop-filter) -->
                    <template x-teleport="body">
                    <div x-show="confirm.open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                        role="dialog" aria-modal="true" aria-labelledby="wallet-confirm-title"
                        @keydown.escape.window="if (!submitting) confirm.open = false">
                        <div @click.outside="if (!submitting) confirm.open = false"
                            class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                            <h4 id="wallet-confirm-title" class="text-lg font-bold text-gray-900 dark:text-white" x-text="confirm.title"></h4>

                            <dl class="rounded-xl bg-gray-50 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <div class="flex justify-between gap-3 px-4 py-2">
                                    <dt class="text-gray-500 dark:text-gray-400">เจ้าของกระเป๋า</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white text-right">{{ $wallet->user->name }}</dd>
                                </div>
                                <template x-if="confirm.target">
                                    <div class="flex justify-between gap-3 px-4 py-2">
                                        <dt class="text-gray-500 dark:text-gray-400">ผู้รับ</dt>
                                        <dd class="font-semibold text-gray-900 dark:text-white text-right" x-text="confirm.target"></dd>
                                    </div>
                                </template>
                                <div class="flex justify-between gap-3 px-4 py-2">
                                    <dt class="text-gray-500 dark:text-gray-400">จำนวน</dt>
                                    <dd class="font-bold text-right" :class="confirm.isCredit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                        x-text="(confirm.isCredit ? '+' : '−') + '฿' + fmt(confirm.amount)"></dd>
                                </div>
                                <div class="flex justify-between gap-3 px-4 py-2">
                                    <dt class="text-gray-500 dark:text-gray-400">ยอดก่อน → ยอดหลัง</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white text-right">
                                        ฿<span x-text="fmt(balance)"></span> → ฿<span x-text="fmt(confirm.after)"></span>
                                    </dd>
                                </div>
                            </dl>

                            <template x-if="confirm.form === 'rollback'">
                                <div>
                                    <label for="rollback_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผลที่ย้อนรายการ <span class="text-red-500">*</span></label>
                                    <textarea id="rollback_reason" rows="2" maxlength="500" x-model="rollback.reason"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="เช่น ระบบเติมเงินผิดออเดอร์"></textarea>
                                </div>
                            </template>
                            <template x-if="confirm.form !== 'rollback'">
                                <p class="text-sm text-gray-600 dark:text-gray-300"><span class="font-medium">เหตุผล:</span> <span x-text="confirm.reason"></span></p>
                            </template>

                            <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" x-model="confirm.checked" class="mt-1 h-5 w-5 rounded border-gray-300 dark:border-gray-600">
                                <span>ฉันตรวจสอบจำนวนเงินและกระเป๋าแล้ว รายการนี้มีผลทันทีและถูกบันทึกในชื่อของฉัน</span>
                            </label>
                            <p x-show="errors.confirm" x-text="errors.confirm" class="text-sm text-red-600 dark:text-red-400" role="alert"></p>

                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="confirm.open = false" :disabled="submitting"
                                    class="py-3 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition disabled:opacity-50 min-h-[44px]">ยกเลิก</button>
                                <button type="button" @click="submitConfirmed()" :disabled="submitting || !confirm.checked"
                                    class="py-3 rounded-xl text-white font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed min-h-[44px]"
                                    :class="confirm.isCredit ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'">
                                    <span x-show="!submitting">ยืนยันทำรายการ</span>
                                    <span x-show="submitting">กำลังบันทึก...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    </template>

                    <hr class="border-gray-200 dark:border-gray-700 my-4">

                    <!-- Lock/Unlock Wallet -->
                    @if($wallet->isActive())
                    <form action="{{ route('admin.wallet.lock-user', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการล็อกกระเป๋าเงินนี้?')" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-xl transition">
                            🔒 ล็อกกระเป๋าเงิน
                        </button>
                    </form>
                    @elseif($wallet->isLocked())
                    <form action="{{ route('admin.wallet.unlock-user', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปลดล็อกกระเป๋าเงินนี้?')" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition">
                            🔓 ปลดล็อกกระเป๋าเงิน
                        </button>
                    </form>
                    @endif

                    <!-- Suspend/Unsuspend -->
                    @if($wallet->status != 'suspended' && $wallet->isActive())
                    <button @click="showSuspend = !showSuspend; showAdjust = false; showRefund = false; showTransfer = false; showRollback = false;" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-xl transition flex items-center justify-center">
                        ⏸️ ระงับกระเป๋าชั่วคราว
                    </button>

                    <div x-show="showSuspend" x-transition class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <form action="{{ route('admin.wallet.suspend-user', $wallet->id) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผลในการระงับ <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="2" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ระบุเหตุผลในการระงับ..."></textarea>
                            </div>
                            <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการระงับกระเป๋าเงินนี้?')" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded-xl transition">
                                ⏸️ ยืนยันการระงับ
                            </button>
                        </form>
                    </div>
                    @elseif($wallet->status == 'suspended')
                    <form action="{{ route('admin.wallet.unsuspend-user', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการระงับกระเป๋าเงินนี้?')" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-xl transition">
                            ▶️ ยกเลิกการระงับ
                        </button>
                    </form>
                    @endif

                    <!-- Reset PIN (Super Admin Only) -->
                    @if(auth()->user()->isSuperAdmin() && $wallet->hasPIN())
                    <hr class="border-gray-200 dark:border-gray-700 my-4">
                    <form action="{{ route('admin.wallet.reset-pin', $wallet->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการรีเซ็ต PIN? ผู้ใช้จะต้องตั้ง PIN ใหม่')" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-4 rounded-xl transition">
                            🔑 รีเซ็ต PIN
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- User Info -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลเจ้าของกระเป๋า</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 h-12 w-12 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                            <span class="text-indigo-600 dark:text-indigo-300 font-bold text-lg">{{ substr($wallet->user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $wallet->user->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $wallet->user->email }}</p>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                        <a href="{{ route('admin.users.show', $wallet->user->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-sm font-semibold">
                            ดูโปรไฟล์ผู้ใช้ →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สถิติ</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">ธุรกรรมทั้งหมด</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($statistics['transactions_count'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">รายรับ 30 วัน</span>
                        <span class="font-bold text-green-600">{{ number_format($statistics['last_30_days_income'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">รายจ่าย 30 วัน</span>
                        <span class="font-bold text-red-600">{{ number_format($statistics['last_30_days_expense'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">รายรับทั้งหมด</span>
                        <span class="font-bold text-green-600">{{ number_format($statistics['total_income'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400 text-sm">รายจ่ายทั้งหมด</span>
                        <span class="font-bold text-red-600">{{ number_format($statistics['total_expense'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ลิงก์ด่วน</h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.wallet.all-transactions', ['wallet_id' => $wallet->id]) }}" class="block px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 transition">
                        📊 ดูธุรกรรมทั้งหมด
                    </a>
                    <a href="{{ route('admin.wallet.all-logs', ['wallet_id' => $wallet->id]) }}" class="block px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 transition">
                        📋 ดู Activity Logs
                    </a>
                    <a href="{{ route('admin.wallet.all') }}" class="block px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 transition">
                        💼 กระเป๋าทั้งหมด
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
