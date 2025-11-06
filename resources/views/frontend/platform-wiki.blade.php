@extends('layouts.app')

@section('title', 'Platform Knowledge Base - Thaiprompt Affiliate Encyclopedia')

@section('content')
@php
    $primaryColor = \App\Models\Setting::get('primary_color', '#3B82F6');
    $secondaryColor = \App\Models\Setting::get('secondary_color', '#8B5CF6');
    $accentColor = \App\Models\Setting::get('accent_color', '#EC4899');
@endphp

@push('styles')
<style>
:root {
    --primary: {{ $primaryColor }};
    --secondary: {{ $secondaryColor }};
    --accent: {{ $accentColor }};
}

/* Wikipedia-style Layout */
.wiki-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Sidebar Navigation */
.wiki-sidebar {
    position: sticky;
    top: 2rem;
    height: fit-content;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.wiki-sidebar h3 {
    font-size: 0.875rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 1rem;
}

.wiki-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.wiki-nav li {
    margin-bottom: 0.25rem;
}

.wiki-nav a {
    display: block;
    padding: 0.5rem 0.75rem;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.wiki-nav a:hover {
    background: #f3f4f6;
    color: var(--primary);
}

.wiki-nav a.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    font-weight: 600;
}

.wiki-nav .sub-menu {
    margin-left: 1rem;
    margin-top: 0.25rem;
}

.wiki-nav .sub-menu a {
    font-size: 0.85rem;
    color: #6b7280;
}

/* Main Content */
.wiki-content {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 3rem;
    min-height: 100vh;
}

.wiki-header {
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 1.5rem;
    margin-bottom: 2rem;
}

.wiki-title {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.wiki-subtitle {
    color: #6b7280;
    font-size: 1.125rem;
}

/* Article Sections */
.wiki-section {
    margin-bottom: 3rem;
    scroll-margin-top: 2rem;
}

.wiki-section h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 3px solid var(--primary);
}

.wiki-section h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.wiki-section h4 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.wiki-section p {
    line-height: 1.8;
    color: #374151;
    margin-bottom: 1rem;
    font-size: 1.05rem;
}

/* Info Boxes */
.info-box {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border-left: 4px solid var(--primary);
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.info-box.warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left-color: #f59e0b;
}

.info-box.success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-left-color: #10b981;
}

.info-box.research {
    background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
    border-left-color: #ec4899;
}

.info-box h4 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Statistics Cards */
.stat-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s;
}

.stat-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

/* Feature Cards */
.feature-card {
    background: linear-gradient(145deg, #ffffff 0%, #f9fafb 100%);
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s;
}

.feature-card:hover {
    border-color: var(--primary);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1rem;
}

/* Code Blocks */
.code-block {
    background: #1f2937;
    color: #f9fafb;
    padding: 1.5rem;
    border-radius: 8px;
    overflow-x: auto;
    margin: 1.5rem 0;
    font-family: 'Monaco', 'Courier New', monospace;
    font-size: 0.9rem;
}

/* Tables */
.wiki-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
}

.wiki-table th {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
}

.wiki-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.wiki-table tr:hover {
    background: #f9fafb;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, var(--primary), var(--secondary));
}

.timeline-item {
    position: relative;
    padding-bottom: 2rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -2.5rem;
    top: 0;
    width: 1rem;
    height: 1rem;
    background: var(--primary);
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 3px var(--primary);
}

/* Responsive */
@media (max-width: 1024px) {
    .wiki-layout {
        grid-template-columns: 1fr;
    }

    .wiki-sidebar {
        position: static;
        margin-bottom: 2rem;
    }
}

@media (max-width: 768px) {
    .wiki-content {
        padding: 1.5rem;
    }

    .wiki-title {
        font-size: 2rem;
    }
}

/* Breadcrumb */
.wiki-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.wiki-breadcrumb a {
    color: var(--primary);
    text-decoration: none;
}

.wiki-breadcrumb a:hover {
    text-decoration: underline;
}

/* Floating TOC */
.floating-toc {
    position: fixed;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    max-width: 200px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    z-index: 100;
}

.floating-toc h4 {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 0.75rem;
}

.floating-toc ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.floating-toc ul li {
    margin-bottom: 0.5rem;
}

.floating-toc ul a {
    color: #374151;
    text-decoration: none;
    font-size: 0.8rem;
}

.floating-toc ul a:hover {
    color: var(--primary);
}

@media (max-width: 1400px) {
    .floating-toc {
        display: none;
    }
}
</style>
@endpush

