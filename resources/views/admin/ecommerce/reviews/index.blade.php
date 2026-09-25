@extends('layouts.admin-v4')

@section('title', 'รีวิวสินค้า')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <div>
        <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · รีวิว</div>
        <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">รีวิวสินค้า ⭐</h1>
        <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">อนุมัติ ซ่อน หรือลบรีวิวที่ไม่เหมาะสม</div>
    </div>

    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.ecommerce.reviews.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; align-items:end;">
            <div>
                <label style="{{ $lbl }}">🔍 ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" class="tp-input" placeholder="ข้อความรีวิวหรือชื่อสินค้า">
            </div>
            <div>
                <label style="{{ $lbl }}">คะแนน</label>
                <select name="rating" class="tp-input">
                    <option value="">ทุกคะแนน</option>
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected((string) request('rating') === (string) $i)>{{ str_repeat('★', $i) }} ({{ $i }})</option>
                    @endfor
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">สถานะ</label>
                <select name="status" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    <option value="approved" @selected(request('status') === 'approved')>อนุมัติแล้ว</option>
                    <option value="pending" @selected(request('status') === 'pending')>ยังไม่อนุมัติ / ซ่อน</option>
                </select>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
                <a href="{{ route('admin.ecommerce.reviews.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
            </div>
        </form>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:880px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">สินค้า</th>
                        <th style="{{ $th }}">ผู้รีวิว</th>
                        <th style="{{ $th }}">คะแนน</th>
                        <th style="{{ $th }}">ความคิดเห็น</th>
                        <th style="{{ $th }}">สถานะ</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }}">
                                @if($review->product)
                                    <a href="{{ route('admin.ecommerce.products.show', $review->product) }}" style="font-weight:700; color:var(--ink); text-decoration:none; display:block; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $review->product->name }}</a>
                                    <div style="font-size:11.5px; color:var(--ink2);">SKU: {{ $review->product->sku ?: '-' }}</div>
                                @else
                                    <span style="color:var(--ink2);">สินค้าถูกลบ</span>
                                @endif
                            </td>
                            <td style="{{ $td }}">
                                <div style="font-weight:600;">{{ $review->user?->name ?? 'ไม่ระบุ' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $review->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td style="{{ $td }} white-space:nowrap; color:{{ $c['warn'] }};" title="{{ (int) $review->rating }}/5">
                                {{ str_repeat('★', (int) $review->rating) }}<span style="color:var(--ink2);">{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}</span>
                            </td>
                            <td style="{{ $td }} max-width:320px;">
                                <div style="overflow-wrap:anywhere;">{{ \Illuminate\Support\Str::limit((string) $review->comment, 140) ?: '—' }}</div>
                            </td>
                            <td style="{{ $td }}">
                                <span class="tp-pill" style="{{ $pill($review->is_approved ? $c['ok'] : $c['mute']) }}">{{ $review->is_approved ? 'แสดงอยู่' : 'ซ่อน' }}</span>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:6px;">
                                    <form method="POST" action="{{ route('admin.ecommerce.reviews.status.update', $review) }}">
                                        @csrf
                                        <input type="hidden" name="is_approved" value="{{ $review->is_approved ? 0 : 1 }}">
                                        <button type="submit" class="tp-btn tp-btn-sm {{ $review->is_approved ? '' : 'tp-btn-primary' }}">
                                            <i class="fas {{ $review->is_approved ? 'fa-eye-slash' : 'fa-check' }}"></i> {{ $review->is_approved ? 'ซ่อน' : 'อนุมัติ' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.ecommerce.reviews.delete', $review) }}"
                                          @submit="if (!confirm('ลบรีวิวนี้ถาวร?')) $event.preventDefault()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" style="width:34px; height:34px; color:{{ $c['bad'] }};" title="ลบ"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:44px 16px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-comment-slash" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                ไม่พบรีวิว
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($reviews->hasPages())
        <div>{{ $reviews->links() }}</div>
    @endif
</div>
@endsection
