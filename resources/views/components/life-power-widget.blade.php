{{-- Life Power Widget - แสดงสถานะพลังชีวิตการรักษายอด --}}
<div class="life-power-widget" id="lifePowerWidget">
    <div class="life-power-container">
        {{-- Header with Icon --}}
        <div class="life-power-header">
            <div class="header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon-heart" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </div>
            <div class="header-content">
                <h3 class="header-title">การรักษายอด</h3>
                <span class="status-badge" id="statusText">กำลังโหลด...</span>
            </div>
        </div>

        {{-- Compact Body --}}
        <div class="life-power-body">
            {{-- Days Remaining Card --}}
            <div class="health-status-card">
                <div class="status-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon-calendar" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="status-content">
                    <span class="status-label">วันที่เหลือ</span>
                    <span class="status-value" id="daysRemaining">-- วัน</span>
                </div>
                <div class="health-bar-outer" id="healthBarOuter">
                    <div class="health-bar-inner" id="healthBarInner">
                        <div class="health-bar-pulse"></div>
                    </div>
                </div>
            </div>

            {{-- Renewal Date --}}
            <div class="renewal-info">
                <svg xmlns="http://www.w3.org/2000/svg" class="renewal-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span id="nextRenewalDate">วันต่ออายุ: --</span>
            </div>

            {{-- Points Info Grid --}}
            <div class="points-info-grid">
                <div class="points-info-card">
                    <div class="points-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div class="points-card-content">
                        <span class="points-card-label">แต้มปัจจุบัน</span>
                        <span class="points-card-value" id="currentPoints">0</span>
                    </div>
                </div>

                <div class="points-info-card">
                    <div class="points-card-icon target">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="points-card-content">
                        <span class="points-card-label">เป้าหมาย</span>
                        <span class="points-card-value" id="requiredPoints">0</span>
                    </div>
                </div>
            </div>

            {{-- Compact Progress Bar --}}
            <div class="points-progress-compact">
                <div class="progress-header">
                    <span class="progress-text" id="remainingPoints">เหลืออีก 0 แต้ม</span>
                    <span class="progress-percentage" id="pointsPercentage">0%</span>
                </div>
                <div class="progress-bar-slim">
                    <div class="progress-fill" id="progressFill">
                        <div class="progress-shine"></div>
                    </div>
                </div>
            </div>

            {{-- Compact Action Links --}}
            <div class="action-links">
                <a href="{{ route('user.retention.index') }}" class="action-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="link-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>รายละเอียด</span>
                </a>
                <a href="{{ route('user.retention.repair') }}" class="action-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="link-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>ซ่อมสิทธิ์</span>
                </a>
                <a href="{{ route('user.retention.advance-renewal') }}" class="action-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="link-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>เติมล่วงหน้า</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.life-power-widget {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.life-power-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark .life-power-container {
    background: linear-gradient(135deg, #5568d3 0%, #6d3d91 100%);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}

.life-power-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
}

/* Compact Header */
.life-power-header {
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.header-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.icon-heart {
    width: 24px;
    height: 24px;
    color: #fff;
    animation: heart-beat 1.5s ease-in-out infinite;
}

@keyframes heart-beat {
    0%, 100% { transform: scale(1); }
    10% { transform: scale(1.08); }
    20% { transform: scale(1); }
    30% { transform: scale(1.12); }
    40% { transform: scale(1); }
}

.header-content {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.header-title {
    margin: 0;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
}

.status-badge {
    padding: 4px 12px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}

/* Compact Body */
.life-power-body {
    padding: 16px;
}

/* Health Status Card */
.health-status-card {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 16px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.icon-calendar {
    width: 24px;
    height: 24px;
    color: #fff;
}

.status-content {
    flex: 1;
    min-width: 0;
}

.status-label {
    display: block;
    color: rgba(255, 255, 255, 0.8);
    font-size: 11px;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-value {
    display: block;
    color: #fff;
    font-size: 24px;
    font-weight: 700;
    line-height: 1;
}

.health-bar-outer {
    width: 80px;
    height: 8px;
    background: rgba(0, 0, 0, 0.25);
    border-radius: 4px;
    overflow: hidden;
    flex-shrink: 0;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.health-bar-inner {
    height: 100%;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.health-bar-pulse {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: pulse-move 2s ease-in-out infinite;
}

@keyframes pulse-move {
    to { left: 100%; }
}

/* Health Colors */
.health-green { background: linear-gradient(90deg, #4ade80, #22c55e); }
.health-yellow { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.health-orange { background: linear-gradient(90deg, #fb923c, #f97316); }
.health-red {
    background: linear-gradient(90deg, #ef4444, #dc2626);
    animation: critical-blink 1s ease-in-out infinite;
}

@keyframes critical-blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Renewal Info */
.renewal-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    margin-bottom: 12px;
    color: rgba(255, 255, 255, 0.9);
    font-size: 12px;
}

.renewal-icon {
    width: 16px;
    height: 16px;
    color: rgba(255, 255, 255, 0.7);
    flex-shrink: 0;
}

/* Points Info Grid */
.points-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

.points-info-card {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    flex-direction: column;
    gap: 8px;
    transition: all 0.2s ease;
}

.points-info-card:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: translateY(-1px);
}

.points-card-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(251, 191, 36, 0.2);
}

.points-card-icon.target {
    background: linear-gradient(135deg, #10b981, #059669);
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
}

.points-card-icon .icon {
    width: 20px;
    height: 20px;
    color: #fff;
}

.points-card-content {
    flex: 1;
}

.points-card-label {
    display: block;
    color: rgba(255, 255, 255, 0.8);
    font-size: 11px;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.points-card-value {
    display: block;
    color: #fff;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.2;
}

/* Compact Progress */
.points-progress-compact {
    margin-bottom: 16px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.progress-text {
    color: rgba(255, 255, 255, 0.95);
    font-size: 12px;
    font-weight: 500;
}

.progress-percentage {
    color: #fff;
    font-size: 13px;
    font-weight: 700;
}

.progress-bar-slim {
    height: 8px;
    background: rgba(0, 0, 0, 0.25);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.progress-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shine 2s ease-in-out infinite;
}

@keyframes shine {
    to { left: 100%; }
}

/* Compact Action Links */
.action-links {
    display: flex;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.action-link {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 10px 8px;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    text-decoration: none;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    transition: all 0.2s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.action-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.link-icon {
    width: 18px;
    height: 18px;
    color: #fff;
}

/* Responsive */
@media (max-width: 768px) {
    .points-info-grid {
        grid-template-columns: 1fr;
    }

    .action-links {
        flex-direction: column;
    }

    .action-link {
        flex-direction: row;
        justify-content: center;
    }

    .health-status-card {
        flex-direction: column;
        text-align: center;
    }

    .health-bar-outer {
        width: 100%;
    }
}
</style>

<script>
(function() {
    const widget = {
        init() {
            this.loadData();
            setInterval(() => this.loadData(), 60000); // Update every minute
        },

        async loadData() {
            try {
                const response = await fetch('{{ route('user.retention.widget-data') }}');
                const result = await response.json();

                if (result.success) {
                    this.updateWidget(result.data);
                }
            } catch (error) {
                console.error('Error loading widget data:', error);
            }
        },

        updateWidget(data) {
            // Update status badge
            const statusBadge = document.getElementById('statusText');
            const statusText = {
                'active': 'ใช้งานอยู่',
                'inactive': 'ไม่ใช้งาน',
                'expired': 'หมดอายุ'
            };
            statusBadge.textContent = statusText[data.status] || 'ไม่ทราบสถานะ';

            // Update days remaining
            const daysRemaining = document.getElementById('daysRemaining');
            daysRemaining.textContent = `${data.days_remaining} วัน`;

            // Update health bar
            const healthBarInner = document.getElementById('healthBarInner');
            const healthBarOuter = document.getElementById('healthBarOuter');
            const healthPercentage = Math.max(0, 100 - data.health_percentage);

            healthBarInner.style.width = `${healthPercentage}%`;
            healthBarInner.className = 'health-bar-inner';
            healthBarOuter.style.borderColor = this.getHealthBorderColor(data.health_color);

            // Add color class
            healthBarInner.classList.add(`health-${data.health_color}`);

            // Update renewal date
            const renewalDate = document.getElementById('nextRenewalDate');
            renewalDate.textContent = `วันต่ออายุ: ${data.next_renewal_date || 'ไม่ระบุ'}`;

            // Update points
            document.getElementById('currentPoints').textContent = this.formatNumber(data.current_points);
            document.getElementById('requiredPoints').textContent = this.formatNumber(data.required_points);

            // Update progress
            const progressFill = document.getElementById('progressFill');
            const progressPercentage = document.getElementById('pointsPercentage');
            progressFill.style.width = `${data.points_percentage}%`;
            progressPercentage.textContent = `${Math.round(data.points_percentage)}%`;

            // Update remaining points
            const remainingPoints = document.getElementById('remainingPoints');
            remainingPoints.textContent = `เหลืออีก ${this.formatNumber(data.remaining_points)} แต้ม`;
        },

        getHealthBorderColor(color) {
            const colors = {
                'green': '#22c55e',
                'yellow': '#f59e0b',
                'orange': '#f97316',
                'red': '#dc2626'
            };
            return colors[color] || '#fff';
        },

        formatNumber(num) {
            return new Intl.NumberFormat('th-TH').format(num);
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => widget.init());
    } else {
        widget.init();
    }
})();
</script>