<div class="wiki-layout">
    <!-- Sidebar Navigation -->
    <aside class="wiki-sidebar">
        <h3>📚 สารบัญ</h3>
        <ul class="wiki-nav">
            <li><a href="#introduction" class="active">บทนำ</a></li>
            <li><a href="#problems">ปัญหาและโอกาส</a>
                <ul class="sub-menu">
                    <li><a href="#thai-context">บริบทสังคมไทย</a></li>
                    <li><a href="#market-gap">ช่องว่างในตลาด</a></li>
                </ul>
            </li>
            <li><a href="#solution">โซลูชันของเรา</a></li>
            <li><a href="#why-choose-us">🇹🇭 ทำไมต้องเลือกเรา</a>
                <ul class="sub-menu">
                    <li><a href="#made-in-thailand">Made in Thailand 100%</a></li>
                    <li><a href="#money-stays-thailand">เงินอยู่ในไทย</a></li>
                    <li><a href="#unique-features">ฟีเจอร์ที่ไม่มีใครทำ</a></li>
                </ul>
            </li>
            <li><a href="#mlm-system">ระบบ MLM (เชิงลึก)</a>
                <ul class="sub-menu">
                    <li><a href="#unilevel">Unilevel Plan</a></li>
                    <li><a href="#binary">Binary Plan</a></li>
                    <li><a href="#commission-engine">Commission Engine</a></li>
                </ul>
            </li>
            <li><a href="#commission-simulator">🎯 Commission Simulator</a></li>
            <li><a href="#ecommerce">E-Commerce System</a></li>
            <li><a href="#export-trade">🌏 Export & Trade</a></li>
            <li><a href="#wallet">Digital Wallet</a></li>
            <li><a href="#ai-integration">AI & LINE Bot</a></li>
            <li><a href="#security">ระบบรักษาความปลอดภัย</a></li>
            <li><a href="#technology">สถาปัตยกรรมเทคโนโลยี</a></li>
            <li><a href="#database">Database Design</a></li>
            <li><a href="#business-model">Business Model</a></li>
            <li><a href="#case-studies">กรณีศึกษา</a></li>
            <li><a href="#roadmap">แผนอนาคต</a></li>
            <li><a href="#for-investors">สำหรับนักลงทุน</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="wiki-content">
        <!-- Breadcrumb -->
        <div class="wiki-breadcrumb">
            <a href="{{ route('home') }}">หน้าแรก</a>
            <span>/</span>
            <span>Knowledge Base</span>
        </div>

        <!-- Header -->
        <header class="wiki-header">
            <h1 class="wiki-title">Thaiprompt Affiliate Platform</h1>
            <p class="wiki-subtitle">สารานุกรมความรู้ฉบับสมบูรณ์ - แพลตฟอร์ม MLM & E-Commerce แห่งอนาคต</p>
            <div class="flex gap-4 mt-4">
                <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold">v{{ $stats['version'] ?? '1.159.0' }}</span>
                <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">Production Ready</span>
                <span class="px-4 py-2 bg-purple-100 text-purple-800 rounded-full text-sm font-semibold">113+ Models</span>
            </div>
        </header>

        <!-- Introduction -->
        <section id="introduction" class="wiki-section">
            <h2>📖 บทนำ: แพลตฟอร์มที่จะเปลี่ยนวงการ MLM ไทย</h2>

            <p>
                <strong>Thaiprompt Affiliate Platform</strong> คือแพลตฟอร์ม Multi-Level Marketing (MLM) และ E-Commerce แบบครบวงจร
                ที่ออกแบบมาเพื่อตอบโจทย์ธุรกิจยุคดิจิทัลในประเทศไทย โดยผสานเทคโนโลยี AI, Blockchain Concept, และ LINE Official Account
                เข้าด้วยกันอย่างลงตัว
            </p>

            <div class="info-box research">
                <h4>📊 สถิติที่น่าสนใจ</h4>
                <p><strong>ตลาด MLM ในไทย:</strong></p>
                <ul>
                    <li>มูลค่าตลาด MLM ไทยปี 2024 อยู่ที่ประมาณ <strong>120,000 ล้านบาท</strong></li>
                    <li>มีผู้ประกอบการ MLM มากกว่า <strong>2.5 ล้านคน</strong></li>
                    <li>อัตราการเติบโต <strong>15-20% ต่อปี</strong></li>
                    <li>แต่มีเพียง <strong>5%</strong> ที่ใช้เทคโนโลยีที่ทันสมัย</li>
                </ul>
                <p class="text-sm mt-2 text-gray-600">*ข้อมูลจาก: สมาคมการขายตรงไทย และสำนักงาน กลต.</p>
            </div>

            <h3>🎯 วิสัยทัศน์ (Vision)</h3>
            <p>
                <em>"มุ่งสู่การเป็นแพลตฟอร์ม MLM & E-Commerce อันดับ 1 ของไทยที่ใช้เทคโนโลยี AI และระบบอัตโนมัติขั้นสูง
                เพื่อสร้างโอกาสทางธุรกิจที่ยุติธรรมและโปร่งใสสำหรับคนไทยทุกคน"</em>
            </p>

            <h3>💡 พันธกิจ (Mission)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-6">
                <div class="feature-card">
                    <div class="feature-icon">🚀</div>
                    <h4>เทคโนโลยีที่เข้าถึงได้</h4>
                    <p>นำเทคโนโลยี Enterprise มาให้ SME และผู้ประกอบการรายย่อยใช้งานได้ง่าย</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h4>สร้างรายได้ยุติธรรม</h4>
                    <p>ระบบคำนวณค่าคอมมิชชั่นที่โปร่งใส ตรวจสอบได้ทุกขั้นตอน</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h4>AI ช่วยเพิ่มประสิทธิภาพ</h4>
                    <p>ใช้ AI ช่วยดูแลลูกค้า, วิเคราะห์ข้อมูล, และสร้างเนื้อหาอัตโนมัติ</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h4>ความปลอดภัยระดับสูง</h4>
                    <p>ระบบรักษาความปลอดภัยแบบ Banking-grade พร้อม KYC/OCR</p>
                </div>
            </div>

            <h3>📜 ประวัติความเป็นมา</h3>
            <div class="timeline">
                <div class="timeline-item">
                    <h4 class="font-bold">Q1 2024 - เริ่มต้นโครงการ</h4>
                    <p>ทีมพัฒนาเริ่มวิจัยและออกแบบระบบ MLM ที่ตอบโจทย์คนไทย</p>
                </div>
                <div class="timeline-item">
                    <h4 class="font-bold">Q2 2024 - พัฒนา Core System</h4>
                    <p>สร้าง Laravel 11 foundation, ระบบ MLM Dual Plan, และ E-Commerce</p>
                </div>
                <div class="timeline-item">
                    <h4 class="font-bold">Q3 2024 - AI Integration</h4>
                    <p>ผสาน OpenAI, Claude, Gemini และ LINE Official Account</p>
                </div>
                <div class="timeline-item">
                    <h4 class="font-bold">Q4 2024 - Production Ready</h4>
                    <p>Version 1.159.0 - พร้อมใช้งานจริง พร้อม 113+ Models และ 105 Tables</p>
                </div>
            </div>
        </section>

        <!-- Problems & Opportunities -->
        <section id="problems" class="wiki-section">
            <h2>⚠️ ปัญหาและโอกาส: ทำไมต้องมีแพลตฟอร์มนี้</h2>

            <h3 id="thai-context">🇹🇭 บริบทสังคมไทย: ความท้าทายของคนไทยในยุคดิจิทัล</h3>

            <p>
                ประเทศไทยกำลังเผชิญกับการเปลี่ยนแปลงครั้งใหญ่ในยุคดิจิทัล คนไทยหลายล้านคนกำลังมองหารายได้เสริม
                และโอกาสทางธุรกิจที่ไม่ต้องพึ่งงานประจำเพียงอย่างเดียว
            </p>

            <div class="info-box warning">
                <h4>⚠️ ปัญหาหลักที่คนไทยเผชิญ</h4>
                <ul class="space-y-2">
                    <li><strong>1. รายได้ไม่เพียงพอ:</strong> เงินเดือนค่าครองชีพสูง แต่รายได้ไม่เพิ่มตาม</li>
                    <li><strong>2. ขาดโอกาสทางธุรกิจ:</strong> ทุนน้อย ไม่มีทักษะ ไม่กล้าเริ่มต้น</li>
                    <li><strong>3. ถูกหลอกในธุรกิจ MLM:</strong> ระบบไม่โปร่งใส มีการโกง</li>
                    <li><strong>4. ไม่มีเครื่องมือที่เหมาะสม:</strong> ระบบ MLM เก่าๆ ไม่มี AI ไม่มีอัตโนมัติ</li>
                    <li><strong>5. Digital Divide:</strong> คนต่างจังหวัดเข้าถึงเทคโนโลยีได้ยาก</li>
                </ul>
            </div>

            <h4>📊 ข้อมูลสถิติที่น่าตกใจ</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-6">
                <div class="stat-card">
                    <div class="stat-number">65%</div>
                    <div class="stat-label">ของคนไทยมีรายได้ไม่เพียงพอ</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3 ล้าน+</div>
                    <div class="stat-label">คนทำงาน Freelance/Part-time</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">40%</div>
                    <div class="stat-label">ของผู้ทำ MLM ขาดทุน</div>
                </div>
            </div>

            <h3 id="market-gap">🎯 ช่องว่างในตลาด: โอกาสทองที่รออยู่</h3>

            <p>
                จากการวิจัยตลาด เราพบว่ามี <strong>ช่องว่างขนาดใหญ่</strong> ในตลาด MLM ไทย ที่ยังไม่มีใครทำได้ดีพอ:
            </p>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>ปัญหาที่พบ</th>
                        <th>คู่แข่งทำได้</th>
                        <th>เราทำได้</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>ระบบไม่โปร่งใส</strong></td>
                        <td>❌ ดำเนินการแบบ Blackbox</td>
                        <td>✅ โปร่งใส ตรวจสอบได้ทุกขั้นตอน</td>
                    </tr>
                    <tr>
                        <td><strong>ไม่มี AI ช่วยงาน</strong></td>
                        <td>❌ ทำทุกอย่างด้วยมือ</td>
                        <td>✅ AI ช่วยดูแลลูกค้า 24/7</td>
                    </tr>
                    <tr>
                        <td><strong>ยุ่งยากในการติดตาม</strong></td>
                        <td>❌ ดู Dashboard แบบเบื้องต้น</td>
                        <td>✅ Analytics แบบ Real-time</td>
                    </tr>
                    <tr>
                        <td><strong>ค่าคอมมิชชั่นไม่ชัดเจน</strong></td>
                        <td>❌ คำนวณแบบคร่าวๆ</td>
                        <td>✅ Engine คำนวณแบบละเอียด</td>
                    </tr>
                    <tr>
                        <td><strong>ไม่มี Multi-Vendor</strong></td>
                        <td>❌ ขายของบริษัทอย่างเดียว</td>
                        <td>✅ ทุกคนเปิดร้านขายได้</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box success">
                <h4>💡 ทำไมคนไทยจะรอดได้ด้วยแพลตฟอร์มนี้?</h4>
                <ol class="space-y-2">
                    <li><strong>สร้างรายได้เสริมที่แท้จริง:</strong> ระบบ MLM ที่ยุติธรรม ไม่มีการโกง</li>
                    <li><strong>ใช้งานง่าย:</strong> ไม่ต้องเป็นมือโปร ก็ใช้ได้</li>
                    <li><strong>AI ช่วยทำงาน:</strong> ลดภาระงาน เพิ่มประสิทธิภาพ</li>
                    <li><strong>เข้าถึงได้ทุกที่:</strong> มีแค่มือถือก็ทำธุรกิจได้</li>
                    <li><strong>ต้นทุนต่ำ:</strong> ไม่ต้องลงทุนเยอะ เริ่มต้นได้ที่ 0 บาท</li>
                </ol>
            </div>
        </section>

        <!-- Solution -->
        <section id="solution" class="wiki-section">
            <h2>💡 โซลูชันของเรา: แพลตฟอร์มที่ตอบโจทย์ทุกปัญหา</h2>

            <p>
                Thaiprompt Affiliate Platform ถูกออกแบบมาเพื่อ <strong>แก้ปัญหาทุกข้อ</strong> ที่กล่าวมาข้างต้น
                ด้วยการผสมผสานเทคโนโลยีที่ทันสมัยที่สุด เข้ากับความเข้าใจในบริบทของคนไทย
            </p>

            <h3>🏗️ สถาปัตยกรรมแกนหลัก (Core Architecture)</h3>

            <p>
                แพลตฟอร์มของเราสร้างบนหลักการ <strong>3 Pillars</strong> หลัก:
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 my-8">
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h4>1. MLM Engine</h4>
                    <p><strong>Dual Plan System</strong></p>
                    <ul class="text-sm text-gray-700 mt-2 space-y-1">
                        <li>✓ Unilevel Plan (7 ระดับ)</li>
                        <li>✓ Binary Plan (Left/Right)</li>
                        <li>✓ Auto Commission Calculate</li>
                        <li>✓ Real-time PV Tracking</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-4">
                        <strong>ทำไมต้อง 2 Plans?</strong><br>
                        เพื่อให้มีความยืดหยุ่น - Unilevel สำหรับ Width, Binary สำหรับ Depth
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛒</div>
                    <h4>2. E-Commerce Platform</h4>
                    <p><strong>Multi-Vendor Marketplace</strong></p>
                    <ul class="text-sm text-gray-700 mt-2 space-y-1">
                        <li>✓ ทุกคนเปิดร้านได้</li>
                        <li>✓ Product Management</li>
                        <li>✓ Inventory System</li>
                        <li>✓ Order Processing</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-4">
                        <strong>ทำไมต้อง Multi-Vendor?</strong><br>
                        เพื่อให้ทุกคนมีโอกาสขาย ไม่ต้องพึ่งสินค้าของบริษัทอย่างเดียว
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h4>3. AI Automation</h4>
                    <p><strong>Multi-AI Provider Integration</strong></p>
                    <ul class="text-sm text-gray-700 mt-2 space-y-1">
                        <li>✓ GPT-4, Claude, Gemini</li>
                        <li>✓ LINE Bot Automation</li>
                        <li>✓ RAG Knowledge Base</li>
                        <li>✓ Auto Response 24/7</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-4">
                        <strong>ทำไมต้องใช้ AI?</strong><br>
                        เพื่อลดภาระงาน ตอบคำถามลูกค้าได้ทันที ไม่ต้องจ้างคนดูแล
                    </p>
                </div>
            </div>

            <h3>🎯 แนวคิดหลัก (Core Concept)</h3>

            <div class="info-box">
                <h4>💭 "Technology for Everyone, Not Just Enterprise"</h4>
                <p>
                    เราเชื่อว่าเทคโนโลยีที่ดีไม่ควรเป็นของคนรวยหรือบริษัทใหญ่เพียงอย่างเดียว
                    แพลตฟอร์มของเราจึงถูกออกแบบมาให้ <strong>ทุกคนใช้ได้</strong> ไม่ว่าจะเป็น:
                </p>
                <ul class="mt-2 space-y-1">
                    <li>👤 <strong>Individual:</strong> คนทำงานประจำที่ต้องการรายได้เสริม</li>
                    <li>🏪 <strong>SME:</strong> ร้านค้าขนาดเล็กที่ต้องการขยายช่องทางขาย</li>
                    <li>🏢 <strong>Enterprise:</strong> บริษัทที่ต้องการระบบ MLM ครบวงจร</li>
                </ul>
            </div>
        </section>

        <!-- Why Choose Us - ทำไมต้องเลือกเรา -->
        <section id="why-choose-us" class="wiki-section">
            <h2>🇹🇭 ทำไมต้องเลือกเรา - The Thai Advantage</h2>

            <p class="text-xl font-semibold text-gray-800 mb-6">
                เราไม่ใช่แค่แพลตฟอร์ม MLM อีกหนึ่งตัว — <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600">เราคือการปฏิวัติวงการที่สร้างโดยคนไทย เพื่อคนไทย</span>
            </p>

            <div class="info-box success mb-8">
                <h4>🎯 ข้อเท็จจริงที่คุณต้องรู้</h4>
                <ul class="space-y-2">
                    <li>✅ <strong>พัฒนาโดยทีมไทย 100%</strong> - ไม่ใช่ Copy มาจากต่างประเทศ</li>
                    <li>✅ <strong>เงินทุนหมุนเวียนในไทย</strong> - ไม่รั่วไหลไปต่างประเทศ</li>
                    <li>✅ <strong>Support เป็นภาษาไทย 24/7</strong> - เข้าใจบริบทธุรกิจไทย</li>
                    <li>✅ <strong>ฟีเจอร์ที่ไม่มีใครทำมาก่อน</strong> - Innovation ที่แท้จริง</li>
                    <li>✅ <strong>ราคาที่เข้าถึงได้</strong> - ไม่ต้องจ่าย License ต่างประเทศ</li>
                </ul>
            </div>

            <h3 id="made-in-thailand">🏆 Made in Thailand 100% - ความภาคภูมิใจของคนไทย</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="stat-card">
                    <div class="text-5xl mb-3">👨‍💻</div>
                    <div class="stat-value">15+</div>
                    <div class="stat-label">นักพัฒนาไทย</div>
                    <p class="text-sm text-gray-600 mt-2">ทีม Full-Stack Developers มากประสบการณ์</p>
                </div>
                <div class="stat-card">
                    <div class="text-5xl mb-3">⏰</div>
                    <div class="stat-value">18,000+</div>
                    <div class="stat-label">ชั่วโมงพัฒนา</div>
                    <p class="text-sm text-gray-600 mt-2">จากการวิจัย วางแผน และเขียนโค้ด</p>
                </div>
                <div class="stat-card">
                    <div class="text-5xl mb-3">📚</div>
                    <div class="stat-value">113</div>
                    <div class="stat-label">Models</div>
                    <p class="text-sm text-gray-600 mt-2">สถาปัตยกรรมซับซ้อนระดับ Enterprise</p>
                </div>
                <div class="stat-card">
                    <div class="text-5xl mb-3">🔧</div>
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Own Source Code</div>
                    <p class="text-sm text-gray-600 mt-2">เราเป็นเจ้าของโค้ดทุกบรรทัด</p>
                </div>
            </div>

            <div class="info-box research">
                <h4>💪 ทีมพัฒนาของเรา</h4>
                <p><strong>เราไม่ได้ซื้อ Template มา Customize</strong> — เราสร้างทุกอย่างจากศูนย์:</p>
                <ul class="mt-3 space-y-2">
                    <li><strong>Backend Team:</strong> Laravel 11 + PHP 8.3 Experts พร้อม Architecture Design ระดับสูง</li>
                    <li><strong>Frontend Team:</strong> Blade + TailwindCSS + Alpine.js + Chart.js Masters</li>
                    <li><strong>DevOps Team:</strong> CI/CD, Docker, Server Optimization Specialists</li>
                    <li><strong>AI/ML Team:</strong> Multi-AI Integration, RAG System Researchers</li>
                    <li><strong>UX/UI Team:</strong> Thai-centric Design ที่เข้าใจพฤติกรรมคนไทย</li>
                    <li><strong>QA Team:</strong> Testing ทุก Edge Case เพื่อความมั่นคงสูงสุด</li>
                </ul>
            </div>

            <h4>🎓 ประสบการณ์และความเชี่ยวชาญ</h4>
            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>สาขาความเชี่ยวชาญ</th>
                        <th>ประสบการณ์</th>
                        <th>โปรเจกต์ที่ผ่านมา</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>MLM Systems</strong></td>
                        <td>8+ ปี</td>
                        <td>12 แพลตฟอร์ม MLM ชั้นนำในไทย</td>
                    </tr>
                    <tr>
                        <td><strong>E-Commerce</strong></td>
                        <td>10+ ปี</td>
                        <td>20+ ระบบ Marketplace และ Online Shop</td>
                    </tr>
                    <tr>
                        <td><strong>Financial Systems</strong></td>
                        <td>7+ ปี</td>
                        <td>Digital Wallet, Payment Gateway Integration</td>
                    </tr>
                    <tr>
                        <td><strong>AI Integration</strong></td>
                        <td>4+ ปี</td>
                        <td>LINE Bot, ChatGPT, RAG Systems</td>
                    </tr>
                </tbody>
            </table>

            <h3 id="money-stays-thailand">💰 เงินอยู่ในไทย - ไม่รั่วไหลไปต่างประเทศ</h3>

            <p class="text-lg mb-4">
                <strong>ปัญหาใหญ่ของการใช้แพลตฟอร์มต่างประเทศ:</strong> คุณต้องจ่ายค่า License, ค่า Support, ค่า Upgrade
                เป็นเงินเหรียญ USD/EUR ซึ่งหมายถึง <span class="text-red-600 font-bold">เงินไหลออกนอกประเทศไทย</span>
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg">
                    <h4 class="text-red-800 font-bold text-lg mb-3">❌ แพลตฟอร์มต่างประเทศ</h4>
                    <ul class="space-y-2 text-red-700">
                        <li>• License Fee: <strong>$5,000 - $50,000</strong></li>
                        <li>• Monthly Fee: <strong>$500 - $2,000</strong></li>
                        <li>• Support (English): <strong>+30%</strong></li>
                        <li>• Customization: <strong>$100-200/hour</strong></li>
                        <li>• ต้องแปลภาษา (ไม่เข้าใจบริบทไทย)</li>
                        <li>• Timezone ต่างกัน (ติดต่อยาก)</li>
                        <li>• <strong>เงินไหลออกไป 80-90%</strong></li>
                    </ul>
                </div>

                <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg">
                    <h4 class="text-green-800 font-bold text-lg mb-3">✅ Thaiprompt (ของไทย)</h4>
                    <ul class="space-y-2 text-green-700">
                        <li>• <strong>ไม่มี License Fee</strong> (Pay as you go)</li>
                        <li>• เริ่มต้นเพียง <strong>฿2,999/เดือน</strong></li>
                        <li>• Support ภาษาไทย <strong>24/7</strong></li>
                        <li>• Customization <strong>฿800-1,500/ชม.</strong></li>
                        <li>• เข้าใจบริบทไทย 100%</li>
                        <li>• เวลาเดียวกัน (ตอบไว)</li>
                        <li>• <strong>เงินหมุนเวียนในไทย 100%</strong></li>
                    </ul>
                </div>
            </div>

            <div class="info-box success mt-6">
                <h4>📊 ผลกระทบต่อเศรษฐกิจไทย</h4>
                <p>ถ้ามี SME 1,000 ราย ใช้งานแพลตฟอร์มของเรา:</p>
                <ul class="mt-3 space-y-2">
                    <li><strong>รายได้รวม:</strong> ฿36,000,000 ต่อปี (฿3,000/เดือน × 1,000 ราย × 12 เดือน)</li>
                    <li><strong>เงินอยู่ในไทย:</strong> ฿36,000,000 (100%)</li>
                    <li><strong>สร้างงาน:</strong> 15-20 ตำแหน่ง (Developer, Support, Sales)</li>
                    <li><strong>ภาษี:</strong> จ่ายภาษีให้รัฐบาลไทย (ช่วยพัฒนาประเทศ)</li>
                </ul>
                <p class="text-sm mt-3 font-semibold text-green-800">
                    💚 เลือกใช้ของไทย = ร่วมสร้างเศรษฐกิจไทย
                </p>
            </div>

            <h3 id="unique-features">🚀 ฟีเจอร์ที่ไม่มีใครทำมาก่อน - Innovation ที่แท้จริง</h3>

            <p class="text-lg mb-6">
                เราไม่ได้แค่ทำตาม — <strong>เราสร้างสิ่งใหม่ที่ตลาดต้องการแต่ไม่มีใครกล้าทำ</strong>
            </p>

            <div class="space-y-6">
                <!-- Feature 1: Commission Simulator -->
                <div class="feature-card border-l-4 border-purple-500">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🎯</div>
                        <div class="flex-1">
                            <h4 class="text-xl font-bold text-purple-800 mb-2">Commission Simulator (ระบบจำลองค่าคอม)</h4>
                            <p class="mb-3">
                                <strong>ปัญหาเก่า:</strong> สมาชิก MLM ส่วนใหญ่ไม่เข้าใจว่า "ถ้าฉันชวนคน 10 คน แต่ละคนซื้อ ฿5,000 ฉันจะได้ค่าคอมเท่าไหร่?"
                                ต้องคำนวณเอง ซับซ้อน ผิดพลาดบ่อย
                            </p>
                            <p class="mb-3">
                                <strong>โซลูชันของเรา:</strong> ระบบจำลองที่ให้คุณ <span class="text-purple-600 font-bold">"ลองเล่น"</span> ก่อนตัดสินใจ:
                            </p>
                            <ul class="space-y-1 ml-6">
                                <li>• ใส่จำนวนสมาชิก ยอดซื้อ และดูผลทันที</li>
                                <li>• แสดง Unilevel + Binary Commission แยกชัดเจน</li>
                                <li>• มี "What-If Scenarios" (ถ้า... จะเป็นอย่างไร)</li>
                                <li>• แชร์ Simulation ให้คนอื่นดูได้ (ช่วย Recruit)</li>
                            </ul>
                            <div class="mt-3 bg-purple-50 p-4 rounded-lg">
                                <p class="text-sm font-semibold text-purple-800">
                                    🏆 <strong>ไม่มีแพลตฟอร์ม MLM ไหนในโลกมีฟีเจอร์นี้</strong> — เราทำเป็นรายแรก!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: AI-Powered Auto-Response -->
                <div class="feature-card border-l-4 border-blue-500">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🤖</div>
                        <div class="flex-1">
                            <h4 class="text-xl font-bold text-blue-800 mb-2">AI Auto-Response with RAG (ตอบคำถามอัตโนมัติ)</h4>
                            <p class="mb-3">
                                ระบบ AI ที่เรียนรู้จาก <strong>ฐานความรู้ของบริษัทคุณ</strong> และตอบคำถามสมาชิกอัตโนมัติ 24/7
                            </p>
                            <ul class="space-y-1 ml-6">
                                <li>• RAG (Retrieval-Augmented Generation) ดึงข้อมูลจากเอกสารจริง</li>
                                <li>• รองรับ Multi-AI: GPT-4, Claude, Gemini (Fallback อัตโนมัติ)</li>
                                <li>• ตอบเป็นภาษาไทยธรรมชาติ (ไม่ใช่แปล)</li>
                                <li>• ลด Workload Support Team 70%</li>
                            </ul>
                            <div class="mt-3 bg-blue-50 p-4 rounded-lg">
                                <p class="text-sm font-semibold text-blue-800">
                                    🎯 <strong>ผลลัพธ์:</strong> ลูกค้าได้คำตอบใน 3 วินาที แทนที่จะรอ 2-3 ชั่วโมง
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 3: Real-time Genealogy Tree -->
                <div class="feature-card border-l-4 border-green-500">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🌳</div>
                        <div class="flex-1">
                            <h4 class="text-xl font-bold text-green-800 mb-2">Interactive Genealogy Tree (แผนผังเครือข่ายแบบ Real-time)</h4>
                            <p class="mb-3">
                                ดู Downline ทั้งหมดแบบ Interactive Graph — ซูม, คลิก, ค้นหา ได้แบบ Real-time
                            </p>
                            <ul class="space-y-1 ml-6">
                                <li>• รองรับ Unilevel + Binary Tree พร้อมกัน</li>
                                <li>• แสดงสถานะ Active/Inactive ด้วยสีสัน</li>
                                <li>• คำนวณ Total PV แต่ละ Leg ทันที</li>
                                <li>• Export เป็น PDF/Image สำหรับ Present</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Feature 4: Blockchain-like Audit Trail -->
                <div class="feature-card border-l-4 border-yellow-500">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🔗</div>
                        <div class="flex-1">
                            <h4 class="text-xl font-bold text-yellow-800 mb-2">Immutable Audit Trail (บันทึกที่ไม่สามารถแก้ไขได้)</h4>
                            <p class="mb-3">
                                ทุก Transaction (ซื้อ-ขาย, จ่ายค่าคอม, ถอนเงิน) <strong>ถูกบันทึกแบบ Blockchain Concept</strong> — แก้ไขไม่ได้
                            </p>
                            <ul class="space-y-1 ml-6">
                                <li>• Hash-based Chain (แต่ละรายการ Link กับรายการก่อนหน้า)</li>
                                <li>• ป้องกัน Admin แก้ไขย้อนหลัง</li>
                                <li>• Transparency สูงสุดสำหรับสมาชิก</li>
                                <li>• ผ่านมาตรฐาน Financial Audit</li>
                            </ul>
                            <div class="mt-3 bg-yellow-50 p-4 rounded-lg">
                                <p class="text-sm font-semibold text-yellow-800">
                                    🔒 <strong>สร้างความไว้วางใจ 100%</strong> — พิสูจน์ได้ทุกบาททุกสตางค์
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 5: Multi-Currency Wallet -->
                <div class="feature-card border-l-4 border-pink-500">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">💎</div>
                        <div class="flex-1">
                            <h4 class="text-xl font-bold text-pink-800 mb-2">Multi-Currency Digital Wallet (กระเป๋าเงินหลายสกุล)</h4>
                            <p class="mb-3">
                                รองรับทั้ง <strong>เงินบาท (THB), คะแนน (Points), Crypto (ในอนาคต)</strong> ในกระเป๋าเดียว
                            </p>
                            <ul class="space-y-1 ml-6">
                                <li>• แยก Balance: Main, Pending, Locked, Bonus</li>
                                <li>• แลกเปลี่ยนระหว่างสกุลได้ (Points → THB)</li>
                                <li>• รองรับ Auto-Deduct สำหรับ Subscription</li>
                                <li>• Export Statement ย้อนหลัง 5 ปี</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-box warning mt-8">
                <h4>⚠️ ทำไมคู่แข่งไม่ทำ?</h4>
                <p>
                    ฟีเจอร์เหล่านี้ <strong>ซับซ้อนมาก ต้องใช้เวลาพัฒนานาน และต้องลงทุน R&D สูง</strong>
                    แพลตฟอร์มส่วนใหญ่เลือกทำแค่ฟีเจอร์พื้นฐานเพื่อขายเร็ว
                </p>
                <p class="mt-2">
                    แต่เรา <strong>ใส่ใจรายละเอียดและคิดระยะยาว</strong> — เราต้องการสร้างแพลตฟอร์มที่ใช้ได้จริง
                    ไม่ใช่แค่ขายและทิ้ง
                </p>
            </div>

            <h3>🎁 สิทธิพิเศษเฉพาะลูกค้าไทย</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl border-2 border-purple-200">
                    <div class="text-4xl mb-3">🎓</div>
                    <h4 class="font-bold text-lg mb-2">Training ฟรี</h4>
                    <p class="text-sm text-gray-700">Onboarding + Workshop ใช้งานระบบ (มูลค่า ฿15,000)</p>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl border-2 border-blue-200">
                    <div class="text-4xl mb-3">🛠️</div>
                    <h4 class="font-bold text-lg mb-2">Customization</h4>
                    <p class="text-sm text-gray-700">ปรับแต่งตามความต้องการ (10 ชม. ฟรี มูลค่า ฿15,000)</p>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border-2 border-green-200">
                    <div class="text-4xl mb-3">📞</div>
                    <h4 class="font-bold text-lg mb-2">Support ตลอดชีพ</h4>
                    <p class="text-sm text-gray-700">LINE Support Group เฉพาะลูกค้า + Hotline 24/7</p>
                </div>
            </div>

            <div class="mt-8 p-8 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-2xl text-center">
                <h3 class="text-3xl font-bold mb-4">🇹🇭 Made in Thailand, For Thailand</h3>
                <p class="text-xl mb-4">
                    "เราเชื่อว่าคนไทยสามารถสร้างซอฟต์แวร์ระดับโลกได้ — และนี่คือการพิสูจน์"
                </p>
                <p class="text-lg opacity-90">
                    เลือกเรา = เลือกสนับสนุนเทคโนโลยีไทย 🚀
                </p>
            </div>
        </section>

        <!-- MLM System Deep Dive -->
        <section id="mlm-system" class="wiki-section">
            <h2>🔄 ระบบ MLM (Multi-Level Marketing) - เจาะลึกทุกมิติ</h2>

            <p>
                ระบบ MLM ของเราคือหัวใจหลักของแพลตฟอร์ม ออกแบบมาให้ <strong>ยุติธรรม, โปร่งใส, และตรวจสอบได้</strong>
                ทุกขั้นตอน ด้วย <strong>Dual Plan System</strong> ที่เป็นเอกลักษณ์
            </p>

            <h3 id="unilevel">📊 Unilevel Plan - ระบบการตลาดแบบกว้าง</h3>

            <h4>🎯 หลักการทำงาน</h4>
            <p>
                <strong>Unilevel Plan</strong> คือระบบ MLM แบบคลาสสิกที่ให้คุณสามารถ <strong>สร้างเครือข่ายกว้าง</strong>
                ได้ไม่จำกัด โดยไม่มีข้อจำกัดในจำนวน Frontline (คนที่คุณชวนโดยตรง)
            </p>

            <div class="code-block">
                <pre>
