{{-- resources/views/admin/fortune/horoscope/post-history.blade.php --}}
{{-- ประวัติการโพสดวงรายวัน — ธีม V4 นวลทองคำ --}}

@extends('layouts.admin-v4')

@section('title', 'ประวัติโพส - ' . $campaign->name)

@php
    use Illuminate\Support\Str;

    $totalPosts   = $posts->total();
    $postedCount  = $campaign->posts()->posted()->count();
    $failedCount  = $campaign->posts()->where('status', 'failed')->count();
    $pendingCount = $campaign->posts()->where('status', 'pending')->count();

    // สีสถานะการโพส
    $postStatusMeta = [
        'posted'  => ['color' => '#5aa07e', 'icon' => '✅', 'label' => 'โพสแล้ว'],
        'posting' => ['color' => '#5689b8', 'icon' => '⏳', 'label' => 'กำลังโพส'],
        'pending' => ['color' => '#e0a52e', 'icon' => '⏸', 'label' => 'รอ'],
        'failed'  => ['color' => '#d9534f', 'icon' => '❌', 'label' => 'ล้มเหลว'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- หัวข้อ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px;">
                <a href="{{ route('admin.fortune.horoscope.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับ</a>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · ประวัติโพส</div>
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:6px 0 0;">📤 ประวัติการโพส</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px;">
                แคมเปญ: <span style="color:var(--deep1); font-weight:600;">{{ $campaign->name }}</span>
                @if($campaign->post_to_facebook) · <span style="color:#5689b8;">📘 Facebook</span> @endif
                @if($campaign->post_to_line) · <span style="color:#5aa07e;">💚 LINE</span> @endif
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <a href="{{ route('admin.fortune.horoscope.content-history', $campaign) }}" class="tp-btn"><i class="fas fa-robot"></i> ดูเนื้อหา</a>
            <form action="{{ route('admin.fortune.horoscope.publish-now', $campaign) }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('ต้องการโพสเนื้อหาทันทีหรือไม่?')">
                @csrf
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> โพสทันที</button>
            </form>
        </div>
    </div>

    {{-- สรุปสถิติ --}}
    @php
        $kpis = [
            ['ทั้งหมด', number_format($totalPosts), 'fa-layer-group', 'var(--deep1)'],
            ['สำเร็จ', number_format($postedCount), 'fa-circle-check', '#5aa07e'],
            ['ล้มเหลว', number_format($failedCount), 'fa-circle-xmark', '#d9534f'],
            ['รอดำเนินการ', number_format($pendingCount), 'fa-clock', '#e0a52e'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px;">
        @foreach($kpis as [$label, $val, $icon, $col])
            <div class="tp-card" style="padding:16px; display:flex; align-items:center; gap:12px;">
                <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:15px; background:linear-gradient(135deg, {{ $col }}, color-mix(in srgb, {{ $col }} 60%, #fff));"><i class="fas {{ $icon }}"></i></span>
                <div>
                    <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:{{ $col }};">{{ $val }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ตารางโพส --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; min-width:860px;">
                <thead>
                    <tr style="box-shadow:var(--inset-sm);">
                        @foreach(['วันที่' => 'left', 'แพลตฟอร์ม' => 'center', 'สถานะ' => 'center', 'เนื้อหา' => 'left', 'โพสเมื่อ' => 'center', 'ลิงก์' => 'center'] as $th => $align)
                            <th style="padding:13px 16px; text-align:{{ $align }}; font-size:11px; color:var(--ink2); font-weight:700; letter-spacing:.4px; text-transform:uppercase; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        @php $ps = $postStatusMeta[$post->status] ?? ['color' => '#9a8f7c', 'icon' => '•', 'label' => $post->status]; @endphp
                        <tr style="box-shadow:var(--inset-sm);">
                            {{-- วันที่เป้าหมาย --}}
                            <td style="padding:14px 16px;">
                                <div class="tp-num" style="font-size:13.5px; font-weight:700;">{{ $post->target_date ? $post->target_date->format('d/m/Y') : '-' }}</div>
                            </td>

                            {{-- แพลตฟอร์ม --}}
                            <td style="padding:14px 16px; text-align:center;">
                                @if($post->platform === 'facebook')
                                    <span class="tp-pill" style="background:rgba(86,137,184,.16); color:#5689b8;">📘 Facebook</span>
                                @elseif($post->platform === 'line')
                                    <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">💚 LINE</span>
                                @else
                                    <span style="font-size:12px; color:var(--ink2);">{{ $post->platform }}</span>
                                @endif
                            </td>

                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; text-align:center;">
                                <span class="tp-pill" style="background:color-mix(in srgb, {{ $ps['color'] }} 16%, transparent); color:{{ $ps['color'] }}; font-weight:700;"
                                      @if($post->error_message) title="{{ $post->error_message }}" @endif>{{ $ps['icon'] }} {{ $ps['label'] }}</span>
                                @if($post->error_message)
                                    <div style="margin-top:5px; font-size:11px; color:#d9534f; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $post->error_message }}">{{ Str::limit($post->error_message, 40) }}</div>
                                @endif
                            </td>

                            {{-- เนื้อหา --}}
                            <td style="padding:14px 16px;">
                                <div style="font-size:13px; color:var(--ink); max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $post->post_content }}">{{ Str::limit($post->post_content, 80) }}</div>
                                @if(!empty($post->image_urls))
                                    <span style="font-size:11px; color:var(--ink2);">🖼 {{ count($post->image_urls) }} รูป</span>
                                @endif
                            </td>

                            {{-- เวลาโพส --}}
                            <td style="padding:14px 16px; text-align:center;">
                                @if($post->published_at)
                                    <span class="tp-num" style="font-size:13px; font-weight:600;">{{ $post->published_at->format('d/m/Y') }}</span>
                                    <div style="font-size:10.5px; color:var(--ink2);">{{ $post->published_at->format('H:i:s') }}</div>
                                @else
                                    <span style="font-size:12px; color:var(--ink2); opacity:.7;">-</span>
                                @endif
                            </td>

                            {{-- ลิงก์โพส --}}
                            <td style="padding:14px 16px; text-align:center;">
                                @if($post->platform_post_url)
                                    <a href="{{ $post->platform_post_url }}" target="_blank" rel="noopener" style="color:var(--deep1); text-decoration:none; font-weight:600; font-size:13px;">🔗 ดูโพส</a>
                                @elseif($post->platform_post_id)
                                    <span style="font-size:11px; color:var(--ink2); font-family:ui-monospace,monospace;">{{ Str::limit($post->platform_post_id, 15) }}</span>
                                @else
                                    <span style="font-size:12px; color:var(--ink2); opacity:.7;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:48px 20px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-inbox" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                <div class="tp-num" style="font-size:15px; font-weight:700;">ยังไม่มีประวัติการโพส</div>
                                <div style="font-size:12.5px; margin-top:3px;">สร้างเนื้อหาแล้วกด "โพสทันที" หรือรอ scheduler ทำงาน</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- แบ่งหน้า --}}
    @if($posts->hasPages())
        <div class="tp-num" style="display:flex; justify-content:center;">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
