@extends('layouts.app')

@section('title', 'Platform Wiki - Thaiprompt Affiliate Knowledge Base')

@section('content')
@php
    $primaryColor = \App\Models\Setting::get('primary_color', '#3B82F6');
    $secondaryColor = \App\Models\Setting::get('secondary_color', '#8B5CF6');
    $accentColor = \App\Models\Setting::get('accent_color', '#EC4899');

    // Get Windows UI RGB for effects
    $primaryRgb = $stats['windows_rgb']['primary_rgb'] ?? '59, 130, 246';
    $secondaryRgb = $stats['windows_rgb']['secondary_rgb'] ?? '139, 92, 246';
    $accentRgb = $stats['windows_rgb']['accent_rgb'] ?? '236, 72, 153';
@endphp

@push('styles')
<style>
:root {
    --primary: {{ $primaryColor }};
    --secondary: {{ $secondaryColor }};
    --accent: {{ $accentColor }};

    /* Windows UI RGB colors for effects */
    --primary-rgb: {{ $primaryRgb }};
    --secondary-rgb: {{ $secondaryRgb }};
    --accent-rgb: {{ $accentRgb }};

    /* Light mode colors */
    --wiki-bg: #ffffff;
    --wiki-border: #e5e7eb;
    --wiki-text-primary: #111827;
    --wiki-text-secondary: #374151;
    --wiki-text-muted: #6b7280;
    --wiki-hover-bg: #f3f4f6;
    --wiki-card-bg: #ffffff;
    --wiki-shadow: rgba(0,0,0,0.05);
    --wiki-shadow-hover: rgba(0,0,0,0.1);

    /* Semantic colors - work in both modes */
    --wiki-success: #10b981;
    --wiki-success-dark: #059669;
    --wiki-danger: #ef4444;
    --wiki-danger-dark: #dc2626;
    --wiki-warning: #f59e0b;
    --wiki-warning-dark: #d97706;
    --wiki-info: #3b82f6;
    --wiki-info-dark: #2563eb;
}

.dark {
    --wiki-bg: #1f2937;
    --wiki-border: #374151;
    --wiki-text-primary: #f9fafb;
    --wiki-text-secondary: #e5e7eb;
    --wiki-text-muted: #9ca3af;
    --wiki-hover-bg: #374151;
    --wiki-card-bg: #111827;
    --wiki-shadow: rgba(0,0,0,0.3);
    --wiki-shadow-hover: rgba(0,0,0,0.5);

    /* Semantic colors adjusted for dark mode */
    --wiki-success: #34d399;
    --wiki-success-dark: #10b981;
    --wiki-danger: #f87171;
    --wiki-danger-dark: #ef4444;
    --wiki-warning: #fbbf24;
    --wiki-warning-dark: #f59e0b;
    --wiki-info: #60a5fa;
    --wiki-info-dark: #3b82f6;
}

/* Reading Progress Bar with RGB gradient */
.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 4px;
    background: linear-gradient(90deg,
        rgb(var(--primary-rgb)),
        rgb(var(--secondary-rgb)),
        rgb(var(--accent-rgb)));
    z-index: 9999;
    transition: width 0.1s ease-out;
    box-shadow: 0 2px 10px rgba(var(--primary-rgb), 0.5);
}

/* Wiki Layout */
.wiki-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 2rem;
    max-width: 100%;
    margin: 0;
    padding: 2rem;
    min-height: 100vh;
}

@media (min-width: 1920px) {
    .wiki-layout {
        grid-template-columns: 380px 1fr;
        padding: 2rem 4rem;
    }
}

/* Sidebar with RGB glow effect on hover */
.wiki-sidebar {
    position: sticky;
    top: 2rem;
    height: fit-content;
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
    background: var(--wiki-card-bg);
    border: 2px solid var(--wiki-border);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px var(--wiki-shadow);
    transition: all 0.3s ease;
}

.wiki-sidebar:hover {
    box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.15),
                0 4px 12px rgba(var(--secondary-rgb), 0.1);
}

.wiki-sidebar::-webkit-scrollbar {
    width: 6px;
}

.wiki-sidebar::-webkit-scrollbar-track {
    background: var(--wiki-hover-bg);
    border-radius: 10px;
}

.wiki-sidebar::-webkit-scrollbar-thumb {
    background: rgb(var(--primary-rgb));
    border-radius: 10px;
}

.sidebar-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--wiki-border);
}

