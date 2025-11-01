@extends('layouts.user')

@section('title', 'เติมวันล่วงหน้า - Advance Renewal')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h1 class="page-title">
                    <i class="fas fa-plus-circle text-success"></i>
                    เติมวันล่วงหน้า
                </h1>
                <p class="text-muted">ซื้อสิทธิ์ล่วงหน้าเพื่อป้องกันการหมดอายุ พร้อมส่วนลดพิเศษ!</p>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($renewalOptions as $option)
        <div class="col-md-4 mb-4">
            <div class="renewal-card {{ $option['months'] == 6 ? 'popular' : '' }}">
                @if($option['months'] == 6)
                <div class="popular-badge">
                    <i class="fas fa-star"></i>
                    แนะนำ
                </div>
                @endif

                <div class="renewal-header">
                    <div class="months-badge">
                        <span class="number">{{ $option['months'] }}</span>
                        <span class="label">เดือน</span>
                    </div>
                </div>

                <div class="renewal-body">
                    <div class="price-section">
                        <div class="price-label">ราคาทั้งหมด</div>
                        <div class="price-value">
                            ฿{{ number_format($option['cost'], 2) }}
                        </div>
                        <div class="price-per-month">
                            ฿{{ number_format($option['cost_per_month'], 2) }}/เดือน
                        </div>
                    </div>

                    <div class="benefits">
                        <div class="benefit-item">
                            <i class="fas fa-check-circle"></i>
                            <span>รักษาสิทธิ์การรับคอมมิชชั่น</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-check-circle"></i>
                            <span>ไม่ต้องกังวลเรื่องการหมดอายุ</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-check-circle"></i>
                            <span>ส่วนลด 10% จากราคาปกติ</span>
                        </div>
                        @if($option['months'] >= 6)
                        <div class="benefit-item highlight">
                            <i class="fas fa-gift"></i>
                            <span>คุ้มค่าที่สุด!</span>
                        </div>
                        @endif
                    </div>

                    <button class="btn btn-renewal" onclick="purchaseRenewal({{ $option['months'] }}, {{ $option['cost'] }})">
                        <i class="fas fa-shopping-cart"></i>
                        ซื้อแพ็กเกจนี้
                    </button>

                    @if($option['months'] >= 3)
                    <div class="savings-badge">
                        ประหยัดได้ถึง {{ number_format(($option['cost'] * 0.1), 2) }} บาท!
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="info-box">
                <div class="info-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>เกี่ยวกับการเติมวันล่วงหน้า</h3>
                </div>
                <div class="info-content">
                    <ul>
                        <li><strong>ส่วนลดพิเศษ 10%</strong> - ราคาถูกกว่าการรักษายอดปกติ</li>
                        <li><strong>ป้องกันการหมดอายุ</strong> - ไม่ต้องกังวลเรื่องการลืมรักษายอด</li>
                        <li><strong>รักษาสิทธิ์การรับคอมมิชชั่น</strong> - คอมมิชชั่นจะถูกคำนวณตามปกติ</li>
                        <li><strong>ยืดหยุ่น</strong> - เลือกแพ็กเกจที่เหมาะกับคุณได้</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.renewal-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
    position: relative;
}

.renewal-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.renewal-card.popular {
    border: 3px solid #10b981;
    transform: scale(1.05);
}

.renewal-card.popular:hover {
    transform: scale(1.05) translateY(-10px);
}

.popular-badge {
    position: absolute;
    top: 20px;
    right: -35px;
    background: #10b981;
    color: #fff;
    padding: 8px 40px;
    font-size: 12px;
    font-weight: 600;
    transform: rotate(45deg);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.renewal-header {
    background: linear-gradient(135deg, #10b981, #059669);
    padding: 40px 20px;
    text-align: center;
}

.months-badge {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    color: #fff;
}

.months-badge .number {
    font-size: 48px;
    font-weight: 700;
    line-height: 1;
}

.months-badge .label {
    font-size: 16px;
    font-weight: 500;
    margin-top: 5px;
}

.renewal-body {
    padding: 30px;
}

.price-section {
    text-align: center;
    margin-bottom: 30px;
}

.price-label {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.price-value {
    color: #10b981;
    font-size: 40px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 10px;
}

.price-per-month {
    color: #999;
    font-size: 16px;
}

.benefits {
    margin-bottom: 25px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    color: #666;
    font-size: 14px;
}

.benefit-item i {
    color: #10b981;
    font-size: 16px;
}

.benefit-item.highlight {
    background: #d1fae5;
    padding: 10px 15px;
    border-radius: 8px;
    margin-top: 10px;
    color: #065f46;
    font-weight: 600;
}

.benefit-item.highlight i {
    color: #065f46;
}

.btn-renewal {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-renewal:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: scale(1.02);
}

.savings-badge {
    text-align: center;
    margin-top: 15px;
    padding: 10px;
    background: #fef3c7;
    border-radius: 8px;
    color: #92400e;
    font-size: 14px;
    font-weight: 600;
}

.info-box {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 30px;
}

.info-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    color: #10b981;
}

.info-header i {
    font-size: 32px;
}

.info-header h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.info-content ul {
    list-style: none;
    padding: 0;
}

.info-content li {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    color: #666;
    font-size: 15px;
}

.info-content li:last-child {
    border-bottom: none;
}

.info-content strong {
    color: #333;
    font-weight: 600;
}
</style>

<script>
async function purchaseRenewal(months, cost) {
    if (!confirm(`คุณต้องการซื้อแพ็กเกจ ${months} เดือน ด้วยราคา ฿${cost.toFixed(2)} ใช่หรือไม่?`)) {
        return;
    }

    try {
        const response = await fetch('{{ route('user.retention.advance-renewal.process') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                months: months,
                amount: cost
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ' + result.message);
            window.location.href = '{{ route('user.retention.index') }}';
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    }
}
</script>
@endsection
