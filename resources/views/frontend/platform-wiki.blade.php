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
            <li><a href="#mlm-system">ระบบ MLM (เชิงลึก)</a>
                <ul class="sub-menu">
                    <li><a href="#unilevel">Unilevel Plan</a></li>
                    <li><a href="#binary">Binary Plan</a></li>
                    <li><a href="#commission-engine">Commission Engine</a></li>
                </ul>
            </li>
            <li><a href="#ecommerce">E-Commerce System</a></li>
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

        <!-- Continue with more sections... -->
        <p class="text-center text-gray-500 my-12">
            🚧 <strong>เนื้อหาส่วนอื่นๆ กำลังดำเนินการเขียน...</strong> 🚧
        </p>

        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-8 text-center">
            <h3 class="text-2xl font-bold mb-4">📖 Platform Wiki กำลังพัฒนา</h3>
            <p class="text-gray-700 mb-4">
                เรากำลังเขียนเนื้อหาเชิงลึกให้ครบทุกหัวข้อ โปรดติดตามใน Version ถัดไป
            </p>
            <a href="{{ route('about.professional') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:shadow-lg transition">
                ดูหน้า About Professional →
            </a>
        </div>

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
</script>
@endpush

@endsection