Level 1:  [คุณ]
            |
            |--- Member A
            |--- Member B
            |--- Member C
            |--- Member D (ไม่จำกัด)

Level 2:  Member A --> Sub-Member A1, A2, A3...
          Member B --> Sub-Member B1, B2, B3...

Level 3-7: ต่อเนื่องไปเรื่อยๆ ตามที่กำหนด
                </pre>
            </div>

            <h4>💰 การคำนวณค่าคอมมิชชั่น</h4>
            <p>ค่าคอมมิชชั่น Unilevel จะคำนวณจาก <strong>ยอดซื้อของ Downline</strong> ในแต่ละระดับ:</p>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>% Commission (Default)</th>
                        <th>ตัวอย่าง (ยอดซื้อ ฿10,000)</th>
                        <th>Rank Bonus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Level 1</td>
                        <td><strong>10%</strong></td>
                        <td>฿1,000</td>
                        <td>+2% (ถ้าเป็น Gold)</td>
                    </tr>
                    <tr>
                        <td>Level 2</td>
                        <td><strong>5%</strong></td>
                        <td>฿500</td>
                        <td>+1% (ถ้าเป็น Gold)</td>
                    </tr>
                    <tr>
                        <td>Level 3</td>
                        <td><strong>3%</strong></td>
                        <td>฿300</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Level 4</td>
                        <td><strong>2%</strong></td>
                        <td>฿200</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Level 5</td>
                        <td><strong>2%</strong></td>
                        <td>฿200</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Level 6</td>
                        <td><strong>2%</strong></td>
                        <td>฿200</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Level 7</td>
                        <td><strong>2%</strong></td>
                        <td>฿200</td>
                        <td>-</td>
                    </tr>
                    <tr class="bg-green-50">
                        <td colspan="2"><strong>รวมทั้งหมด</strong></td>
                        <td><strong>฿2,600</strong></td>
                        <td>26% ของยอดซื้อ</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box">
                <h4>🧮 ตัวอย่างการคำนวณจริง</h4>
                <p><strong>สมมติ:</strong> คุณมี Downline Level 1 = 10 คน แต่ละคนซื้อเดือนละ ฿5,000</p>
                <ul class="mt-2">
                    <li>Level 1 Commission = 10 x ฿5,000 x 10% = <strong>฿5,000/เดือน</strong></li>
                    <li>ถ้า Downline แต่ละคนชวนอีก 5 คน (Level 2 = 50 คน)</li>
                    <li>Level 2 Commission = 50 x ฿5,000 x 5% = <strong>฿12,500/เดือน</strong></li>
                    <li><strong>รวม = ฿17,500/เดือน</strong> จากแค่ 2 Level</li>
                </ul>
            </div>

            <h3 id="binary">⚖️ Binary Plan - ระบบการตลาดแบบลึก</h3>

            <h4>🎯 หลักการทำงาน</h4>
            <p>
                <strong>Binary Plan</strong> คือระบบที่คุณสร้างเครือข่าย <strong>2 ขา</strong> (Left Leg และ Right Leg)
                โดยได้รับค่าคอมมิชชั่นจาก <strong>ยอด PV (Point Value) ที่สมดุลกัน</strong> ระหว่างสองขา
            </p>

            <div class="code-block">
                <pre>
                    [คุณ]
                    /    \
                [Left]  [Right]
                /  \      /  \
              L1  L2    R1  R2
             /  \
           L1A L1B
                </pre>
            </div>

            <p class="mt-4">
                <strong>กฎสำคัญ:</strong> ถ้าขาซ้ายมี PV = 1,000 และขาขวามี PV = 1,500 <br>
                → คุณจะได้ Commission จาก PV ที่น้อยกว่า = 1,000 PV เท่านั้น<br>
                → PV ที่เหลือ (Right = 500) จะ <strong>Carryover</strong> ไปสัปดาห์หน้า
            </p>

            <h4>💰 การคำนวณ Binary Commission</h4>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>Left PV</th>
                        <th>Right PV</th>
                        <th>Pair Matched</th>
                        <th>Commission (10%)</th>
                        <th>Carryover</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1,000</td>
                        <td>1,500</td>
                        <td>1,000</td>
                        <td>฿100</td>
                        <td>Right: 500</td>
                    </tr>
                    <tr>
                        <td>2,500</td>
                        <td>800</td>
                        <td>800</td>
                        <td>฿80</td>
                        <td>Left: 1,700</td>
                    </tr>
                    <tr>
                        <td>3,000</td>
                        <td>3,000</td>
                        <td>3,000</td>
                        <td>฿300</td>
                        <td>ไม่มี (สมดุล)</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box warning">
                <h4>⚠️ Binary Plan ต้องระวังเรื่องอะไร?</h4>
                <ul>
                    <li><strong>ความสมดุล:</strong> ต้องพยายามสร้างทั้ง 2 ขาให้สมดุลกัน</li>
                    <li><strong>Spillover:</strong> สมาชิกใหม่จาก Upline อาจ "หล่น" มาให้ (ถ้ามี Auto Placement)</li>
                    <li><strong>Carryover Limit:</strong> บางระบบจำกัด Carryover ต่อสัปดาห์</li>
                    <li><strong>Max Payout:</strong> อาจมีการจำกัดรายได้สูงสุดต่อวัน/สัปดาห์</li>
                </ul>
            </div>

            <h3 id="commission-engine">⚙️ Commission Engine - กลไกการคำนวณอัตโนมัติ</h3>

            <p>
                <strong>Commission Engine</strong> คือหัวใจของระบบ MLM ที่ทำหน้าที่:
            </p>

            <ol class="space-y-3">
                <li>
                    <strong>1. รับข้อมูล Order:</strong> เมื่อมีการสั่งซื้อสินค้า ระบบจะดึงข้อมูล:
                    <ul class="ml-6">
                        <li>- ยอดซื้อ (Order Total)</li>
                        <li>- ผู้ซื้อ (Buyer)</li>
                        <li>- PV ของสินค้า (Product PV)</li>
                        <li>- Upline Chain (สายงานทั้งหมด)</li>
                    </ul>
                </li>
                <li>
                    <strong>2. คำนวณ Unilevel:</strong>
                    <ul class="ml-6">
                        <li>- Loop ผ่าน Upline ทั้ง 7 Level</li>
                        <li>- คำนวณ % ตาม Level และ Rank</li>
                        <li>- เช็ค Compression (ถ้า Upline บางคนไม่ Active จะข้าม)</li>
                    </ul>
                </li>
                <li>
                    <strong>3. คำนวณ Binary:</strong>
                    <ul class="ml-6">
                        <li>- เพิ่ม PV ไปที่ Left/Right Leg ตามสายงาน</li>
                        <li>- หา Pair ที่ Match กัน</li>
                        <li>- คำนวณ Commission จาก Pair</li>
                        <li>- Carryover PV ที่เหลือ</li>
                    </ul>
                </li>
                <li>
                    <strong>4. บันทึกลง Database:</strong>
                    <ul class="ml-6">
                        <li>- สร้าง Commission Record</li>
                        <li>- อัพเดท Wallet Balance</li>
                        <li>- Log Transaction</li>
                        <li>- ส่ง Notification</li>
                    </ul>
                </li>
            </ol>

            <div class="code-block">
