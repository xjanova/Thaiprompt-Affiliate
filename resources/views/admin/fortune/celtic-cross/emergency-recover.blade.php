{{-- ════════════════════════════════════════════════════════════
     Emergency Recovery — Celtic Cross  (Theme V4 "นวลทองคำ")
     กู้บิล Celtic Cross ที่ลูกค้าจ่ายแล้วแต่บอทเงียบ — ใส่เลขบิลแล้ว re-push
     คงฟอร์ม/field/action เดิม 100% (mode/bills/minutes/notify_message)
     ════════════════════════════════════════════════════════════ --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ───────── Header ───────── --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · Celtic Cross
            </div>
            <h1 class="tp-num" style="margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-triangle-exclamation" style="color:#d9534f;"></i>
                กู้บิลด่วน (Emergency Recovery)
            </h1>
            <div class="tp-muted" style="margin-top:6px;font-size:13px;max-width:560px;">
                กู้บิล Celtic Cross ที่ลูกค้าจ่ายแล้วแต่บอทเงียบ — ใส่เลขบิลแล้ว re-push ทันที
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.fortune.celtic-cross.index'))
                <a href="{{ route('admin.fortune.celtic-cross.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-arrow-left"></i> กลับไป Celtic Cross
                </a>
            @endif
        </div>
    </div>

    {{-- ───────── Stuck count tile ───────── --}}
    <div class="tp-card tp-raise" style="border-left:4px solid #e0a52e;">
        <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
            <div class="tp-tile" style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#e0a52e;flex:0 0 auto;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div style="flex:1;min-width:240px;">
                <div style="font-size:14px;color:var(--ink);">
                    ตอนนี้มี
                    <span class="tp-num" style="font-size:26px;font-weight:700;color:#e0a52e;vertical-align:-3px;">{{ $stuckCount }}</span>
                    reading ค้าง
                    <span class="tp-pill tp-pill-soft" style="margin-left:6px;">paid · ยังไม่ใช้สิทธิ์ · ค้าง ≥ 5 นาที</span>
                </div>
                <div class="tp-muted" style="font-size:12px;margin-top:6px;">
                    Auto-scanner รันทุก 5 นาที — ถ้าเร่งด่วนใช้ปุ่ม "สแกน + กู้ทั้งหมด" ด้านล่าง
                </div>
            </div>
        </div>
    </div>

    {{-- ───────── Two forms grid ───────── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px;">

        {{-- ───── โหมด A — กู้ตามเลขบิล ───── --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-list-check" style="color:#d9534f;"></i>
                <span>โหมด A — กู้ตามเลขบิล</span>
            </div>
            <div class="tp-muted" style="font-size:13px;margin:8px 0 16px;">
                วางเลขบิล (เช่น
                <span class="tp-pill tp-pill-soft" style="font-family:monospace;">FTU-260505-J1439</span>)
                หรือ reading ID — แยกบรรทัด / comma / เว้นวรรค ก็ได้
            </div>

            <form method="POST" action="{{ route('admin.fortune.celtic-cross.emergency-recover.action') }}">
                @csrf
                <input type="hidden" name="mode" value="bills">

                <div class="tp-well tp-input" style="padding:0;margin-bottom:14px;">
                    <textarea name="bills"
                              rows="4"
                              required
                              placeholder="FTU-260505-J1439&#10;FTU-260505-K2345&#10;หรือ reading ID เช่น 12345"
                              style="width:100%;background:transparent;border:0;outline:0;padding:12px 14px;color:var(--ink);font-size:14px;font-family:monospace;resize:vertical;line-height:1.6;"></textarea>
                </div>

                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">
                    ข้อความหัวเรื่อง (เปล่า = ใช้ข้อความ default "ขออภัยที่ทำให้รอนะคะ...")
                </label>
                <div class="tp-well tp-input" style="padding:0;margin-bottom:18px;">
                    <input type="text"
                           name="notify_message"
                           placeholder="(optional) เช่น: ขอบคุณที่อดทนรอนะคะ ระบบกลับมาแล้ว 🙏"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>

                <button type="submit"
                        onclick="return confirm('ยืนยันกู้บิลตามรายการ? ลูกค้าจะได้รับ message ใหม่ทันที')"
                        class="tp-btn tp-btn-primary"
                        style="width:100%;justify-content:center;background:#d9534f;border-color:#d9534f;">
                    <i class="fas fa-triangle-exclamation"></i> กู้บิลตามรายการ
                </button>
            </form>
        </div>

        {{-- ───── โหมด B — กู้ทั้งหมด (auto-scan) ───── --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-rotate" style="color:#d6824a;"></i>
                <span>โหมด B — กู้ทั้งหมด (auto-scan)</span>
            </div>
            <div class="tp-muted" style="font-size:13px;margin:8px 0 16px;">
                สแกน Celtic readings ที่
                <strong style="color:var(--ink);">paid + questions_used=0 + ค้าง ≥ N นาที</strong>
                แล้วกู้ทั้งหมด
            </div>

            <form method="POST" action="{{ route('admin.fortune.celtic-cross.emergency-recover.action') }}">
                @csrf
                <input type="hidden" name="mode" value="auto">

                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">
                    เกณฑ์เวลาที่ค้าง (นาที)
                </label>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                    <div class="tp-well tp-input" style="padding:0;width:120px;">
                        <input type="number"
                               name="minutes"
                               value="5"
                               min="1"
                               max="1440"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                    <span class="tp-muted" style="font-size:13px;">นาที</span>
                </div>

                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">
                    ข้อความหัวเรื่อง (optional)
                </label>
                <div class="tp-well tp-input" style="padding:0;margin-bottom:18px;">
                    <input type="text"
                           name="notify_message"
                           placeholder="(optional)"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>

                <button type="submit"
                        onclick="return confirm('ยืนยันสแกน + กู้ทั้งหมด? ลูกค้าทุกคนที่ค้างจะได้รับ message ใหม่')"
                        class="tp-btn tp-btn-primary"
                        style="width:100%;justify-content:center;background:#d6824a;border-color:#d6824a;">
                    <i class="fas fa-rotate"></i> สแกน + กู้ทั้งหมด
                </button>
            </form>
        </div>
    </div>

    {{-- ───────── ผลการกู้ ───────── --}}
    @if(! empty($results))
        <div class="tp-card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
                <div class="tp-section-h" style="margin:0;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-chart-column" style="color:var(--accent1);"></i>
                    <span>ผลการกู้</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;border-color:rgba(90,160,126,.35);">
                        <i class="fas fa-circle-check"></i> สำเร็จ {{ $okCount ?? 0 }}
                    </span>
                    @if(($failCount ?? 0) > 0)
                        <span class="tp-pill" style="background:rgba(217,83,79,.16);color:#d9534f;border-color:rgba(217,83,79,.35);">
                            <i class="fas fa-circle-xmark"></i> ล้มเหลว {{ $failCount }}
                        </span>
                    @endif
                </div>
            </div>

            @if(! empty($notFound))
                <div class="tp-inset" style="padding:12px 14px;margin-bottom:16px;border-left:3px solid #e0a52e;">
                    <span style="color:#e0a52e;"><i class="fas fa-triangle-exclamation"></i> บิลที่ไม่พบ:</span>
                    <code style="color:var(--ink);font-size:13px;">{{ implode(', ', $notFound) }}</code>
                </div>
            @endif

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="text-align:left;color:var(--ink2);border-bottom:1px solid var(--sd);">
                            <th style="padding:10px 12px;font-weight:600;">ID</th>
                            <th style="padding:10px 12px;font-weight:600;">บิล</th>
                            <th style="padding:10px 12px;font-weight:600;">ลูกค้า</th>
                            <th style="padding:10px 12px;font-weight:600;">Platform</th>
                            <th style="padding:10px 12px;font-weight:600;">Status (ก่อน → หลัง)</th>
                            <th style="padding:10px 12px;font-weight:600;text-align:center;">เปิดไปแล้ว</th>
                            <th style="padding:10px 12px;font-weight:600;">ผล</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $row)
                            <tr style="border-bottom:1px solid var(--sd);{{ $row['ok'] ? '' : 'background:rgba(217,83,79,.06);' }}">
                                <td style="padding:10px 12px;font-family:monospace;">
                                    @if(Route::has('admin.fortune.celtic-cross.show'))
                                        <a href="{{ route('admin.fortune.celtic-cross.show', $row['id']) }}"
                                           style="color:var(--accent1);text-decoration:none;">#{{ $row['id'] }}</a>
                                    @else
                                        <span style="color:var(--ink);">#{{ $row['id'] }}</span>
                                    @endif
                                </td>
                                <td style="padding:10px 12px;font-family:monospace;font-size:12px;color:var(--ink2);">{{ $row['bill'] }}</td>
                                <td style="padding:10px 12px;color:var(--ink);">{{ $row['user'] }}</td>
                                <td style="padding:10px 12px;color:var(--ink2);">{{ $row['platform'] }}</td>
                                <td style="padding:10px 12px;font-size:12px;color:var(--ink2);">
                                    <code>{{ $row['status_before'] }}</code>
                                    @if(! empty($row['status_after']) && $row['status_after'] !== $row['status_before'])
                                        <span class="tp-muted">→</span>
                                        <code style="color:#5aa07e;">{{ $row['status_after'] }}</code>
                                    @endif
                                </td>
                                <td style="padding:10px 12px;text-align:center;color:var(--ink2);">{{ $row['picked'] }}/10</td>
                                <td style="padding:10px 12px;color:{{ $row['ok'] ? '#5aa07e' : '#d9534f' }};">
                                    {{ $row['msg'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(! empty($summary))
                <div class="tp-muted" style="margin-top:16px;font-size:13px;">{{ $summary }}</div>
            @endif
        </div>
    @endif

    {{-- ───────── Help / CLI fallback ───────── --}}
    <div class="tp-card" style="border-left:4px solid #5689b8;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:10px;">
            <i class="fas fa-circle-info" style="color:#5689b8;"></i>
            <span>การกู้ทำอะไร</span>
        </div>
        <ul style="margin:12px 0 0;padding-left:20px;color:var(--ink2);font-size:13px;line-height:1.9;">
            <li>เคส <code>celtic_pending_payment</code> + paid → transition → CELTIC_PICKING + ส่ง prompt ใบ 1</li>
            <li>เคส <code>new</code> + paid (slip transition fail) → force-promote → CELTIC_PICKING + ส่ง prompt ใบ 1</li>
            <li>เคส <code>celtic_picking</code> ค้างกลางทาง → re-push prompt ใบที่กำลังจะเปิด (ไม่ reset ไพ่ที่เปิดแล้ว)</li>
            <li>เคส <code>celtic_awaiting_question</code> → re-push prompt "ถามคำถามได้เลย"</li>
        </ul>

        <div class="tp-divider" style="margin:18px 0;"></div>

        <div class="tp-section-h" style="display:flex;align-items:center;gap:10px;font-size:14px;">
            <i class="fas fa-terminal" style="color:#5689b8;"></i>
            <span>CLI fallback</span>
        </div>
        <div class="tp-inset-sm" style="margin-top:10px;padding:12px 14px;font-family:monospace;font-size:12px;color:var(--ink);line-height:1.9;">
            php artisan fortune:celtic-recover FTU-260505-J1439<br>
            php artisan fortune:celtic-recover --auto
        </div>
    </div>

</div>
@endsection
