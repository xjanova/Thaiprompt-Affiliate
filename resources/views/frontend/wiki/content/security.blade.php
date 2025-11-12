{{-- Hero Section with RGB Gradient --}}
<div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 4rem 2rem; border-radius: 20px; margin-bottom: 3rem; box-shadow: 0 20px 60px rgba(var(--primary-rgb), 0.3);">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <div style="display: inline-block; background: rgba(255,255,255,0.2); padding: 0.5rem 1.5rem; border-radius: 50px; margin-bottom: 1.5rem; backdrop-filter: blur(10px);">
            <span style="color: white; font-weight: 600; font-size: 0.9rem;">🔒 Security & Compliance</span>
        </div>
        <h1 style="color: white; font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem; text-shadow: 0 2px 20px rgba(0,0,0,0.2);">
            ระบบรักษาความปลอดภัย & Compliance
        </h1>
        <p style="color: rgba(255,255,255,0.95); font-size: 1.3rem; line-height: 1.8; margin-bottom: 2rem;">
            ระบบรักษาความปลอดภัยระดับสูง Authentication, Threat Protection, KYC/AML<br>
            พร้อม Compliance ตาม PDPA, ISO 27001 และมาตรฐานสากล
        </p>
        <div style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">256-bit</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">AES Encryption</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">99.99%</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Uptime SLA</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">24/7</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Security Monitoring</div>
            </div>
        </div>
    </div>
</section>

