<div class="wiki-content-section">
    {{-- Hero Section --}}
    <div class="bg-gradient-to-br from-primary-500 via-secondary-500 to-accent-500 p-8 md:p-12 rounded-2xl mb-8 shadow-xl">
        <h1 class="text-white text-2xl md:text-4xl font-extrabold mb-3 drop-shadow-lg">⚙️ Technology Architecture</h1>
        <p class="text-white/95 text-lg md:text-xl font-medium">สถาปัตยกรรมและเทคโนโลยีที่ทันสมัยและมีประสิทธิภาพสูง</p>
    </div>

    {{-- Tab 1: Technology Stack --}}
    <section id="tech-stack" class="wiki-section">
        <div class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/20 dark:to-secondary-900/20 p-6 rounded-xl mb-6 border-l-4 border-primary-500">
            <h3 class="font-bold mb-2 text-primary-600 dark:text-primary-400">🚀 Modern Technology Stack</h3>
            <p class="leading-relaxed">ใช้เทคโนโลยีชั้นนำและ Best Practices ในการพัฒนา ทำให้ระบบมีประสิทธิภาพสูง ปลอดภัย และขยายตัวได้ง่าย</p>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🔧 Core Technology Stack</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            {{-- Backend Framework --}}
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🐘</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Backend Framework</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li><span class="font-semibold text-primary-600 dark:text-primary-400">Laravel 11.x</span> - Latest version</li>
                    <li>PHP 8.1+ - Modern features (แนะนำ 8.3)</li>
                    <li>Eloquent ORM - Database abstraction</li>
                    <li>Queue Jobs - Async processing</li>
                    <li>RESTful API - Standard architecture</li>
                </ul>
            </div>

            {{-- Frontend Technologies --}}
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🎨</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Frontend Technologies</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li><span class="font-semibold text-secondary-600 dark:text-secondary-400">Blade Templates</span> - Server-side rendering</li>
                    <li>TailwindCSS - Utility-first CSS</li>
                    <li>Alpine.js - Lightweight reactivity</li>
                    <li>JavaScript ES6+ - Modern JS</li>
                    <li>SortableJS - Drag & drop</li>
                </ul>
            </div>

            {{-- Database & Cache --}}
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🗄️</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Database & Cache</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li><span class="font-semibold text-accent-600 dark:text-accent-400">MySQL 8.0+</span> - Primary database</li>
                    <li>MariaDB - Alternative option</li>
                    <li>Redis - Cache & sessions</li>
                    <li>Database Migrations - Version control</li>
                    <li>{{ $stats['database_tables'] ?? 694 }} Tables - Comprehensive schema</li>
                </ul>
            </div>

            {{-- DevOps & Tools --}}
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🚢</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">DevOps & Tools</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li><span class="font-semibold text-primary-600 dark:text-primary-400">Git</span> - Version control</li>
                    <li>Docker - Containerization</li>
                    <li>GitHub Actions - CI/CD pipeline</li>
                    <li>Composer - PHP dependencies</li>
                    <li>NPM/Yarn - JS dependencies</li>
                </ul>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📊 System Statistics & Metrics</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Component</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Count</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Description</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>📦 Database Models</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">{{ $stats['database_models'] ?? 548 }}</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Eloquent models for data access</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">Active</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🎮 HTTP Controllers</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-secondary-600 dark:text-secondary-400">{{ $stats['http_controllers'] ?? 421 }}</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Request handlers and routing</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">Active</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>⚙️ Service Classes</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-accent-600 dark:text-accent-400">{{ $stats['services_count'] ?? 295 }}</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Business logic layer</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">Active</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🗃️ Database Tables</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">{{ $stats['database_tables'] ?? 694 }}</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Database schema tables</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">Active</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🔄 API Endpoints</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-secondary-600 dark:text-secondary-400">{{ $stats['api_endpoints'] ?? 1500 }}+</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">RESTful API routes</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">Active</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>📋 Blade Views</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-accent-600 dark:text-accent-400">1,590+</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Frontend templates</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">Active</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🔌 Third-Party Integrations</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-primary-500 hover:scale-105 transition-all duration-300">
                <div class="text-4xl mb-3">💳</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Payment Gateways</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Stripe, PayPal, 2C2P, OmiseGO</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-secondary-500 hover:scale-105 transition-all duration-300">
                <div class="text-4xl mb-3">📧</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Email Services</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">SendGrid, Mailgun, AWS SES</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-accent-500 hover:scale-105 transition-all duration-300">
                <div class="text-4xl mb-3">☁️</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Cloud Storage</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">AWS S3, Google Cloud, DigitalOcean</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-primary-500 hover:scale-105 transition-all duration-300">
                <div class="text-4xl mb-3">📊</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Analytics</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Google Analytics, Mixpanel, Hotjar</p>
            </div>
        </div>

        <div class="bg-gradient-to-r from-secondary-50 to-accent-50 dark:from-secondary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-secondary-500">
            <h4 class="font-bold mb-3 text-secondary-600 dark:text-secondary-400">💡 Technology Best Practices</h4>
            <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                <li><strong>Security First:</strong> HTTPS only, CSRF protection, SQL injection prevention, XSS filtering</li>
                <li><strong>Performance:</strong> Redis caching, Database indexing, Lazy loading, CDN integration</li>
                <li><strong>Scalability:</strong> Horizontal scaling ready, Load balancer compatible, Queue workers</li>
                <li><strong>Maintainability:</strong> PSR-12 coding standards, PHPDoc documentation, Automated testing</li>
                <li><strong>Modern PHP:</strong> Type hints, Null coalescing, Arrow functions, Named arguments</li>
            </ul>
        </div>
    </section>

    {{-- Tab 2: Architecture Patterns --}}
    <section id="architecture" class="wiki-section mt-12">
        <div class="bg-gradient-to-r from-secondary-50 to-accent-50 dark:from-secondary-900/20 dark:to-accent-900/20 p-6 rounded-xl mb-6 border-l-4 border-secondary-500">
            <h3 class="font-bold mb-2 text-secondary-600 dark:text-secondary-400">🏗️ Architecture & Design Patterns</h3>
            <p class="leading-relaxed">สถาปัตยกรรมที่ออกแบบมาอย่างดี ใช้ Design Patterns ที่เหมาะสม ทำให้โค้ดอ่านง่าย แก้ไขง่าย และขยายตัวได้ดี</p>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🎯 Core Architecture Patterns</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">🔷 MVC Pattern</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Model-View-Controller for separation of concerns</p>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                    <li>✅ Models: Data & business logic</li>
                    <li>✅ Views: Presentation layer</li>
                    <li>✅ Controllers: Request handling</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <h4 class="font-bold mb-2 text-secondary-600 dark:text-secondary-400">⚙️ Service Layer</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Business logic separated from controllers</p>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                    <li>✅ Reusable business logic</li>
                    <li>✅ Testable services</li>
                    <li>✅ Dependency injection</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <h4 class="font-bold mb-2 text-accent-600 dark:text-accent-400">🗄️ Repository Pattern</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Data access abstraction layer</p>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                    <li>✅ Database abstraction</li>
                    <li>✅ Easy to swap data sources</li>
                    <li>✅ Clean query methods</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">👀 Observer Pattern</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Event-driven architecture</p>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                    <li>✅ Loose coupling</li>
                    <li>✅ Event listeners</li>
                    <li>✅ Async notifications</li>
                </ul>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📐 Application Layers</h2>

        <div class="bg-gray-50 dark:bg-gray-900 p-6 rounded-xl mb-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-blue-500">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-bold text-blue-600 dark:text-blue-400">🌐 Presentation Layer</span>
                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs">Frontend</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Blade Views, Alpine.js Components, JavaScript, TailwindCSS - Handles user interface and interactions</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-purple-500">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-bold text-purple-600 dark:text-purple-400">🎮 Controller Layer</span>
                    <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-xs">Request Handling</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">HTTP Controllers, API Controllers, Form Requests - Receives requests, validates input, returns responses</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-orange-500">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-bold text-orange-600 dark:text-orange-400">⚙️ Service Layer</span>
                    <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-xs">Business Logic</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Service Classes, Action Classes, DTOs - Contains reusable business logic, orchestrates operations</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-teal-500">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-bold text-teal-600 dark:text-teal-400">🗄️ Data Access Layer</span>
                    <span class="bg-teal-500 text-white px-3 py-1 rounded-full text-xs">Repositories</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Repositories, Eloquent Models, Query Builders - Abstracts database operations and queries</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-red-500">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-bold text-red-600 dark:text-red-400">💾 Database Layer</span>
                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs">Persistence</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">MySQL/MariaDB, Redis, Migrations, Seeders - Stores and retrieves data persistently</p>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🔄 Request Lifecycle</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Step</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Component</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Action</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Avg Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold">1</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🌐 HTTP Request</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">User sends request to server</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">~5ms</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold">2</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🔐 Middleware</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Authentication, CSRF, Rate limiting</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">~10ms</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold">3</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🛣️ Router</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Match route, dispatch to controller</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">~3ms</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold">4</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🎮 Controller</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Validate input, call service layer</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">~8ms</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold">5</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>⚙️ Service</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Execute business logic, call repository</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">~15ms</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold">6</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🗄️ Repository</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Query database, return data</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">~20ms</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold">7</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>📋 View</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Render Blade template with data</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">~12ms</td>
                    </tr>
                    <tr class="bg-gradient-to-r from-primary-50/50 to-secondary-50/50 dark:from-primary-900/10 dark:to-secondary-900/10">
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold">8</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>✅ Response</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Send HTML/JSON to user</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">~73ms total</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-gradient-to-r from-accent-50 to-primary-50 dark:from-accent-900/20 dark:to-primary-900/20 p-6 rounded-xl border-l-4 border-accent-500">
            <h4 class="font-bold mb-4 text-accent-600 dark:text-accent-400">🎯 Architecture Benefits</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <strong class="text-primary-600 dark:text-primary-400">✅ Maintainability</strong>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Clean separation makes code easy to understand and modify</p>
                </div>
                <div>
                    <strong class="text-secondary-600 dark:text-secondary-400">✅ Testability</strong>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Each layer can be tested independently with mocks</p>
                </div>
                <div>
                    <strong class="text-accent-600 dark:text-accent-400">✅ Scalability</strong>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Services and repositories can scale horizontally</p>
                </div>
                <div>
                    <strong class="text-primary-600 dark:text-primary-400">✅ Reusability</strong>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Services can be reused across controllers and APIs</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Tab 3: Infrastructure & Deployment --}}
    <section id="infrastructure" class="wiki-section mt-12">
        <div class="bg-gradient-to-r from-accent-50 to-primary-50 dark:from-accent-900/20 dark:to-primary-900/20 p-6 rounded-xl mb-6 border-l-4 border-accent-500">
            <h3 class="font-bold mb-2 text-accent-600 dark:text-accent-400">☁️ Infrastructure & Deployment</h3>
            <p class="leading-relaxed">โครงสร้างพื้นฐานที่แข็งแกร่ง รองรับการ Deploy อัตโนมัติ และสามารถขยายตัวได้ตามความต้องการ</p>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🖥️ Server Requirements</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Component</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Minimum</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Recommended</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Production</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>💻 CPU</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">2 cores</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">4 cores</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">8+ cores</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🧠 RAM</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">4 GB</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">8 GB</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">16+ GB</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>💾 Storage</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">20 GB SSD</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">50 GB SSD</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">100+ GB SSD</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🌐 Bandwidth</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">100 Mbps</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">500 Mbps</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">1+ Gbps</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🐘 PHP Version</strong></td>
                        <td colspan="3" class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">PHP 8.1+ (แนะนำ 8.3)</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🗄️ Database</strong></td>
                        <td colspan="3" class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-secondary-600 dark:text-secondary-400">MySQL 8.0+ / MariaDB 10.5+</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">☁️ Cloud Hosting Options</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🚀</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Laravel Forge</h4>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <li>✅ Optimized for Laravel</li>
                    <li>✅ Auto deployment</li>
                    <li>✅ SSL certificates</li>
                    <li>✅ Server monitoring</li>
                </ul>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400"><strong>Cost:</strong> $19/mo + server</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🌊</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">DigitalOcean</h4>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <li>✅ App Platform</li>
                    <li>✅ Droplets (VPS)</li>
                    <li>✅ Managed databases</li>
                    <li>✅ Load balancers</li>
                </ul>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400"><strong>Cost:</strong> From $6/mo</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">☁️</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">AWS (Amazon)</h4>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <li>✅ EC2 instances</li>
                    <li>✅ RDS databases</li>
                    <li>✅ S3 storage</li>
                    <li>✅ CloudFront CDN</li>
                </ul>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400"><strong>Cost:</strong> Pay-as-you-go</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🔵</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Linode / Akamai</h4>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <li>✅ Compute instances</li>
                    <li>✅ Object storage</li>
                    <li>✅ NodeBalancers</li>
                    <li>✅ Kubernetes</li>
                </ul>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400"><strong>Cost:</strong> From $5/mo</p>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">⚖️ Load Balancing & Scaling</h2>

        <div class="bg-gray-50 dark:bg-gray-900 p-6 rounded-xl mb-8 space-y-4">
            <h4 class="font-bold mb-4 text-gray-900 dark:text-white">🔄 Horizontal Scaling Architecture</h4>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-blue-500">
                <span class="font-bold text-blue-600 dark:text-blue-400">⚖️ Load Balancer (Nginx/HAProxy)</span>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Distributes incoming traffic across multiple application servers using round-robin or least-connections algorithm</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-green-500">
                    <span class="font-bold text-green-600 dark:text-green-400">🖥️ App Server 1</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Primary instance</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-green-500">
                    <span class="font-bold text-green-600 dark:text-green-400">🖥️ App Server 2</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Replica instance</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-green-500">
                    <span class="font-bold text-green-600 dark:text-green-400">🖥️ App Server N</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Auto-scaled</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-red-500">
                    <span class="font-bold text-red-600 dark:text-red-400">🗄️ Database Master (Write)</span>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Primary database for writes, replicates to read replicas</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-yellow-500">
                    <span class="font-bold text-yellow-600 dark:text-yellow-400">🗄️ Database Slaves (Read)</span>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Read replicas for queries, reduces master load</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-purple-500">
                <span class="font-bold text-purple-600 dark:text-purple-400">⚡ Redis Cluster (Cache & Sessions)</span>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Shared cache layer, session storage, job queue - all servers connect to same Redis cluster</p>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🔄 Backup & Disaster Recovery</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Backup Type</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Frequency</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Retention</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Storage Location</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>💾 Full Database Backup</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">Daily</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">30 days</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">AWS S3 / Google Cloud</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>⚡ Incremental Backup</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">Every 6 hours</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">7 days</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">AWS S3 / Google Cloud</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>📂 File Storage Backup</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">Daily</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">90 days</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">S3 Glacier (Archive)</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>📸 Server Snapshot</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">Weekly</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">4 weeks</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700">Cloud Provider</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/20 dark:to-secondary-900/20 p-6 rounded-xl border-l-4 border-primary-500">
            <h4 class="font-bold mb-4 text-primary-600 dark:text-primary-400">🎯 Disaster Recovery Metrics</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                <div>
                    <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">4 hours</div>
                    <div class="font-semibold text-gray-900 dark:text-white">RTO (Recovery Time Objective)</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Maximum downtime tolerance</div>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-secondary-600 dark:text-secondary-400">15 min</div>
                    <div class="font-semibold text-gray-900 dark:text-white">RPO (Recovery Point Objective)</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Maximum data loss tolerance</div>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-accent-600 dark:text-accent-400">99.9%</div>
                    <div class="font-semibold text-gray-900 dark:text-white">Uptime SLA</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">~43 min downtime/month allowed</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Tab 4: Development Workflow --}}
    <section id="development" class="wiki-section mt-12">
        <div class="bg-gradient-to-r from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl mb-6 border-l-4 border-primary-500">
            <h3 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💻 Development Workflow & Standards</h3>
            <p class="leading-relaxed">กระบวนการพัฒนาที่มีมาตรฐาน ทดสอบอัตโนมัติ และ Deploy แบบ Continuous Integration/Continuous Deployment (CI/CD)</p>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📝 Coding Standards</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-primary-600 dark:text-primary-400">🐘 PHP Standards</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ PSR-12 Coding Style</li>
                    <li>✅ PSR-4 Autoloading</li>
                    <li>✅ Type declarations</li>
                    <li>✅ PHPDoc comments</li>
                    <li>✅ PHP CS Fixer</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-secondary-600 dark:text-secondary-400">🎨 Frontend Standards</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ ESLint rules</li>
                    <li>✅ Prettier formatting</li>
                    <li>✅ Component structure</li>
                    <li>✅ Accessibility (WCAG)</li>
                    <li>✅ Responsive design</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-accent-600 dark:text-accent-400">📚 Documentation</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ README files</li>
                    <li>✅ API documentation</li>
                    <li>✅ Inline comments</li>
                    <li>✅ Architecture docs</li>
                    <li>✅ Changelog</li>
                </ul>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🧪 Testing Strategy</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Test Type</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Tool / Framework</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Coverage Goal</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Purpose</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🔬 Unit Tests</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700">PHPUnit / Pest</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">80%+</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Test individual methods, classes</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🔗 Feature Tests</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700">Laravel Dusk, PHPUnit</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-secondary-600 dark:text-secondary-400">70%+</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Test complete features, workflows</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🌐 E2E Tests</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700">Cypress, Playwright</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-accent-600 dark:text-accent-400">Critical paths</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Full user journey testing</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>⚡ Performance Tests</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700">Apache JMeter, k6</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-primary-600 dark:text-primary-400">Key endpoints</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Load testing, stress testing</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🔒 Security Tests</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700">OWASP ZAP, SonarQube</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="font-bold text-secondary-600 dark:text-secondary-400">All code</span></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Vulnerability scanning</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🔄 CI/CD Pipeline</h2>

        <div class="bg-gray-50 dark:bg-gray-900 p-6 rounded-xl mb-8 space-y-3">
            <h4 class="font-bold mb-4 text-gray-900 dark:text-white">🚀 Automated Deployment Process</h4>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-cyan-500">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-cyan-600 dark:text-cyan-400">1️⃣ Code Push</span>
                    <span class="bg-cyan-500 text-white px-3 py-1 rounded-full text-xs">Git</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Developer pushes code to GitHub/GitLab → Webhook triggers CI/CD pipeline</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-purple-500">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-purple-600 dark:text-purple-400">2️⃣ Code Quality Checks</span>
                    <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-xs">~2 min</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">PHP CS Fixer, ESLint, PHPStan (static analysis), SonarQube security scan</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-yellow-500">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-yellow-600 dark:text-yellow-400">3️⃣ Automated Testing</span>
                    <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs">~5 min</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Unit tests (PHPUnit), Feature tests, E2E tests (critical paths only)</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-orange-500">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-orange-600 dark:text-orange-400">4️⃣ Build Assets</span>
                    <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-xs">~3 min</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">npm run build, compile CSS/JS, optimize images, generate asset manifest</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-teal-500">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-teal-600 dark:text-teal-400">5️⃣ Deploy to Staging</span>
                    <span class="bg-teal-500 text-white px-3 py-1 rounded-full text-xs">~2 min</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Deploy to staging environment, run database migrations, clear cache</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-blue-500">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-blue-600 dark:text-blue-400">6️⃣ Smoke Tests</span>
                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs">~1 min</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Test critical endpoints on staging, verify homepage loads, check database connectivity</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-green-500">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-green-600 dark:text-green-400">7️⃣ Deploy to Production</span>
                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs">~3 min</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Zero-downtime deployment, blue-green strategy, automatic rollback on failure</p>
            </div>

            <div class="bg-gradient-to-r from-primary-50/50 to-secondary-50/50 dark:from-primary-900/10 dark:to-secondary-900/10 p-4 rounded-lg border-2 border-primary-500 text-center">
                <span class="font-bold text-xl text-primary-600 dark:text-primary-400">✅ Total Pipeline Time: ~16 minutes</span>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">From code push to production deployment</p>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🌿 Git Workflow (Git Flow)</h2>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center hover:border-primary-500 hover:scale-105 transition-all duration-300">
                <div class="text-3xl mb-2">🌳</div>
                <h4 class="font-bold text-gray-900 dark:text-white">main</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Production-ready code</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center hover:border-secondary-500 hover:scale-105 transition-all duration-300">
                <div class="text-3xl mb-2">🚀</div>
                <h4 class="font-bold text-gray-900 dark:text-white">develop</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Integration branch</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center hover:border-accent-500 hover:scale-105 transition-all duration-300">
                <div class="text-3xl mb-2">✨</div>
                <h4 class="font-bold text-gray-900 dark:text-white">feature/*</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">New features</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center hover:border-primary-500 hover:scale-105 transition-all duration-300">
                <div class="text-3xl mb-2">🐛</div>
                <h4 class="font-bold text-gray-900 dark:text-white">bugfix/*</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Bug fixes</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center hover:border-secondary-500 hover:scale-105 transition-all duration-300">
                <div class="text-3xl mb-2">🔥</div>
                <h4 class="font-bold text-gray-900 dark:text-white">hotfix/*</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Emergency fixes</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center hover:border-accent-500 hover:scale-105 transition-all duration-300">
                <div class="text-3xl mb-2">📦</div>
                <h4 class="font-bold text-gray-900 dark:text-white">release/*</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Release preparation</p>
            </div>
        </div>

        <div class="bg-gradient-to-r from-secondary-50 to-accent-50 dark:from-secondary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-secondary-500">
            <h4 class="font-bold mb-3 text-secondary-600 dark:text-secondary-400">💡 Development Best Practices</h4>
            <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                <li><strong>Pull Request Reviews:</strong> All code must be reviewed by at least 1 senior developer</li>
                <li><strong>Branch Protection:</strong> main and develop branches are protected, require PR approval</li>
                <li><strong>Semantic Commits:</strong> Use conventional commits (feat:, fix:, docs:, refactor:, test:)</li>
                <li><strong>Code Coverage:</strong> New code must maintain or improve overall test coverage</li>
                <li><strong>Performance Monitoring:</strong> Track key metrics (response time, memory usage, query count)</li>
            </ul>
        </div>
    </section>
</div>