.sidebar-title {
    font-size: 1.25rem;
    font-weight: 800;
    background: linear-gradient(135deg,
        rgb(var(--primary-rgb)),
        rgb(var(--secondary-rgb)));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Menu Items with RGB hover effects */
.wiki-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.wiki-menu-item {
    margin-bottom: 0.5rem;
}

.wiki-menu-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--wiki-text-secondary);
    text-decoration: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.wiki-menu-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg,
        rgba(var(--primary-rgb), 0.1),
        rgba(var(--secondary-rgb), 0.1));
    transition: left 0.3s ease;
    z-index: -1;
}

.wiki-menu-link:hover::before {
    left: 0;
}

.wiki-menu-link:hover {
    color: rgb(var(--primary-rgb));
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.2);
}

.wiki-menu-link.active {
    background: linear-gradient(135deg,
        rgb(var(--primary-rgb)),
        rgb(var(--secondary-rgb)));
    color: white;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.4);
}

.menu-icon {
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
}

/* Main Content Area with RGB glow */
.wiki-main-content {
    background: var(--wiki-card-bg);
    border: 2px solid var(--wiki-border);
    border-radius: 16px;
    padding: 3rem;
    min-height: 600px;
    box-shadow: 0 4px 6px var(--wiki-shadow);
    transition: all 0.3s ease;
}

.wiki-main-content:hover {
    box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.1),
                0 4px 12px rgba(var(--secondary-rgb), 0.08);
}

/* Loading Spinner with RGB */
.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
}

.spinner {
    border: 4px solid var(--wiki-border);
    border-top: 4px solid rgb(var(--primary-rgb));
    border-right: 4px solid rgb(var(--secondary-rgb));
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    margin-top: 1rem;
    color: var(--wiki-text-muted);
}

/* Mobile Responsive */
.mobile-menu-toggle {
    display: none;
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg,
        rgb(var(--primary-rgb)),
        rgb(var(--secondary-rgb)));
    color: white;
    border: none;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.4);
    cursor: pointer;
    z-index: 1000;
    font-size: 1.5rem;
    transition: all 0.3s ease;
}

.mobile-menu-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.6);
}

@media (max-width: 1024px) {
    .wiki-layout {
        grid-template-columns: 1fr;
        padding: 1rem;
    }

    .wiki-sidebar {
        position: fixed;
        top: 0;
        left: -100%;
        width: 280px;
        height: 100vh;
        max-height: 100vh;
        z-index: 999;
        border-radius: 0 16px 16px 0;
        transition: left 0.3s ease-in-out;
    }

    .wiki-sidebar.active {
        left: 0;
    }

    .mobile-menu-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 998;
        display: none;
        backdrop-filter: blur(4px);
    }

    .sidebar-overlay.active {
        display: block;
    }

    .wiki-main-content {
        padding: 1.5rem;
    }
}

/* Badges with RGB glow */
.wiki-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    cursor: default;
}

.badge-green {
    background: #d1fae5;
    color: #065f46;
}

.badge-green:hover {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    transform: translateY(-2px);
}

.badge-blue {
    background: #dbeafe;
    color: #1e40af;
}

.badge-blue:hover {
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.4);
    transform: translateY(-2px);
}

.badge-purple {
    background: #e0e7ff;
    color: #5b21b6;
}

.badge-purple:hover {
    box-shadow: 0 4px 12px rgba(var(--secondary-rgb), 0.4);
    transform: translateY(-2px);
}

.dark .badge-green {
    background: #064e3b;
    color: #d1fae5;
}

.dark .badge-blue {
    background: #1e3a8a;
    color: #dbeafe;
}

.dark .badge-purple {
    background: #4c1d95;
    color: #e0e7ff;
}

/* Info Box with icons and RGB effects */
.info-box {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border-left: 4px solid rgb(var(--primary-rgb));
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
    transition: all 0.3s ease;
    position: relative;
}

.info-box:hover {
    box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.2);
    transform: translateY(-2px);
}

.info-box h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.info-box h4::before {
    content: 'ℹ️';
    font-size: 1.5rem;
}

.dark .info-box {
    background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 100%);
}

.info-box.success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-left-color: #10b981;
}

.info-box.success h4::before {
    content: '✅';
}

.dark .info-box.success {
    background: linear-gradient(135deg, #065f46 0%, #047857 100%);
}

.info-box.warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left-color: #f59e0b;
}

.info-box.warning h4::before {
    content: '⚠️';
}

.dark .info-box.warning {
    background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
}

