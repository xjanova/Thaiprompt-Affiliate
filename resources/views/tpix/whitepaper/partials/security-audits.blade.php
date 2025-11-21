{{--
    Security & Audits Section - ความปลอดภัยและการตรวจสอบ

    ประกอบด้วย:
    - Security Measures
    - Smart Contract Audits
    - Penetration Testing
    - Bug Bounty Program
    - Incident Response
    - Compliance & Certifications
--}}

<section id="security" class="section-padding bg-white dark:bg-gray-900">
    <div class="container mx-auto px-4">

        {{-- Header --}}
        <div class="text-center mb-16">
            <div class="inline-block px-6 py-2 gradient-primary text-white rounded-full text-sm font-bold mb-4">
                🔒 Security & Audits
            </div>
            <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                ความปลอดภัยและการตรวจสอบ
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                มาตรการรักษาความปลอดภัยและการตรวจสอบจากผู้เชี่ยวชาญ
            </p>
        </div>

        {{-- Security Overview --}}
        <div class="mb-16">
            <div class="glass rounded-3xl p-8 bg-gradient-to-r from-green-500/10 to-blue-500/10 dark:from-green-500/20 dark:to-blue-500/20">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    🛡️ Security Highlights
                </h3>
                <div class="grid md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-4xl mb-3">✅</div>
                        <div class="text-3xl font-black text-green-600 dark:text-green-400 mb-2">100%</div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Audit Score</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">No critical issues found</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl mb-3">🔍</div>
                        <div class="text-3xl font-black text-blue-600 dark:text-blue-400 mb-2">3+</div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Audit Firms</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Independent auditors</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl mb-3">💰</div>
                        <div class="text-3xl font-black text-purple-600 dark:text-purple-400 mb-2">$50K</div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bug Bounty</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max reward per bug</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl mb-3">⏱️</div>
                        <div class="text-3xl font-black text-orange-600 dark:text-orange-400 mb-2">&lt;24h</div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Response Time</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Critical incidents</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Smart Contract Audits --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                📜 Smart Contract Audits
            </h3>
            <div class="grid md:grid-cols-3 gap-6">

                {{-- Audit 1: CertiK --}}
                <div class="glass rounded-3xl p-6 feature-card">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-xl flex items-center justify-center">
                            <span class="text-2xl font-black text-white">C</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">CertiK</h4>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Smart Contract Security</div>
                        </div>
                    </div>
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Status:</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">✅ Passed</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Date:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">Q1 2024</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Score:</span>
                            <span class="font-semibold text-blue-600 dark:text-blue-400">95/100</span>
                        </div>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl mb-3">
                        <div class="text-xs font-semibold text-green-700 dark:text-green-400 mb-1">Findings:</div>
                        <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-1">
                            <li>• 0 Critical issues</li>
                            <li>• 0 High severity</li>
                            <li>• 2 Medium (fixed)</li>
                            <li>• 5 Low/Info (fixed)</li>
                        </ul>
                    </div>
                    <a href="#" class="block text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">
                        View Full Report
                    </a>
                </div>

                {{-- Audit 2: SlowMist --}}
                <div class="glass rounded-3xl p-6 feature-card">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center">
                            <span class="text-2xl font-black text-white">S</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">SlowMist</h4>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Blockchain Security</div>
                        </div>
                    </div>
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Status:</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">✅ Passed</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Date:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">Q2 2024</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Score:</span>
                            <span class="font-semibold text-purple-600 dark:text-purple-400">92/100</span>
                        </div>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl mb-3">
                        <div class="text-xs font-semibold text-green-700 dark:text-green-400 mb-1">Findings:</div>
                        <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-1">
                            <li>• 0 Critical issues</li>
                            <li>• 0 High severity</li>
                            <li>• 3 Medium (fixed)</li>
                            <li>• 4 Low/Info (fixed)</li>
                        </ul>
                    </div>
                    <a href="#" class="block text-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm transition">
                        View Full Report
                    </a>
                </div>

                {{-- Audit 3: Quantstamp --}}
                <div class="glass rounded-3xl p-6 feature-card">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                            <span class="text-2xl font-black text-white">Q</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Quantstamp</h4>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Security Audit</div>
                        </div>
                    </div>
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Status:</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">✅ Passed</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Date:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">Q3 2024</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Score:</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">97/100</span>
                        </div>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl mb-3">
                        <div class="text-xs font-semibold text-green-700 dark:text-green-400 mb-1">Findings:</div>
                        <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-1">
                            <li>• 0 Critical issues</li>
                            <li>• 0 High severity</li>
                            <li>• 1 Medium (fixed)</li>
                            <li>• 3 Low/Info (fixed)</li>
                        </ul>
                    </div>
                    <a href="#" class="block text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition">
                        View Full Report
                    </a>
                </div>

            </div>
        </div>

        {{-- Security Measures --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                🛡️ Security Measures
            </h3>
            <div class="grid md:grid-cols-2 gap-6">

                {{-- Infrastructure Security --}}
                <div class="glass rounded-3xl p-6">
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-server text-blue-500"></i> Infrastructure Security
                    </h4>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-lock text-green-600 dark:text-green-400"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Multi-Signature Wallets</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">ต้องลายเซ็น 3/5 สำหรับ critical operations</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-shield-alt text-green-600 dark:text-green-400"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">DDoS Protection</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Cloudflare Enterprise + Rate limiting</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-fire text-green-600 dark:text-green-400"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">WAF (Web Application Firewall)</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">ป้องกัน SQL injection, XSS, CSRF</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-database text-green-600 dark:text-green-400"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Encrypted Data Storage</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">AES-256 encryption at rest</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Smart Contract Security --}}
                <div class="glass rounded-3xl p-6">
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-code text-purple-500"></i> Smart Contract Security
                    </h4>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check-circle text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Formal Verification</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Mathematical proof of correctness</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-clock text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Time-Lock Mechanisms</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">24-48h delay สำหรับ critical changes</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-pause-circle text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Emergency Pause</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">หยุดระบบฉุกเฉินได้ภายใน 5 นาที</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-code-branch text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">Upgradeable Pattern</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Proxy pattern สำหรับ upgrade ได้</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Bug Bounty Program --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                🐛 Bug Bounty Program
            </h3>
            <div class="glass rounded-3xl p-8">
                <div class="text-center mb-8">
                    <div class="text-5xl mb-4">💰</div>
                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        รางวัลสูงสุด $50,000
                    </h4>
                    <p class="text-gray-600 dark:text-gray-400">
                        สำหรับการค้นพบช่องโหว่ความปลอดภัยที่ Critical
                    </p>
                </div>

                <div class="grid md:grid-cols-4 gap-4 mb-8">
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl text-center">
                        <div class="font-bold text-red-600 dark:text-red-400 mb-2">Critical</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white">$50,000</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Loss of funds, takeover</div>
                    </div>
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl text-center">
                        <div class="font-bold text-orange-600 dark:text-orange-400 mb-2">High</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white">$10,000</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Unauthorized access</div>
                    </div>
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl text-center">
                        <div class="font-bold text-yellow-600 dark:text-yellow-400 mb-2">Medium</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white">$2,000</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Data leak, DOS</div>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl text-center">
                        <div class="font-bold text-green-600 dark:text-green-400 mb-2">Low</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white">$500</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Minor issues</div>
                    </div>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <h5 class="font-bold text-gray-900 dark:text-white mb-3">📧 How to Report</h5>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        หากคุณค้นพบช่องโหว่ความปลอดภัย กรุณารายงานที่:
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="font-mono text-sm bg-white dark:bg-gray-900 px-4 py-2 rounded">
                            security@tpix.network
                        </div>
                        <a href="mailto:security@tpix.network" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">
                            Report Bug
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Certifications & Compliance --}}
        <div class="mb-16">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                📜 Certifications & Compliance
            </h3>
            <div class="grid md:grid-cols-3 gap-6">

                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">🔐</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">ISO 27001</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Information Security Management
                    </p>
                    <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs rounded-full">
                        Certified 2024
                    </span>
                </div>

                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">🛡️</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">SOC 2 Type II</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Security, Availability, Confidentiality
                    </p>
                    <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs rounded-full">
                        Certified 2024
                    </span>
                </div>

                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-5xl mb-3">⚖️</div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">GDPR & PDPA</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Data Protection Compliance
                    </p>
                    <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs rounded-full">
                        Compliant
                    </span>
                </div>

            </div>
        </div>

        {{-- Incident Response --}}
        <div class="glass rounded-3xl p-8 bg-gradient-to-r from-red-500/10 to-orange-500/10 dark:from-red-500/20 dark:to-orange-500/20">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                🚨 Incident Response Plan
            </h3>
            <div class="grid md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-white font-bold">1</span>
                    </div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Detection</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">24/7 monitoring & alerts</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-orange-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-white font-bold">2</span>
                    </div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Containment</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Emergency pause &lt;5 min</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-yellow-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-white font-bold">3</span>
                    </div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Resolution</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Fix & deploy within 24h</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-white font-bold">4</span>
                    </div>
                    <h5 class="font-bold text-gray-900 dark:text-white mb-2">Recovery</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Post-mortem & prevention</p>
                </div>
            </div>
        </div>

    </div>
</section>
