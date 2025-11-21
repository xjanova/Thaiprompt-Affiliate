{{--
    Partnerships & Integrations Section - แสดง partners และ integrations ของ TPIX

    ประกอบด้วย:
    - Strategic Partners
    - Technology Partners
    - Exchange Partners
    - Ecosystem Partners
    - Integration Roadmap
--}}

<section id="partnerships" class="section-padding bg-white dark:bg-gray-900">
    <div class="container mx-auto px-4">

        {{-- Header --}}
        <div class="text-center mb-16">
            <div class="inline-block px-6 py-2 gradient-primary text-white rounded-full text-sm font-bold mb-4">
                🤝 Partnerships & Integrations
            </div>
            <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                พันธมิตรและการเชื่อมต่อ
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                TPIX ร่วมมือกับ partners ชั้นนำเพื่อขยายระบบนิเวศและเพิ่มมูลค่าให้กับ ecosystem
            </p>
        </div>

        {{-- Strategic Partners --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                🎯 Strategic Partners
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- Partner 1: Thaiprompt --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-black text-white">TP</span>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">
                        Thaiprompt Affiliate
                    </h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 text-center">
                        แพลตฟอร์ม affiliate หลักที่ใช้ TPIX เป็น native token
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">E-commerce Integration</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">MLM System</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">AI Services Marketplace</span>
                        </div>
                    </div>
                </div>

                {{-- Partner 2: LINE --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fab fa-line text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">
                        LINE Platform
                    </h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 text-center">
                        Integration กับ LINE สำหรับ AI Bots และ Payment
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">LINE Bot Integration</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">LINE Pay (Planned)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Mini App Support</span>
                        </div>
                    </div>
                </div>

                {{-- Partner 3: Google Cloud --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fab fa-google text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">
                        Google Cloud
                    </h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 text-center">
                        AI และ Cloud Infrastructure
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Translation API</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Vision API (OCR)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Cloud Infrastructure</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Technology Stack --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                🔧 Technology Stack
            </h3>
            <div class="glass rounded-3xl p-8">
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">

                    {{-- Blockchain Tech --}}
                    <div class="text-center">
                        <div class="text-4xl mb-3">⛓️</div>
                        <h5 class="font-bold text-gray-900 dark:text-white mb-2">Blockchain</h5>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>Go-Ethereum (Geth)</li>
                            <li>IBFT Consensus</li>
                            <li>EVM Compatible</li>
                            <li>Web3.js / Ethers.js</li>
                        </ul>
                    </div>

                    {{-- Backend Tech --}}
                    <div class="text-center">
                        <div class="text-4xl mb-3">⚙️</div>
                        <h5 class="font-bold text-gray-900 dark:text-white mb-2">Backend</h5>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>Laravel 11</li>
                            <li>PHP 8.1+</li>
                            <li>MySQL 8.0</li>
                            <li>Redis Cache</li>
                        </ul>
                    </div>

                    {{-- Frontend Tech --}}
                    <div class="text-center">
                        <div class="text-4xl mb-3">🎨</div>
                        <h5 class="font-bold text-gray-900 dark:text-white mb-2">Frontend</h5>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>Vite 5.0</li>
                            <li>Tailwind CSS 3.4</li>
                            <li>Alpine.js 3.13</li>
                            <li>Chart.js 4.4</li>
                        </ul>
                    </div>

                    {{-- Integration Tech --}}
                    <div class="text-center">
                        <div class="text-4xl mb-3">🔌</div>
                        <h5 class="font-bold text-gray-900 dark:text-white mb-2">Integration</h5>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>REST API</li>
                            <li>GraphQL</li>
                            <li>WebSocket</li>
                            <li>Webhooks</li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>

        {{-- Exchange Partnerships (Planned) --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                📈 Exchange Listings (Roadmap)
            </h3>
            <div class="grid md:grid-cols-3 gap-6">

                {{-- DEX Listings --}}
                <div class="glass rounded-3xl p-6">
                    <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center">
                        🔄 DEX (Q2 2025)
                    </h5>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-pink-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-exchange-alt text-white"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Uniswap</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Ethereum DEX</div>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-exchange-alt text-white"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">PancakeSwap</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">BSC DEX</div>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-exchange-alt text-white"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">SushiSwap</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Multi-chain DEX</div>
                            </div>
                        </li>
                    </ul>
                </div>

                {{-- Regional CEX --}}
                <div class="glass rounded-3xl p-6">
                    <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center">
                        🏦 Regional CEX (Q3 2025)
                    </h5>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">BX</span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Bitkub</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Thailand Exchange</div>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">ST</span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Satang Pro</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Thailand Exchange</div>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">ZX</span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Zipmex</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">ASEAN Exchange</div>
                            </div>
                        </li>
                    </ul>
                </div>

                {{-- Major CEX --}}
                <div class="glass rounded-3xl p-6">
                    <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center">
                        🌐 Major CEX (Q4 2025)
                    </h5>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">BN</span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Binance</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Target Listing</div>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">CB</span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Coinbase</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Target Listing</div>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">KU</span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">KuCoin</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Target Listing</div>
                            </div>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        {{-- Ecosystem Integrations --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                🌐 Ecosystem Integrations
            </h3>
            <div class="grid md:grid-cols-2 gap-6">

                {{-- DeFi Integrations --}}
                <div class="glass rounded-3xl p-6">
                    <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">💰</span> DeFi Integrations
                    </h5>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Liquidity Pools</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">TPIX/USDT, TPIX/ETH pairs</div>
                            </div>
                            <span class="px-3 py-1 bg-green-500 text-white text-xs rounded-full">Q2 2025</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Yield Farming</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Stake LP tokens for rewards</div>
                            </div>
                            <span class="px-3 py-1 bg-yellow-500 text-white text-xs rounded-full">Q3 2025</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Lending Protocol</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Collateralize TPIX for loans</div>
                            </div>
                            <span class="px-3 py-1 bg-orange-500 text-white text-xs rounded-full">Q4 2025</span>
                        </div>
                    </div>
                </div>

                {{-- Payment Integrations --}}
                <div class="glass rounded-3xl p-6">
                    <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">💳</span> Payment Integrations
                    </h5>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">E-commerce Plugins</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">WooCommerce, Shopify</div>
                            </div>
                            <span class="px-3 py-1 bg-green-500 text-white text-xs rounded-full">Q2 2025</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Payment Gateway</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Accept TPIX on any website</div>
                            </div>
                            <span class="px-3 py-1 bg-green-500 text-white text-xs rounded-full">Q2 2025</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">POS Integration</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Physical retail payment</div>
                            </div>
                            <span class="px-3 py-1 bg-yellow-500 text-white text-xs rounded-full">Q3 2025</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Partnership Benefits --}}
        <div class="glass rounded-3xl p-8 bg-gradient-to-r from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                💎 ประโยชน์จาก Partnerships
            </h3>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-4xl mb-3">🚀</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Ecosystem Growth</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        ขยาย use cases และเพิ่มมูลค่าให้ TPIX ผ่าน partnerships
                    </p>
                </div>
                <div class="text-center">
                    <div class="text-4xl mb-3">💰</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Liquidity</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        เพิ่ม liquidity ผ่าน exchange listings และ DeFi pools
                    </p>
                </div>
                <div class="text-center">
                    <div class="text-4xl mb-3">🌍</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Global Reach</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        เข้าถึงผู้ใช้ทั่วโลกผ่าน partnerships และ integrations
                    </p>
                </div>
            </div>
        </div>

    </div>
</section>