.info-box.tip {
    background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
    border-left-color: #ec4899;
}

.info-box.tip h4::before {
    content: '💡';
}

.dark .info-box.tip {
    background: linear-gradient(135deg, #831843 0%, #9f1239 100%);
}

/* Wiki Tables - Professional Styling */
.wiki-table {
    width: 100%;
    border-collapse: collapse;
    margin: 2rem 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px var(--wiki-shadow);
}

.wiki-table thead {
    background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb)));
    color: white;
}

.wiki-table thead th {
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.wiki-table tbody tr {
    border-bottom: 1px solid var(--wiki-border);
    transition: all 0.2s ease;
}

.wiki-table tbody tr:hover {
    background: var(--wiki-hover-bg);
    transform: scale(1.01);
}

.wiki-table tbody tr:last-child {
    border-bottom: none;
}

.wiki-table tbody td {
    padding: 1rem;
    color: var(--wiki-text-secondary);
}

.wiki-table tbody td strong {
    color: var(--wiki-text-primary);
    font-weight: 600;
}

/* Wiki Cards - Enhanced with Hover */
.wiki-card {
    background: var(--wiki-card-bg);
    border: 2px solid var(--wiki-border);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.wiki-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05), rgba(var(--secondary-rgb), 0.05));
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 0;
}

.wiki-card:hover::before {
    opacity: 1;
}

.wiki-card:hover {
    border-color: rgb(var(--primary-rgb));
    box-shadow: 0 12px 32px rgba(var(--primary-rgb), 0.2);
    transform: translateY(-4px) scale(1.02);
}

.wiki-card > * {
    position: relative;
    z-index: 1;
}

/* Feature Icons */
.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb)));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
    transition: all 0.3s ease;
}

.wiki-card:hover .feature-icon {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.4);
}

/* Step Indicators */
.step-indicator {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb)));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0 auto 0.5rem;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
    transition: all 0.3s ease;
}

.step-indicator:hover {
    transform: scale(1.15);
    box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.5);
}

/* Grid Layouts */
.wiki-grid {
    display: grid;
    gap: 1.5rem;
    margin: 2rem 0;
}

.wiki-grid-2 {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.wiki-grid-3 {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.wiki-grid-4 {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

/* Typography Improvements */
.wiki-section h2 {
    font-size: 1.875rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    color: var(--wiki-text-primary);
    padding-bottom: 0.75rem;
    border-bottom: 3px solid transparent;
    border-image: linear-gradient(90deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))) 1;
}

.wiki-section h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 2rem 0 1rem;
    color: var(--wiki-text-primary);
}

.wiki-section h4 {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--wiki-text-primary);
}

.wiki-section p {
    line-height: 1.8;
    color: var(--wiki-text-secondary);
    margin-bottom: 1rem;
}

.wiki-section ul, .wiki-section ol {
    line-height: 1.8;
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.wiki-section ul li, .wiki-section ol li {
    color: var(--wiki-text-secondary);
    margin-bottom: 0.5rem;
}

/* Code Blocks */
.wiki-code {
    background: var(--wiki-hover-bg);
    border: 1px solid var(--wiki-border);
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1.5rem 0;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    line-height: 1.8;
    overflow-x: auto;
}

/* Highlight Boxes */
.wiki-highlight {
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05));
    border-left: 4px solid rgb(var(--primary-rgb));
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.wiki-highlight h4 {
    color: rgb(var(--primary-rgb));
    margin-bottom: 0.75rem;
}

/* Responsive Improvements */
@media (max-width: 768px) {
    .wiki-grid {
        grid-template-columns: 1fr !important;
    }

    .wiki-section h2 {
        font-size: 1.5rem;
    }

    .wiki-section h3 {
        font-size: 1.25rem;
    }

    .wiki-card {
        padding: 1rem;
    }
}
</style>
@endpush

<div class="reading-progress"></div>

