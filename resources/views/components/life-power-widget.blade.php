{{-- Life Power Widget - แสดงสถานะพลังชีวิตการรักษายอด --}}
<div class="life-power-widget" id="lifePowerWidget">
    <div class="life-power-container">
        <div class="life-power-header">
            <div class="header-left">
                <div class="heart-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="header-text">
                    <h3>สถานะพลังชีวิต</h3>
                    <p class="subtitle">Membership Life Power</p>
                </div>
            </div>
            <div class="status-badge" id="statusBadge">
                <i class="fas fa-circle pulse-dot"></i>
                <span id="statusText">กำลังโหลด...</span>
            </div>
        </div>

        <div class="life-power-body">
            {{-- Main Health Bar --}}
            <div class="health-bar-container">
                <div class="health-bar-label">
                    <span>วันที่เหลือ</span>
                    <span class="days-remaining" id="daysRemaining">-- วัน</span>
                </div>
                <div class="health-bar-outer" id="healthBarOuter">
                    <div class="health-bar-inner" id="healthBarInner">
                        <div class="health-bar-shine"></div>
                    </div>
                </div>
                <div class="health-bar-info">
                    <span id="nextRenewalDate">วันต่ออายุ: --</span>
                </div>
            </div>

            {{-- Points Progress --}}
            <div class="points-container">
                <div class="points-card">
                    <div class="points-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="points-info">
                        <div class="points-label">แต้มปัจจุบัน</div>
                        <div class="points-value" id="currentPoints">0</div>
                    </div>
                </div>

                <div class="points-divider">
                    <i class="fas fa-arrow-right"></i>
                </div>

                <div class="points-card">
                    <div class="points-icon target">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="points-info">
                        <div class="points-label">เป้าหมาย</div>
                        <div class="points-value" id="requiredPoints">0</div>
                    </div>
                </div>
            </div>

            {{-- Points Progress Bar --}}
            <div class="points-progress-container">
                <div class="progress-label">
                    <span>ความคืบหน้า</span>
                    <span class="progress-percentage" id="pointsPercentage">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill">
                        <div class="progress-glow"></div>
                    </div>
                </div>
                <div class="progress-info">
                    <span id="remainingPoints">เหลืออีก 0 แต้ม</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="action-buttons">
                <a href="{{ route('user.retention.index') }}" class="btn btn-primary">
                    <i class="fas fa-chart-line"></i>
                    <span>ดูรายละเอียด</span>
                </a>
                <a href="{{ route('user.retention.repair') }}" class="btn btn-warning">
                    <i class="fas fa-tools"></i>
                    <span>ซ่อมสิทธิ์</span>
                </a>
                <a href="{{ route('user.retention.advance-renewal') }}" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i>
                    <span>เติมล่วงหน้า</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.life-power-widget {
    margin: 20px 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.life-power-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    transition: transform 0.3s ease, background 0.3s ease;
}

/* Dark Mode Support */
.dark .life-power-container {
    background: linear-gradient(135deg, #5568d3 0%, #6d3d91 100%);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.life-power-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
}

.life-power-header {
    padding: 25px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.heart-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    animation: heartbeat 1.5s ease-in-out infinite;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10% { transform: scale(1.1); }
    20% { transform: scale(1); }
    30% { transform: scale(1.15); }
    40% { transform: scale(1); }
}

.header-text h3 {
    margin: 0;
    color: #fff;
    font-size: 20px;
    font-weight: 600;
}

.header-text .subtitle {
    margin: 0;
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    font-weight: 400;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
}

.pulse-dot {
    font-size: 10px;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.life-power-body {
    padding: 25px;
}

/* Health Bar */
.health-bar-container {
    margin-bottom: 25px;
}

.health-bar-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #fff;
    font-size: 14px;
}

.days-remaining {
    font-weight: 700;
    font-size: 18px;
}

.health-bar-outer {
    height: 30px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 15px;
    overflow: hidden;
    position: relative;
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: border-color 0.3s ease;
}

.health-bar-inner {
    height: 100%;
    transition: all 0.5s ease;
    position: relative;
    overflow: hidden;
}

.health-bar-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shine 2s ease-in-out infinite;
}

@keyframes shine {
    0% { left: -100%; }
    100% { left: 100%; }
}

.health-bar-info {
    margin-top: 8px;
    color: rgba(255, 255, 255, 0.9);
    font-size: 12px;
    text-align: center;
}

/* Health Colors */
.health-green { background: linear-gradient(90deg, #4ade80, #22c55e); }
.health-yellow { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.health-orange { background: linear-gradient(90deg, #fb923c, #f97316); }
.health-red { background: linear-gradient(90deg, #ef4444, #dc2626); animation: critical-pulse 1s ease-in-out infinite; }

@keyframes critical-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Points Container */
.points-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 20px;
}

.points-card {
    flex: 1;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.points-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    box-shadow: 0 4px 10px rgba(251, 191, 36, 0.3);
}

.points-icon.target {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
}

.points-info {
    flex: 1;
}

.points-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    margin-bottom: 5px;
}

.points-value {
    color: #fff;
    font-size: 24px;
    font-weight: 700;
}

.points-divider {
    color: #fff;
    font-size: 20px;
    opacity: 0.6;
}

/* Points Progress */
.points-progress-container {
    margin-bottom: 25px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #fff;
    font-size: 14px;
}

.progress-percentage {
    font-weight: 700;
}

.progress-bar {
    height: 20px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    transition: width 0.5s ease;
    position: relative;
    overflow: hidden;
}

.progress-glow {
    position: absolute;
    top: 0;
    left: -50%;
    width: 50%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: glow 1.5s ease-in-out infinite;
}

@keyframes glow {
    0% { left: -50%; }
    100% { left: 100%; }
}

.progress-info {
    margin-top: 8px;
    color: rgba(255, 255, 255, 0.9);
    font-size: 12px;
    text-align: center;
}

/* Action Buttons */
.action-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.action-buttons .btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 15px 10px;
    border-radius: 12px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.action-buttons .btn i {
    font-size: 20px;
}

.action-buttons .btn-primary {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
}

.action-buttons .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4);
}

.action-buttons .btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
}

.action-buttons .btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(245, 158, 11, 0.4);
}

.action-buttons .btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.action-buttons .btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .action-buttons {
        grid-template-columns: 1fr;
    }

    .points-container {
        flex-direction: column;
    }

    .points-divider {
        transform: rotate(90deg);
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