{{-- 1: Authentication & Access Control --}}
<section id="tab1" class="wiki-section">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>🔐</span> Authentication Methods
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📱</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Two-Factor Authentication</h4>
                <p style="color: var(--wiki-text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                    เพิ่มความปลอดภัยด้วย 2FA ผ่าน SMS, Email, หรือ Authenticator App
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Google Authenticator</li>
                    <li>SMS OTP (6 digits)</li>
                    <li>Email OTP</li>
                    <li>Backup Codes (10 codes)</li>
                    <li>Time-based (TOTP 30s)</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--secondary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">👆</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Biometric Authentication</h4>
                <p style="color: var(--wiki-text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                    รองรับการยืนยันตัวตนผ่านลายนิ้วมือและใบหน้า
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Fingerprint (Touch ID)</li>
                    <li>Face Recognition (Face ID)</li>
                    <li>Iris Scanning</li>
                    <li>Voice Recognition</li>
                    <li>Liveness Detection</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--accent-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔗</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Single Sign-On (SSO)</h4>
                <p style="color: var(--wiki-text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                    เข้าสู่ระบบครั้งเดียว ใช้งานได้ทุก Platform
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>OAuth 2.0 / OpenID Connect</li>
                    <li>SAML 2.0</li>
                    <li>Social Login (Google, FB, LINE)</li>
                    <li>Corporate LDAP/Active Directory</li>
                    <li>JWT Token-based</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔑</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Password Security</h4>
                <p style="color: var(--wiki-text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                    นโยบายรหัสผ่านที่เข้มงวด ป้องกันการ Brute Force
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Min 12 characters requirement</li>
                    <li>Complexity rules (A-Z, a-z, 0-9, !@#$)</li>
                    <li>Password expiry (90 days)</li>
                    <li>History (last 5 passwords)</li>
                    <li>bcrypt/Argon2 hashing</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            👥 Role-Based Access Control (RBAC)
        </h3>

        <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead style="background: var(--wiki-card-bg);">
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Role</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Description</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Users</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Products</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Orders</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Reports</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Settings</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>👑 Super Admin</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Full system access, all permissions</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">CRUD</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">CRUD</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">CRUD</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">All</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">CRUD</strong></td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔧 Admin</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Manage users, products, orders</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">CRUD</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">CRUD</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">CRUD</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">View</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Limited</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📊 Manager</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">View reports, manage orders</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">View</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Edit</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Edit</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">All</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">❌</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>👨‍💼 Staff</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Process orders, customer support</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">View</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">View</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Edit</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Limited</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">❌</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>👤 User</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Regular customer, self-service</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Self</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">View</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Own</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Own</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Profile</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            ⏱️ Session Management
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; border-left: 4px solid rgb(var(--primary-rgb)); margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem;">
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏰</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Auto Timeout</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Session expires after 30 minutes of inactivity, requires re-login for security
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💻</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Device Tracking</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Track all active sessions by device, browser, location. Force logout suspicious devices
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔒</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Concurrent Limits</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Limit simultaneous logins (max 3 devices), prevent account sharing
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔄</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Remember Me</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Optional "Remember Me" with secure token (30 days expiry), encrypted cookies
                    </p>
                </div>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--accent-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">💡 Authentication Best Practices</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Enforce 2FA:</strong> Require two-factor authentication for admin accounts and high-value transactions (>฿50,000)</li>
                <li><strong>Password Managers:</strong> Encourage users to use password managers (1Password, LastPass) for strong unique passwords</li>
                <li><strong>Failed Login Lockout:</strong> Lock account after 5 failed attempts for 15 minutes, send alert email</li>
                <li><strong>Geolocation Alerts:</strong> Send email/SMS when login from new country or unusual location detected</li>
                <li><strong>Regular Audits:</strong> Review user permissions quarterly, remove inactive accounts after 90 days</li>
            </ul>
        </div>
    </section>
</section>

{{-- 2: Threat Protection --}}
<section id="tab2" class="wiki-section">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>🛡️</span> Multi-Layer Security Protection
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">
                    🔥 Web Application Firewall (WAF)
                </h4>
                <p style="color: var(--wiki-text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                    ป้องกันการโจมตีที่พบบ่อยตาม OWASP Top 10
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li><strong>SQL Injection:</strong> Parameterized queries, input validation</li>
                    <li><strong>XSS (Cross-Site Scripting):</strong> Content sanitization, CSP headers</li>
                    <li><strong>CSRF:</strong> Token validation on all forms</li>
                    <li><strong>File Upload:</strong> Type checking, virus scanning</li>
                    <li><strong>Directory Traversal:</strong> Path normalization</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">
                    🚫 DDoS Protection
                </h4>
                <p style="color: var(--wiki-text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                    ป้องกันการโจมตีแบบ Distributed Denial of Service
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li><strong>Layer 3/4:</strong> SYN flood, UDP flood protection</li>
                    <li><strong>Layer 7:</strong> HTTP flood mitigation (Cloudflare/AWS Shield)</li>
                    <li><strong>Rate Limiting:</strong> 100 req/min per IP</li>
                    <li><strong>Bot Detection:</strong> Challenge suspicious traffic</li>
                    <li><strong>CDN Integration:</strong> Distribute traffic globally</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">
                    📊 Threat Intelligence
                </h4>
                <p style="color: var(--wiki-text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                    ระบบตรวจจับภัยคุกคามจาก Threat Intelligence Feeds
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li><strong>IP Reputation:</strong> Block known malicious IPs (blacklist 10M+)</li>
                    <li><strong>Malware Signatures:</strong> Real-time threat database</li>
                    <li><strong>Phishing Detection:</strong> URL analysis, domain reputation</li>
                    <li><strong>Zero-Day Protection:</strong> Behavioral analysis</li>
                    <li><strong>SIEM Integration:</strong> Splunk, ELK Stack</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🔒 Data Encryption
        </h3>

        <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead style="background: var(--wiki-card-bg);">
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Layer</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Encryption Method</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Key Size</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Use Case</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🌐 Transport (TLS)</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">TLS 1.3 with Perfect Forward Secrecy</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">2048-bit RSA</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">HTTPS connections, API calls</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💾 Data at Rest</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">AES-256-GCM (Galois/Counter Mode)</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">256-bit AES</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Database, file storage, backups</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔑 Sensitive Data</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Field-level encryption + tokenization</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">256-bit AES</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Passwords, credit cards, SSN</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>☁️ Cloud Storage</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Server-side encryption (S3/Azure)</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">256-bit AES</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Documents, images, videos</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🔍 Vulnerability Management
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔎</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">Vulnerability Scanning</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Weekly automated scans using Nessus, OpenVAS to detect security flaws
                    </p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚔️</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">Penetration Testing</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Quarterly pen tests by certified ethical hackers (CEH/OSCP)
                    </p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🐛</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">Bug Bounty Program</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Rewards for responsible disclosure: ฿10K-฿500K based on severity
                    </p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔧</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">Patch Management</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Critical patches deployed within 24 hours, regular updates monthly
                    </p>
                </div>
            </div>
        </div>

        <div style="background: #fff3cd; padding: 2rem; border-radius: 12px; border-left: 4px solid #ffc107; margin-bottom: 2rem;">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: #856404;">⚠️ Security Incident Response Plan</h4>
            <div style="line-height: 2; color: #856404;">
                <strong>1. Detection (0-15 min):</strong> Automated alerts via SIEM, security team notified immediately<br>
                <strong>2. Analysis (15-30 min):</strong> Determine scope, severity, affected systems. Activate incident response team<br>
                <strong>3. Containment (30-60 min):</strong> Isolate affected systems, block malicious IPs, preserve evidence<br>
                <strong>4. Eradication (1-4 hours):</strong> Remove threats, patch vulnerabilities, scan for persistence<br>
                <strong>5. Recovery (4-24 hours):</strong> Restore services, validate integrity, monitor for re-infection<br>
                <strong>6. Post-Incident (1-7 days):</strong> Root cause analysis, update defenses, report to stakeholders
            </div>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.1) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--accent-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">💡 Threat Protection Best Practices</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Defense in Depth:</strong> Multiple security layers - if one fails, others still protect (WAF → IDS → Firewall → App)</li>
                <li><strong>Least Privilege:</strong> Grant minimum permissions needed, regularly review and revoke unnecessary access</li>
                <li><strong>Security by Default:</strong> Secure configurations out-of-the-box, disable unnecessary services and ports</li>
                <li><strong>Input Validation:</strong> Never trust user input - validate, sanitize, escape all data (whitelist approach)</li>
                <li><strong>Regular Backups:</strong> 3-2-1 rule: 3 copies, 2 different media, 1 offsite. Test restoration quarterly</li>
            </ul>
        </div>
    </section>
