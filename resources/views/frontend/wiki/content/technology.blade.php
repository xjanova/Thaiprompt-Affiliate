<div class="wiki-content-section">
    {{-- Hero Section with RGB Gradient --}}
    <div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 3rem 2rem; border-radius: 20px; margin-bottom: 3rem; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
        <h1 style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">⚙️ Technology Architecture</h1>
        <p style="color: rgba(255,255,255,0.95); font-size: 1.25rem; font-weight: 500; margin: 0;">สถาปัตยกรรมและเทคโนโลยีที่ทันสมัยและมีประสิทธิภาพสูง</p>
    </div>

    <section class="wiki-section">
                {{-- Tab 1: Technology Stack --}}
        <section id="tech-stack" class="wiki-section">
            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; border-left: 4px solid rgb(var(--primary-rgb));">
                <h3 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">🚀 Modern Technology Stack</h3>
                <p style="line-height: 1.8; margin: 0;">ใช้เทคโนโลยีชั้นนำและ Best Practices ในการพัฒนา ทำให้ระบบมีประสิทธิภาพสูง ปลอดภัย และขยายตัวได้ง่าย</p>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🔧 Core Technology Stack</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--primary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🐘</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text-primary);">Backend Framework</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li><strong style="color: rgb(var(--primary-rgb));">Laravel 11.x</strong> - Latest version</li>
                        <li>PHP 8.3+ - Modern features</li>
                        <li>Eloquent ORM - Database abstraction</li>
                        <li>Queue Jobs - Async processing</li>
                        <li>RESTful API - Standard architecture</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--secondary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🎨</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text-primary);">Frontend Technologies</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li><strong style="color: rgb(var(--secondary-rgb));">Blade Templates</strong> - Server-side rendering</li>
                        <li>TailwindCSS - Utility-first CSS</li>
                        <li>Alpine.js - Lightweight reactivity</li>
                        <li>JavaScript ES6+ - Modern JS</li>
                        <li>Livewire - Dynamic components</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--accent-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🗄️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text-primary);">Database & Cache</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li><strong style="color: rgb(var(--accent-rgb));">MySQL 8.0+</strong> - Primary database</li>
                        <li>MariaDB - Alternative option</li>
                        <li>Redis - Cache & sessions</li>
                        <li>Database Migrations - Version control</li>
                        <li>{{ $stats['database_tables'] ?? 127 }} Tables - Comprehensive schema</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--primary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🚢</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text-primary);">DevOps & Tools</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li><strong style="color: rgb(var(--primary-rgb));">Git</strong> - Version control</li>
                        <li>Docker - Containerization</li>
                        <li>GitHub Actions - CI/CD pipeline</li>
                        <li>Composer - PHP dependencies</li>
                        <li>NPM/Yarn - JS dependencies</li>
                    </ul>
                </div>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">📊 System Statistics & Metrics</h2>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: var(--wiki-card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <thead style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Component</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Count</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Description</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📦 Database Models</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">{{ $stats['database_models'] ?? 89 }}</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Eloquent models for data access</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Active</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🎮 HTTP Controllers</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">{{ $stats['http_controllers'] ?? 142 }}</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Request handlers and routing</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Active</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⚙️ Service Classes</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">{{ $stats['services_count'] ?? 67 }}</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Business logic layer</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Active</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🗃️ Database Tables</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">{{ $stats['database_tables'] ?? 127 }}</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Database schema tables</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Active</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔄 API Endpoints</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">380+</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">RESTful API routes</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Active</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📋 Blade Views</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">250+</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Frontend templates</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Active</span></td>
                    </tr>
                </tbody>
            </table>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🔌 Third-Party Integrations</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">💳</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Payment Gateways</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">Stripe, PayPal, 2C2P, OmiseGO</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📧</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Email Services</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">SendGrid, Mailgun, AWS SES</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">☁️</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Cloud Storage</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">AWS S3, Google Cloud, DigitalOcean</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📊</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Analytics</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">Google Analytics, Mixpanel, Hotjar</p>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--secondary-rgb));">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Technology Best Practices</h4>
                <ul style="line-height: 2; margin: 0;">
                    <li><strong>Security First:</strong> HTTPS only, CSRF protection, SQL injection prevention, XSS filtering</li>
                    <li><strong>Performance:</strong> Redis caching, Database indexing, Lazy loading, CDN integration</li>
                    <li><strong>Scalability:</strong> Horizontal scaling ready, Load balancer compatible, Queue workers</li>
                    <li><strong>Maintainability:</strong> PSR-12 coding standards, PHPDoc documentation, Automated testing</li>
                    <li><strong>Modern PHP:</strong> Type hints, Null coalescing, Arrow functions, Named arguments</li>
                </ul>
            </div>
        </section>