<div class="wiki-layout">
    {{-- Sidebar Navigation --}}
    <aside class="wiki-sidebar" id="wikiSidebar">
        <div class="sidebar-header">
            <h2 class="sidebar-title">📚 Wiki Navigation</h2>
        </div>

        <ul class="wiki-menu">
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link active" data-category="overview">
                    <span class="menu-icon">🚀</span>
                    <span>ภาพรวม & สรุปฟีเจอร์</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="mlm-affiliate">
                    <span class="menu-icon">💎</span>
                    <span>MLM & Affiliate</span>
                </a>
                <ul class="sub-menu" style="list-style: none; padding-left: 2rem; margin-top: 0.5rem; display: none;">
                    <li><a href="#mlm-binary" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🌳 Binary System</a></li>
                    <li><a href="#mlm-unilevel" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🎯 Unilevel System</a></li>
                    <li><a href="#mlm-commission" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">💰 Commission</a></li>
                    <li><a href="#mlm-rank" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">👑 Rank & Bonus</a></li>
                    <li><a href="#mlm-genealogy" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">📊 Genealogy</a></li>
                </ul>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="ai-bot">
                    <span class="menu-icon">🤖</span>
                    <span>AI & Bot System</span>
                </a>
                <ul class="sub-menu" style="list-style: none; padding-left: 2rem; margin-top: 0.5rem; display: none;">
                    <li><a href="#ai-chatbot" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🧠 AI Chatbot</a></li>
                    <li><a href="#ai-creation" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🎨 AI Image/Video</a></li>
                    <li><a href="#line-bot" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">💬 LINE Bot</a></li>
                    <li><a href="#knowledge" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">📚 Knowledge Base</a></li>
                    <li><a href="#rental" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">💼 Bot Rental</a></li>
                </ul>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="ecommerce">
                    <span class="menu-icon">🛍️</span>
                    <span>E-Commerce</span>
                </a>
                <ul class="sub-menu" style="list-style: none; padding-left: 2rem; margin-top: 0.5rem; display: none;">
                    <li><a href="#products" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">📦 Product Management</a></li>
                    <li><a href="#cart" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🛒 Shopping Cart</a></li>
                    <li><a href="#shipping" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🚚 Shipping</a></li>
                    <li><a href="#marketplace" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🏪 Marketplace</a></li>
                </ul>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="crypto">
                    <span class="menu-icon">₿</span>
                    <span>Crypto & Trading</span>
                </a>
                <ul class="sub-menu" style="list-style: none; padding-left: 2rem; margin-top: 0.5rem; display: none;">
                    <li><a href="#wallet" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">💰 Wallet</a></li>
                    <li><a href="#crypto" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">₿ Crypto</a></li>
                    <li><a href="#exchange" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">💱 Exchange</a></li>
                    <li><a href="#investment" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">📈 Investment</a></li>
                    <li><a href="#security" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🔒 Security</a></li>
                </ul>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="payment">
                    <span class="menu-icon">💳</span>
                    <span>Payment & Wallet</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="hrm">
                    <span class="menu-icon">👥</span>
                    <span>HRM System</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="academy">
                    <span class="menu-icon">🎓</span>
                    <span>Academy & Learning</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="pos">
                    <span class="menu-icon">🏪</span>
                    <span>POS System</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="accounting">
                    <span class="menu-icon">📊</span>
                    <span>ระบบบัญชี</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="hotel">
                    <span class="menu-icon">🏨</span>
                    <span>Hotel Booking</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="software">
                    <span class="menu-icon">💻</span>
                    <span>Software Sales</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="security">
                    <span class="menu-icon">🔒</span>
                    <span>Security System</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="vendor">
                    <span class="menu-icon">🏬</span>
                    <span>Vendor System</span>
                </a>
                <ul class="sub-menu" style="list-style: none; padding-left: 2rem; margin-top: 0.5rem; display: none;">
                    <li><a href="#vendor-store" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">🏪 Store Management</a></li>
                    <li><a href="#vendor-commission" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">💰 Commission</a></li>
                    <li><a href="#vendor-analytics" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">📊 Analytics</a></li>
                    <li><a href="#vendor-admin" style="display: block; padding: 0.5rem 0; color: var(--wiki-text-muted); font-size: 0.9rem; text-decoration: none;">⚙️ Admin</a></li>
                </ul>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="support">
                    <span class="menu-icon">🎫</span>
                    <span>Support Ticket</span>
                </a>
            </li>
            <li class="wiki-menu-item">
                <a href="#" class="wiki-menu-link" data-category="technology">
                    <span class="menu-icon">⚙️</span>
                    <span>Technology Stack</span>
                </a>
            </li>
        </ul>
    </aside>

    {{-- Main Content --}}
    <main class="wiki-main-content" id="wikiContent">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p class="loading-text">กำลังโหลดข้อมูล...</p>
        </div>
    </main>
</div>

{{-- Mobile Menu Toggle --}}
<button class="mobile-menu-toggle" id="mobileMenuToggle">
    📚
</button>

