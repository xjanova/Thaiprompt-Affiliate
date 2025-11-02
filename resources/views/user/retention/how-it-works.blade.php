@extends('layouts.user')

@section('title', 'หลักการทำงานระบบรักษายอด')

@section('content')
<div class="container-fluid retention-guide-page">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="guide-header">
                <div class="header-background"></div>
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="header-text">
                        <h1 class="header-title">คู่มือระบบรักษายอด</h1>
                        <p class="header-subtitle">เข้าใจหลักการทำงานและวิธีรักษาสมาชิกภาพ</p>
                    </div>
                </div>
                <a href="{{ route('user.retention.index') }}" class="btn-back-to-status">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับหน้าสถานะ</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Quick Summary Cards --}}
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="summary-card summary-card-primary">
                <div class="summary-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-number">30 วัน</h3>
                    <p class="summary-label">รอบการต่ออายุ</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card summary-card-success">
                <div class="summary-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-number">{{ number_format($settings['minimum_points_per_month'] ?? 1000) }}</h3>
                    <p class="summary-label">แต้มต่อเดือน</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card summary-card-warning">
                <div class="summary-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-number">{{ $settings['grace_period_days'] ?? 3 }} วัน</h3>
                    <p class="summary-label">ระยะผ่อนผัน</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card summary-card-info">
                <div class="summary-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="summary-content">
                    <h3 class="summary-number">{{ $settings['warning_days_before_expiry'] ?? 7 }} วัน</h3>
                    <p class="summary-label">แจ้งเตือนล่วงหน้า</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row">
        <div class="col-lg-8">
            {{-- How It Works Section --}}
            <div class="guide-card">
                <div class="guide-card-header">
                    <div class="card-header-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="card-header-text">
                        <h2 class="card-title">ระบบรักษายอดทำงานอย่างไร?</h2>
                        <p class="card-subtitle">Membership Retention System</p>
                    </div>
                </div>
                <div class="guide-card-body">
                    <div class="guide-section">
                        <div class="section-icon section-icon-primary">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <div class="section-content">
                            <h3 class="section-title">1. เริ่มต้นการนับวัน</h3>
                            <p class="section-text">
                                ระบบจะเริ่มนับวันตั้งแต่ <strong>วันที่คุณซื้อแพคเกจหรือสินค้าครั้งแรก</strong> (ไม่นับรวมการเติมเงินเข้า Wallet)
                            </p>
                            <div class="example-box example-box-primary">
                                <i class="fas fa-lightbulb"></i>
                                <div>
                                    <strong>ตัวอย่าง:</strong> หากคุณซื้อแพคเกจวันที่ 15 มกราคม 2025 ระบบจะเริ่มนับตั้งแต่วันที่ 15 มกราคม
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="guide-section">
                        <div class="section-icon section-icon-success">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="section-content">
                            <h3 class="section-title">2. รอบการต่ออายุ 30 วัน</h3>
                            <p class="section-text">
                                ทุกๆ <strong>30 วัน</strong> นับจากวันที่เริ่มต้น คุณจะต้องรักษายอดให้ได้ตามเป้าหมาย
                            </p>
                            <ul class="feature-list">
                                <li><i class="fas fa-check-circle"></i> รอบ 1: วันที่ 15 ม.ค. - 13 ก.พ. (30 วัน)</li>
                                <li><i class="fas fa-check-circle"></i> รอบ 2: วันที่ 14 ก.พ. - 15 มี.ค. (30 วัน)</li>
                                <li><i class="fas fa-check-circle"></i> รอบ 3: วันที่ 16 มี.ค. - 14 เม.ย. (30 วัน)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="guide-section">
                        <div class="section-icon section-icon-warning">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="section-content">
                            <h3 class="section-title">3. เป้าหมายแต้มต่อรอบ</h3>
                            <p class="section-text">
                                คุณต้องทำยอดให้ได้ <strong class="highlight-points">{{ number_format($settings['minimum_points_per_month'] ?? 1000) }} แต้ม</strong> ภายในทุกๆ รอบ 30 วัน
                            </p>
                            <div class="points-calculation">
                                <div class="calc-item">
                                    <div class="calc-label">แต้มที่ได้รับ</div>
                                    <div class="calc-formula">
                                        <span class="formula-item">ค่าคอมมิชชั่น</span>
                                        <span class="formula-operator">+</span>
                                        <span class="formula-item">การซื้อสินค้า</span>
                                        <span class="formula-operator">=</span>
                                        <span class="formula-result">แต้มสะสม</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="guide-section">
                        <div class="section-icon section-icon-info">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="section-content">
                            <h3 class="section-title">4. การนับวันคงเหลือ</h3>
                            <p class="section-text">
                                ระบบจะแสดง <strong>วันที่เหลือ</strong> จนถึงวันหมดอายุของรอบปัจจุบัน
                            </p>
                            <div class="timeline-visual">
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-green"></div>
                                    <div class="timeline-content">
                                        <strong>15+ วัน</strong>
                                        <p>ปลอดภัย (สีเขียว)</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-yellow"></div>
                                    <div class="timeline-content">
                                        <strong>8-14 วัน</strong>
                                        <p>ควรเริ่มระมัดระวัง (สีเหลือง)</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-orange"></div>
                                    <div class="timeline-content">
                                        <strong>4-7 วัน</strong>
                                        <p>ใกล้หมดอายุ (สีส้ม)</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-red"></div>
                                    <div class="timeline-content">
                                        <strong>1-3 วัน</strong>
                                        <p>ฉุกเฉิน! (สีแดง)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="guide-section">
                        <div class="section-icon section-icon-danger">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="section-content">
                            <h3 class="section-title">5. ระยะผ่อนผัน (Grace Period)</h3>
                            <p class="section-text">
                                หากคุณยังทำยอดไม่ครบภายในวันหมดอายุ ระบบจะให้ <strong class="highlight-grace">ระยะผ่อนผัน {{ $settings['grace_period_days'] ?? 3 }} วัน</strong>
                            </p>
                            <div class="grace-period-visual">
                                <div class="grace-timeline">
                                    <div class="grace-phase grace-phase-normal">
                                        <div class="phase-label">รอบปกติ</div>
                                        <div class="phase-duration">30 วัน</div>
                                    </div>
                                    <div class="grace-arrow">→</div>
                                    <div class="grace-phase grace-phase-grace">
                                        <div class="phase-label">ระยะผ่อนผัน</div>
                                        <div class="phase-duration">+{{ $settings['grace_period_days'] ?? 3 }} วัน</div>
                                    </div>
                                </div>
                                <p class="grace-note">
                                    <i class="fas fa-info-circle"></i>
                                    ภายในระยะนี้คุณสามารถทำยอดให้ครบ หรือเลือก <strong>ซ่อมสิทธิ์</strong> หรือ <strong>เติมวันล่วงหน้า</strong> ได้
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-section">
                        <div class="section-icon section-icon-purple">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="section-content">
                            <h3 class="section-title">6. การแจ้งเตือน</h3>
                            <p class="section-text">
                                ระบบจะแจ้งเตือนคุณล่วงหน้า <strong>{{ $settings['warning_days_before_expiry'] ?? 7 }} วัน</strong> ก่อนหมดอายุ
                            </p>
                            <div class="notification-types">
                                <div class="notification-item">
                                    <i class="fas fa-envelope"></i>
                                    <span>อีเมล</span>
                                </div>
                                <div class="notification-item">
                                    <i class="fas fa-bell"></i>
                                    <span>การแจ้งเตือนในระบบ</span>
                                </div>
                                <div class="notification-item">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>Dashboard Widget</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- What Happens When Expired --}}
            <div class="guide-card mt-4">
                <div class="guide-card-header">
                    <div class="card-header-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="card-header-text">
                        <h2 class="card-title">เมื่อหมดอายุจะเกิดอะไรขึ้น?</h2>
                        <p class="card-subtitle">Consequences of Expiration</p>
                    </div>
                </div>
                <div class="guide-card-body">
                    <div class="consequence-list">
                        <div class="consequence-item consequence-critical">
                            <div class="consequence-icon">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div class="consequence-content">
                                <h4>การบล็อกคอมมิชชั่น</h4>
                                <p>คอมมิชชั่นจากผู้แนะนำจะถูกระงับจนกว่าจะต่ออายุ</p>
                            </div>
                        </div>
                        <div class="consequence-item consequence-warning">
                            <div class="consequence-icon">
                                <i class="fas fa-pause-circle"></i>
                            </div>
                            <div class="consequence-content">
                                <h4>สถานะเป็น "หมดอายุ"</h4>
                                <p>บัญชีของคุณจะถูกทำเครื่องหมายว่าหมดอายุ</p>
                            </div>
                        </div>
                        <div class="consequence-item consequence-info">
                            <div class="consequence-icon">
                                <i class="fas fa-undo"></i>
                            </div>
                            <div class="consequence-content">
                                <h4>วิธีกู้คืนสถานะ</h4>
                                <p>สามารถ <strong>ซ่อมสิทธิ์</strong> หรือ <strong>เติมวันล่วงหน้า</strong> เพื่อกู้คืนสถานะได้ทันที</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="guide-card">
                <div class="guide-card-header">
                    <div class="card-header-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="card-header-text">
                        <h2 class="card-title">ดำเนินการด่วน</h2>
                    </div>
                </div>
                <div class="guide-card-body">
                    <div class="quick-actions">
                        <a href="{{ route('user.retention.index') }}" class="quick-action-btn quick-action-primary">
                            <i class="fas fa-chart-line"></i>
                            <span>ตรวจสอบสถานะ</span>
                        </a>
                        <a href="{{ route('user.retention.repair') }}" class="quick-action-btn quick-action-warning">
                            <i class="fas fa-tools"></i>
                            <span>ซ่อมสิทธิ์</span>
                        </a>
                        <a href="{{ route('user.retention.advance-renewal') }}" class="quick-action-btn quick-action-success">
                            <i class="fas fa-plus-circle"></i>
                            <span>เติมวันล่วงหน้า</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Tips & Tricks --}}
            <div class="guide-card mt-4">
                <div class="guide-card-header">
                    <div class="card-header-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="card-header-text">
                        <h2 class="card-title">เคล็ดลับ</h2>
                    </div>
                </div>
                <div class="guide-card-body">
                    <div class="tips-list">
                        <div class="tip-item">
                            <i class="fas fa-star"></i>
                            <p>ติดตามสถานะผ่าน Dashboard เป็นประจำ</p>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-star"></i>
                            <p>ตั้งเป้าหมายทำยอดให้ครบก่อนหมดรอบ 7 วัน</p>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-star"></i>
                            <p>ใช้ระบบเติมวันล่วงหน้าเพื่อความมั่นใจ</p>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-star"></i>
                            <p>เปิดการแจ้งเตือนเพื่อไม่พลาดข้อมูลสำคัญ</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact Support --}}
            <div class="guide-card mt-4">
                <div class="guide-card-header">
                    <div class="card-header-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="card-header-text">
                        <h2 class="card-title">ต้องการความช่วยเหลือ?</h2>
                    </div>
                </div>
                <div class="guide-card-body">
                    <p class="support-text">หากมีข้อสงสัยเพิ่มเติม ติดต่อทีมสนับสนุนได้ตลอด 24 ชั่วโมง</p>
                    <a href="#" class="support-btn">
                        <i class="fas fa-headset"></i>
                        <span>ติดต่อฝ่ายสนับสนุน</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Global Styles */