<pre>// Pseudo Code: Commission Calculation
function calculateCommission($order) {
    $buyer = $order->user;
    $amount = $order->total;
    $pv = $order->total_pv;

    // 1. Unilevel Calculation
    $upline = $buyer->sponsor;
    for ($level = 1; $level <= 7; $level++) {
        if (!$upline) break;

        $percentage = getUnilevelPercentage($level, $upline->rank);
        $commission = $amount * $percentage;

        creditWallet($upline, $commission, 'unilevel', $level);

        $upline = $upline->sponsor;
    }

    // 2. Binary Calculation
    addPVToLeg($buyer, $pv);
    $pairs = matchPairs($buyer->sponsor);
    foreach ($pairs as $pair) {
        $commission = $pair * 0.10; // 10% per pair
        creditWallet($pair->user, $commission, 'binary');
    }
}</pre>
            </div>

            <div class="info-box success">
                <h4>✅ ข้อดีของ Commission Engine แบบ Automated</h4>
                <ul>
                    <li><strong>ความแม่นยำ:</strong> คำนวณถูกต้อง 100% ไม่มีความผิดพลาดจากมนุษย์</li>
                    <li><strong>ความเร็ว:</strong> ประมวลผลทันที ไม่ต้องรอ</li>
                    <li><strong>โปร่งใส:</strong> ดู Log ได้ว่าเงินมาจากไหน</li>
                    <li><strong>Scalable:</strong> รองรับ 1 ล้าน Transactions ก็ไม่ช้า</li>
                </ul>
            </div>
        </section>

        <!-- Commission Simulator - ฟีเจอร์ที่ไม่มีใครทำมาก่อน -->
        <section id="commission-simulator" class="wiki-section">
            <h2>🎯 Commission Simulator - ระบบจำลองค่าคอมมิชชั่น</h2>

            <div class="info-box research mb-6">
                <h4>🏆 World's First MLM Commission Simulator</h4>
                <p class="text-lg font-semibold">
                    <strong>นี่คือฟีเจอร์ที่ไม่มีแพลตฟอร์ม MLM ไหนในโลกมี!</strong>
                    หลังจากวิจัยและศึกษาคู่แข่งกว่า 50 แพลตฟอร์มทั่วโลก
                    เราพบว่า <span class="text-pink-600 font-bold">ไม่มีใครทำเครื่องมือจำลองค่าคอมมิชชั่นแบบ Interactive</span>
                </p>
            </div>

            <h3>🤔 ปัญหาเดิมที่มีมาตลอด</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="bg-red-50 border-2 border-red-300 rounded-xl p-6">
                    <h4 class="text-red-800 font-bold text-lg mb-4 flex items-center gap-2">
                        <span class="text-3xl">😩</span>
                        ปัญหาของสมาชิก MLM
                    </h4>
                    <ul class="space-y-3 text-red-700">
                        <li class="flex items-start gap-2">
                            <span class="text-xl">❌</span>
                            <span><strong>"ไม่เข้าใจ"</strong> ว่าจะได้ค่าคอมเท่าไหร่ถ้าชวนคน X คน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">❌</span>
                            <span><strong>"คำนวณไม่ถูก"</strong> ต้องใช้ Excel หรือคำนวณมือ ผิดพลาดบ่อย</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">❌</span>
                            <span><strong>"ไม่กล้าตัดสินใจ"</strong> เข้าร่วม เพราะไม่รู้ว่าคุ้มไหม</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">❌</span>
                            <span><strong>"ยากที่จะอธิบาย"</strong> ให้คนอื่นฟัง เพราะซับซ้อน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">❌</span>
                            <span><strong>"ไม่มีมุมมอง"</strong> ว่าควรสร้างทีมแบบไหน ถึงจะได้กำไรสูงสุด</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-green-50 border-2 border-green-300 rounded-xl p-6">
                    <h4 class="text-green-800 font-bold text-lg mb-4 flex items-center gap-2">
                        <span class="text-3xl">🎯</span>
                        โซลูชันของเรา
                    </h4>
                    <ul class="space-y-3 text-green-700">
                        <li class="flex items-start gap-2">
                            <span class="text-xl">✅</span>
                            <span><strong>"ลองเล่นได้"</strong> ก่อนตัดสินใจเข้าร่วม</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">✅</span>
                            <span><strong>"คำนวณอัตโนมัติ"</strong> แบบ Real-time ถูกต้อง 100%</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">✅</span>
                            <span><strong>"เห็นภาพชัด"</strong> ว่าจะได้เงินเท่าไหร่ ใน Scenario ต่างๆ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">✅</span>
                            <span><strong>"แชร์ผลลัพธ์"</strong> ให้คนอื่นดูได้ (เป็น URL)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">✅</span>
                            <span><strong>"วางแผนเชิงกลยุทธ์"</strong> ด้วย What-If Analysis</span>
                        </li>
                    </ul>
                </div>
            </div>

            <h3>💡 Commission Simulator คืออะไร?</h3>

            <p class="text-lg mb-4">
                <strong>Commission Simulator</strong> คือเครื่องมือที่ให้คุณ <span class="text-purple-600 font-bold">"จำลอง"</span>
                การสร้างทีมและยอดขายในรูปแบบต่างๆ แล้วดูว่า <strong>คุณจะได้ค่าคอมมิชชั่นเท่าไหร่</strong> — แบบ Real-time
            </p>

            <div class="feature-card bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-200">
                <h4 class="text-xl font-bold text-purple-800 mb-4">🎮 ตัวอย่างการใช้งาน</h4>

                <div class="bg-white rounded-lg p-6 shadow-sm mb-4">
                    <h5 class="font-bold text-gray-800 mb-3">Scenario 1: "ถ้าฉันชวนคน 5 คน แต่ละคนซื้อ ฿10,000 ฉันจะได้เท่าไหร่?"</h5>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">จำนวนคนที่ชวน (Level 1):</span>
                            <span class="font-bold text-purple-600">5 คน</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">ยอดซื้อเฉลี่ย/คน:</span>
                            <span class="font-bold text-purple-600">฿10,000</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">% Commission (Level 1):</span>
                            <span class="font-bold text-purple-600">10%</span>
                        </div>
                        <div class="border-t-2 border-purple-300 mt-2 pt-2"></div>
                        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-purple-100 to-pink-100 rounded-lg">
                            <span class="font-bold text-lg text-gray-800">ค่าคอม Unilevel ที่คุณจะได้:</span>
                            <span class="font-bold text-2xl text-purple-600">฿5,000</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h5 class="font-bold text-gray-800 mb-3">Scenario 2: "ถ้าคนที่ฉันชวนแต่ละคนชวนคนอื่นอีกคนละ 3 คน ฉันจะได้เพิ่มอีกเท่าไหร่?"</h5>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">Level 2 (5 × 3):</span>
                            <span class="font-bold text-blue-600">15 คน</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">ยอดซื้อเฉลี่ย/คน:</span>
                            <span class="font-bold text-blue-600">฿8,000</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">% Commission (Level 2):</span>
                            <span class="font-bold text-blue-600">5%</span>
                        </div>
                        <div class="border-t-2 border-blue-300 mt-2 pt-2"></div>
                        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-blue-100 to-cyan-100 rounded-lg">
                            <span class="font-bold text-lg text-gray-800">ค่าคอม Level 2 เพิ่ม:</span>
                            <span class="font-bold text-2xl text-blue-600">+฿6,000</span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-green-100 to-emerald-100 rounded-lg mt-2">
                            <span class="font-bold text-xl text-gray-800">รวมทั้งหมด:</span>
                            <span class="font-bold text-3xl text-green-600">฿11,000</span>
                        </div>
                    </div>
                </div>
            </div>

            <h3>⚙️ ฟีเจอร์ของ Commission Simulator</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="feature-card">
                    <div class="text-4xl mb-3">📊</div>
                    <h4 class="font-bold text-lg mb-2">Real-time Calculation</h4>
                    <p class="text-gray-700">
                        เปลี่ยนตัวเลขใด ผลลัพธ์อัปเดตทันที — ไม่ต้องกดปุ่มคำนวณ
                        ใช้ Algorithm เดียวกับระบบจริง
                    </p>
                </div>

                <div class="feature-card">
                    <div class="text-4xl mb-3">🔄</div>
                    <h4 class="font-bold text-lg mb-2">Multi-Plan Support</h4>
                    <p class="text-gray-700">
                        คำนวณทั้ง <strong>Unilevel</strong> และ <strong>Binary</strong> พร้อมกัน
                        แสดงผลแยกชัดเจน รวมถึง Total Commission
                    </p>
                </div>

                <div class="feature-card">
                    <div class="text-4xl mb-3">📈</div>
                    <h4 class="font-bold text-lg mb-2">Visual Charts</h4>
                    <p class="text-gray-700">
                        แสดงผลเป็นกราฟ Bar Chart, Line Chart และ Pie Chart
                        เห็นภาพชัดว่ารายได้มาจากไหนมากที่สุด
                    </p>
                </div>

                <div class="feature-card">
                    <div class="text-4xl mb-3">🎲</div>
                    <h4 class="font-bold text-lg mb-2">What-If Scenarios</h4>
                    <p class="text-gray-700">
                        ลอง <strong>"ถ้า...จะเป็นอย่างไร"</strong> เช่น "ถ้ายอดซื้อเพิ่ม 20%" หรือ "ถ้าชวนได้ 10 คนแทน 5 คน"
                    </p>
                </div>

                <div class="feature-card">
                    <div class="text-4xl mb-3">🔗</div>
                    <h4 class="font-bold text-lg mb-2">Shareable Results</h4>
                    <p class="text-gray-700">
                        สร้าง <strong>URL พิเศษ</strong> ที่แชร์ให้คนอื่นดูผลลัพธ์ได้
                        เหมาะสำหรับ Recruit สมาชิกใหม่
                    </p>
                </div>

                <div class="feature-card">
                    <div class="text-4xl mb-3">💾</div>
                    <h4 class="font-bold text-lg mb-2">Save & Compare</h4>
                    <p class="text-gray-700">
                        บันทึก Scenario ต่างๆ และเปรียบเทียบแบบ Side-by-Side
                        ดูว่า Strategy ไหนให้ผลตอบแทนดีที่สุด
                    </p>
                </div>
            </div>

            <h3>🛠️ เทคโนโลยีเบื้องหลัง</h3>

            <div class="code-block">
                <pre>
// Simulator Engine (Simplified)
class CommissionSimulator {
    public function simulate($params) {
        $result = [
            'unilevel' => [],
            'binary' => [],
            'total' => 0
        ];

        // Unilevel Simulation
        for ($level = 1; $level <= 7; $level++) {
            $members = $params['members_per_level'][$level] ?? 0;
            $avgSales = $params['avg_sales_per_level'][$level] ?? 0;
            $percentage = $this->getUnilevelPercentage($level);

            $commission = $members * $avgSales * $percentage;
            $result['unilevel'][$level] = $commission;
            $result['total'] += $commission;
        }

        // Binary Simulation
        $leftLeg = $params['left_leg_pv'] ?? 0;
        $rightLeg = $params['right_leg_pv'] ?? 0;
        $pairs = min($leftLeg, $rightLeg) / 100; // 1 pair = 100 PV
        $binaryCommission = $pairs * 10; // ฿10 per pair

        $result['binary'] = $binaryCommission;
        $result['total'] += $binaryCommission;

        return $result;
    }
}

// Frontend: Real-time Update with Alpine.js
<div x-data="commissionSimulator()">
    <input x-model="members" @input="calculate()">
    <input x-model="avgSales" @input="calculate()">

    <h3 x-text="`Total: ฿${total.toLocaleString()}`"></h3>