{{-- 2: Architecture Patterns --}}
        <section id="architecture" class="wiki-section">
            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.05) 100%); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; border-left: 4px solid rgb(var(--secondary-rgb));">
                <h3 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">🏗️ Architecture & Design Patterns</h3>
                <p style="line-height: 1.8; margin: 0;">สถาปัตยกรรมที่ออกแบบมาอย่างดี ใช้ Design Patterns ที่เหมาะสม ทำให้โค้ดอ่านง่าย แก้ไขง่าย และขยายตัวได้ดี</p>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🎯 Core Architecture Patterns</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--primary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">🔷 MVC Pattern</h4>
                    <p style="line-height: 1.8; margin-bottom: 1rem; color: var(--wiki-text-secondary);">Model-View-Controller for separation of concerns</p>
                    <ul style="list-style: none; padding: 0; line-height: 2; font-size: 0.9rem;">
                        <li>✅ Models: Data & business logic</li>
                        <li>✅ Views: Presentation layer</li>
                        <li>✅ Controllers: Request handling</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--secondary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">⚙️ Service Layer</h4>
                    <p style="line-height: 1.8; margin-bottom: 1rem; color: var(--wiki-text-secondary);">Business logic separated from controllers</p>
                    <ul style="list-style: none; padding: 0; line-height: 2; font-size: 0.9rem;">
                        <li>✅ Reusable business logic</li>
                        <li>✅ Testable services</li>
                        <li>✅ Dependency injection</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--accent-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">🗄️ Repository Pattern</h4>
                    <p style="line-height: 1.8; margin-bottom: 1rem; color: var(--wiki-text-secondary);">Data access abstraction layer</p>
                    <ul style="list-style: none; padding: 0; line-height: 2; font-size: 0.9rem;">
                        <li>✅ Database abstraction</li>
                        <li>✅ Easy to swap data sources</li>
                        <li>✅ Clean query methods</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--primary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">👀 Observer Pattern</h4>
                    <p style="line-height: 1.8; margin-bottom: 1rem; color: var(--wiki-text-secondary);">Event-driven architecture</p>
                    <ul style="list-style: none; padding: 0; line-height: 2; font-size: 0.9rem;">
                        <li>✅ Loose coupling</li>
                        <li>✅ Event listeners</li>
                        <li>✅ Async notifications</li>
                    </ul>
                </div>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">📐 Application Layers</h2>

            <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #0d6efd;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #0d6efd; font-size: 1.1rem;">🌐 Presentation Layer</strong>
                            <span style="background: #0d6efd; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Frontend</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                            Blade Views, Livewire Components, JavaScript, TailwindCSS - Handles user interface and interactions
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #6f42c1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #6f42c1; font-size: 1.1rem;">🎮 Controller Layer</strong>
                            <span style="background: #6f42c1; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Request Handling</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                            HTTP Controllers, API Controllers, Form Requests - Receives requests, validates input, returns responses
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #fd7e14;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #fd7e14; font-size: 1.1rem;">⚙️ Service Layer</strong>
                            <span style="background: #fd7e14; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Business Logic</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                            Service Classes, Action Classes, DTOs - Contains reusable business logic, orchestrates operations
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #20c997;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #20c997; font-size: 1.1rem;">🗄️ Data Access Layer</strong>
                            <span style="background: #20c997; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Repositories</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                            Repositories, Eloquent Models, Query Builders - Abstracts database operations and queries
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #dc3545;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #dc3545; font-size: 1.1rem;">💾 Database Layer</strong>
                            <span style="background: #dc3545; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Persistence</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                            MySQL/MariaDB, Redis, Migrations, Seeders - Stores and retrieves data persistently
                        </p>
                    </div>
                </div>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🔄 Request Lifecycle</h2>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: var(--wiki-card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <thead style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%);">
                    <tr>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Step</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Component</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Action</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Avg Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>1</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🌐 HTTP Request</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">User sends request to server</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">~5ms</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>2</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔐 Middleware</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Authentication, CSRF, Rate limiting</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">~10ms</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>3</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🛣️ Router</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Match route, dispatch to controller</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">~3ms</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>4</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🎮 Controller</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Validate input, call service layer</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">~8ms</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>5</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⚙️ Service</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Execute business logic, call repository</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">~15ms</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>6</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🗄️ Repository</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Query database, return data</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">~20ms</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>7</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📋 View</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Render Blade template with data</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">~12ms</td>
                    </tr>
                    <tr style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05) 0%, rgba(var(--secondary-rgb), 0.02) 100%);">
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>8</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>✅ Response</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Send HTML/JSON to user</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">~73ms total</strong></td>
                    </tr>
                </tbody>
            </table>

            <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--accent-rgb));">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">🎯 Architecture Benefits</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div>
                        <strong style="color: rgb(var(--primary-rgb));">✅ Maintainability</strong>
                        <p style="margin: 0.5rem 0 0 0; line-height: 1.6;">Clean separation makes code easy to understand and modify</p>
                    </div>
                    <div>
                        <strong style="color: rgb(var(--secondary-rgb));">✅ Testability</strong>
                        <p style="margin: 0.5rem 0 0 0; line-height: 1.6;">Each layer can be tested independently with mocks</p>
                    </div>
                    <div>
                        <strong style="color: rgb(var(--accent-rgb));">✅ Scalability</strong>
                        <p style="margin: 0.5rem 0 0 0; line-height: 1.6;">Services and repositories can scale horizontally</p>
                    </div>
                    <div>
                        <strong style="color: rgb(var(--primary-rgb));">✅ Reusability</strong>
                        <p style="margin: 0.5rem 0 0 0; line-height: 1.6;">Services can be reused across controllers and APIs</p>
                    </div>
                </div>
            </div>
        </section>

