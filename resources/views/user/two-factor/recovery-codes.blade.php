@extends('layouts.user-v4')

@section('title', 'รหัสกู้คืน 2FA')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:900px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #7c5cbf 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:24px; background:#7c5cbf;"><span style="color:#fff;">🔑</span></span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">รหัสกู้คืน (Recovery Codes)</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">เก็บรหัสเหล่านี้ไว้ในที่ปลอดภัย</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── คำเตือน ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px; border-left:4px solid #d9534f;">
        <div style="display:flex; gap:14px;">
            <span style="font-size:32px;">⚠️</span>
            <div>
                <div style="font-size:18px; font-weight:800; color:#d9534f; margin-bottom:8px;">ข้อควรระวัง - สำคัญมาก!</div>
                <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:8px; color:var(--ink);">
                    <li style="display:flex; gap:8px;"><span>•</span><span><strong>เก็บรักษารหัสเหล่านี้ให้ปลอดภัย</strong> - อย่าแชร์ให้ใครเห็น</span></li>
                    <li style="display:flex; gap:8px;"><span>•</span><span><strong>แต่ละรหัสใช้ได้เพียงครั้งเดียว</strong> - เมื่อใช้แล้วจะใช้ซ้ำไม่ได้</span></li>
                    <li style="display:flex; gap:8px;"><span>•</span><span><strong>พิมพ์หรือบันทึกไว้ในที่ปลอดภัย</strong> - เช่น Password Manager</span></li>
                    <li style="display:flex; gap:8px;"><span>•</span><span><strong>ใช้เมื่อไม่สามารถเข้าถึงวิธีการยืนยันหลักได้</strong> - กรณีฉุกเฉิน</span></li>
                </ul>
            </div>
        </div>
    </div>

    @if(!empty($codes) && count($codes) > 0)
        {{-- ── รหัสกู้คืน ─────────────────────────────────────── --}}
        <div class="tp-card" style="padding:24px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px;">
                <div class="tp-section-h">รหัสกู้คืนของคุณ</div>
                <span style="padding:6px 14px; border-radius:10px; font-weight:800; color:#5689b8; background:color-mix(in srgb, #5689b8 16%, transparent);">{{ count($codes) }} รหัส</span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-bottom:20px;" id="codesGrid">
                @foreach($codes as $index => $code)
                    <div style="border-radius:12px; box-shadow:var(--inset-sm); padding:14px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span style="width:32px; height:32px; background:color-mix(in srgb, #5689b8 16%, transparent); color:#5689b8; border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">{{ $index + 1 }}</span>
                                <code style="font-size:16px; font-family:monospace; font-weight:800; color:var(--ink);">{{ $code }}</code>
                            </div>
                            <button type="button" onclick="copyCode('{{ $code }}')" title="คัดลอก" style="padding:8px; background:color-mix(in srgb, #5689b8 16%, transparent); color:#5689b8; border:none; border-radius:10px; cursor:pointer;">
                                <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px;">
                <button type="button" onclick="copyAllCodes()" class="tp-btn tp-btn-primary" style="justify-content:center; background:#5689b8; border-color:#5689b8;"><i class="fas fa-copy"></i> คัดลอกทั้งหมด</button>
                <button type="button" onclick="downloadCodes()" class="tp-btn" style="justify-content:center; background:#5aa07e; border-color:#5aa07e; color:#fff;"><i class="fas fa-download"></i> ดาวน์โหลด</button>
                <button type="button" onclick="printCodes()" class="tp-btn" style="justify-content:center; background:#7c5cbf; border-color:#7c5cbf; color:#fff;"><i class="fas fa-print"></i> พิมพ์</button>
            </div>
        </div>

        {{-- ── วิธีใช้ ────────────────────────────────────────── --}}
        <div class="tp-card" style="padding:24px;">
            <div class="tp-section-h" style="margin-bottom:16px;">💡 วิธีใช้รหัสกู้คืน</div>
            <div style="display:flex; flex-direction:column; gap:14px;">
                @foreach([['1','เมื่อไหร่ควรใช้?','เมื่อคุณไม่สามารถรับรหัส 2FA ผ่านช่องทางหลักได้ (เช่น เปลี่ยนเบอร์โทรศัพท์, ไม่มีอุปกรณ์)'],['2','วิธีใช้งาน','ในหน้ายืนยันตัวตน 2FA ให้คลิก "ใช้รหัสกู้คืนแทน" แล้วกรอกรหัสกู้คืนที่เหลืออยู่'],['3','หลังใช้งาน','รหัสที่ใช้แล้วจะใช้ซ้ำไม่ได้ ถ้ารหัสใกล้หมดควรสร้างรหัสใหม่']] as $step)
                    <div style="display:flex; gap:12px;">
                        <span style="width:32px; height:32px; background:#5689b8; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0;">{{ $step[0] }}</span>
                        <div><div style="font-weight:700; color:var(--ink);">{{ $step[1] }}</div><div style="font-size:13px; color:var(--ink2); margin-top:2px;">{{ $step[2] }}</div></div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="tp-card" style="padding:48px; text-align:center;">
            <span style="font-size:56px; display:block; margin-bottom:16px;">🔒</span>
            <h2 style="font-size:20px; font-weight:800; color:var(--ink); margin:0 0 8px;">ไม่พบรหัสกู้คืน</h2>
            <p style="color:var(--ink2); margin:0 0 20px;">คุณยังไม่มีรหัสกู้คืน หรือรหัสเดิมหมดอายุแล้ว</p>
            <form action="{{ route('user.two-factor.regenerate-codes') }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('สร้างรหัสกู้คืนใหม่?')" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;">สร้างรหัสกู้คืนใหม่</button>
            </form>
        </div>
    @endif

    <div style="text-align:center;">
        <a href="{{ route('user.two-factor.setup') }}" class="tp-btn"><i class="fas fa-arrow-left"></i> กลับไปหน้าตั้งค่า 2FA</a>
    </div>
</div>

{{-- Toast --}}
<div id="toast" class="hidden" style="position:fixed; bottom:16px; right:16px; background:#5aa07e; color:#fff; padding:12px 24px; border-radius:10px; box-shadow:var(--raise); z-index:60;">
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