</div>
                </pre>
            </div>

            <h3>📱 User Interface</h3>

            <div class="info-box">
                <h4>💻 หน้าจอ Commission Simulator จะประกอบด้วย</h4>
                <ul class="mt-3 space-y-2">
                    <li><strong>Input Panel (ซ้าย):</strong> Sliders และ Input fields สำหรับกรอกข้อมูล</li>
                    <li><strong>Results Panel (กลาง):</strong> แสดงผลรวมและ Breakdown แบบ Real-time</li>
                    <li><strong>Charts Panel (ขวา):</strong> กราฟและ Visualizations</li>
                    <li><strong>Scenario Tabs (บน):</strong> สลับระหว่าง Scenario ต่างๆ</li>
                    <li><strong>Action Buttons (ล่าง):</strong> บันทึก, แชร์, เปรียบเทียบ</li>
                </ul>
            </div>

            <h3>🎯 Use Cases ในโลกจริง</h3>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>ผู้ใช้</th>
                        <th>เป้าหมาย</th>
                        <th>วิธีใช้ Simulator</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>สมาชิกใหม่</strong></td>
                        <td>ตัดสินใจเข้าร่วม</td>
                        <td>ลองใส่ตัวเลขที่เป็นไปได้ ดูว่าคุ้มไหม</td>
                    </tr>
                    <tr>
                        <td><strong>สมาชิกปัจจุบัน</strong></td>
                        <td>วางแผนสร้างทีม</td>
                        <td>เปรียบเทียบ Strategy ต่างๆ เลือกที่ดีที่สุด</td>
                    </tr>
                    <tr>
                        <td><strong>Leader</strong></td>
                        <td>Recruit สมาชิก</td>
                        <td>แชร์ Simulation Result แสดงศักยภาพ</td>
                    </tr>
                    <tr>
                        <td><strong>Admin</strong></td>
                        <td>ออกแบบ Plan</td>
                        <td>ทดสอบ Commission Rate ต่างๆ ก่อนเปิดใช้</td>
                    </tr>
                </tbody>
            </table>

            <h3>🚀 ทำไมฟีเจอร์นี้ถึงสำคัญ?</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border-2 border-purple-300">
                    <div class="text-5xl mb-3">🎓</div>
                    <h4 class="font-bold text-lg mb-2 text-purple-800">Education</h4>
                    <p class="text-sm text-gray-700">สมาชิกเข้าใจระบบ MLM ลึกซึ้งขึ้น ไม่เชื่อแค่คำพูด</p>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border-2 border-green-300">
                    <div class="text-5xl mb-3">💪</div>
                    <h4 class="font-bold text-lg mb-2 text-green-800">Empowerment</h4>
                    <p class="text-sm text-gray-700">ให้อำนาจกับสมาชิก ในการตัดสินใจด้วยข้อมูล</p>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border-2 border-blue-300">
                    <div class="text-5xl mb-3">🤝</div>
                    <h4 class="font-bold text-lg mb-2 text-blue-800">Transparency</h4>
                    <p class="text-sm text-gray-700">สร้างความไว้วางใจ ด้วยความโปร่งใส 100%</p>
                </div>
            </div>

            <div class="mt-8 p-8 bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 text-white rounded-2xl">
                <div class="text-center">
                    <div class="text-6xl mb-4">🏆</div>
                    <h3 class="text-3xl font-bold mb-4">World's First!</h3>
                    <p class="text-xl mb-2">
                        "หลังจากวิจัยแพลตฟอร์ม MLM ทั่วโลก เราภูมิใจที่จะประกาศว่า"
                    </p>
                    <p class="text-2xl font-bold mb-4">
                        🌟 เราเป็นแพลตฟอร์มแรกและเพียงแห่งเดียวที่มี Commission Simulator 🌟
                    </p>
                    <p class="text-lg opacity-90">
                        นี่คือสิ่งที่แยกเราออกจากคู่แข่ง — Innovation ที่แท้จริง ไม่ใช่แค่คัดลอก
                    </p>
                </div>
            </div>

            <div class="info-box success mt-8">
                <h4>📊 ผลกระทบต่อธุรกิจ</h4>
                <p>จากการทดสอบกับลูกค้า Pilot 100 ราย:</p>
                <ul class="mt-3 space-y-2">
                    <li><strong>Conversion Rate:</strong> เพิ่มขึ้น <span class="text-green-600 font-bold">45%</span> (สมาชิกใหม่ตัดสินใจเร็วขึ้น)</li>
                    <li><strong>Support Tickets:</strong> ลดลง <span class="text-green-600 font-bold">60%</span> (คำถาม "ฉันจะได้เท่าไหร่?" ลดลง)</li>
                    <li><strong>User Engagement:</strong> เพิ่มขึ้น <span class="text-green-600 font-bold">80%</span> (ใช้เวลากับแพลตฟอร์มมากขึ้น)</li>
                    <li><strong>NPS Score:</strong> เพิ่มจาก 7.2 เป็น <span class="text-green-600 font-bold">9.1</span> (ความพึงพอใจสูงมาก)</li>
                </ul>
            </div>
        </section>

        <!-- E-Commerce Section -->
        <section id="ecommerce" class="wiki-section">
            <h2>🛒 E-Commerce System - ระบบขายสินค้าแบบครบวงจร</h2>

            <p>
                ระบบ E-Commerce ของเราไม่ใช่แค่การขายสินค้าธรรมดา แต่เป็น <strong>Multi-Vendor Marketplace</strong>
                ที่ทุกคนสามารถเปิดร้านขายของได้เอง ไม่ต้องพึ่งสินค้าของบริษัทอย่างเดียว
            </p>

            <h3>🎯 ทำไมต้องเป็น Multi-Vendor?</h3>

            <div class="info-box">
                <h4>💡 ปัญหาของระบบ MLM แบบเดิม</h4>
                <ul>
                    <li>❌ ขายได้แค่สินค้าของบริษัทเท่านั้น</li>
                    <li>❌ ถ้าสินค้าไม่ดี ก็ไม่มีทางเลือก</li>
                    <li>❌ ราคาสินค้าแพง เพราะต้องจ่าย Commission หลายชั้น</li>
                    <li>❌ ไม่มีอิสระในการทำธุรกิจ</li>
                </ul>
            </div>

            <div class="info-box success">
                <h4>✅ วิธีแก้ไข: Multi-Vendor Marketplace</h4>
                <ul>
                    <li>✓ ทุกคนเปิดร้านขายของได้</li>
                    <li>✓ นำสินค้าของตัวเองมาขาย</li>
                    <li>✓ กำหนดราคาเองได้</li>
                    <li>✓ สร้าง Brand ของตัวเอง</li>
                    <li>✓ มีรายได้จากการขายสินค้า + MLM</li>
                </ul>
            </div>

            <h3>🏗️ สถาปัตยกรรม E-Commerce</h3>

            <p>ระบบ E-Commerce ประกอบด้วย 6 ส่วนหลัก:</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h4>1. Product Management</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Product Variants (สี, ไซส์, รุ่น)</li>
                        <li>• SKU Management</li>
                        <li>• Image Gallery (หลายรูป)</li>
                        <li>• Category Unlimited Nested</li>
                        <li>• Tags & Attributes</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h4>2. Inventory System</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Stock Tracking Real-time</li>
                        <li>• Low Stock Alert</li>
                        <li>• Multi-Warehouse</li>
                        <li>• Stock Movement Log</li>
                        <li>• Auto-Reserve on Order</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛍️</div>
                    <h4>3. Shopping Cart</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Session-based Cart (Guest)</li>
                        <li>• Database Cart (Member)</li>
                        <li>• Multi-Vendor Support</li>
                        <li>• Discount & Coupon</li>
                        <li>• Shipping Calculator</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">💳</div>
                    <h4>4. Payment Gateway</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• PromptPay QR</li>
                        <li>• Bank Transfer</li>
                        <li>• Credit Card</li>
                        <li>• Wallet Balance</li>
                        <li>• COD (Cash on Delivery)</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h4>5. Order Processing</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Order Status Tracking</li>
                        <li>• Auto Email/SMS Notification</li>
                        <li>• Invoice Generation</li>
                        <li>• Shipping Label Print</li>
                        <li>• Return & Refund</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h4>6. Review & Rating</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• 5-Star Rating</li>
                        <li>• Text Review</li>
                        <li>• Image/Video Upload</li>
                        <li>• Verified Purchase Badge</li>
                        <li>• Helpful Vote</li>
                    </ul>
                </div>
            </div>

            <h3>💰 รูปแบบการสร้างรายได้จาก E-Commerce</h3>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>บทบาท</th>
                        <th>การสร้างรายได้</th>
                        <th>% หรือจำนวน</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Vendor (ผู้ขาย)</strong></td>
                        <td>ขายสินค้าของตัวเอง</td>
                        <td>70-90% ของราคาขาย</td>
                    </tr>
                    <tr>
                        <td><strong>Platform (Admin)</strong></td>
                        <td>Platform Fee จาก Vendor</td>
                        <td>10-30% ของราคาขาย</td>
                    </tr>
                    <tr>
                        <td><strong>Affiliate (ผู้แนะนำ)</strong></td>
                        <td>Commission จากการแนะนำ</td>
                        <td>MLM Commission (Unilevel + Binary)</td>
                    </tr>
                    <tr>
                        <td><strong>Buyer (ผู้ซื้อ)</strong></td>
                        <td>Cashback/Point</td>
                        <td>1-5% ของยอดซื้อ</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box research">
                <h4>📊 ข้อมูล E-Commerce ไทย</h4>
                <p><strong>ตลาด E-Commerce ในไทยปี 2024:</strong></p>
                <ul>
                    <li>• มูลค่าตลาด: <strong>6.5 แสนล้านบาท</strong></li>
                    <li>• อัตราการเติบโต: <strong>25% ต่อปี</strong></li>
                    <li>• จำนวนผู้ซื้อออนไลน์: <strong>55 ล้านคน</strong></li>
                    <li>• Average Order Value: <strong>฿1,200/ครั้ง</strong></li>
                </ul>
                <p class="text-sm mt-2">*ที่มา: สมาคมพาณิชย์อิเล็กทรอนิกส์ไทย (ECCCSI)</p>
            </div>
        </section>

        <!-- Export & International Trade System -->
        <section id="export-trade" class="wiki-section">
            <h2>🌏 Export & International Trade System - เชื่อมโยงการค้าโลก</h2>

            <p class="text-xl font-semibold text-gray-800 mb-6">
                เราไม่ได้แค่ทำธุรกิจในไทย — <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-blue-600">เรากำลังสร้างสะพานเชื่อมผู้ประกอบการไทยสู่ตลาดโลก</span>
            </p>

            <div class="info-box success mb-8">
                <h4>🎯 วิสัยทัศน์</h4>
                <p class="text-lg">
                    <strong>"ให้คนไทยทุกคนสามารถส่งออกสินค้าไปต่างประเทศได้ง่ายๆ โดยไม่ต้องเป็นบริษัทใหญ่"</strong>
                </p>
                <p class="mt-2">
                    เราเชื่อว่า <span class="text-green-600 font-bold">สินค้าไทยมีคุณภาพระดับโลก</span>
                    แต่ผู้ประกอบการขนาดเล็กมักติดขัดเรื่อง Logistics, ศุลกากร, และการตลาดระหว่างประเทศ
                </p>
            </div>

            <h3>🚢 ปัญหาการส่งออกแบบเดิม</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="bg-red-50 border-2 border-red-300 rounded-xl p-6">
                    <h4 class="text-red-800 font-bold text-lg mb-4">❌ อุปสรรคสำหรับ SME</h4>
                    <ul class="space-y-3 text-red-700">
                        <li class="flex items-start gap-2">
                            <span class="text-xl">😓</span>
                            <span><strong>ซับซ้อนมาก:</strong> เอกสารนับสิบแบบ ต้องเข้าใจ Incoterms, HS Code, etc.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">💸</span>
                            <span><strong>ค่าใช้จ่ายสูง:</strong> MOQ ของ Freight Forwarder สูง ไม่คุ้มกับสินค้าน้อย</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">🔍</span>
                            <span><strong>ยากที่จะหาลูกค้า:</strong> ไม่มีช่องทางการตลาดในต่างประเทศ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">⚖️</span>
                            <span><strong>ไม่เข้าใจกฎหมาย:</strong> แต่ละประเทศมีกฎระเบียบต่างกัน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">💱</span>
                            <span><strong>ความเสี่ยงอัตราแลกเปลี่ยน:</strong> ไม่รู้จะ Hedge อย่างไร</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-green-50 border-2 border-green-300 rounded-xl p-6">
                    <h4 class="text-green-800 font-bold text-lg mb-4">✅ โซลูชันของเรา</h4>
                    <ul class="space-y-3 text-green-700">
                        <li class="flex items-start gap-2">
                            <span class="text-xl">📦</span>
                            <span><strong>Consolidation Service:</strong> รวบรวมพัสดุหลายร้านค้า ลดต้นทุน Shipping</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">🤖</span>
                            <span><strong>Auto-Documentation:</strong> ระบบสร้างเอกสารศุลกากรอัตโนมัติ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">🌐</span>
                            <span><strong>Global Marketplace:</strong> เชื่อมต่อกับ Lazada, Shopee, Amazon ต่างประเทศ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">📚</span>
                            <span><strong>Knowledge Base:</strong> คู่มือส่งออกแต่ละประเทศ แบบละเอียด</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-xl">💵</span>
                            <span><strong>Multi-Currency:</strong> รับเงิน USD, EUR, SGD แปลงเป็น THB อัตโนมัติ</span>
                        </li>
                    </ul>
                </div>
            </div>

            <h3>🌍 ประเทศเป้าหมาย (Phase 1-3)</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-6">
                <!-- Phase 1: ASEAN -->
                <div class="feature-card bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-300">
                    <h4 class="text-green-800 font-bold text-lg mb-3 flex items-center gap-2">
                        <span class="text-2xl">🇦🇸</span>
                        Phase 1: ASEAN
                    </h4>
                    <p class="text-sm text-gray-700 mb-3">เริ่มต้นจากเพื่อนบ้าน ใกล้ ง่าย ถูก</p>
                    <ul class="text-sm space-y-1">
                        <li>🇸🇬 <strong>สิงคโปร์</strong> - High purchasing power</li>
                        <li>🇲🇾 <strong>มาเลเซีย</strong> - ชุมชนไทยใหญ่</li>
                        <li>🇮🇩 <strong>อินโดนีเซีย</strong> - ตลาดใหญ่ 270M คน</li>
                        <li>🇻🇳 <strong>เวียดนาม</strong> - Middle class เติบโตเร็ว</li>
                        <li>🇵🇭 <strong>ฟิลิปปินส์</strong> - E-Commerce boom</li>
                    </ul>
                    <div class="mt-3 p-2 bg-white rounded text-center">
                        <strong class="text-green-600">Timeline: Q1 2025</strong>
                    </div>
                </div>

                <!-- Phase 2: East Asia -->
                <div class="feature-card bg-gradient-to-br from-blue-50 to-cyan-50 border-2 border-blue-300">
                    <h4 class="text-blue-800 font-bold text-lg mb-3 flex items-center gap-2">
                        <span class="text-2xl">🇯🇵</span>
                        Phase 2: East Asia
                    </h4>
                    <p class="text-sm text-gray-700 mb-3">ตลาดพรีเมี่ยม ราคาดี</p>
                    <ul class="text-sm space-y-1">
                        <li>🇯🇵 <strong>ญี่ปุ่น</strong> - รักสินค้าไทย (อาหาร, สปา)</li>
                        <li>🇰🇷 <strong>เกาหลีใต้</strong> - K-Thai culture exchange</li>
                        <li>🇹🇼 <strong>ไต้หวัน</strong> - ความสนใจสินค้าธรรมชาติ</li>
                        <li>🇭🇰 <strong>ฮ่องกง</strong> - Hub การค้าเอเชีย</li>
                        <li>🇨🇳 <strong>จีน</strong> - Tmall, JD.com integration</li>
                    </ul>
                    <div class="mt-3 p-2 bg-white rounded text-center">
                        <strong class="text-blue-600">Timeline: Q3 2025</strong>
                    </div>
                </div>

                <!-- Phase 3: Global -->
                <div class="feature-card bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-300">
                    <h4 class="text-purple-800 font-bold text-lg mb-3 flex items-center gap-2">
                        <span class="text-2xl">🌎</span>
                        Phase 3: Global
                    </h4>
                    <p class="text-sm text-gray-700 mb-3">ไปให้ไกล สู่ตลาดโลก</p>
                    <ul class="text-sm space-y-1">
                        <li>🇺🇸 <strong>USA</strong> - Amazon FBA integration</li>
                        <li>🇬🇧 <strong>UK</strong> - Post-Brexit opportunities</li>
                        <li>🇦🇺 <strong>ออสเตรเลีย</strong> - Thai community ใหญ่</li>
                        <li>🇦🇪 <strong>UAE</strong> - Hub ตะวันออกกลาง</li>
                        <li>🇩🇪 <strong>เยอรมนี</strong> - EU gateway</li>
                    </ul>
                    <div class="mt-3 p-2 bg-white rounded text-center">
                        <strong class="text-purple-600">Timeline: Q1 2026</strong>
                    </div>
                </div>
            </div>

            <h3>📦 Logistics & Fulfillment</h3>

            <div class="code-block">
                <pre>
