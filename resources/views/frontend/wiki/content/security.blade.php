{{-- Hero Section --}}
<div class="bg-gradient-to-br from-primary-500 via-secondary-500 to-accent-500 p-8 md:p-16 rounded-2xl mb-8 shadow-2xl">
    <div class="max-w-4xl mx-auto text-center">
        <div class="inline-block bg-white/20 px-6 py-2 rounded-full mb-6 backdrop-blur-sm">
            <span class="text-white font-semibold text-sm">🔒 Security & Compliance</span>
        </div>
        <h1 class="text-white text-2xl md:text-4xl lg:text-5xl font-extrabold mb-6 drop-shadow-lg">
            ระบบรักษาความปลอดภัย & Compliance
        </h1>
        <p class="text-white/95 text-lg md:text-xl mb-8 leading-relaxed">
            ระบบรักษาความปลอดภัยระดับสูง Authentication, Threat Protection, KYC/AML<br>
            พร้อม Compliance ตาม PDPA, ISO 27001 และมาตรฐานสากล
        </p>
        <div class="flex gap-6 justify-center flex-wrap">
            <div class="bg-white/20 px-6 py-4 rounded-xl backdrop-blur-sm">
                <div class="text-white text-2xl font-bold">256-bit</div>
                <div class="text-white/90 text-sm">AES Encryption</div>
            </div>
            <div class="bg-white/20 px-6 py-4 rounded-xl backdrop-blur-sm">
                <div class="text-white text-2xl font-bold">99.99%</div>
                <div class="text-white/90 text-sm">Uptime SLA</div>
            </div>
            <div class="bg-white/20 px-6 py-4 rounded-xl backdrop-blur-sm">
                <div class="text-white text-2xl font-bold">24/7</div>
                <div class="text-white/90 text-sm">Security Monitoring</div>
            </div>
        </div>
    </div>
</div>

