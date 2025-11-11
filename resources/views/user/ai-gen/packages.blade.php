@extends('layouts.user')

@section('title', 'แพ็คเกจ AI Generation')

@section('content')
<div class="ai-packages-container" x-data="packagesData()">
    <!-- Hero Section -->
    <div class="packages-hero">
        <div class="hero-content text-center">
            <div class="hero-icon animate-float">
                <svg class="w-20 h-20 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h1 class="hero-title">
                เลือกแพ็คเกจที่ใช่ <span class="gradient-text">สำหรับคุณ</span>
            </h1>
            <p class="hero-subtitle">ปลดปล่อยความคิดสร้างสรรค์ไร้ขีดจำกัด ด้วย AI Generation</p>

            <!-- Billing Toggle -->
            <div class="billing-toggle mt-8">
                <button @click="billingPeriod = 'monthly'"
                        :class="{'active': billingPeriod === 'monthly'}"
                        class="toggle-btn">
                    รายเดือน
                </button>
                <button @click="billingPeriod = 'yearly'"
                        :class="{'active': billingPeriod === 'yearly'}"
                        class="toggle-btn">
                    รายปี
                    <span class="save-badge">ประหยัด 20%</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Packages Grid -->
    <div class="packages-content">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($packages as $index => $package)
                <div class="package-card"
                     :class="{'popular': {{ $package->is_popular ? 'true' : 'false' }}}"
                     data-aos="fade-up"
                     data-aos-delay="{{ $index * 100 }}">

                    @if($package->is_popular)
                    <div class="popular-badge">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        แนะนำ
                    </div>
                    @endif

                    <div class="package-header">
                        <div class="package-icon">
                            @if($package->icon)
                                <i class="{{ $package->icon }} text-4xl"></i>
                            @else
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            @endif
                        </div>
                        <h3 class="package-name">{{ $package->name }}</h3>
                        <p class="package-description">{{ $package->description }}</p>
                    </div>

                    <div class="package-pricing">
                        <div class="price-wrapper">
                            <span class="currency">฿</span>
                            <span class="price" x-text="billingPeriod === 'yearly' ? {{ $package->price_yearly ?? $package->price * 10 }} : {{ $package->price }}"></span>
                            <span class="period" x-text="billingPeriod === 'yearly' ? '/ปี' : '/เดือน'"></span>
                        </div>
                        <template x-if="billingPeriod === 'yearly'">
                            <div class="price-note">
                                <span class="line-through text-gray-500">฿{{ $package->price * 12 }}</span>
                                <span class="text-green-500 ml-2 font-semibold">ประหยัด ฿{{ $package->price * 12 - ($package->price_yearly ?? $package->price * 10) }}</span>
                            </div>
                        </template>
                    </div>

                    <div class="package-features">
                        <div class="feature-item">
                            <svg class="feature-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><strong>{{ number_format($package->image_credits ?? 0) }}</strong> ภาพ/เดือน</span>
                        </div>
                        <div class="feature-item">
                            <svg class="feature-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><strong>{{ number_format($package->video_credits ?? 0) }}</strong> วิดีโอ/เดือน</span>
                        </div>

                        @if($package->features)
                            @foreach(json_decode($package->features, true) as $feature)
                            <div class="feature-item">
                                <svg class="feature-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $feature }}</span>
                            </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="package-action">
                        <button @click="selectPackage({{ $package->id }})"
                                class="btn-select"
                                :class="{'btn-popular': {{ $package->is_popular ? 'true' : 'false' }}}">
                            <span>เลือกแพ็คเกจนี้</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12">
                    <div class="empty-state">
                        <svg class="w-24 h-24 mx-auto mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">ไม่พบแพ็คเกจ</h3>
                        <p class="text-gray-500 dark:text-gray-400">กำลังเตรียมแพ็คเกจสุดพิเศษสำหรับคุณ</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Features Comparison Section -->
    <div class="comparison-section mt-20">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    เปรียบเทียบ<span class="gradient-text">ฟีเจอร์</span>
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400">ดูความแตกต่างของแต่ละแพ็คเกจ</p>
            </div>

            <div class="comparison-table-wrapper">
                <div class="overflow-x-auto">
                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th class="feature-col">ฟีเจอร์</th>
                                @foreach($packages->take(4) as $package)
                                <th class="package-col">{{ $package->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="feature-name">ภาพ AI ต่อเดือน</td>
                                @foreach($packages->take(4) as $package)
                                <td>{{ number_format($package->image_credits ?? 0) }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="feature-name">วิดีโอ AI ต่อเดือน</td>
                                @foreach($packages->take(4) as $package)
                                <td>{{ number_format($package->video_credits ?? 0) }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="feature-name">คุณภาพสูงสุด</td>
                                @foreach($packages->take(4) as $package)
                                <td>
                                    <span class="quality-badge">{{ $package->max_quality ?? '4K' }}</span>
                                </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="feature-name">AI Models</td>
                                @foreach($packages->take(4) as $package)
                                <td>{{ $package->ai_models_count ?? 'All' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="feature-name">Priority Support</td>
                                @foreach($packages->take(4) as $package)
                                <td>
                                    @if($package->priority_support ?? false)
                                        <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-gray-400 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section mt-20 mb-12">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    คำถาม<span class="gradient-text">ที่พบบ่อย</span>
                </h2>
            </div>

            <div class="space-y-4">
                <div class="faq-item" x-data="{ open: false }">
                    <button @click="open = !open" class="faq-question">
                        <span>สามารถเปลี่ยนแพ็คเกจได้ไหม?</span>
                        <svg class="faq-icon" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="faq-answer">
                        ได้เลยครับ สามารถอัพเกรดหรือดาวน์เกรดแพ็คเกจได้ตลอดเวลา เครดิตจะถูกคำนวณตามสัดส่วน
                    </div>
                </div>

                <div class="faq-item" x-data="{ open: false }">
                    <button @click="open = !open" class="faq-question">
                        <span>เครดิตหมดอายุไหม?</span>
                        <svg class="faq-icon" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="faq-answer">
                        เครดิตรายเดือนจะรีเซ็ตทุกเดือน ส่วนเครดิตรายปีจะแบ่งให้ทุกเดือนตลอดระยะเวลา 1 ปี
                    </div>
                </div>

                <div class="faq-item" x-data="{ open: false }">
                    <button @click="open = !open" class="faq-question">
                        <span>ยกเลิกได้ทุกเมื่อไหม?</span>
                        <svg class="faq-icon" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="faq-answer">
                        ยกเลิกได้ทุกเมื่อโดยไม่มีค่าปรับ หากยกเลิกยังสามารถใช้งานได้จนกว่าจะหมดระยะเวลาที่ชำระแล้ว
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
<style>
    .ai-packages-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        padding-bottom: 60px;
    }

    /* Dark Mode */
    .dark .ai-packages-container {
        background: linear-gradient(135deg, #1e1b4b 0%, #4c1d95 50%, #581c87 100%);
    }

    /* Hero Section */
    .packages-hero {
        padding: 80px 20px 60px;
        color: white;
    }

    .hero-icon {
        filter: drop-shadow(0 10px 30px rgba(255, 255, 255, 0.3));
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    .animate-float {
        animation: float 3s ease-in-out infinite;
    }

    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 1rem;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .gradient-text {
        background: linear-gradient(90deg, #ffd89b 0%, #19547b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .dark .gradient-text {
        background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        opacity: 0.95;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    /* Billing Toggle */
    .billing-toggle {
        display: inline-flex;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 50px;
        padding: 4px;
        gap: 4px;
    }

    .dark .billing-toggle {
        background: rgba(0, 0, 0, 0.3);
    }

    .toggle-btn {
        position: relative;
        padding: 12px 32px;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .toggle-btn.active {
        background: white;
        color: #667eea;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .dark .toggle-btn.active {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        color: white;
    }

    .save-badge {
        background: #10b981;
        color: white;
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 700;
    }

    /* Packages Content */
    .packages-content {
        margin-top: -30px;
        position: relative;
        z-index: 1;
    }

    /* Package Card */
    .package-card {
        background: white;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        border: 2px solid transparent;
    }

    .dark .package-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .package-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
    }

    .dark .package-card:hover {
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
    }

    .package-card.popular {
        border: 2px solid #fbbf24;
        transform: scale(1.05);
    }

    .dark .package-card.popular {
        border: 2px solid #f59e0b;
        box-shadow: 0 0 40px rgba(251, 191, 36, 0.3);
    }

    .popular-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
    }

    /* Package Header */
    .package-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .package-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .dark .package-icon {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
    }

    .package-name {
        font-size: 1.75rem;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 10px;
    }

    .dark .package-name {
        color: #f1f5f9;
    }

    .package-description {
        color: #6b7280;
        font-size: 1rem;
    }

    .dark .package-description {
        color: #94a3b8;
    }

    /* Package Pricing */
    .package-pricing {
        text-align: center;
        padding: 30px 0;
        border-top: 2px solid #f3f4f6;
        border-bottom: 2px solid #f3f4f6;
        margin-bottom: 30px;
    }

    .dark .package-pricing {
        border-color: #334155;
    }

    .price-wrapper {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 4px;
    }

    .currency {
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea;
    }

    .dark .currency {
        color: #a78bfa;
    }

    .price {
        font-size: 3.5rem;
        font-weight: 900;
        color: #1f2937;
        line-height: 1;
    }

    .dark .price {
        color: #f1f5f9;
    }

    .period {
        font-size: 1.125rem;
        color: #6b7280;
    }

    .dark .period {
        color: #94a3b8;
    }

    .price-note {
        margin-top: 10px;
        font-size: 0.875rem;
    }

    /* Package Features */
    .package-features {
        space-y: 12px;
        margin-bottom: 30px;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        color: #4b5563;
    }

    .dark .feature-item {
        color: #cbd5e1;
    }

    .feature-icon {
        width: 24px;
        height: 24px;
        color: #10b981;
        flex-shrink: 0;
    }

    .dark .feature-icon {
        color: #34d399;
    }

    /* Package Action */
    .btn-select {
        width: 100%;
        padding: 16px 32px;
        border-radius: 16px;
        font-weight: 700;
        font-size: 1.125rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        cursor: pointer;
    }

    .dark .btn-select {
        background: transparent;
        border-color: #8b5cf6;
        color: #a78bfa;
    }

    .btn-select:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .dark .btn-select:hover {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        border-color: transparent;
        color: white;
        box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
    }

    .btn-popular {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
    }

    .dark .btn-popular {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    /* Comparison Table */
    .comparison-section {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        padding: 60px 20px;
    }

    .dark .comparison-section {
        background: rgba(0, 0, 0, 0.2);
    }

    .comparison-table-wrapper {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    }

    .dark .comparison-table-wrapper {
        background: #1e293b;
    }

    .comparison-table {
        width: 100%;
        border-collapse: collapse;
    }

    .comparison-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .dark .comparison-table thead {
        background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 100%);
    }

    .comparison-table th,
    .comparison-table td {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid #f3f4f6;
    }

    .dark .comparison-table th,
    .dark .comparison-table td {
        border-color: #334155;
    }

    .feature-col {
        text-align: left;
        font-weight: 700;
    }

    .feature-name {
        font-weight: 600;
        color: #1f2937;
    }

    .dark .feature-name {
        color: #f1f5f9;
    }

    .quality-badge {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    /* FAQ Section */
    .faq-section {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        padding: 60px 20px;
    }

    .dark .faq-section {
        background: rgba(0, 0, 0, 0.2);
    }

    .faq-item {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .dark .faq-item {
        background: #1e293b;
    }

    .faq-question {
        width: 100%;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: none;
        border: none;
        font-weight: 600;
        font-size: 1.125rem;
        color: #1f2937;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark .faq-question {
        color: #f1f5f9;
    }

    .faq-question:hover {
        background: #f9fafb;
    }

    .dark .faq-question:hover {
        background: #334155;
    }

    .faq-icon {
        width: 24px;
        height: 24px;
        transition: transform 0.3s ease;
        color: #667eea;
    }

    .dark .faq-icon {
        color: #a78bfa;
    }

    .faq-answer {
        padding: 0 24px 20px;
        color: #6b7280;
        line-height: 1.6;
    }

    .dark .faq-answer {
        color: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2rem;
        }

        .package-card {
            padding: 30px 20px;
        }

        .price {
            font-size: 2.5rem;
        }

        .comparison-table {
            font-size: 0.875rem;
        }

        .comparison-table th,
        .comparison-table td {
            padding: 12px 8px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });

    function packagesData() {
        return {
            billingPeriod: 'monthly',

            selectPackage(packageId) {
                // Show loading
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                btn.disabled = true;

                // Redirect to purchase
                window.location.href = `/user/ai-gen/packages/${packageId}/purchase?period=${this.billingPeriod}`;
            }
        }
    }
</script>
@endpush