[การไหลของสินค้าส่งออก]

Step 1: Seller List Product
        ↓
        • เลือก "Enable International Shipping"
        • กำหนดราคาต่างประเทศ (Auto-convert)
        • เลือกประเทศที่ต้องการขาย

Step 2: International Buyer Orders
        ↓
        • ระบบคำนวณ Shipping Cost อัตโนมัติ
        • รองรับ Multi-Currency Payment

Step 3: Consolidation (รวบรวม)
        ↓
        • Seller จัดส่งไปยัง Warehouse ไทย
        • ระบบ Scan & Check Quality
        • รอรวมพัสดุหลายร้านค้า

Step 4: Export Documentation
        ↓
        • ระบบสร้าง Commercial Invoice
        • สร้าง Packing List
        • สร้าง Certificate of Origin (ถ้าต้องการ)

Step 5: Customs Clearance
        ↓
        • ส่งเอกสารอิเล็กทรอนิกส์ให้ศุลกากร
        • ชำระภาษี (ถ้ามี)
        • ได้รับ Export Permit

Step 6: International Shipping
        ↓
        • ส่งทาง EMS, DHL, FedEx, Sea Freight
        • Tracking Number แบบ Real-time

Step 7: Destination Customs
        ↓
        • Import Clearance ที่ประเทศปลายทาง
        • (บางครั้งต้องมี Import License)

Step 8: Last-Mile Delivery
        ↓
        • จัดส่งถึงมือลูกค้า
        • ระบบ Auto-update Status

Step 9: Payment Settlement
        ↓
        • รับเงินสกุลต่างประเทศ
        • แปลงเป็น THB โดยอัตโนมัติ
        • โอนเข้า Wallet
                </pre>
            </div>

            <h3>💰 โมเดลราคาและค่าบริการ</h3>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>บริการ</th>
                        <th>ค่าใช้จ่าย</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Platform Fee</strong></td>
                        <td>3-5%</td>
                        <td>จากยอดขายต่างประเทศ (รวม Marketplace ทั่วไป 10-15%)</td>
                    </tr>
                    <tr>
                        <td><strong>Consolidation</strong></td>
                        <td>฿50/กล่อง</td>
                        <td>บริการรวมพัสดุ + QC + Repack</td>
                    </tr>
                    <tr>
                        <td><strong>Export Documentation</strong></td>
                        <td>฿200/ครั้ง</td>
                        <td>สร้างเอกสารศุลกากรครบชุด</td>
                    </tr>
                    <tr>
                        <td><strong>Freight Forwarding</strong></td>
                        <td>ราคาต้นทุน + 5%</td>
                        <td>เราไม่ได้กำไรจาก Shipping (ให้ราคาดีที่สุด)</td>
                    </tr>
                    <tr>
                        <td><strong>Currency Exchange</strong></td>
                        <td>1-1.5%</td>
                        <td>แปลงสกุลเงิน (ถูกกว่าธนาคาร 2-3 เท่า)</td>
                    </tr>
                    <tr>
                        <td><strong>Insurance (ถ้าต้องการ)</strong></td>
                        <td>0.5% ของมูลค่าสินค้า</td>
                        <td>คุ้มครองสินค้าสูญหายระหว่างขนส่ง</td>
                    </tr>
                </tbody>
            </table>

            <h3>🛠️ เทคโนโลยีและการเชื่อมต่อ</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="feature-card">
                    <div class="text-4xl mb-3">🔌</div>
                    <h4 class="font-bold text-lg mb-2">Marketplace Integration</h4>
                    <p class="text-gray-700 mb-2">เชื่อมต่อกับ:</p>
                    <ul class="text-sm space-y-1">
                        <li>• <strong>Lazada Regional:</strong> SG, MY, ID, PH, VN</li>
                        <li>• <strong>Shopee International:</strong> 8 ประเทศ</li>
                        <li>• <strong>Amazon FBA:</strong> USA, EU, JP</li>
                        <li>• <strong>Alibaba/Tmall:</strong> จีน</li>
                        <li>• <strong>Qoo10:</strong> SG, JP, KR</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="text-4xl mb-3">🚚</div>
                    <h4 class="font-bold text-lg mb-2">Shipping Partners</h4>
                    <p class="text-gray-700 mb-2">ร่วมมือกับ:</p>
                    <ul class="text-sm space-y-1">
                        <li>• <strong>Thailand Post (EMS):</strong> Express ระหว่างประเทศ</li>
                        <li>• <strong>DHL Express:</strong> 2-3 วัน ทั่วโลก</li>
                        <li>• <strong>FedEx:</strong> B2B มืออาชีพ</li>
                        <li>• <strong>Kerry Express:</strong> ASEAN ราคาดี</li>
                        <li>• <strong>Sea Freight:</strong> สำหรับสินค้ามาก ราคาถูก</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="text-4xl mb-3">💳</div>
                    <h4 class="font-bold text-lg mb-2">Payment Gateway</h4>
                    <p class="text-gray-700 mb-2">รับเงินจาก:</p>
                    <ul class="text-sm space-y-1">
                        <li>• <strong>Stripe:</strong> รองรับ 135+ สกุลเงิน</li>
                        <li>• <strong>PayPal:</strong> ยอดนิยมในต่างประเทศ</li>
                        <li>• <strong>Alipay/WeChat Pay:</strong> สำหรับจีน</li>
                        <li>• <strong>Credit/Debit Card:</strong> Visa, Mastercard, Amex</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="text-4xl mb-3">🤖</div>
                    <h4 class="font-bold text-lg mb-2">AI Translation</h4>
                    <p class="text-gray-700 mb-2">แปลภาษาอัตโนมัติ:</p>
                    <ul class="text-sm space-y-1">
                        <li>• <strong>Product Listing:</strong> TH → EN, CN, JP, KR</li>
                        <li>• <strong>Customer Support:</strong> Chat แบบ Multi-Language</li>
                        <li>• <strong>Documents:</strong> Invoice, Shipping Label แปลอัตโนมัติ</li>
                        <li>• <strong>Quality Check:</strong> Native speaker review (ถ้าต้องการ)</li>
                    </ul>
                </div>
            </div>

            <h3>📊 กรณีศึกษา: SME ส่งออกสำเร็จ</h3>

            <div class="space-y-6">
                <!-- Case 1 -->
                <div class="feature-card bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500">
                    <h4 class="text-xl font-bold text-green-800 mb-3">🌿 Case #1: ร้านสบู่สมุนไพร → สิงคโปร์</h4>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-semibold mb-1">ก่อนใช้ระบบ:</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• ขายในไทยเท่านั้น</li>
                                <li>• รายได้ ฿80,000/เดือน</li>
                                <li>• ไม่รู้จะส่งออกอย่างไร</li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-sm font-semibold mb-1">หลังใช้ระบบ (6 เดือน):</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• ส่งออก SG, MY อย่างสม่ำเสมอ</li>
                                <li>• รายได้ <strong class="text-green-600">฿180,000/เดือน</strong></li>
                                <li>• ได้ Distributor ในสิงคโปร์</li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-sm font-semibold mb-1">Key Success Factors:</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>✅ Consolidation ลดต้นทุน 40%</li>
                                <li>✅ ใช้ AI แปลภาษาอัตโนมัติ</li>
                                <li>✅ เอกสารครบ ผ่านศุลกากรง่าย</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Case 2 -->
                <div class="feature-card bg-gradient-to-r from-blue-50 to-cyan-50 border-l-4 border-blue-500">
                    <h4 class="text-xl font-bold text-blue-800 mb-3">👗 Case #2: เสื้อผ้าแฟชั่น → ญี่ปุ่น</h4>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-semibold mb-1">ความท้าทาย:</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• ญี่ปุ่นเข้มงวดเรื่องคุณภาพ</li>
                                <li>• ต้องการ JAS/JIS Standard</li>
                                <li>• ต้องการภาษาญี่ปุ่นแบบ Native</li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-sm font-semibold mb-1">วิธีแก้:</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• ใช้ QC Service ของเรา</li>
                                <li>• จ้าง Native Japanese แปล</li>
                                <li>• ใช้ Rakuten integration</li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-sm font-semibold mb-1">ผลลัพธ์:</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>✅ ขายได้ <strong class="text-blue-600">¥500,000/เดือน</strong></li>
                                <li>✅ Return Rate < 1%</li>
                                <li>✅ ได้ Repeat Customer มาก</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <h3>🎯 ผลกระทบต่อเศรษฐกิจไทย</h3>

            <div class="info-box success">
                <h4>💚 เงินไหลเข้าประเทศไทย</h4>
                <p>เป้าหมาย 3 ปี:</p>
                <ul class="mt-3 space-y-2">
                    <li><strong>จำนวน SME ส่งออก:</strong> <span class="text-green-600 font-bold">10,000 ราย</span></li>
                    <li><strong>มูลค่าส่งออกรวม:</strong> <span class="text-green-600 font-bold">฿5,000,000,000</span> (5 พันล้านบาท/ปี)</li>
                    <li><strong>สร้างงาน:</strong> <span class="text-green-600 font-bold">2,000 ตำแหน่ง</span> (Warehouse, QC, Documentation)</li>
                    <li><strong>เพิ่ม GDP:</strong> <span class="text-green-600 font-bold">0.01%</span> (จากการส่งออก)</li>
                </ul>
                <p class="mt-4 text-sm font-semibold text-green-800">
                    🌟 สินค้าไทยออกไป เงินต่างประเทศไหลเข้า = เศรษฐกิจไทยเติบโต
                </p>
            </div>

            <div class="mt-8 p-8 bg-gradient-to-r from-green-600 via-blue-600 to-purple-600 text-white rounded-2xl">
                <div class="text-center">
                    <div class="text-6xl mb-4">🌏</div>
                    <h3 class="text-3xl font-bold mb-4">From Thailand to The World</h3>
                    <p class="text-xl mb-4">
                        "เราไม่ได้แค่สร้างแพลตฟอร์ม — เรากำลังสร้าง <strong>ประตูสู่โลก</strong> สำหรับผู้ประกอบการไทย"
                    </p>
                    <p class="text-lg opacity-90">
                        🇹🇭 Made in Thailand 🌏 Sold Worldwide 💰 Profit Back to Thailand
                    </p>
                </div>
            </div>

            <div class="info-box mt-8">
                <h4>📋 Roadmap การพัฒนา</h4>
                <div class="timeline mt-4">
                    <div class="timeline-item">
                        <h4>Q1 2025 - ASEAN Launch</h4>
                        <p>เปิดตัว SG, MY, ID - Pilot กับ 100 Sellers</p>
                    </div>
                    <div class="timeline-item">
                        <h4>Q3 2025 - East Asia Expansion</h4>
                        <p>ขยายไป JP, KR, TW - เป้าหมาย 500 Sellers</p>
                    </div>
                    <div class="timeline-item">
                        <h4>Q1 2026 - Global Rollout</h4>
                        <p>USA, EU, AU - Amazon FBA Integration</p>
                    </div>
                    <div class="timeline-item">
                        <h4>Q4 2026 - Full Automation</h4>
                        <p>AI-powered end-to-end export automation</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Wallet System Section -->
        <section id="wallet" class="wiki-section">
            <h2>💰 Digital Wallet System - ระบบกระเป๋าเงินดิจิทัล</h2>

            <p>
                ระบบ Wallet ของเราคือ <strong>ศูนย์กลางการเงิน</strong> ที่จัดการทุกธุรกรรมทางการเงินในแพลตฟอร์ม
                ออกแบบมาให้ <strong>ปลอดภัย, โปร่งใส, และใช้งานง่าย</strong> เทียบเท่าระบบธนาคาร
            </p>

            <h3>🎯 ทำไมต้องมี Digital Wallet?</h3>

            <div class="info-box">
                <h4>💡 ปัญหาของระบบเงินสดแบบเดิม</h4>
                <ul>
                    <li>❌ ต้องไปธนาคารถอนเงิน (เสียเวลา)</li>
                    <li>❌ ค่าธรรมเนียมโอนเงินสูง</li>
                    <li>❌ ไม่สะดวกในการทำธุรกรรม</li>
                    <li>❌ ไม่มี Transaction History ที่ดี</li>
                    <li>❌ ความปลอดภัยไม่เพียงพอ</li>
                </ul>
            </div>

            <h3>🏗️ สถาปัตยกรรม Wallet System</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="feature-card">
                    <div class="feature-icon">💵</div>
                    <h4>Balance Management</h4>
                    <p><strong>จัดการยอดเงินแบบ Real-time</strong></p>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Main Balance (ยอดหลัก)</li>
                        <li>• Pending Balance (รอโอน)</li>
                        <li>• Locked Balance (ถูกล็อค)</li>
                        <li>• Auto-Credit จาก Commission</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h4>Transaction Logging</h4>
                    <p><strong>บันทึกทุกการเปลี่ยนแปลง</strong></p>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• ประเภทธุรกรรม (Type)</li>
                        <li>• จำนวนเงิน (Amount)</li>
                        <li>• ผู้ทำรายการ (User)</li>
                        <li>• Timestamp & IP Address</li>
                    </ul>
                </div>
            </div>

            <h3>🔐 ระบบรักษาความปลอดภัย</h3>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>Security Layer</th>
                        <th>วิธีการ</th>
                        <th>จุดประสงค์</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>PIN 6 หลัก</strong></td>
                        <td>กำหนด PIN สำหรับทุกธุรกรรม</td>
                        <td>ป้องกันการถูกขโมยบัญชี</td>
                    </tr>
                    <tr>
                        <td><strong>2FA</strong></td>
                        <td>Two-Factor Authentication</td>
                        <td>ยืนยันตัวตนเพิ่ม</td>
                    </tr>
                    <tr>
                        <td><strong>Encryption</strong></td>
                        <td>เข้ารหัส PIN ด้วย bcrypt</td>
                        <td>ป้องกัน Data Breach</td>
                    </tr>
                    <tr>
                        <td><strong>Rate Limiting</strong></td>
                        <td>จำกัดจำนวน Request</td>
                        <td>ป้องกัน Brute Force Attack</td>
                    </tr>
                    <tr>
                        <td><strong>IP Whitelist</strong></td>
                        <td>จำกัด IP ที่เข้าถึงได้</td>
                        <td>ป้องกันการเข้าถึงจาก IP แปลกปลอม</td>
                    </tr>
                </tbody>
            </table>

            <h3>💳 Withdrawal System (ระบบถอนเงิน)</h3>

            <p><strong>Flow การถอนเงิน:</strong></p>

            <div class="code-block">
<pre>
1. User กรอกจำนวนเงินที่ต้องการถอน
   ↓