{{-- 3: Infrastructure & Deployment --}}
        <section id="infrastructure" class="wiki-section">
            <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; border-left: 4px solid rgb(var(--accent-rgb));">
                <h3 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">☁️ Infrastructure & Deployment</h3>
                <p style="line-height: 1.8; margin: 0;">โครงสร้างพื้นฐานที่แข็งแกร่ง รองรับการ Deploy อัตโนมัติ และสามารถขยายตัวได้ตามความต้องการ</p>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🖥️ Server Requirements</h2>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: var(--wiki-card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <thead style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Component</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Minimum</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Recommended</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Production</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💻 CPU</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">2 cores</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">4 cores</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">8+ cores</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🧠 RAM</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">4 GB</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">8 GB</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">16+ GB</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💾 Storage</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">20 GB SSD</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">50 GB SSD</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">100+ GB SSD</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🌐 Bandwidth</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">100 Mbps</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">500 Mbps</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">1+ Gbps</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🐘 PHP Version</strong></td>
                        <td colspan="3" style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">PHP 8.3+ (Required)</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🗄️ Database</strong></td>
                        <td colspan="3" style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">MySQL 8.0+ / MariaDB 10.5+</strong></td>
                    </tr>
                </tbody>
            </table>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">☁️ Cloud Hosting Options</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--primary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🚀</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text-primary);">Laravel Forge</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li>✅ Optimized for Laravel</li>
                        <li>✅ Auto deployment</li>
                        <li>✅ SSL certificates</li>
                        <li>✅ Server monitoring</li>
                    </ul>
                    <p style="margin-top: 1rem; color: var(--wiki-text-secondary); font-size: 0.9rem;"><strong>Cost:</strong> $19/mo + server</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--secondary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🌊</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text-primary);">DigitalOcean</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li>✅ App Platform</li>
                        <li>✅ Droplets (VPS)</li>
                        <li>✅ Managed databases</li>
                        <li>✅ Load balancers</li>
                    </ul>
                    <p style="margin-top: 1rem; color: var(--wiki-text-secondary); font-size: 0.9rem;"><strong>Cost:</strong> From $6/mo</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--accent-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">☁️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text-primary);">AWS (Amazon)</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li>✅ EC2 instances</li>
                        <li>✅ RDS databases</li>
                        <li>✅ S3 storage</li>
                        <li>✅ CloudFront CDN</li>
                    </ul>
                    <p style="margin-top: 1rem; color: var(--wiki-text-secondary); font-size: 0.9rem;"><strong>Cost:</strong> Pay-as-you-go</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 25px rgba(var(--primary-rgb), 0.15)'; this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🔵</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text-primary);">Linode / Akamai</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li>✅ Compute instances</li>
                        <li>✅ Object storage</li>
                        <li>✅ NodeBalancers</li>
                        <li>✅ Kubernetes</li>
                    </ul>
                    <p style="margin-top: 1rem; color: var(--wiki-text-secondary); font-size: 0.9rem;"><strong>Cost:</strong> From $5/mo</p>
                </div>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">⚖️ Load Balancing & Scaling</h2>

            <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1.5rem;">🔄 Horizontal Scaling Architecture</h4>

                <div style="display: grid; gap: 1.5rem;">
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #0d6efd;">
                        <strong style="color: #0d6efd; font-size: 1.1rem;">⚖️ Load Balancer (Nginx/HAProxy)</strong>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                            Distributes incoming traffic across multiple application servers using round-robin or least-connections algorithm
                        </p>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="background: white; padding: 1rem; border-radius: 10px; border-left: 4px solid #28a745;">
                            <strong style="color: #28a745;">🖥️ App Server 1</strong>
                            <p style="margin: 0.5rem 0 0 0; color: #6c757d; font-size: 0.9rem;">Primary instance</p>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 10px; border-left: 4px solid #28a745;">
                            <strong style="color: #28a745;">🖥️ App Server 2</strong>
                            <p style="margin: 0.5rem 0 0 0; color: #6c757d; font-size: 0.9rem;">Replica instance</p>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 10px; border-left: 4px solid #28a745;">
                            <strong style="color: #28a745;">🖥️ App Server N</strong>
                            <p style="margin: 0.5rem 0 0 0; color: #6c757d; font-size: 0.9rem;">Auto-scaled</p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #dc3545;">
                            <strong style="color: #dc3545; font-size: 1.1rem;">🗄️ Database Master (Write)</strong>
                            <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                                Primary database for writes, replicates to read replicas
                            </p>
                        </div>
                        <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #ffc107;">
                            <strong style="color: #ffc107; font-size: 1.1rem;">🗄️ Database Slaves (Read)</strong>
                            <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                                Read replicas for queries, reduces master load
                            </p>
                        </div>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #6f42c1;">
                        <strong style="color: #6f42c1; font-size: 1.1rem;">⚡ Redis Cluster (Cache & Sessions)</strong>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d; line-height: 1.8;">
                            Shared cache layer, session storage, job queue - all servers connect to same Redis cluster
                        </p>
                    </div>
                </div>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🔄 Backup & Disaster Recovery</h2>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: var(--wiki-card-bg); border-radius: 12px; overflow: hidden;">
                <thead style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Backup Type</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Frequency</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Retention</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Storage Location</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💾 Full Database Backup</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Daily</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">30 days</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">AWS S3 / Google Cloud</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⚡ Incremental Backup</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Every 6 hours</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">7 days</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">AWS S3 / Google Cloud</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📂 File Storage Backup</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Daily</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">90 days</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">S3 Glacier (Archive)</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📸 Server Snapshot</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Weekly</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">4 weeks</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Cloud Provider</td>
                    </tr>
                </tbody>
            </table>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--primary-rgb));">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">🎯 Disaster Recovery Metrics</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb));">4 hours</div>
                        <div style="font-weight: 600;">RTO (Recovery Time Objective)</div>
                        <div style="font-size: 0.85rem; color: #6c757d; margin-top: 0.5rem;">Maximum downtime tolerance</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb));">15 min</div>
                        <div style="font-weight: 600;">RPO (Recovery Point Objective)</div>
                        <div style="font-size: 0.85rem; color: #6c757d; margin-top: 0.5rem;">Maximum data loss tolerance</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb));">99.9%</div>
                        <div style="font-weight: 600;">Uptime SLA</div>
                        <div style="font-size: 0.85rem; color: #6c757d; margin-top: 0.5rem;">~43 min downtime/month allowed</div>
                    </div>
                </div>
            </div>
        </section>