</section>

{{-- 3: Compliance & Audit --}}
<section id="tab3" class="wiki-section">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>✅</span> Regulatory Compliance
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🇹🇭</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">PDPA (Personal Data Protection Act)</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Consent management (Opt-in/Opt-out)</li>
                    <li>Right to access personal data</li>
                    <li>Right to deletion (forget me)</li>
                    <li>Data portability (export to CSV/JSON)</li>
                    <li>Privacy policy & Cookie policy</li>
                    <li>Data breach notification (72 hours)</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--secondary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🆔</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">KYC/AML Compliance</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Identity verification (ID card + selfie)</li>
                    <li>Liveness detection (blink, smile)</li>
                    <li>Document verification (OCR + AI)</li>
                    <li>PEP (Politically Exposed Person) screening</li>
                    <li>Sanctions list checking (OFAC, UN, EU)</li>
                    <li>Transaction monitoring (>฿50K flagged)</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--accent-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">💳</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">PCI-DSS (Payment Card Industry)</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Secure cardholder data (no plain storage)</li>
                    <li>Tokenization for credit cards</li>
                    <li>Payment gateway integration (2C2P, Omise)</li>
                    <li>3D Secure authentication</li>
                    <li>Quarterly network scans (ASV)</li>
                    <li>Annual on-site assessment (QSA)</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔒</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">ISO 27001 (Information Security)</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Information Security Management System (ISMS)</li>
                    <li>Risk assessment & treatment</li>
                    <li>Security policies & procedures (100+ docs)</li>
                    <li>Asset management</li>
                    <li>Incident management</li>
                    <li>Annual certification audit</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            📝 Audit Logging System
        </h3>

        <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead style="background: var(--wiki-card-bg);">
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Event Type</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">What's Logged</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Retention</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Alert</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔐 Authentication</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Login, logout, failed attempts, IP, device, geolocation</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">365 days</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">5 failures</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔑 Access Control</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Permission changes, role assignments, privilege escalation</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">2 years</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Admin role</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💰 Transactions</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Orders, payments, refunds, amount, status, payment method</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">7 years</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">>฿100K</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📝 Data Changes</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Create, update, delete records. Old value → New value</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">1 year</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Bulk delete</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⚙️ System Config</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Settings changes, integrations, API keys, webhooks</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">2 years</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Always</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🚨 Security Events</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Suspicious activity, blocked IPs, malware, attacks</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">5 years</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Always</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            💾 Backup & Disaster Recovery
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem;">
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏰</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">Backup Schedule</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        <strong>Full:</strong> Weekly (Sunday 2 AM)<br>
                        <strong>Incremental:</strong> Daily (1 AM)<br>
                        <strong>Transaction log:</strong> Every 15 minutes<br>
                        <strong>Retention:</strong> 30 days online, 1 year archive
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🌍</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">Geographic Redundancy</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        <strong>Primary:</strong> Bangkok datacenter<br>
                        <strong>Secondary:</strong> Singapore datacenter<br>
                        <strong>Tertiary:</strong> AWS S3 (multiple regions)<br>
                        <strong>Sync:</strong> Real-time replication (RPO < 1 min)
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔄</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">Recovery Objectives</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        <strong>RTO (Recovery Time):</strong> 4 hours max<br>
                        <strong>RPO (Recovery Point):</strong> 15 minutes max<br>
                        <strong>Full restore:</strong> Tested quarterly<br>
                        <strong>Failover:</strong> Automatic within 5 minutes
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📋</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">DR Plan</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        <strong>Documentation:</strong> 100+ page runbook<br>
                        <strong>Team:</strong> 24/7 on-call rotation<br>
                        <strong>Testing:</strong> Twice yearly DR drill<br>
                        <strong>Communication:</strong> Status page, email, SMS
                    </p>
                </div>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--primary-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">💡 Compliance & Audit Best Practices</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Principle of Least Privilege:</strong> Only collect necessary data, delete after retention period (PDPA right to be forgotten)</li>
                <li><strong>Immutable Audit Logs:</strong> Write-once logs, cryptographically signed, stored in append-only storage (WORM)</li>
                <li><strong>Regular Audits:</strong> Internal monthly, external annual. Use findings to improve controls and processes</li>
                <li><strong>Employee Training:</strong> Quarterly security awareness training, phishing simulations, compliance certification</li>
                <li><strong>Vendor Management:</strong> Security assessments for all third-party providers, contractual security requirements</li>
                <li><strong>Incident Documentation:</strong> Every security incident documented with timeline, impact, remediation, lessons learned</li>
            </ul>
        </div>
    </section>