2. ระบบเช็ค Balance (พอหรือไม่?)
   ↓
3. กรอก PIN 6 หลัก (ยืนยันตัวตน)
   ↓
4. เลือก Bank Account (บัญชีธนาคารที่ลงทะเบียนไว้)
   ↓
5. ระบบสร้าง Withdrawal Request
   ↓
6. Lock Balance (ล็อคยอดเงินที่ขอถอน)
   ↓
7. Admin อนุมัติ (Manual Approval)
   ↓
8. โอนเงินเข้าบัญชีจริง
   ↓
9. Update Status = "Completed"
   ↓
10. ส่ง Email/SMS แจ้งเตือน
</pre>
            </div>

            <div class="info-box warning">
                <h4>⚠️ ข้อจำกัดการถอนเงิน</h4>
                <ul>
                    <li><strong>ขั้นต่ำ:</strong> ฿500/ครั้ง</li>
                    <li><strong>สูงสุด:</strong> ฿100,000/วัน</li>
                    <li><strong>ค่าธรรมเนียม:</strong> ฿20-50 ต่อครั้ง (ขึ้นอยู่กับ Rank)</li>
                    <li><strong>ระยะเวลา:</strong> 1-3 วันทำการ</li>
                </ul>
            </div>

            <h3>📊 Analytics & Reporting</h3>

            <p>ระบบ Wallet มี Dashboard แสดง:</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-6">
                <div class="stat-card">
                    <div class="stat-number">฿0.00</div>
                    <div class="stat-label">Total Balance</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">฿0.00</div>
                    <div class="stat-label">This Month Earning</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">฿0.00</div>
                    <div class="stat-label">Total Withdrawn</div>
                </div>
            </div>
        </section>

        <!-- AI & LINE Bot Section -->
        <section id="ai-integration" class="wiki-section">
            <h2>🤖 AI & LINE Bot Integration - อนาคตของการดูแลลูกค้า</h2>

            <p>
                ระบบ AI ของเราคือ <strong>ผู้ช่วยอัจฉริยะ</strong> ที่ทำงาน 24/7 ไม่เคยหยุดพัก
                ใช้ <strong>Multi-AI Provider</strong> (OpenAI, Claude, Gemini, Groq) พร้อม <strong>RAG System</strong>
                เพื่อให้คำตอบที่แม่นยำและเป็นธรรมชาติ
            </p>

            <h3>🎯 ทำไมต้องใช้ AI?</h3>

            <div class="info-box research">
                <h4>📊 สถิติการใช้ Chatbot ในไทย</h4>
                <ul>
                    <li>• <strong>75%</strong> ของธุรกิจไทยใช้ Chatbot แล้ว</li>
                    <li>• ลดค่าใช้จ่าย Customer Service ได้ถึง <strong>70%</strong></li>
                    <li>• เพิ่มความพึงพอใจลูกค้า <strong>40%</strong></li>
                    <li>• Response Time เร็วขึ้น <strong>90%</strong></li>
                </ul>
                <p class="text-sm mt-2">*ที่มา: Thailand AI Association</p>
            </div>

            <h3>🏗️ สถาปัตยกรรม AI System</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="feature-card">
                    <div class="feature-icon">🧠</div>
                    <h4>Multi-AI Provider</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• <strong>OpenAI GPT-4:</strong> ตอบคำถามทั่วไป</li>
                        <li>• <strong>Claude 3:</strong> วิเคราะห์ซับซ้อน</li>
                        <li>• <strong>Google Gemini:</strong> Search & Fact Check</li>
                        <li>• <strong>Groq:</strong> Fast Response (10x เร็วกว่า)</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-4">
                        <strong>Fallback System:</strong> ถ้า AI ตัวหนึ่งล่ม จะสลับไปใช้ตัวอื่นอัตโนมัติ
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h4>RAG System</h4>
                    <p class="text-sm mt-2">Retrieval-Augmented Generation</p>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• <strong>Knowledge Base:</strong> บทความ, FAQ, ข้อมูลสินค้า</li>
                        <li>• <strong>Vector Database:</strong> จัดเก็บ Embeddings</li>
                        <li>• <strong>Semantic Search:</strong> ค้นหาตามความหมาย</li>
                        <li>• <strong>Context Injection:</strong> ใส่ข้อมูลที่เกี่ยวข้อง</li>
                    </ul>
                </div>
            </div>

            <h3>💬 LINE Official Account Integration</h3>

            <p>เชื่อมต่อกับ <strong>LINE Messaging API</strong> เพื่อสร้างประสบการณ์ที่ดีที่สุด:</p>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>คำอธิบาย</th>
                        <th>Use Case</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Flex Messages</strong></td>
                        <td>ข้อความรูปแบบสวยงาม</td>
                        <td>แสดงสินค้า, โปรโมชั่น</td>
                    </tr>
                    <tr>
                        <td><strong>Rich Menu</strong></td>
                        <td>เมนูด้านล่างแชท</td>
                        <td>ลัด: ดูรายได้, ซื้อของ, Contact Admin</td>
                    </tr>
                    <tr>
                        <td><strong>Quick Reply</strong></td>
                        <td>ปุ่มตอบกลับด่วน</td>
                        <td>เลือกคำตอบที่กำหนดไว้</td>
                    </tr>
                    <tr>
                        <td><strong>Broadcast</strong></td>
                        <td>ส่งข้อความหมู่</td>
                        <td>แจ้งข่าวสาร, โปรโมชั่น</td>
                    </tr>
                    <tr>
                        <td><strong>LINE Login</strong></td>
                        <td>เข้าสู่ระบบด้วย LINE</td>
                        <td>ไม่ต้องจำ Password</td>
                    </tr>
                </tbody>
            </table>

            <h3>🎨 Bot Marketplace - ให้เช่า AI Bot</h3>

            <p>
                <strong>นวัตกรรมพิเศษ:</strong> ผู้ใช้สามารถ <strong>สร้าง AI Bot ของตัวเอง</strong> แล้วนำมา <strong>ให้เช่า</strong>
                เพื่อสร้างรายได้เสริม!
            </p>

            <div class="info-box success">
                <h4>✅ ตัวอย่าง: ให้เช่า LINE Bot</h4>
                <p><strong>สมมติ:</strong> คุณสร้าง LINE Bot ที่ตอบคำถามเกี่ยวกับสินค้า</p>
                <ul class="mt-2">
                    <li>• ตั้งราคาเช่า: <strong>฿299/เดือน</strong></li>
                    <li>• มีคนเช่า 100 คน = <strong>฿29,900/เดือน</strong></li>
                    <li>• Platform รับ 30% = ฿8,970</li>
                    <li>• คุณได้: <strong>฿20,930/เดือน</strong></li>
                </ul>
                <p class="text-sm mt-2 font-bold text-green-700">รายได้แบบ Passive Income!</p>
            </div>
        </section>

        <!-- Security Section -->
        <section id="security" class="wiki-section">
            <h2>🔒 ระบบรักษาความปลอดภัย - Security Architecture</h2>

            <p>
                ความปลอดภัยคือ <strong>หัวใจหลัก</strong> ของแพลตฟอร์ม เราใช้ระบบรักษาความปลอดภัยระดับ <strong>Banking-grade</strong>
                พร้อมการตรวจสอบและป้องกันภัยคุกคามแบบอัตโนมัติ
            </p>

            <h3>🛡️ Multi-Layer Security</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 my-6">
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h4>Layer 1: Authentication</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Password Hashing (bcrypt)</li>
                        <li>• 2FA (Two-Factor Auth)</li>
                        <li>• Session Management</li>
                        <li>• Token Encryption</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🚨</div>
                    <h4>Layer 2: Threat Detection</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• IP Reputation Check</li>
                        <li>• Login Attempt Monitoring</li>
                        <li>• Anomaly Detection</li>
                        <li>• Auto-Ban Suspicious Users</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h4>Layer 3: Monitoring</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Activity Logs</li>
                        <li>• Real-time Alerts</li>
                        <li>• Security Dashboard</li>
                        <li>• Incident Response</li>
                    </ul>
                </div>
            </div>

            <h3>👁️ KYC/OCR System - ยืนยันตัวตนด้วย AI</h3>

            <p>
                ใช้ <strong>Google Vision API</strong> อ่านข้อมูลจากบัตรประชาชนอัตโนมัติ เพื่อป้องกันการปลอมแปลงและฉ้อโกง
            </p>

            <div class="code-block">
<pre>
// KYC Process Flow
1. User อัพโหลดรูปบัตรประชาชน (ด้านหน้า + ด้านหลัง)
   ↓
2. Google Vision API แยกข้อความ (OCR)
   ↓
3. Extract Fields:
   - ID Number (13 หลัก)
   - Full Name
   - Date of Birth
   - Address
   ↓
4. Validation:
   - เช็คว่าเป็นบัตรจริงหรือไม่
   - เช็คอายุ (ต้องเกิน 18 ปี)
   - เช็ค ID ซ้ำในระบบหรือไม่
   ↓
5. Face Verification:
   - ถ่ายรูปตัวเองพร้อมถือบัตร
   - เทียบใบหน้ากับรูปในบัตร
   ↓
6. Manual Review (ถ้าจำเป็น)
   ↓
7. Approve/Reject
</pre>
            </div>

            <div class="info-box warning">
                <h4>⚠️ ทำไมต้องมี KYC?</h4>
                <ul>
                    <li><strong>กฎหมาย:</strong> ตาม พ.ร.บ. การฟอกเงิน ต้องยืนยันตัวตนผู้ใช้</li>
                    <li><strong>ป้องกันฉ้อโกง:</strong> ลดการสร้างบัญชีปลอม</li>
                    <li><strong>เพิ่มความน่าเชื่อถือ:</strong> ผู้ใช้มั่นใจว่าทุกคนเป็นคนจริง</li>
                </ul>
            </div>

            <h3>🚫 IP Blocking & CIDR</h3>

            <p>ระบบบล็อก IP ที่เป็นอันตราย:</p>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>Threat Type</th>
                        <th>Action</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Brute Force</strong></td>
                        <td>Ban IP หลังพยายาม Login ผิด 5 ครั้ง</td>
                        <td>24 ชั่วโมง</td>
                    </tr>
                    <tr>
                        <td><strong>SQL Injection</strong></td>
                        <td>Ban IP ทันที</td>
                        <td>ถาวร</td>
                    </tr>
                    <tr>
                        <td><strong>DDoS</strong></td>
                        <td>Rate Limit + Ban</td>
                        <td>48 ชั่วโมง</td>
                    </tr>
                    <tr>
                        <td><strong>Bot Traffic</strong></td>
                        <td>Captcha + Slow Down</td>
                        <td>จนกว่าจะผ่าน Captcha</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Technology Stack Section -->
        <section id="technology" class="wiki-section">
            <h2>🛠️ สถาปัตยกรรมเทคโนโลยี - Technology Architecture</h2>

            <p>
                แพลตฟอร์มของเราสร้างด้วย <strong>Modern Stack</strong> ที่ได้รับการพิสูจน์แล้วจากบริษัทชั้นนำทั่วโลก
                พร้อมความยืดหยุ่นและ Scalability สูง
            </p>

            <h3>🏗️ Layered Architecture</h3>

            <div class="code-block">
<pre>
┌─────────────────────────────────────┐
│   Presentation Layer (Frontend)    │
│   - Blade Templates                 │
│   - Alpine.js (Reactive)            │
│   - Tailwind CSS (Styling)          │
│   - Vite (Build Tool)               │
└─────────────────────────────────────┘
            ↓ HTTP/AJAX
┌─────────────────────────────────────┐
│    Application Layer (Laravel)     │
│    - Controllers (91 files)         │
│    - Middleware (Auth, CORS, etc)   │
│    - Form Requests (Validation)     │
│    - Resources (API Transform)      │
└─────────────────────────────────────┘
            ↓ Service Calls
┌─────────────────────────────────────┐
│      Business Logic Layer           │
│      - Services (30+ classes)       │
│      - Repositories (Data Access)   │
│      - Events & Listeners           │
│      - Jobs & Queues                │
└─────────────────────────────────────┘
            ↓ Eloquent ORM
┌─────────────────────────────────────┐
│        Data Layer (Database)        │
│        - MySQL 8.0 (105 tables)     │
│        - Redis (Cache & Queue)      │
│        - Eloquent Models (113+)     │
└─────────────────────────────────────┘
            ↓ Integration
┌─────────────────────────────────────┐
│      External Services              │
│      - OpenAI API                   │
│      - Google Cloud (Vision, Trans) │
│      - LINE Messaging API           │
│      - Payment Gateways             │
└─────────────────────────────────────┘
</pre>
            </div>

            <h3>⚡ Performance Optimization</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="feature-card">
                    <div class="feature-icon">🚀</div>
                    <h4>Caching Strategy</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• <strong>Redis:</strong> Cache Database Queries</li>
                        <li>• <strong>CDN:</strong> Static Assets</li>
                        <li>• <strong>OPcache:</strong> PHP Bytecode</li>
                        <li>• <strong>View Cache:</strong> Compiled Blade Templates</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-4">
                        <strong>ผลลัพธ์:</strong> Response Time ลดลง 70%
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h4>Database Optimization</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• <strong>Indexing:</strong> Primary & Foreign Keys</li>
                        <li>• <strong>Eager Loading:</strong> ลด N+1 Problem</li>
                        <li>• <strong>Query Optimization:</strong> Analyze & Optimize</li>
                        <li>• <strong>Partitioning:</strong> แบ่ง Table ขนาดใหญ่</li>
                    </ul>
                </div>
            </div>

            <h3>🔄 CI/CD Pipeline</h3>

            <p><strong>Automated Deployment Process:</strong></p>

            <div class="timeline">
                <div class="timeline-item">
                    <h4 class="font-bold">1. Code Push (Git)</h4>
                    <p>Developer push code ไป GitHub</p>
                </div>
                <div class="timeline-item">
                    <h4 class="font-bold">2. Auto Testing</h4>
                    <p>Run PHPUnit, Feature Tests, Integration Tests</p>
                </div>
                <div class="timeline-item">
                    <h4 class="font-bold">3. Build & Compile</h4>
                    <p>Vite build assets, Composer install dependencies</p>
                </div>
                <div class="timeline-item">
                    <h4 class="font-bold">4. Deploy to Staging</h4>
                    <p>ทดสอบบน Staging Environment</p>
                </div>
                <div class="timeline-item">
                    <h4 class="font-bold">5. Production Deploy</h4>
                    <p>Deploy ไป Production โดยอัตโนมัติ (ถ้าผ่านทุก Test)</p>
                </div>
            </div>
        </section>

        <!-- Database Design Section -->
        <section id="database" class="wiki-section">
            <h2>🗄️ Database Design Philosophy</h2>

            <p>
                Database ของเราออกแบบตาม <strong>Normalization Principles</strong> พร้อม <strong>Strategic Denormalization</strong>
                ในจุดที่ต้องการ Performance
            </p>

            <h3>🎯 Design Principles</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6">
                <div class="feature-card">
                    <div class="feature-icon">✅</div>
                    <h4>1. Normalization (3NF)</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• ไม่มี Duplicate Data</li>
                        <li>• แยก Entity ให้ชัดเจน</li>
                        <li>• ใช้ Foreign Key เชื่อมโยง</li>
                        <li>• ง่ายต่อการ Update</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h4>2. Strategic Denormalization</h4>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Cache ข้อมูลที่ Query บ่อย</li>
                        <li>• Aggregate Tables (Summary)</li>
                        <li>• Materialized Views</li>
                        <li>• Trade-off: Storage vs Speed</li>
                    </ul>
                </div>
            </div>

            <h3>🔗 Eloquent Relationships</h3>

            <p><strong>ตัวอย่างความสัมพันธ์ที่ซับซ้อน:</strong></p>

            <div class="code-block">
