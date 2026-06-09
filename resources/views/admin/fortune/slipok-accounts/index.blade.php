@extends('layouts.admin-v3')

@section('title', 'SlipOK Account Pool')

@section('content')
<div class="container mx-auto px-4 py-6"
     x-data="slipokPool()">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🪪 SlipOK Account Pool</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                หมุนเวียนหลายบัญชี SlipOK กันโควต้าฟรี (~100/เดือน) ตันทั้งระบบ
            </p>
        </div>
        <button @click="openAdd()"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
            + เพิ่มบัญชี
        </button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 text-sm">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 text-sm">
            @foreach($errors->all() as $e)<div>⚠️ {{ $e }}</div>@endforeach
        </div>
    @endif

    {{-- Pool mode settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚙️ โหมดการหมุนบัญชี</h2>
        <form method="POST" action="{{ route('admin.fortune.slipok-accounts.update-mode') }}"
              class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @csrf
            @method('PUT')

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="slipok_pool_enabled" value="1"
                       {{ ($settings->slipok_pool_enabled ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-indigo-600">
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">เปิดใช้ Pool</span>
            </label>

            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">โหมด</label>
                <select name="slipok_pool_mode"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($modes as $key => $label)
                        <option value="{{ $key }}" {{ ($settings->slipok_pool_mode ?? 'near_empty') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">เกณฑ์ใกล้หมด (near-empty)</label>
                <input type="number" name="slipok_pool_threshold" min="1"
                       value="{{ $settings->slipok_pool_threshold ?? 10 }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>

            <button type="submit"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition">
                บันทึกโหมด
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-3">
            🔁 <b>near-empty</b>: เหลือ &lt; เกณฑ์ → สลับบัญชีถัดไป &nbsp;|&nbsp;
            🛟 <b>failover</b>: ใช้จนหมด/พังค่อยสลับ &nbsp;|&nbsp;
            ⚖️ <b>balance</b>: เฉลี่ย เลือกตัวเหลือมากสุด &nbsp;·&nbsp;
            ทุกโหมดมี <b>auto-failover</b> เมื่อ API หมดโควต้า/คีย์พังในระหว่างยิง
        </p>
    </div>

    {{-- Accounts table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-300">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">บัญชี</th>
                        <th class="px-4 py-3">Branch ID</th>
                        <th class="px-4 py-3">API Key</th>
                        <th class="px-4 py-3 text-center">ใช้/โควต้า</th>
                        <th class="px-4 py-3 text-center">คงเหลือ</th>
                        <th class="px-4 py-3 text-center">สถานะ</th>
                        <th class="px-4 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($accounts as $a)
                        @php
                            $remaining = $a->remainingQuota();
                            $isExhausted = $a->isExhausted();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-3 text-gray-500">{{ $a->priority }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $a->label ?: '—' }}</div>
                                @if($a->last_error)
                                    <div class="text-xs text-red-500 truncate max-w-[180px]" title="{{ $a->last_error }}">⚠️ {{ $a->last_error }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $a->branch_id }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-400">{{ $a->masked_key }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                {{ $a->used_this_month }} / {{ $a->monthly_quota }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-semibold {{ $remaining <= ($settings->slipok_pool_threshold ?? 10) ? 'text-orange-500' : 'text-green-600 dark:text-green-400' }}">
                                    {{ $remaining }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(!$a->is_active)
                                    <span class="px-2 py-1 rounded-full text-xs bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">ปิด</span>
                                @elseif($isExhausted)
                                    <span class="px-2 py-1 rounded-full text-xs bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300">พัก</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">พร้อม</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            @click='testAccount({{ $a->id }})'
                                            class="px-2.5 py-1 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 text-xs hover:bg-blue-100">
                                        <span x-show="testing !== {{ $a->id }}">เทส</span>
                                        <span x-show="testing === {{ $a->id }}">...</span>
                                    </button>
                                    {{-- 🔧 (2026-06-09) ใช้ double-quote ครอบ @click — Js::from() output = JSON.parse('...')
                                         มี single-quote ข้างใน ถ้าครอบด้วย single-quote attribute จะตัดจบเร็ว → @click พัง → modal ไม่เปิด --}}
                                    <button type="button"
                                            @click="openEdit({{ Js::from([
                                                'id' => $a->id,
                                                'label' => $a->label,
                                                'branch_id' => $a->branch_id,
                                                'priority' => $a->priority,
                                                'monthly_quota' => $a->monthly_quota,
                                                'notes' => $a->notes,
                                            ]) }})"
                                            class="px-2.5 py-1 rounded bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-300 text-xs hover:bg-amber-100">
                                        แก้ไข
                                    </button>
                                    <form method="POST" action="{{ route('admin.fortune.slipok-accounts.toggle', $a->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs hover:bg-gray-200">
                                            {{ $a->is_active ? 'ปิด' : 'เปิด' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.fortune.slipok-accounts.destroy', $a->id) }}" class="inline"
                                          onsubmit="return confirm('ลบบัญชีนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-300 text-xs hover:bg-red-100">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                                ยังไม่มีบัญชีใน pool — กด "เพิ่มบัญชี" เพื่อเริ่ม
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add / Edit modal --}}
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @click.self="showModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4"
                x-text="form.id ? 'แก้ไขบัญชี SlipOK' : 'เพิ่มบัญชี SlipOK'"></h3>

            <form :action="form.id
                    ? '{{ url('admin/fortune/slipok-accounts') }}/' + form.id
                    : '{{ route('admin.fortune.slipok-accounts.store') }}'"
                  method="POST" class="space-y-4">
                @csrf
                {{-- 🔧 (2026-06-09) method spoof แบบ always-present + bound — เชื่อถือได้กว่า <template x-if>
                     (x-if บางครั้ง inject ไม่ทัน → POST /{account} ไม่มี route → 405 → แก้ไขไม่บันทึก) --}}
                <input type="hidden" name="_method" x-bind:value="form.id ? 'PUT' : 'POST'">

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ชื่อเรียก (label)</label>
                    <input type="text" name="label" x-model="form.label"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Branch ID <span class="text-red-500">*</span></label>
                    <input type="text" name="branch_id" x-model="form.branch_id" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                        API Key <span x-show="!form.id" class="text-red-500">*</span>
                        <span x-show="form.id" class="text-gray-400">(เว้นว่าง = ไม่เปลี่ยน)</span>
                    </label>
                    <input type="text" name="api_key" :required="!form.id" autocomplete="off"
                           placeholder="SLIPOKXXXXXXX"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ลำดับ (priority)</label>
                        <input type="number" name="priority" x-model="form.priority" min="0"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">โควต้า/เดือน</label>
                        <input type="number" name="monthly_quota" x-model="form.monthly_quota" min="1"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">หมายเหตุ</label>
                    <input type="text" name="notes" x-model="form.notes"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showModal = false"
                            class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm">ยกเลิก</button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- 🔧 (2026-06-09) Alpine component อยู่ใน @push('scripts') (โหลดก่อน Alpine init)
     — ตรงกับ pattern ที่ใช้ได้จริงในหน้าอื่น (saved-questions ฯลฯ) --}}
@push('scripts')
<script>
function slipokPool() {
    return {
        showModal: false,
        testing: null,
        form: { id: null, label: '', branch_id: '', priority: 100, monthly_quota: 100, notes: '' },
        openAdd() {
            this.form = { id: null, label: '', branch_id: '', priority: 100, monthly_quota: 100, notes: '' };
            this.showModal = true;
        },
        openEdit(data) {
            this.form = {
                id: data.id,
                label: data.label || '',
                branch_id: data.branch_id || '',
                priority: data.priority ?? 100,
                monthly_quota: data.monthly_quota ?? 100,
                notes: data.notes || '',
            };
            this.showModal = true;
        },
        async testAccount(id) {
            this.testing = id;
            try {
                const res = await fetch('{{ url('admin/fortune/slipok-accounts') }}/' + id + '/test', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    alert('✅ เชื่อมต่อสำเร็จ\nโควต้าคงเหลือ: ' + (data.quota ?? '?') +
                          (data.specialQuota ? ' (+พิเศษ ' + data.specialQuota + ')' : '') +
                          (data.endDate ? '\nหมดอายุ: ' + data.endDate : ''));
                    window.location.reload();
                } else {
                    alert('❌ ' + (data.message || 'เชื่อมต่อไม่สำเร็จ'));
                }
            } catch (e) {
                alert('❌ ทดสอบไม่สำเร็จ: ' + e.message);
            } finally {
                this.testing = null;
            }
        },
    };
}
</script>
@endpush