</section>

{{-- 4: Security Monitoring --}}
<section id="tab4" class="wiki-section">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>📊</span> 24/7 Security Operations Center (SOC)
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--primary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">👁️</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Monitored Events/Day</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb));">2.4M+</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Logs analyzed in real-time</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--secondary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚡</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Mean Detection Time</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb));">< 5 min</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">From incident to alert</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--accent-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🚨</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Threats Blocked/Month</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb));">18,500</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Automated prevention</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--primary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⏱️</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Mean Response Time</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb));">< 15 min</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Alert to action</div>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🎯 Security Alert Categories
        </h3>

        <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead style="background: var(--wiki-card-bg);">
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Severity</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Examples</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Response SLA</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Notification</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong style="color: #dc3545;">🔴 Critical</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Active breach, data exfiltration, ransomware, system compromise</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>5 minutes</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Phone call + SMS + Email (CISO, CEO)</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong style="color: #fd7e14;">🟠 High</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Failed login attempts (10+), DDoS attack, malware detected</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>15 minutes</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Email + SMS (Security Team)</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong style="color: #ffc107;">🟡 Medium</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Suspicious activity, policy violations, unauthorized access attempts</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>1 hour</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Email (Security Analysts)</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong style="color: #28a745;">🟢 Low</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Informational events, compliance checks, routine scans</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>4 hours</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Dashboard only</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🔍 Security Monitoring Tools
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">
                    📊 SIEM (Security Information & Event Management)
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Splunk Enterprise Security</li>
                    <li>Real-time correlation rules (500+)</li>
                    <li>Automated incident creation</li>
                    <li>Threat intelligence integration</li>
                    <li>Dashboards & visualizations</li>
                    <li>90-day hot storage, 2-year archive</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">
                    🕵️ IDS/IPS (Intrusion Detection/Prevention)
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Snort + Suricata engines</li>
                    <li>Network traffic analysis</li>
                    <li>Signature-based detection</li>
                    <li>Anomaly-based detection</li>
                    <li>Auto-block malicious IPs</li>
                    <li>Daily rule updates (ET, Snort VRT)</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">
                    🔐 UEBA (User & Entity Behavior Analytics)
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Machine learning anomaly detection</li>
                    <li>User baseline behavior profiles</li>
                    <li>Detect insider threats</li>
                    <li>Compromised account detection</li>
                    <li>Risk scoring (0-100)</li>
                    <li>Peer group analysis</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">
                    📈 APM (Application Performance Monitoring)
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>New Relic / Datadog</li>
                    <li>Request tracing & profiling</li>
                    <li>Error tracking & alerting</li>
                    <li>Database query performance</li>
                    <li>Infrastructure monitoring</li>
                    <li>Real user monitoring (RUM)</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--primary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🎓 Security Training & Awareness
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📚</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">Security Onboarding</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        New employee security training (4 hours) covering policies, password management, phishing, data handling, incident reporting
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎣</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">Phishing Simulations</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Monthly simulated phishing campaigns. Track click rates (target <5%), provide immediate training for clickers
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📅</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">Quarterly Refreshers</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Quarterly security awareness training (1 hour) on latest threats, compliance updates, security tips
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏆</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">Certification Program</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Annual certification exam (80% to pass). Certificate displayed on profile. Gamification with security champion badges
                    </p>
                </div>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
            <h4 style="font-weight: 700; margin-bottom: 1.5rem;">📊 Security Metrics Dashboard</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">99.97%</div>
                    <div style="font-size: 0.9rem; color: var(--wiki-text-secondary);">System Uptime (Last 30 days)</div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">0</div>
                    <div style="font-size: 0.9rem; color: var(--wiki-text-secondary);">Data Breaches (All-time)</div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">8.2 min</div>
                    <div style="font-size: 0.9rem; color: var(--wiki-text-secondary);">Avg Incident Response Time</div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">96%</div>
                    <div style="font-size: 0.9rem; color: var(--wiki-text-secondary);">Employee Security Training Rate</div>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--primary-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">💡 Security Monitoring Best Practices</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Centralized Logging:</strong> All systems log to SIEM - servers, firewalls, apps, databases. No blind spots.</li>
                <li><strong>Real-Time Alerting:</strong> Automated alerts with actionable intelligence, not just raw events. Reduce false positives (target <5%)</li>
                <li><strong>Threat Hunting:</strong> Proactive weekly searches for IOCs (Indicators of Compromise) even without alerts</li>
                <li><strong>Regular Reviews:</strong> Weekly security dashboard reviews, monthly metrics reporting to management</li>
                <li><strong>Continuous Improvement:</strong> Use incident post-mortems to refine detection rules, improve response procedures</li>
                <li><strong>Security Culture:</strong> Security is everyone's responsibility, not just IT. Reward proactive security reporting</li>
            </ul>
        </div>
    </section>
</div>

