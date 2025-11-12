<div class="wiki-content-section">
    <h1>⚙️ Technology Architecture</h1>
    <p class="subtitle">สถาปัตยกรรมและเทคโนโลยีที่ใช้</p>

    <section class="wiki-section">
        <div class="info-box">
            <h4>🚀 เทคโนโลยีที่ทันสมัย</h4>
            <p>ใช้เทคโนโลยีชั้นนำและ Best Practices ในการพัฒนา</p>
        </div>

        <h2>🔧 Technology Stack</h2>
        <div class="tech-stack-grid">
            <div class="tech-item">
                <h4>Backend Framework</h4>
                <ul>
                    <li>Laravel 11.x (PHP 8.3+)</li>
                    <li>RESTful API Architecture</li>
                    <li>Eloquent ORM</li>
                    <li>Queue Jobs</li>
                </ul>
            </div>

            <div class="tech-item">
                <h4>Database</h4>
                <ul>
                    <li>MySQL/MariaDB</li>
                    <li>Redis Cache</li>
                    <li>Database Migrations</li>
                    <li>{{ $stats['database_tables'] ?? 0 }} Tables</li>
                </ul>
            </div>

            <div class="tech-item">
                <h4>Frontend</h4>
                <ul>
                    <li>Blade Templates</li>
                    <li>TailwindCSS</li>
                    <li>Alpine.js</li>
                    <li>JavaScript ES6+</li>
                </ul>
            </div>

            <div class="tech-item">
                <h4>DevOps</h4>
                <ul>
                    <li>Git Version Control</li>
                    <li>Automated Deployment</li>
                    <li>Docker Support</li>
                    <li>CI/CD Pipeline</li>
                </ul>
            </div>
        </div>

        <h2>🏗️ Architecture Patterns</h2>
        <div class="pattern-list">
            <div class="pattern-item">
                <h4>MVC Pattern</h4>
                <p>Model-View-Controller สำหรับ separation of concerns</p>
            </div>
            <div class="pattern-item">
                <h4>Service Layer</h4>
                <p>Business logic แยกออกจาก Controllers</p>
            </div>
            <div class="pattern-item">
                <h4>Repository Pattern</h4>
                <p>Data access abstraction layer</p>
            </div>
            <div class="pattern-item">
                <h4>Observer Pattern</h4>
                <p>Event-driven architecture</p>
            </div>
        </div>

        <h2>📊 System Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['database_models'] ?? 0 }}</div>
                <div class="stat-label">Models</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['http_controllers'] ?? 0 }}</div>
                <div class="stat-label">Controllers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['services_count'] ?? 0 }}</div>
                <div class="stat-label">Services</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['database_tables'] ?? 0 }}</div>
                <div class="stat-label">Migrations</div>
            </div>
        </div>
    </section>
</div>

<style>
.tech-stack-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.tech-item {
    background: var(--wiki-card-bg);
    border: 2px solid var(--wiki-border);
    border-radius: 12px;
    padding: 1.5rem;
}

.tech-item h4 {
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--primary);
}

.pattern-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.pattern-item {
    background: var(--wiki-card-bg);
    border: 2px solid var(--wiki-border);
    border-radius: 12px;
    padding: 1.5rem;
}

.pattern-item h4 {
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--wiki-text-primary);
}
</style>