{{-- Sidebar Overlay --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>

@push('scripts')
<script>
(function() {
    'use strict';

    // Variables
    const wikiContent = document.getElementById('wikiContent');
    const menuLinks = document.querySelectorAll('.wiki-menu-link');
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('wikiSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    let currentCategory = 'overview';

    // Load wiki content
    async function loadWikiContent(category) {
        // Show loading
        wikiContent.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p class="loading-text">กำลังโหลดข้อมูล...</p>
            </div>
        `;

        try {
            const response = await fetch(`/wiki/content/${category}`);
            const data = await response.json();

            if (data.success) {
                wikiContent.innerHTML = data.content;
                currentCategory = category;

                // Initialize tabs after content loaded
                initializeTabs();

                // Update URL without reload
                history.pushState({category}, '', `/wiki#${category}`);

                // Scroll to top
                window.scrollTo({top: 0, behavior: 'smooth'});

                // Close mobile menu
                closeMobileMenu();
            } else {
                throw new Error(data.message || 'Failed to load content');
            }
        } catch (error) {
            console.error('Error loading wiki content:', error);
            wikiContent.innerHTML = `
                <div class="info-box warning">
                    <h4>⚠️ เกิดข้อผิดพลาด</h4>
                    <p>ไม่สามารถโหลดเนื้อหาได้ กรุณาลองใหม่อีกครั้ง</p>
                    <button onclick="location.reload()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))); color: white; border: none; border-radius: 8px; cursor: pointer;">รีโหลดหน้า</button>
                </div>
            `;
        }
    }

    // Expose loadWikiContent to global scope for use in content files (e.g., overview.blade.php)
    window.loadWikiContent = loadWikiContent;

    // Initialize tabs functionality
    function initializeTabs() {
        const tabBtns = wikiContent.querySelectorAll('.tab-btn');
        const tabContents = wikiContent.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Scroll to top when switching tabs
                window.scrollTo({top: 0, behavior: 'smooth'});

                const tabName = this.getAttribute('data-tab');

                // Remove active class and hide all tabs
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => {
                    c.classList.remove('active');
                    c.style.display = 'none';
                });

                // Add active class and show target tab
                this.classList.add('active');
                const targetContent = wikiContent.querySelector(`[data-tab-content="${tabName}"]`);
                if (targetContent) {
                    targetContent.classList.add('active');
                    targetContent.style.display = 'block';
                }
            });
        });
    }

    // Menu click handler
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const category = this.getAttribute('data-category');
            const submenu = this.nextElementSibling;

            // Update active state
            menuLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            // Toggle submenu if exists
            if (submenu && submenu.classList.contains('sub-menu')) {
                // Hide all other submenus
                document.querySelectorAll('.sub-menu').forEach(sm => {
                    if (sm !== submenu) sm.style.display = 'none';
                });

                // Toggle current submenu
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }

            // Load content
            loadWikiContent(category);
        });
    });

    // Submenu link click handler - handle anchor links
    document.querySelectorAll('.sub-menu a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');

            // If it's an anchor link (starts with #)
            if (href && href.startsWith('#')) {
                const targetId = href.substring(1);
                // Wait a bit for content to load if needed
                setTimeout(() => {
                    const target = document.getElementById(targetId);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });

                        // Update URL hash
                        if (history.pushState) {
                            history.pushState(null, null, href);
                        }

                        // Close mobile menu
                        closeMobileMenu();
                    }
                }, 300);
            }
        });
    });

    // Mobile menu toggle
    function toggleMobileMenu() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    function closeMobileMenu() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', toggleMobileMenu);
    }

    if (overlay) {
        overlay.addEventListener('click', closeMobileMenu);
    }

    // Reading progress
    function updateReadingProgress() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight - windowHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const progress = (scrollTop / documentHeight) * 100;

        const progressBar = document.querySelector('.reading-progress');
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
    }

    window.addEventListener('scroll', updateReadingProgress);

    // Handle browser back/forward
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.category) {
            loadWikiContent(event.state.category);

            // Update menu active state
            menuLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('data-category') === event.state.category) {
                    link.classList.add('active');
                }
            });
        }
    });

    // Load initial content from URL hash or default
    function loadInitialContent() {
        const hash = window.location.hash.replace('#', '');
        const category = hash || 'overview';

        // Find and activate the menu item
        menuLinks.forEach(link => {
            if (link.getAttribute('data-category') === category) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        loadWikiContent(category);
    }

    // Initialize
    loadInitialContent();

})();
</script>
@endpush

@endsection