.retention-guide-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 30px 0;
}

/* Header */
.guide-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 40px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-background {
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 25px;
    position: relative;
    z-index: 1;
}

.header-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: #fff;
}

.header-title {
    color: #fff;
    font-size: 36px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.header-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 16px;
    margin: 0;
}

.btn-back-to-status {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    color: #fff;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.btn-back-to-status:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateX(-5px);
    color: #fff;
}

/* Summary Cards */
.summary-card {
    background: #fff;
    border-radius: 16px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border-left: 4px solid;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.summary-card-primary { border-color: #667eea; }
.summary-card-success { border-color: #10b981; }
.summary-card-warning { border-color: #f59e0b; }
.summary-card-info { border-color: #3b82f6; }

.summary-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
}

.summary-card-primary .summary-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.summary-card-success .summary-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.summary-card-warning .summary-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.summary-card-info .summary-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.summary-number {
    font-size: 32px;
    font-weight: 800;
    color: #1f2937;
    margin: 0;
}

.summary-label {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

/* Guide Cards */
.guide-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.guide-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.card-header-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
}

.card-title {
    color: #fff;
    font-size: 22px;
    font-weight: 700;
    margin: 0;
}

.card-subtitle {
    color: rgba(255, 255, 255, 0.85);
    font-size: 13px;
    margin: 0;
}

.guide-card-body {
    padding: 30px;
}

/* Guide Sections */
.guide-section {
    display: flex;
    gap: 20px;
    margin-bottom: 35px;
    padding-bottom: 35px;
    border-bottom: 2px solid #f3f4f6;
}

.guide-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.section-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    flex-shrink: 0;
}

.section-icon-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.section-icon-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.section-icon-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.section-icon-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.section-icon-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.section-icon-purple { background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); }

.section-content {
    flex: 1;
}

.section-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 12px 0;
}

.section-text {
    font-size: 15px;
    color: #4b5563;
    line-height: 1.7;
    margin: 0 0 15px 0;
}

.highlight-points {
    color: #f59e0b;
    font-size: 18px;
}

.highlight-grace {
    color: #ef4444;
    font-size: 16px;
}

/* Example Box */
.example-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.6;
    margin-top: 15px;
}