{{-- 4: Development Workflow --}}
        <section id="development" class="wiki-section">
            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.05) 100%); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; border-left: 4px solid rgb(var(--primary-rgb));">
                <h3 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">💻 Development Workflow & Standards</h3>
                <p style="line-height: 1.8; margin: 0;">กระบวนการพัฒนาที่มีมาตรฐาน ทดสอบอัตโนมัติ และ Deploy แบบ Continuous Integration/Continuous Deployment (CI/CD)</p>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">📝 Coding Standards</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">🐘 PHP Standards</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li>✅ PSR-12 Coding Style</li>
                        <li>✅ PSR-4 Autoloading</li>
                        <li>✅ Type declarations</li>
                        <li>✅ PHPDoc comments</li>
                        <li>✅ PHP CS Fixer</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">🎨 Frontend Standards</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li>✅ ESLint rules</li>
                        <li>✅ Prettier formatting</li>
                        <li>✅ Component structure</li>
                        <li>✅ Accessibility (WCAG)</li>
                        <li>✅ Responsive design</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">📚 Documentation</h4>
                    <ul style="list-style: none; padding: 0; line-height: 2;">
                        <li>✅ README files</li>
                        <li>✅ API documentation</li>
                        <li>✅ Inline comments</li>
                        <li>✅ Architecture docs</li>
                        <li>✅ Changelog</li>
                    </ul>
                </div>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🧪 Testing Strategy</h2>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: var(--wiki-card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <thead style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Test Type</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Tool / Framework</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border); font-weight: 700;">Coverage Goal</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border); font-weight: 700;">Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔬 Unit Tests</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">PHPUnit / Pest</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">80%+</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Test individual methods, classes</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔗 Feature Tests</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Laravel Dusk, PHPUnit</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">70%+</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Test complete features, workflows</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🌐 E2E Tests</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Cypress, Playwright</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">Critical paths</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Full user journey testing</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⚡ Performance Tests</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Apache JMeter, k6</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">Key endpoints</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Load testing, stress testing</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔒 Security Tests</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">OWASP ZAP, SonarQube</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">All code</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Vulnerability scanning</td>
                    </tr>
                </tbody>
            </table>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🔄 CI/CD Pipeline</h2>

            <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1.5rem;">🚀 Automated Deployment Process</h4>

                <div style="display: grid; gap: 1rem;">
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #0dcaf0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #0dcaf0; font-size: 1.1rem;">1️⃣ Code Push</strong>
                            <span style="background: #0dcaf0; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Git</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                            Developer pushes code to GitHub/GitLab → Webhook triggers CI/CD pipeline
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #6f42c1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #6f42c1; font-size: 1.1rem;">2️⃣ Code Quality Checks</strong>
                            <span style="background: #6f42c1; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">~2 min</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                            PHP CS Fixer, ESLint, PHPStan (static analysis), SonarQube security scan
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #ffc107;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #ffc107; font-size: 1.1rem;">3️⃣ Automated Testing</strong>
                            <span style="background: #ffc107; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">~5 min</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                            Unit tests (PHPUnit), Feature tests, E2E tests (critical paths only)
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #fd7e14;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #fd7e14; font-size: 1.1rem;">4️⃣ Build Assets</strong>
                            <span style="background: #fd7e14; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">~3 min</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                            npm run build, compile CSS/JS, optimize images, generate asset manifest
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #20c997;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #20c997; font-size: 1.1rem;">5️⃣ Deploy to Staging</strong>
                            <span style="background: #20c997; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">~2 min</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                            Deploy to staging environment, run database migrations, clear cache
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #0d6efd;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #0d6efd; font-size: 1.1rem;">6️⃣ Smoke Tests</strong>
                            <span style="background: #0d6efd; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">~1 min</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                            Test critical endpoints on staging, verify homepage loads, check database connectivity
                        </p>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #28a745;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #28a745; font-size: 1.1rem;">7️⃣ Deploy to Production</strong>
                            <span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">~3 min</span>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                            Zero-downtime deployment, blue-green strategy, automatic rollback on failure
                        </p>
                    </div>

                    <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05) 0%, rgba(var(--secondary-rgb), 0.02) 100%); padding: 1.5rem; border-radius: 10px; border: 2px solid rgb(var(--primary-rgb));">
                        <div style="text-align: center;">
                            <strong style="color: rgb(var(--primary-rgb)); font-size: 1.3rem;">✅ Total Pipeline Time: ~16 minutes</strong>
                            <p style="margin: 0.5rem 0 0 0; color: #6c757d;">From code push to production deployment</p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 style="font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text-primary);">🌿 Git Workflow (Git Flow)</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🌳</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">main</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">Production-ready code</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🚀</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">develop</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">Integration branch</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">✨</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">feature/*</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">New features</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🐛</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">bugfix/*</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">Bug fixes</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔥</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">hotfix/*</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">Emergency fixes</p>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; text-align: center;"
                     onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='scale(1.05)';"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='scale(1)';"
                     style="transition: all 0.3s;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📦</div>
                    <h4 style="font-weight: 700; margin-bottom: 0.5rem;">release/*</h4>
                    <p style="color: var(--wiki-text-secondary); margin: 0; font-size: 0.9rem;">Release preparation</p>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--secondary-rgb));">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Development Best Practices</h4>
                <ul style="line-height: 2; margin: 0;">
                    <li><strong>Pull Request Reviews:</strong> All code must be reviewed by at least 1 senior developer</li>
                    <li><strong>Branch Protection:</strong> main and develop branches are protected, require PR approval</li>
                    <li><strong>Semantic Commits:</strong> Use conventional commits (feat:, fix:, docs:, refactor:, test:)</li>
                    <li><strong>Code Coverage:</strong> New code must maintain or improve overall test coverage</li>
                    <li><strong>Performance Monitoring:</strong> Track key metrics (response time, memory usage, query count)</li>
                </ul>
            </div>
        </div>
    </section>
</div>

