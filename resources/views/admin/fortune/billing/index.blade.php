@extends('layouts.admin-v4')

@section('title', 'จัดการบิลดูดวง')

@section('content')
{{-- 💰 หน้าจัดการบิลดูดวง — ธีม V4 นวลทองคำ (financial dashboard) --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ╔═══ HEADER ═══╗ --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · จัดการบิล
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;">
                <i class="fas fa-sack-dollar" style="color:var(--accent1);margin-right:8px;"></i>จัดการบิลดูดวง
            </h1>
            <div class="tp-muted" style="font-size:13px;margin-top:4px;">ดูรายได้ สถิติ และจัดการบิลทั้งหมด</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.fortune.billing.floating-bills'))
                <a href="{{ route('admin.fortune.billing.floating-bills') }}" class="tp-btn" style="position:relative;">
                    <i class="fas fa-clipboard-list" style="color:#d6824a;"></i> บิลลอย
                    @if($stats['floating']['count'] > 0)
                        <span class="tp-pill" style="background:#d6824a;color:#fff;margin-left:6px;font-size:11px;padding:1px 8px;">
                            {{ $stats['floating']['count'] }}
                        </span>
                    @endif
                </a>
            @endif
            @if(Route::has('admin.fortune.billing.export-revenue'))
                <a href="{{ route('admin.fortune.billing.export-revenue', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                   class="tp-btn tp-btn-primary">
                    <i class="fas fa-file-arrow-down"></i> Export รายได้
                </a>
            @endif
        </div>
    </div>

    {{-- ╔═══ KPI หลัก — รายได้ ═══╗ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
        {{-- รายได้วันนี้ --}}
        <div class="tp-card tp-card-hover">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:13px;">รายได้วันนี้</span>
                    <i class="fas fa-money-bill-wave" style="color:#5aa07e;font-size:18px;"></i>
                </div>
                <div class="tp-num" style="font-size:24px;font-weight:800;">฿{{ number_format($stats['today']['revenue'], 2) }}</div>
                <div class="tp-muted" style="font-size:12px;">{{ $stats['today']['count'] }} รายการ</div>
            </div>
        </div>

        {{-- รายได้เดือนนี้ --}}
        <div class="tp-card tp-card-hover">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:13px;">รายได้เดือนนี้</span>
                    <i class="fas fa-chart-column" style="color:#5689b8;font-size:18px;"></i>
                </div>
                <div class="tp-num" style="font-size:24px;font-weight:800;">฿{{ number_format($stats['month']['revenue'], 2) }}</div>
                <div class="tp-muted" style="font-size:12px;">{{ $stats['month']['count'] }} รายการ</div>
            </div>
        </div>

        {{-- รายได้ทั้งหมด --}}
        <div class="tp-card tp-card-hover">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:13px;">รายได้ทั้งหมด</span>
                    <i class="fas fa-gem" style="color:#b79ae8;font-size:18px;"></i>
                </div>
                <div class="tp-num" style="font-size:24px;font-weight:800;color:var(--accent1);">฿{{ number_format($stats['total']['revenue'], 2) }}</div>
                <div class="tp-muted" style="font-size:12px;">{{ $stats['total']['count'] }} รายการ</div>
            </div>
        </div>

        {{-- อัตราแปลง (Conversion) --}}
        <div class="tp-card tp-card-hover">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="tp-muted" style="font-size:13px;">Conversion Rate</span>
                    <i class="fas fa-arrow-trend-up" style="color:#e0a52e;font-size:18px;"></i>
                </div>
                <div class="tp-num" style="font-size:24px;font-weight:800;">{{ $stats['conversion_rate'] }}%</div>
                <div class="tp-muted" style="font-size:12px;">{{ $stats['period']['count'] }}/{{ $stats['total_readings_period'] }} รายการ</div>
            </div>
        </div>
    </div>

    {{-- ╔═══ KPI รอง ═══╗ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
        {{-- บิลลอย --}}
        <div class="tp-card">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:6px;border-left:3px solid #d6824a;">
                <span class="tp-muted" style="font-size:12px;">บิลลอย (ยังไม่ระบุผู้ใช้)</span>
                <span class="tp-num" style="font-size:22px;font-weight:800;">{{ $stats['floating']['count'] }}</span>
                <span style="color:#d6824a;font-size:13px;font-weight:600;">฿{{ number_format($stats['floating']['amount'], 2) }}</span>
            </div>
        </div>

        {{-- รอชำระ --}}
        <div class="tp-card">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:6px;border-left:3px solid #e0a52e;">
                <span class="tp-muted" style="font-size:12px;">รอชำระเงิน</span>
                <span class="tp-num" style="font-size:22px;font-weight:800;">{{ $stats['pending']['count'] }}</span>
                <span style="color:#e0a52e;font-size:13px;font-weight:600;">รอการโอนเงิน</span>
            </div>
        </div>

        {{-- เฉลี่ยต่อบิล --}}
        <div class="tp-card">
            <div class="tp-tile" style="display:flex;flex-direction:column;gap:6px;border-left:3px solid #5aa07e;">
                <span class="tp-muted" style="font-size:12px;">เฉลี่ยต่อบิล</span>
                <span class="tp-num" style="font-size:22px;font-weight:800;">฿{{ number_format($stats['period']['avg_per_bill'], 2) }}</span>
                <span style="color:#5aa07e;font-size:13px;font-weight:600;">ในช่วงที่เลือก</span>
            </div>
        </div>
    </div>

    {{-- ╔═══ กราฟรายได้ 7 วัน — CSS .tp-bars (แทน Chart.js เดิม) ═══╗ --}}
    @php
        // 📊 หา max เพื่อ scale ความสูงแท่ง
        $maxRevenue = collect($dailyRevenue)->max('revenue') ?: 1;
    @endphp
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
            <i class="fas fa-chart-line" style="color:var(--accent1);"></i>
            <span style="font-weight:700;font-size:15px;">รายได้ 7 วันล่าสุด</span>
        </div>
        <div class="tp-divider"></div>
        @if($maxRevenue > 0)
            <div class="tp-bars" style="--bars-h:200px;margin-top:8px;">
                @foreach($dailyRevenue as $day)
                    @php
                        // 🔢 คำนวณ % ความสูงแท่ง (กันหาร 0)
                        $pct = $maxRevenue > 0 ? round(($day['revenue'] / $maxRevenue) * 100, 1) : 0;
                    @endphp
                    <div class="col" title="฿{{ number_format($day['revenue'], 2) }} · {{ $day['count'] }} รายการ">
                        <div class="bar a" style="height:{{ max($pct, 2) }}%;"></div>
                        <div class="lbl">{{ $day['date_label'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="tp-muted" style="text-align:center;padding:32px 0;">
                <i class="fas fa-inbox" style="font-size:28px;display:block;margin-bottom:8px;opacity:.5;"></i>
                ยังไม่มีรายได้ในช่วงนี้
            </div>
        @endif
    </div>

    {{-- ╔═══ ตัวกรอง ═══╗ --}}
    <div class="tp-card">
        <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;align-items:end;">
            {{-- วันที่เริ่มต้น --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;font-weight:600;">วันที่เริ่มต้น</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
            </div>

            {{-- วันที่สิ้นสุด --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;font-weight:600;">วันที่สิ้นสุด</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
            </div>

            {{-- สถานะ --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;font-weight:600;">สถานะ</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="status" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        <option value="">ทั้งหมด</option>
                        <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>รอชำระ</option>
                        <option value="floating" {{ $status === 'floating' ? 'selected' : '' }}>บิลลอย</option>
                        <option value="free" {{ $status === 'free' ? 'selected' : '' }}>ฟรี</option>
                    </select>
                </div>
            </div>

            {{-- ปุ่ม --}}
            <div style="display:flex;gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> กรอง
                </button>
                <a href="{{ route('admin.fortune.billing.index') }}" class="tp-btn">
                    <i class="fas fa-eraser"></i> ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- ╔═══ ตารางบิล ═══╗ --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
            <i class="fas fa-receipt" style="color:var(--accent1);"></i>
            <span style="font-weight:700;font-size:15px;">รายการบิล</span>
        </div>
        <div class="tp-divider"></div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:separate;border-spacing:0 8px;min-width:880px;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:8px 14px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink2);font-weight:700;">วันที่</th>
                        <th style="padding:8px 14px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink2);font-weight:700;">ผู้ใช้</th>
                        <th style="padding:8px 14px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink2);font-weight:700;">ประเภท</th>
                        <th style="padding:8px 14px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink2);font-weight:700;">จำนวนเงิน</th>
                        <th style="padding:8px 14px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink2);font-weight:700;">สถานะ</th>
                        <th style="padding:8px 14px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink2);font-weight:700;">ช่องทาง</th>
                        <th style="padding:8px 14px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink2);font-weight:700;text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills as $bill)
                        {{-- 🔁 แต่ละแถวต้องมี x-data เอง (Alpine subtree init) --}}
                        <tr x-data="{ submitting: false }" style="box-shadow:var(--inset-sm);border-radius:12px;">
                            {{-- วันที่ --}}
                            <td style="padding:12px 14px;font-size:13px;color:var(--ink);white-space:nowrap;border-radius:12px 0 0 12px;">
                                <div class="tp-num" style="font-weight:600;">{{ $bill->created_at->format('d/m/Y') }}</div>
                                <div class="tp-muted" style="font-size:11px;">{{ $bill->created_at->format('H:i') }}</div>
                            </td>

                            {{-- ผู้ใช้ --}}
                            <td style="padding:12px 14px;font-size:13px;color:var(--ink);">
                                <div style="font-weight:600;">{{ $bill->facebook_user_name ?? $bill->user?->name ?? 'ไม่ระบุ' }}</div>
                                <div class="tp-muted" style="font-size:11px;">
                                    @if($bill->is_floating)
                                        <span style="color:#d6824a;">บิลลอย</span>
                                    @else
                                        {{ $bill->facebook_user_id }}
                                    @endif
                                </div>
                            </td>

                            {{-- ประเภท --}}
                            <td style="padding:12px 14px;white-space:nowrap;">
                                @if($bill->reading_type === 'deep')
                                    <span class="tp-pill" style="background:var(--a2soft);color:#b79ae8;font-size:12px;">
                                        <i class="fas fa-star"></i> เชิงลึก
                                    </span>
                                @else
                                    <span class="tp-pill" style="background:var(--a1soft);color:#5689b8;font-size:12px;">
                                        <i class="fas fa-moon"></i> พื้นฐาน
                                    </span>
                                @endif
                            </td>

                            {{-- จำนวนเงิน --}}
                            <td style="padding:12px 14px;white-space:nowrap;font-weight:700;" class="tp-num">
                                @if($bill->is_paid)
                                    <span style="color:#5aa07e;">฿{{ number_format($bill->amount_paid, 2) }}</span>
                                @elseif($bill->conversation_status === 'pending_payment')
                                    <span style="color:#e0a52e;">฿{{ number_format($bill->amount_paid, 2) }}</span>
                                @else
                                    <span class="tp-muted">-</span>
                                @endif
                            </td>

                            {{-- สถานะ --}}
                            <td style="padding:12px 14px;white-space:nowrap;">
                                @php
                                    // 🏷️ (2026-05-25) Cancellation detection — ดู conversation_state.cancellation_reason
                                    //    Cancelled bills ใช้ status=completed + is_paid=false (ไม่มี STATUS_CANCELLED แยก)
                                    $cancellationLabel = $bill->getCancellationReasonLabelOrNull();
                                @endphp
                                @if($bill->is_paid)
                                    <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;font-size:12px;">
                                        <i class="fas fa-circle-check"></i> ชำระแล้ว
                                    </span>
                                @elseif($bill->conversation_status === 'pending_payment')
                                    <span class="tp-pill" style="background:rgba(224,165,46,.16);color:#e0a52e;font-size:12px;">
                                        <i class="fas fa-hourglass-half"></i> รอชำระ
                                    </span>
                                @elseif($cancellationLabel)
                                    <span class="tp-pill" style="background:rgba(217,83,79,.16);color:#d9534f;font-size:12px;"
                                          title="{{ data_get($bill->conversation_state, 'cancellation_reason', 'unknown') }}">
                                        <i class="fas fa-circle-xmark"></i> {{ $cancellationLabel }}
                                    </span>
                                @elseif($bill->is_floating)
                                    <span class="tp-pill" style="background:rgba(214,130,74,.16);color:#d6824a;font-size:12px;">
                                        <i class="fas fa-clipboard-list"></i> บิลลอย
                                    </span>
                                @else
                                    <span class="tp-pill tp-pill-soft" style="font-size:12px;">ฟรี</span>
                                @endif
                            </td>

                            {{-- ช่องทาง --}}
                            <td style="padding:12px 14px;white-space:nowrap;font-size:13px;color:var(--ink2);">
                                @if($bill->payment_method === 'stripe')
                                    {{-- 💳 Stripe payment --}}
                                    <span style="color:#b79ae8;font-weight:700;"><i class="fas fa-credit-card"></i> Stripe</span>
                                    {{-- 🌍 Audit badge — country ที่ Stripe detect ตอนจ่ายจริง (เก็บไว้เพื่อ audit) --}}
                                    @if($bill->stripe_customer_region === 'th')
                                        <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;font-size:11px;margin-left:4px;"
                                              title="legacy TH tier — ไม่มี option แสดงให้ลูกค้าเลือกแล้ว">🇹🇭</span>
                                    @endif
                                    @if($bill->stripe_payment_intent_id)
                                        @php
                                            $stripeService = new \App\Services\Fortune\FortuneStripeService();
                                            $dashboardUrl = $stripeService->buildDashboardUrl($bill->stripe_payment_intent_id);
                                        @endphp
                                        <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener"
                                           style="display:block;font-size:11px;color:#b79ae8;margin-top:4px;text-decoration:none;"
                                           title="ดูใน Stripe Dashboard">
                                            <i class="fas fa-link"></i> {{ \Illuminate\Support\Str::limit($bill->stripe_payment_intent_id, 18) }}
                                        </a>
                                    @elseif($bill->stripe_session_id)
                                        <div class="tp-muted" style="font-size:11px;" title="{{ $bill->stripe_session_id }}">
                                            session: {{ \Illuminate\Support\Str::limit($bill->stripe_session_id, 14) }}
                                        </div>
                                    @endif
                                @elseif($bill->sms_notification_id)
                                    <span style="color:#5689b8;font-weight:600;"><i class="fas fa-mobile-screen"></i> SMS</span>
                                    @if($bill->sender_bank)
                                        <div class="tp-muted" style="font-size:11px;">{{ $bill->sender_bank }}</div>
                                    @endif
                                @elseif($bill->is_paid)
                                    <span class="tp-muted">Manual</span>
                                @else
                                    <span class="tp-muted">-</span>
                                @endif
                            </td>

                            {{-- จัดการ --}}
                            <td style="padding:12px 14px;white-space:nowrap;text-align:right;border-radius:0 12px 12px 0;">
                                <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;flex-wrap:wrap;">
                                    @if(Route::has('admin.fortune.readings.show'))
                                        <a href="{{ route('admin.fortune.readings.show', $bill) }}"
                                           style="color:#5689b8;text-decoration:none;font-size:13px;font-weight:600;" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i> ดู
                                        </a>
                                    @endif

                                    @if(!$bill->is_paid && $bill->conversation_status === 'pending_payment')
                                        <button onclick="showManualConfirm({{ $bill->id }}, {{ $bill->amount_paid }})"
                                                style="background:none;border:0;cursor:pointer;color:#5aa07e;font-size:13px;font-weight:600;">
                                            <i class="fas fa-check"></i> ยืนยัน
                                        </button>
                                    @endif

                                    @if($bill->is_paid && !$bill->is_floating)
                                        @if($bill->is_paid && !empty($bill->getCollectedQuestions()) && empty($bill->deep_response))
                                            <form action="{{ route('admin.fortune.billing.retry-fortune', $bill) }}" method="POST" style="display:inline;"
                                                  @submit="if(!confirm('ต้องการส่งคำทำนายให้ลูกค้าอีกครั้ง?')) { $event.preventDefault(); return; } submitting = true; showLoadingOverlay('กำลังส่งคำทำนาย...');">
                                                @csrf
                                                <button type="submit" :disabled="submitting"
                                                        style="background:none;border:0;cursor:pointer;color:#b79ae8;font-size:13px;font-weight:600;"
                                                        :style="submitting ? 'opacity:.5;' : ''">
                                                    <template x-if="!submitting"><span><i class="fas fa-wand-magic-sparkles"></i> ส่งคำทำนาย</span></template>
                                                    <template x-if="submitting"><span style="display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-spinner fa-spin"></i> กำลังส่ง...</span></template>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.fortune.billing.void', $bill) }}" method="POST" style="display:inline;"
                                              @submit="if(!confirm('ยืนยันยกเลิกบิลนี้?')) { $event.preventDefault(); return; } submitting = true;">
                                            @csrf
                                            <button type="submit" :disabled="submitting"
                                                    style="background:none;border:0;cursor:pointer;color:#d9534f;font-size:13px;font-weight:600;"
                                                    :style="submitting ? 'opacity:.5;' : ''">
                                                <i class="fas fa-ban"></i> ยกเลิก
                                            </button>
                                        </form>
                                    @endif

                                    {{-- 💳 (2026-05-09) Stripe-specific actions --}}
                                    @if($bill->payment_method === 'stripe')
                                        @if($bill->is_paid && $bill->stripe_payment_intent_id)
                                            {{-- Refund — confirm dialog + reason field --}}
                                            <button onclick="showStripeRefund({{ $bill->id }}, {{ $bill->amount_paid }})"
                                                    style="background:none;border:0;cursor:pointer;color:#d6824a;font-size:13px;font-weight:600;"
                                                    title="คืนเงินผ่าน Stripe">
                                                <i class="fas fa-money-bill-transfer"></i> Refund
                                            </button>
                                        @endif
                                        @if(!$bill->is_paid && $bill->stripe_session_id)
                                            {{-- Expire pending session --}}
                                            <form action="{{ route('admin.fortune.billing.stripe-expire', $bill) }}" method="POST" style="display:inline;"
                                                  @submit="if(!confirm('ยืนยัน expire session นี้? ลูกค้าจะจ่ายไม่ได้แล้ว')) { $event.preventDefault(); return; } submitting = true;">
                                                @csrf
                                                <button type="submit" :disabled="submitting"
                                                        style="background:none;border:0;cursor:pointer;color:#d6824a;font-size:13px;font-weight:600;"
                                                        :style="submitting ? 'opacity:.5;' : ''">
                                                    <i class="fas fa-clock"></i> Expire
                                                </button>
                                            </form>
                                        @endif
                                        @if($bill->stripe_session_id)
                                            {{-- Resync จาก Stripe API (กรณี webhook ตก) --}}
                                            <form action="{{ route('admin.fortune.billing.stripe-resync', $bill) }}" method="POST" style="display:inline;"
                                                  @submit="submitting = true;">
                                                @csrf
                                                <button type="submit" :disabled="submitting"
                                                        style="background:none;border:0;cursor:pointer;color:#5689b8;font-size:13px;font-weight:600;"
                                                        :style="submitting ? 'opacity:.5;' : ''"
                                                        title="ดึง status จาก Stripe API ใหม่ (กรณี webhook ตก)">
                                                    <i class="fas fa-rotate"></i> Resync
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px 14px;text-align:center;color:var(--ink2);">
                                <i class="fas fa-inbox" style="font-size:32px;display:block;margin-bottom:10px;opacity:.5;"></i>
                                ไม่พบข้อมูลบิลในช่วงเวลาที่เลือก
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($bills->hasPages())
            <div class="tp-divider"></div>
            <div style="margin-top:12px;">
                {{ $bills->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ╔═══ Loading Overlay ═══╗ --}}
<div id="loadingOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:100;align-items:center;justify-content:center;">
    <div class="tp-card tp-raise" style="max-width:320px;margin:0 16px;text-align:center;padding:32px;">
        <i class="fas fa-spinner fa-spin" style="font-size:40px;color:var(--accent1);display:block;margin-bottom:16px;"></i>
        <p id="loadingMessage" style="color:var(--ink);font-weight:700;font-size:17px;margin:0;">กำลังประมวลผล...</p>
        <p class="tp-muted" style="font-size:13px;margin-top:8px;">กรุณารอสักครู่ อย่าปิดหน้านี้</p>
    </div>
</div>

{{-- ╔═══ Manual Confirm Modal ═══╗ --}}
<div id="manualConfirmModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:60;align-items:center;justify-content:center;">
    <div class="tp-card tp-raise" x-data="{ submitting: false }" style="max-width:440px;width:100%;margin:0 16px;padding:24px;">
        <h3 style="font-size:18px;font-weight:800;color:var(--ink);margin:0 0 16px;">
            <i class="fas fa-check-circle" style="color:#5aa07e;"></i> ยืนยันการชำระเงินด้วยตนเอง
        </h3>
        <form id="manualConfirmForm" method="POST"
              @submit="submitting = true; showLoadingOverlay('กำลังยืนยันบิลและส่งคำทำนาย...');">
            @csrf
            <div style="margin-bottom:14px;">
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">จำนวนเงิน</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="number" name="amount" id="confirmAmount" step="0.01" required
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">หมายเหตุ (ถ้ามี)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="note" placeholder="เช่น โอนผ่าน PromptPay"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" :disabled="submitting" class="tp-btn tp-btn-primary" style="flex:1;justify-content:center;">
                    <template x-if="!submitting"><span><i class="fas fa-check"></i> ยืนยันการชำระ</span></template>
                    <template x-if="submitting"><span style="display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-spinner fa-spin"></i> กำลังดำเนินการ...</span></template>
                </button>
                <button type="button" @click="if(!submitting) closeManualConfirm()" :disabled="submitting" class="tp-btn" style="flex:1;justify-content:center;">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ╔═══ 💳 (2026-05-09) Stripe Refund Modal ═══╗ --}}
<div id="stripeRefundModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:60;align-items:center;justify-content:center;">
    <div class="tp-card tp-raise" x-data="{ submitting: false, fullRefund: true }" style="max-width:440px;width:100%;margin:0 16px;padding:24px;">
        <h3 style="font-size:18px;font-weight:800;color:var(--ink);margin:0 0 8px;">
            <i class="fas fa-money-bill-transfer" style="color:#d6824a;"></i> Refund Stripe Payment
        </h3>
        <p style="font-size:13px;color:#d9534f;margin:0 0 16px;">
            <i class="fas fa-triangle-exclamation"></i> การ refund ไม่สามารถยกเลิกได้ กรุณาตรวจสอบให้แน่ใจก่อนทำรายการ
        </p>
        <form id="stripeRefundForm" method="POST"
              @submit="if(!confirm('ยืนยันการ refund? ไม่สามารถ undo ได้')) { $event.preventDefault(); return; } submitting = true;">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--ink);cursor:pointer;">
                    <input type="checkbox" x-model="fullRefund"> Refund เต็มจำนวน
                </label>
            </div>
            <div style="margin-bottom:14px;" x-show="!fullRefund" x-cloak>
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">จำนวนเงิน (บาท)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="number" name="amount" id="refundAmount" step="0.01" min="0.01"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
                <p class="tp-muted" style="font-size:11px;margin-top:4px;">เว้นว่างไว้ = refund เต็ม</p>
            </div>
            <div style="margin-bottom:18px;">
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">เหตุผล <span style="color:#d9534f;">*</span></label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="reason" required rows="3" maxlength="500" placeholder="เช่น ลูกค้าขอเงินคืน เนื่องจาก..."
                              style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;resize:vertical;"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" :disabled="submitting" class="tp-btn" style="flex:1;justify-content:center;background:#d6824a;color:#fff;">
                    <template x-if="!submitting"><span><i class="fas fa-money-bill-transfer"></i> Refund</span></template>
                    <template x-if="submitting"><span><i class="fas fa-spinner fa-spin"></i> กำลังดำเนินการ...</span></template>
                </button>
                <button type="button" @click="if(!submitting) closeStripeRefund()" :disabled="submitting" class="tp-btn" style="flex:1;justify-content:center;">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // แสดง loading overlay ขณะรอระบบประมวลผล
    function showLoadingOverlay(message) {
        const overlay = document.getElementById('loadingOverlay');
        const msg = document.getElementById('loadingMessage');
        if (msg && message) msg.textContent = message;
        overlay.style.display = 'flex';
    }

    // Manual Confirm Modal — endpoint/payload เดิม (POST /admin/fortune/billing/{id}/manual-confirm)
    function showManualConfirm(billId, amount) {
        document.getElementById('manualConfirmForm').action = '/admin/fortune/billing/' + billId + '/manual-confirm';
        document.getElementById('confirmAmount').value = amount;
        document.getElementById('manualConfirmModal').style.display = 'flex';
    }

    function closeManualConfirm() {
        document.getElementById('manualConfirmModal').style.display = 'none';
    }

    // 💳 (2026-05-09) Stripe Refund Modal — endpoint/payload เดิม (POST /admin/fortune/billing/{id}/stripe-refund)
    function showStripeRefund(billId, amount) {
        document.getElementById('stripeRefundForm').action = '/admin/fortune/billing/' + billId + '/stripe-refund';
        document.getElementById('refundAmount').value = amount;
        document.getElementById('stripeRefundModal').style.display = 'flex';
    }

    function closeStripeRefund() {
        document.getElementById('stripeRefundModal').style.display = 'none';
    }
</script>
@endpush