{{-- 1: Authentication & Access Control --}}
<section id="tab1" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-8 flex items-center gap-2">
        <span>🔐</span> Authentication Methods
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">📱</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Two-Factor Authentication</h4>
            <p class="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                เพิ่มความปลอดภัยด้วย 2FA ผ่าน SMS, Email, หรือ Authenticator App
            </p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Google Authenticator</li>
                <li>SMS OTP (6 digits)</li>
                <li>Email OTP</li>
                <li>Backup Codes (10 codes)</li>
                <li>Time-based (TOTP 30s)</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">👆</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Biometric Authentication</h4>
            <p class="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                รองรับการยืนยันตัวตนผ่านลายนิ้วมือและใบหน้า
            </p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Fingerprint (Touch ID)</li>
                <li>Face Recognition (Face ID)</li>
                <li>Iris Scanning</li>
                <li>Voice Recognition</li>
                <li>Liveness Detection</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🔗</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Single Sign-On (SSO)</h4>
            <p class="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                เข้าสู่ระบบครั้งเดียว ใช้งานได้ทุก Platform
            </p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>OAuth 2.0 / OpenID Connect</li>
                <li>SAML 2.0</li>
                <li>Social Login (Google, FB, LINE)</li>
                <li>Corporate LDAP/Active Directory</li>
                <li>JWT Token-based</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🔑</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Password Security</h4>
            <p class="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                นโยบายรหัสผ่านที่เข้มงวด ป้องกันการ Brute Force
            </p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Min 12 characters requirement</li>
                <li>Complexity rules (A-Z, a-z, 0-9, !@#$)</li>
                <li>Password expiry (90 days)</li>
                <li>History (last 5 passwords)</li>
                <li>bcrypt/Argon2 hashing</li>
            </ul>
        </div>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-6">
        👥 Role-Based Access Control (RBAC)
    </h3>

    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Role</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Description</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Users</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Products</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Orders</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Reports</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Settings</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>👑 Super Admin</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Full system access, all permissions</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">CRUD</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">CRUD</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">CRUD</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">All</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">CRUD</strong></td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🔧 Admin</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Manage users, products, orders</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-secondary-600 dark:text-secondary-400">CRUD</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-secondary-600 dark:text-secondary-400">CRUD</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-secondary-600 dark:text-secondary-400">CRUD</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">View</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Limited</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>📊 Manager</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">View reports, manage orders</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">View</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Edit</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Edit</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-accent-600 dark:text-accent-400">All</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">❌</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>👨‍💼 Staff</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Process orders, customer support</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">View</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">View</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Edit</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Limited</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">❌</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>👤 User</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Regular customer, self-service</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Self</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">View</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Own</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Own</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Profile</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-6">
        ⏱️ Session Management
    </h3>

    <div class="bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-900/20 dark:to-secondary-900/20 p-8 rounded-xl border-l-4 border-primary-500 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <div class="text-3xl mb-2">⏰</div>
                <h5 class="font-bold mb-2 text-gray-900 dark:text-white">Auto Timeout</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Session expires after 30 minutes of inactivity, requires re-login for security
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">💻</div>
                <h5 class="font-bold mb-2 text-gray-900 dark:text-white">Device Tracking</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Track all active sessions by device, browser, location. Force logout suspicious devices
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">🔒</div>
                <h5 class="font-bold mb-2 text-gray-900 dark:text-white">Concurrent Limits</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Limit simultaneous logins (max 3 devices), prevent account sharing
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">🔄</div>
                <h5 class="font-bold mb-2 text-gray-900 dark:text-white">Remember Me</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Optional "Remember Me" with secure token (30 days expiry), encrypted cookies
                </p>
            </div>
        </div>
    </div>

    <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-xl border-l-4 border-accent-500">
        <h4 class="font-bold mb-3 text-accent-600 dark:text-accent-400">💡 Authentication Best Practices</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Enforce 2FA:</strong> Require two-factor authentication for admin accounts and high-value transactions (>฿50,000)</li>
            <li><strong>Password Managers:</strong> Encourage users to use password managers (1Password, LastPass) for strong unique passwords</li>
            <li><strong>Failed Login Lockout:</strong> Lock account after 5 failed attempts for 15 minutes, send alert email</li>
            <li><strong>Geolocation Alerts:</strong> Send email/SMS when login from new country or unusual location detected</li>
            <li><strong>Regular Audits:</strong> Review user permissions quarterly, remove inactive accounts after 90 days</li>
        </ul>
    </div>
</section>

{{-- 2: Threat Protection --}}
<section id="tab2" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-8 flex items-center gap-2">
        <span>🛡️</span> Multi-Layer Security Protection
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">
                🔥 Web Application Firewall (WAF)
            </h4>
            <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                ป้องกันการโจมตีที่พบบ่อยตาม OWASP Top 10
            </p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li><strong>SQL Injection:</strong> Parameterized queries, input validation</li>
                <li><strong>XSS (Cross-Site Scripting):</strong> Content sanitization, CSP headers</li>
                <li><strong>CSRF:</strong> Token validation on all forms</li>
                <li><strong>File Upload:</strong> Type checking, virus scanning</li>
                <li><strong>Directory Traversal:</strong> Path normalization</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-secondary-600 dark:text-secondary-400">
                🚫 DDoS Protection
            </h4>
            <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                ป้องกันการโจมตีแบบ Distributed Denial of Service
            </p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li><strong>Layer 3/4:</strong> SYN flood, UDP flood protection</li>
                <li><strong>Layer 7:</strong> HTTP flood mitigation (Cloudflare/AWS Shield)</li>
                <li><strong>Rate Limiting:</strong> 100 req/min per IP</li>
                <li><strong>Bot Detection:</strong> Challenge suspicious traffic</li>
                <li><strong>CDN Integration:</strong> Distribute traffic globally</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-accent-600 dark:text-accent-400">
                📊 Threat Intelligence
            </h4>
            <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                ระบบตรวจจับภัยคุกคามจาก Threat Intelligence Feeds
            </p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li><strong>IP Reputation:</strong> Block known malicious IPs (blacklist 10M+)</li>
                <li><strong>Malware Signatures:</strong> Real-time threat database</li>
                <li><strong>Phishing Detection:</strong> URL analysis, domain reputation</li>
                <li><strong>Zero-Day Protection:</strong> Behavioral analysis</li>
                <li><strong>SIEM Integration:</strong> Splunk, ELK Stack</li>
            </ul>
        </div>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-6">
        🔒 Data Encryption
    </h3>

    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Layer</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Encryption Method</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Key Size</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Use Case</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🌐 Transport (TLS)</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">TLS 1.3 with Perfect Forward Secrecy</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">2048-bit RSA</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">HTTPS connections, API calls</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>💾 Data at Rest</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">AES-256-GCM (Galois/Counter Mode)</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-secondary-600 dark:text-secondary-400">256-bit AES</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Database, file storage, backups</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🔑 Sensitive Data</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Field-level encryption + tokenization</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-accent-600 dark:text-accent-400">256-bit AES</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Passwords, credit cards, SSN</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>☁️ Cloud Storage</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Server-side encryption (S3/Azure)</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">256-bit AES</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Documents, images, videos</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-6">
        🔍 Vulnerability Management
    </h3>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-8 rounded-xl mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-4xl mb-2">🔎</div>
                <h5 class="font-bold mb-2 text-primary-600 dark:text-primary-400">Vulnerability Scanning</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Weekly automated scans using Nessus, OpenVAS to detect security flaws
                </p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-2">⚔️</div>
                <h5 class="font-bold mb-2 text-secondary-600 dark:text-secondary-400">Penetration Testing</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Quarterly pen tests by certified ethical hackers (CEH/OSCP)
                </p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-2">🐛</div>
                <h5 class="font-bold mb-2 text-accent-600 dark:text-accent-400">Bug Bounty Program</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Rewards for responsible disclosure: ฿10K-฿500K based on severity
                </p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-2">🔧</div>
                <h5 class="font-bold mb-2 text-primary-600 dark:text-primary-400">Patch Management</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Critical patches deployed within 24 hours, regular updates monthly
                </p>
            </div>
        </div>
    </div>

    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-6 rounded-xl border-l-4 border-yellow-500 mb-8">
        <h4 class="font-bold mb-4 text-yellow-700 dark:text-yellow-400">⚠️ Security Incident Response Plan</h4>
        <div class="space-y-2 text-yellow-700 dark:text-yellow-300">
            <p><strong>1. Detection (0-15 min):</strong> Automated alerts via SIEM, security team notified immediately</p>
            <p><strong>2. Analysis (15-30 min):</strong> Determine scope, severity, affected systems. Activate incident response team</p>
            <p><strong>3. Containment (30-60 min):</strong> Isolate affected systems, block malicious IPs, preserve evidence</p>
            <p><strong>4. Eradication (1-4 hours):</strong> Remove threats, patch vulnerabilities, scan for persistence</p>
            <p><strong>5. Recovery (4-24 hours):</strong> Restore services, validate integrity, monitor for re-infection</p>
            <p><strong>6. Post-Incident (1-7 days):</strong> Root cause analysis, update defenses, report to stakeholders</p>
        </div>
    </div>

    <div class="bg-gradient-to-br from-accent-50 to-primary-50 dark:from-accent-900/20 dark:to-primary-900/20 p-6 rounded-xl border-l-4 border-accent-500">
        <h4 class="font-bold mb-3 text-accent-600 dark:text-accent-400">💡 Threat Protection Best Practices</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Defense in Depth:</strong> Multiple security layers - if one fails, others still protect (WAF → IDS → Firewall → App)</li>
            <li><strong>Least Privilege:</strong> Grant minimum permissions needed, regularly review and revoke unnecessary access</li>
            <li><strong>Security by Default:</strong> Secure configurations out-of-the-box, disable unnecessary services and ports</li>
            <li><strong>Input Validation:</strong> Never trust user input - validate, sanitize, escape all data (whitelist approach)</li>
            <li><strong>Regular Backups:</strong> 3-2-1 rule: 3 copies, 2 different media, 1 offsite. Test restoration quarterly</li>
        </ul>
    </div>
</section>

{{-- 3: Compliance & Audit --}}
<section id="tab3" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-8 flex items-center gap-2">
        <span>✅</span> Regulatory Compliance
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🇹🇭</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">PDPA (Personal Data Protection Act)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Consent management (Opt-in/Opt-out)</li>
                <li>Right to access personal data</li>
                <li>Right to deletion (forget me)</li>
                <li>Data portability (export to CSV/JSON)</li>
                <li>Privacy policy & Cookie policy</li>
                <li>Data breach notification (72 hours)</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🆔</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">KYC/AML Compliance</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Identity verification (ID card + selfie)</li>
                <li>Liveness detection (blink, smile)</li>
                <li>Document verification (OCR + AI)</li>
                <li>PEP (Politically Exposed Person) screening</li>
                <li>Sanctions list checking (OFAC, UN, EU)</li>
                <li>Transaction monitoring (>฿50K flagged)</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">💳</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">PCI-DSS (Payment Card Industry)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Secure cardholder data (no plain storage)</li>
                <li>Tokenization for credit cards</li>
                <li>Payment gateway integration (2C2P, Omise)</li>
                <li>3D Secure authentication</li>
                <li>Quarterly network scans (ASV)</li>
                <li>Annual on-site assessment (QSA)</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🔒</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">ISO 27001 (Information Security)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Information Security Management System (ISMS)</li>
                <li>Risk assessment & treatment</li>
                <li>Security policies & procedures (100+ docs)</li>
                <li>Asset management</li>
                <li>Incident management</li>
                <li>Annual certification audit</li>
            </ul>
        </div>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-6">
        📝 Audit Logging System
    </h3>

    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Event Type</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">What's Logged</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Retention</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Alert</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🔐 Authentication</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Login, logout, failed attempts, IP, device, geolocation</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">365 days</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">5 failures</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🔑 Access Control</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Permission changes, role assignments, privilege escalation</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">2 years</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Admin role</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>💰 Transactions</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Orders, payments, refunds, amount, status, payment method</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">7 years</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">>฿100K</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>📝 Data Changes</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Create, update, delete records. Old value → New value</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">1 year</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Bulk delete</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>⚙️ System Config</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Settings changes, integrations, API keys, webhooks</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">2 years</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Always</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🚨 Security Events</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Suspicious activity, blocked IPs, malware, attacks</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">5 years</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Always</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-6">
        💾 Backup & Disaster Recovery
    </h3>

    <div class="bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-900/20 dark:to-secondary-900/20 p-8 rounded-xl mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <div class="text-3xl mb-2">⏰</div>
                <h5 class="font-bold mb-2 text-primary-600 dark:text-primary-400">Backup Schedule</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    <strong>Full:</strong> Weekly (Sunday 2 AM)<br>
                    <strong>Incremental:</strong> Daily (1 AM)<br>
                    <strong>Transaction log:</strong> Every 15 minutes<br>
                    <strong>Retention:</strong> 30 days online, 1 year archive
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">🌍</div>
                <h5 class="font-bold mb-2 text-secondary-600 dark:text-secondary-400">Geographic Redundancy</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    <strong>Primary:</strong> Bangkok datacenter<br>
                    <strong>Secondary:</strong> Singapore datacenter<br>
                    <strong>Tertiary:</strong> AWS S3 (multiple regions)<br>
                    <strong>Sync:</strong> Real-time replication (RPO < 1 min)
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">🔄</div>
                <h5 class="font-bold mb-2 text-accent-600 dark:text-accent-400">Recovery Objectives</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    <strong>RTO (Recovery Time):</strong> 4 hours max<br>
                    <strong>RPO (Recovery Point):</strong> 15 minutes max<br>
                    <strong>Full restore:</strong> Tested quarterly<br>
                    <strong>Failover:</strong> Automatic within 5 minutes
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">📋</div>
                <h5 class="font-bold mb-2 text-primary-600 dark:text-primary-400">DR Plan</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    <strong>Documentation:</strong> 100+ page runbook<br>
                    <strong>Team:</strong> 24/7 on-call rotation<br>
                    <strong>Testing:</strong> Twice yearly DR drill<br>
                    <strong>Communication:</strong> Status page, email, SMS
                </p>
            </div>
        </div>
    </div>

    <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-xl border-l-4 border-primary-500">
        <h4 class="font-bold mb-3 text-primary-600 dark:text-primary-400">💡 Compliance & Audit Best Practices</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Principle of Least Privilege:</strong> Only collect necessary data, delete after retention period (PDPA right to be forgotten)</li>
            <li><strong>Immutable Audit Logs:</strong> Write-once logs, cryptographically signed, stored in append-only storage (WORM)</li>
            <li><strong>Regular Audits:</strong> Internal monthly, external annual. Use findings to improve controls and processes</li>
            <li><strong>Employee Training:</strong> Quarterly security awareness training, phishing simulations, compliance certification</li>
            <li><strong>Vendor Management:</strong> Security assessments for all third-party providers, contractual security requirements</li>
            <li><strong>Incident Documentation:</strong> Every security incident documented with timeline, impact, remediation, lessons learned</li>
        </ul>
    </div>
</section>

{{-- 4: Security Monitoring --}}
<section id="tab4" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-8 flex items-center gap-2">
        <span>📊</span> 24/7 Security Operations Center (SOC)
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-l-4 border-primary-500">
            <div class="text-4xl mb-2">👁️</div>
            <h4 class="text-sm text-gray-600 dark:text-gray-400 mb-1">Monitored Events/Day</h4>
            <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">2.4M+</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Logs analyzed in real-time</div>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 p-6 rounded-xl border-l-4 border-secondary-500">
            <div class="text-4xl mb-2">⚡</div>
            <h4 class="text-sm text-gray-600 dark:text-gray-400 mb-1">Mean Detection Time</h4>
            <div class="text-3xl font-extrabold text-secondary-600 dark:text-secondary-400">< 5 min</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">From incident to alert</div>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 p-6 rounded-xl border-l-4 border-accent-500">
            <div class="text-4xl mb-2">🚨</div>
            <h4 class="text-sm text-gray-600 dark:text-gray-400 mb-1">Threats Blocked/Month</h4>
            <div class="text-3xl font-extrabold text-accent-600 dark:text-accent-400">18,500</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Automated prevention</div>
        </div>

        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-l-4 border-primary-500">
            <div class="text-4xl mb-2">⏱️</div>
            <h4 class="text-sm text-gray-600 dark:text-gray-400 mb-1">Mean Response Time</h4>
            <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">< 15 min</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Alert to action</div>
        </div>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-6">
        🎯 Security Alert Categories
    </h3>

    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Severity</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Examples</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Response SLA</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Notification</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong class="text-red-600 dark:text-red-400">🔴 Critical</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Active breach, data exfiltration, ransomware, system compromise</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong>5 minutes</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Phone call + SMS + Email (CISO, CEO)</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong class="text-orange-600 dark:text-orange-400">🟠 High</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Failed login attempts (10+), DDoS attack, malware detected</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong>15 minutes</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Email + SMS (Security Team)</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong class="text-yellow-600 dark:text-yellow-400">🟡 Medium</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Suspicious activity, policy violations, unauthorized access attempts</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong>1 hour</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Email (Security Analysts)</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong class="text-green-600 dark:text-green-400">🟢 Low</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Informational events, compliance checks, routine scans</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong>4 hours</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Dashboard only</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-6">
        🔍 Security Monitoring Tools
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">
                📊 SIEM
            </h4>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">Security Information & Event Management</p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc text-sm">
                <li>Splunk Enterprise Security</li>
                <li>Real-time correlation rules (500+)</li>
                <li>Automated incident creation</li>
                <li>Threat intelligence integration</li>
                <li>Dashboards & visualizations</li>
                <li>90-day hot storage, 2-year archive</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-secondary-600 dark:text-secondary-400">
                🕵️ IDS/IPS
            </h4>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">Intrusion Detection/Prevention</p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc text-sm">
                <li>Snort + Suricata engines</li>
                <li>Network traffic analysis</li>
                <li>Signature-based detection</li>
                <li>Anomaly-based detection</li>
                <li>Auto-block malicious IPs</li>
                <li>Daily rule updates (ET, Snort VRT)</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-accent-600 dark:text-accent-400">
                🔐 UEBA
            </h4>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">User & Entity Behavior Analytics</p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc text-sm">
                <li>Machine learning anomaly detection</li>
                <li>User baseline behavior profiles</li>
                <li>Detect insider threats</li>
                <li>Compromised account detection</li>
                <li>Risk scoring (0-100)</li>
                <li>Peer group analysis</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">
                📈 APM
            </h4>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">Application Performance Monitoring</p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc text-sm">
                <li>New Relic / Datadog</li>
                <li>Request tracing & profiling</li>
                <li>Error tracking & alerting</li>
                <li>Database query performance</li>
                <li>Infrastructure monitoring</li>
                <li>Real user monitoring (RUM)</li>
            </ul>
        </div>
    </div>

    <h3 class="text-primary-600 dark:text-primary-400 text-xl font-bold mb-6">
        🎓 Security Training & Awareness
    </h3>

    <div class="bg-gradient-to-br from-accent-50 to-primary-50 dark:from-accent-900/20 dark:to-primary-900/20 p-8 rounded-xl mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <div class="text-3xl mb-2">📚</div>
                <h5 class="font-bold mb-2 text-primary-600 dark:text-primary-400">Security Onboarding</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    New employee security training (4 hours) covering policies, password management, phishing, data handling, incident reporting
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">🎣</div>
                <h5 class="font-bold mb-2 text-secondary-600 dark:text-secondary-400">Phishing Simulations</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Monthly simulated phishing campaigns. Track click rates (target <5%), provide immediate training for clickers
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">📅</div>
                <h5 class="font-bold mb-2 text-accent-600 dark:text-accent-400">Quarterly Refreshers</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Quarterly security awareness training (1 hour) on latest threats, compliance updates, security tips
                </p>
            </div>
            <div>
                <div class="text-3xl mb-2">🏆</div>
                <h5 class="font-bold mb-2 text-primary-600 dark:text-primary-400">Certification Program</h5>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Annual certification exam (80% to pass). Certificate displayed on profile. Gamification with security champion badges
                </p>
            </div>
        </div>
    </div>

    <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-xl mb-8">
        <h4 class="font-bold mb-6 text-gray-900 dark:text-white">📊 Security Metrics Dashboard</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow text-center">
                <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400 mb-2">99.97%</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">System Uptime (Last 30 days)</div>
            </div>
            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow text-center">
                <div class="text-3xl font-extrabold text-secondary-600 dark:text-secondary-400 mb-2">0</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Data Breaches (All-time)</div>
            </div>
            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow text-center">
                <div class="text-3xl font-extrabold text-accent-600 dark:text-accent-400 mb-2">8.2 min</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Avg Incident Response Time</div>
            </div>
            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow text-center">
                <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400 mb-2">96%</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Employee Security Training Rate</div>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-900/20 dark:to-secondary-900/20 p-6 rounded-xl border-l-4 border-primary-500">
        <h4 class="font-bold mb-3 text-primary-600 dark:text-primary-400">💡 Security Monitoring Best Practices</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Centralized Logging:</strong> All systems log to SIEM - servers, firewalls, apps, databases. No blind spots.</li>
            <li><strong>Real-Time Alerting:</strong> Automated alerts with actionable intelligence, not just raw events. Reduce false positives (target <5%)</li>
            <li><strong>Threat Hunting:</strong> Proactive weekly searches for IOCs (Indicators of Compromise) even without alerts</li>
            <li><strong>Regular Reviews:</strong> Weekly security dashboard reviews, monthly metrics reporting to management</li>
            <li><strong>Continuous Improvement:</strong> Use incident post-mortems to refine detection rules, improve response procedures</li>
            <li><strong>Security Culture:</strong> Security is everyone's responsibility, not just IT. Reward proactive security reporting</li>
        </ul>
    </div>
</section>