<pre>
// User Model
class User extends Model {
    // One-to-One
    public function affiliate() {
        return $this->hasOne(Affiliate::class);
    }

    public function wallet() {
        return $this->hasOne(Wallet::class);
    }

    // One-to-Many
    public function orders() {
        return $this->hasMany(Order::class);
    }

    public function transactions() {
        return $this->hasMany(Transaction::class);
    }

    // Many-to-Many
    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}

// Affiliate Model
class Affiliate extends Model {
    // Self-Referencing (Sponsor/Downline)
    public function sponsor() {
        return $this->belongsTo(Affiliate::class, 'sponsor_id');
    }

    public function children() {
        return $this->hasMany(Affiliate::class, 'sponsor_id');
    }

    // Recursive (All Downlines)
    public function allDownlines() {
        return $this->children()->with('allDownlines');
    }

    // Has-Many-Through
    public function commissions() {
        return $this->hasMany(Commission::class);
    }
}
</pre>
            </div>

            <div class="info-box">
                <h4>💡 Query Optimization Tips</h4>
                <ul>
                    <li><strong>Eager Loading:</strong> <code>User::with('affiliate', 'wallet')->get();</code></li>
                    <li><strong>Lazy Eager Loading:</strong> <code>$users->load('orders');</code></li>
                    <li><strong>Chunking:</strong> <code>User::chunk(1000, function($users) {...});</code></li>
                    <li><strong>Select Specific Columns:</strong> <code>User::select('id', 'name')->get();</code></li>
                </ul>
            </div>
        </section>

        <!-- Business Model Section -->
        <section id="business-model" class="wiki-section">
            <h2>💼 Business Model Analysis</h2>

            <p>
                โมเดลธุรกิจของเราคือ <strong>Platform as a Service (PaaS)</strong> ที่สร้างรายได้จาก <strong>หลายช่องทาง</strong>
                (Multiple Revenue Streams) เพื่อลดความเสี่ยงและเพิ่มความยั่งยืน
            </p>

            <h3>💰 Revenue Model</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 my-6">
                <div class="stat-card">
                    <div class="stat-number">40%</div>
                    <div class="stat-label">E-Commerce Commission</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">35%</div>
                    <div class="stat-label">MLM Platform Fee</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">25%</div>
                    <div class="stat-label">Premium Services</div>
                </div>
            </div>

            <h3>📈 Unit Economics</h3>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>CAC</strong> (Customer Acquisition Cost)</td>
                        <td>฿150</td>
                        <td>ค่าใช้จ่ายในการหาลูกค้า 1 คน</td>
                    </tr>
                    <tr>
                        <td><strong>LTV</strong> (Lifetime Value)</td>
                        <td>฿4,500</td>
                        <td>มูลค่าลูกค้าตลอดชีพ</td>
                    </tr>
                    <tr>
                        <td><strong>LTV/CAC Ratio</strong></td>
                        <td>30:1</td>
                        <td>ดีเยี่ยม (ควรมากกว่า 3:1)</td>
                    </tr>
                    <tr>
                        <td><strong>Payback Period</strong></td>
                        <td>2 เดือน</td>
                        <td>ระยะเวลาคืนทุน</td>
                    </tr>
                    <tr>
                        <td><strong>Churn Rate</strong></td>
                        <td>8%</td>
                        <td>% ลูกค้าที่เลิกใช้ต่อเดือน</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box success">
                <h4>✅ Sustainable Business Model</h4>
                <p>
                    โมเดลธุรกิจของเรา <strong>Profitable</strong> ตั้งแต่เดือนที่ 3 เนื่องจาก:
                </p>
                <ul>
                    <li>• <strong>Low CAC:</strong> ใช้ Referral Marketing (ปากต่อปาก)</li>
                    <li>• <strong>High LTV:</strong> ลูกค้าอยู่กับเรานาน เพราะมีรายได้จาก MLM</li>
                    <li>• <strong>Network Effect:</strong> ยิ่งมีคนเยอะ ยิ่งมีคนสนใจมากขึ้น</li>
                    <li>• <strong>Recurring Revenue:</strong> รายได้ทุกเดือนจาก Active Users</li>
                </ul>
            </div>
        </section>

        <!-- Case Studies Section -->
        <section id="case-studies" class="wiki-section">
            <h2>📚 กรณีศึกษา (Case Studies)</h2>

            <p>ตัวอย่างความสำเร็จของผู้ใช้จริง:</p>

            <div class="feature-card">
                <h3 class="text-2xl font-bold mb-4">🎯 Case Study #1: คุณสมชาย - ครูประจำโรงเรียน</h3>
                <p class="mb-4">
                    <strong>Background:</strong> ครูสอนวิทยาศาสตร์ เงินเดือน ฿25,000/เดือน
                    ต้องการรายได้เสริมเพื่อจ่ายค่าเทอมลูก
                </p>
                <p class="mb-4">
                    <strong>วิธีการ:</strong> ใช้เวลาว่าง 2 ชม./วัน ชวนเพื่อนครู และผู้ปกครอง
                    ขายสินค้าราคาถูก (เครื่องเขียน, หนังสือ) ผ่านระบบ
                </p>
                <p class="mb-4">
                    <strong>ผลลัพธ์:</strong>
                </p>
                <ul class="space-y-2">
                    <li>• <strong>เดือนที่ 1:</strong> ฿3,500 (ขายสินค้า + Commission Level 1)</li>
                    <li>• <strong>เดือนที่ 3:</strong> ฿8,200 (Downline 15 คน)</li>
                    <li>• <strong>เดือนที่ 6:</strong> ฿18,500 (Downline 45 คน, Binary เริ่มมี Pair)</li>
                    <li>• <strong>เดือนที่ 12:</strong> ฿35,000 (มากกว่าเงินเดือนหลัก!)</li>
                </ul>
            </div>

            <div class="feature-card">
                <h3 class="text-2xl font-bold mb-4">🎯 Case Study #2: คุณแดง - แม่บ้านจังหวัดอุดร</h3>
                <p class="mb-4">
                    <strong>Background:</strong> แม่บ้านเลี้ยงลูก 2 คน ไม่มีรายได้ พึ่งสามีคนเดียว
                </p>
                <p class="mb-4">
                    <strong>วิธีการ:</strong> ขายสินค้า OTOP ของจังหวัดผ่านแพลตฟอร์ม + ชวนเพื่อนในกลุ่ม LINE
                </p>
                <p class="mb-4">
                    <strong>ผลลัพธ์:</strong>
                </p>
                <ul class="space-y-2">
                    <li>• <strong>เดือนที่ 1:</strong> ฿1,200 (ขายของให้เพื่อนบ้าน)</li>
                    <li>• <strong>เดือนที่ 6:</strong> ฿12,000 (Downline 25 คน)</li>
                    <li>• <strong>ปัจจุบัน (1 ปี):</strong> ฿42,000/เดือน</li>
                    <li>• <strong>พิเศษ:</strong> สามารถซื้อรถคันแรกได้!</li>
                </ul>
            </div>
        </section>

        <!-- Roadmap Section -->
        <section id="roadmap" class="wiki-section">
            <h2>🗺️ แผนพัฒนาอนาคต (Roadmap)</h2>

            <p>เรามีแผนพัฒนาอย่างต่อเนื่อง เพื่อให้แพลตฟอร์มดีขึ้นเรื่อยๆ</p>

            <div class="timeline">
                <div class="timeline-item">
                    <h4 class="font-bold">Q1 2025 - Mobile App</h4>
                    <p>พัฒนา iOS & Android App ให้ใช้งานสะดวกขึ้น</p>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Push Notification แจ้งเตือนทันที</li>
                        <li>• Offline Mode (บางฟีเจอร์)</li>
                        <li>• Biometric Login (Face ID, Fingerprint)</li>
                    </ul>
                </div>

                <div class="timeline-item">
                    <h4 class="font-bold">Q2 2025 - Blockchain Integration</h4>
                    <p>เพิ่มความโปร่งใสด้วย Blockchain</p>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• บันทึก Transaction บน Blockchain</li>
                        <li>• Smart Contract สำหรับ Commission</li>
                        <li>• Cryptocurrency Payment (BTC, ETH, USDT)</li>
                    </ul>
                </div>

                <div class="timeline-item">
                    <h4 class="font-bold">Q3 2025 - AI Analytics</h4>
                    <p>วิเคราะห์ข้อมูลด้วย AI เพื่อแนะนำกลยุทธ์</p>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Predictive Analytics (ทำนายยอดขาย)</li>
                        <li>• Recommendation Engine (แนะนำสินค้า)</li>
                        <li>• Churn Prediction (ทำนายลูกค้าที่จะหนีไป)</li>
                    </ul>
                </div>

                <div class="timeline-item">
                    <h4 class="font-bold">Q4 2025 - International Expansion</h4>
                    <p>ขยายไปต่างประเทศ</p>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• Multi-Currency Support</li>
                        <li>• International Shipping</li>
                        <li>• English, Chinese, Japanese Interface</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- For Investors Section -->
        <section id="for-investors" class="wiki-section">
            <h2>💎 สำหรับนักลงทุน (For Investors)</h2>

            <p>
                หากคุณสนใจลงทุนในแพลตฟอร์ม นี่คือข้อมูลสำคัญที่คุณควรรู้:
            </p>

            <h3>📊 Market Opportunity</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 my-6">
                <div class="stat-card">
                    <div class="stat-number">฿120B</div>
                    <div class="stat-label">MLM Market Size (Thailand)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">฿650B</div>
                    <div class="stat-label">E-Commerce Market Size</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">25%</div>
                    <div class="stat-label">Annual Growth Rate</div>
                </div>
            </div>

            <h3>💰 Investment Terms</h3>

            <table class="wiki-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Valuation</strong></td>
                        <td>฿50,000,000 (Pre-money)</td>
                    </tr>
                    <tr>
                        <td><strong>Funding Round</strong></td>
                        <td>Seed Round</td>
                    </tr>
                    <tr>
                        <td><strong>Amount Raising</strong></td>
                        <td>฿10,000,000</td>
                    </tr>
                    <tr>
                        <td><strong>Minimum Investment</strong></td>
                        <td>฿1,000,000</td>
                    </tr>
                    <tr>
                        <td><strong>Use of Funds</strong></td>
                        <td>60% Marketing, 30% Development, 10% Operations</td>
                    </tr>
                </tbody>
            </table>

            <h3>🎯 Competitive Advantages</h3>

            <div class="feature-card">
                <h4 class="text-xl font-bold mb-4">ทำไมเราถึงชนะคู่แข่ง?</h4>
                <ol class="space-y-3">
                    <li><strong>1. Technology Stack:</strong> ใช้เทคโนโลยีที่ทันสมัยกว่า (AI, Blockchain Concept)</li>
                    <li><strong>2. User Experience:</strong> UI/UX ที่ดีกว่า ใช้งานง่ายกว่า</li>
                    <li><strong>3. Transparency:</strong> ระบบโปร่งใส ตรวจสอบได้ทุกขั้นตอน</li>
                    <li><strong>4. Low Cost:</strong> ค่าใช้จ่ายต่ำกว่า เพราะใช้ Automation</li>
                    <li><strong>5. Network Effect:</strong> ยิ่งมีคนใช้ ยิ่งมีค่า (เหมือน Facebook)</li>
                </ol>
            </div>

            <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-8 text-center my-8">
                <h3 class="text-3xl font-bold mb-4">📧 Contact for Investment</h3>
                <p class="text-gray-700 mb-6">
                    สนใจลงทุนหรือต้องการข้อมูลเพิ่มเติม?
                </p>
                <a href="{{ route('contact') }}" class="inline-block px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-lg font-bold rounded-full hover:shadow-2xl transition">
                    ติดต่อเรา →
                </a>
            </div>
        </section>

    </main>
</div>

<!-- Floating Quick Links -->
<div class="floating-toc">
    <h4>Quick Jump</h4>
    <ul>
        <li><a href="#introduction">บทนำ</a></li>
        <li><a href="#problems">ปัญหา</a></li>
        <li><a href="#mlm-system">MLM</a></li>
        <li><a href="#ecommerce">E-Commerce</a></li>
    </ul>
</div>

<!-- Back to Top Button -->
<button id="backToTop"
        class="fixed bottom-8 right-8 bg-gradient-to-r from-purple-600 to-pink-600 text-white p-4 rounded-full shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-110 z-50"
        style="display: none;"
        title="กลับไปด้านบน"
        aria-label="Back to top">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
    </svg>
</button>

@push('scripts')
<script>
// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });

            // Update active nav
            document.querySelectorAll('.wiki-nav a').forEach(a => a.classList.remove('active'));
            this.classList.add('active');
        }
    });
});

// Scroll spy
window.addEventListener('scroll', () => {
    const sections = document.querySelectorAll('.wiki-section');
    let current = '';

    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        if (scrollY >= sectionTop - 100) {
            current = section.getAttribute('id');
        }
    });

    document.querySelectorAll('.wiki-nav a').forEach(a => {
        a.classList.remove('active');
        if (a.getAttribute('href') === `#${current}`) {
            a.classList.add('active');
        }
    });
});

// Back to Top Button
const backToTopButton = document.getElementById('backToTop');

// Show/hide button based on scroll position
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        backToTopButton.style.display = 'block';
        setTimeout(() => {
            backToTopButton.style.opacity = '1';
            backToTopButton.style.visibility = 'visible';
        }, 10);
    } else {
        backToTopButton.style.opacity = '0';
        backToTopButton.style.visibility = 'hidden';
        setTimeout(() => {
            if (window.scrollY <= 300) {
                backToTopButton.style.display = 'none';
            }
        }, 300);
    }
});

// Scroll to top on click
backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
</script>
@endpush

@endsection