.example-box-primary {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border-left: 4px solid #3b82f6;
}

.example-box i {
    font-size: 20px;
    margin-top: 2px;
}

/* Feature List */
.feature-list {
    list-style: none;
    padding: 0;
    margin: 15px 0 0 0;
}

.feature-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 14px;
    color: #4b5563;
}

.feature-list i {
    color: #10b981;
    font-size: 16px;
}

/* Points Calculation */
.points-calculation {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    padding: 20px;
    margin-top: 15px;
}

.calc-label {
    font-size: 14px;
    font-weight: 600;
    color: #78350f;
    margin-bottom: 12px;
}

.calc-formula {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.formula-item {
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #78350f;
}

.formula-operator {
    font-size: 18px;
    font-weight: 700;
    color: #78350f;
}

.formula-result {
    padding: 8px 16px;
    background: #f59e0b;
    color: #fff;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
}

/* Timeline Visual */
.timeline-visual {
    margin-top: 20px;
}

.timeline-item {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.timeline-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    flex-shrink: 0;
}

.timeline-dot-green { background: #10b981; }
.timeline-dot-yellow { background: #f59e0b; }
.timeline-dot-orange { background: #fb923c; }
.timeline-dot-red { background: #ef4444; }

.timeline-content strong {
    display: block;
    font-size: 15px;
    color: #1f2937;
    margin-bottom: 3px;
}

.timeline-content p {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

/* Grace Period Visual */
.grace-period-visual {
    margin-top: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-radius: 12px;
    border-left: 4px solid #ef4444;
}

.grace-timeline {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 15px;
}

.grace-phase {
    padding: 15px 20px;
    border-radius: 10px;
    text-align: center;
}

.grace-phase-normal {
    background: rgba(255, 255, 255, 0.6);
}

.grace-phase-grace {
    background: rgba(239, 68, 68, 0.2);
    border: 2px dashed #ef4444;
}

.phase-label {
    font-size: 13px;
    font-weight: 600;
    color: #991b1b;
    margin-bottom: 5px;
}

.phase-duration {
    font-size: 18px;
    font-weight: 800;
    color: #7f1d1d;
}

.grace-arrow {
    font-size: 24px;
    color: #991b1b;
    font-weight: 700;
}

.grace-note {
    font-size: 13px;
    color: #991b1b;
    margin: 0;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.grace-note i {
    margin-top: 2px;
    flex-shrink: 0;
}

/* Notification Types */
.notification-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.notification-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.notification-item i {
    font-size: 28px;
    color: #667eea;
}

/* Consequence List */
.consequence-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.consequence-item {
    display: flex;
    gap: 20px;
    padding: 20px;
    border-radius: 14px;
    border-left: 4px solid;
}

.consequence-critical {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-color: #ef4444;
}

.consequence-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-color: #f59e0b;
}

.consequence-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-color: #3b82f6;
}

.consequence-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.consequence-critical .consequence-icon {
    background: #ef4444;
    color: #fff;
}

.consequence-warning .consequence-icon {
    background: #f59e0b;
    color: #fff;
}

.consequence-info .consequence-icon {
    background: #3b82f6;
    color: #fff;
}

.consequence-content h4 {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.consequence-critical .consequence-content h4 { color: #991b1b; }
.consequence-warning .consequence-content h4 { color: #78350f; }
.consequence-info .consequence-content h4 { color: #1e40af; }

.consequence-content p {
    font-size: 14px;
    margin: 0;
}

.consequence-critical .consequence-content p { color: #7f1d1d; }
.consequence-warning .consequence-content p { color: #713f12; }
.consequence-info .consequence-content p { color: #1e3a8a; }

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 15px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    color: #fff;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    color: #fff;
}

.quick-action-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.quick-action-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.quick-action-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }

/* Tips List */
.tips-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tip-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 10px;
    font-size: 14px;
    color: #78350f;
}

.tip-item i {
    color: #f59e0b;
    margin-top: 2px;
    flex-shrink: 0;
}

.tip-item p {
    margin: 0;
    font-weight: 500;
}

/* Support */
.support-text {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 15px;
}

.support-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.support-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: #fff;
}

/* Responsive */
@media (max-width: 768px) {
    .guide-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }

    .header-content {
        flex-direction: column;
        text-align: center;
    }

    .guide-section {
        flex-direction: column;
    }

    .grace-timeline {
        flex-direction: column;
    }

    .grace-arrow {
        transform: rotate(90deg);
    }
}
</style>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
@endsection
