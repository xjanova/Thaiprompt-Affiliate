{{-- Rank Widget - แสดงสถานะและความก้าวหน้าของระดับ --}}
<div class="rank-widget" id="rankWidget">
    <div class="rank-container">
        <div class="rank-header">
            <div class="header-left">
                <div class="rank-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="header-text">
                    <h3>ระดับของคุณ</h3>
                    <p class="subtitle">Your Rank & Progress</p>
                </div>
            </div>
            <div class="rank-badge" id="rankBadge">
                <i class="fas fa-crown pulse-icon"></i>
                <span id="currentRankName">กำลังโหลด...</span>
            </div>
        </div>

        <div class="rank-body">
            {{-- Current Rank Display --}}
            <div class="current-rank-display">
                <div class="rank-medal">
                    <div class="medal-circle" id="medalCircle">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="rank-level" id="rankLevel">Lv.--</div>
                </div>
                <div class="rank-details">
                    <div class="rank-name-display" id="rankNameDisplay">---</div>
                    <div class="rank-subtitle" id="rankSubtitle">ระดับปัจจุบัน</div>
                </div>
            </div>

            {{-- Leaderboard Position --}}
            <div class="leaderboard-position">
                <div class="position-icon">
                    <i class="fas fa-ranking-star"></i>
                </div>
                <div class="position-info">
                    <div class="position-label">อันดับของคุณ</div>
                    <div class="position-value" id="leaderboardPosition">#--</div>
                </div>
                <div class="position-chart">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>

            {{-- Points Progress --}}
            <div class="points-container">
                <div class="points-card">
                    <div class="points-icon">
                        <i class="fas fa-coins"></i>
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
                        <div class="points-label">ระดับถัดไป</div>
                        <div class="points-value" id="requiredPoints">0</div>
                    </div>
                </div>
            </div>

            {{-- Progress to Next Rank --}}
            <div class="rank-progress-container">
                <div class="progress-label">
                    <span>ความคืบหน้าไประดับถัดไป</span>
                    <span class="progress-percentage" id="rankPercentage">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill">
                        <div class="progress-glow"></div>
                    </div>
                </div>
                <div class="progress-info">
                    <span id="nextRankName">เหลืออีก 0 แต้ม</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="action-buttons">
                <a href="{{ route('user.ranks.dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i>
                    <span>รายละเอียด</span>
                </a>
                <a href="{{ route('user.ranks.progress') }}" class="btn btn-success">
                    <i class="fas fa-tasks"></i>
                    <span>ความคืบหน้า</span>
                </a>
                <a href="{{ route('user.ranks.leaderboard') }}" class="btn btn-warning">
                    <i class="fas fa-trophy"></i>
                    <span>กระดานผู้นำ</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.rank-widget {
    margin: 20px 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.rank-container {
    background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    transition: transform 0.3s ease, background 0.3s ease;
}

/* Dark Mode Support */
.dark .rank-container {
    background: linear-gradient(135deg, #d97706 0%, #c2410c 100%);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.rank-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
}

.rank-header {
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

.rank-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    animation: trophy-bounce 2s ease-in-out infinite;
}

@keyframes trophy-bounce {
    0%, 100% { transform: translateY(0) scale(1); }
    25% { transform: translateY(-5px) scale(1.05); }
    50% { transform: translateY(0) scale(1); }
    75% { transform: translateY(-3px) scale(1.02); }
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

.rank-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
}

.pulse-icon {
    font-size: 12px;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.2); }
}

.rank-body {
    padding: 25px;
}

/* Current Rank Display */
.current-rank-display {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.rank-medal {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.medal-circle {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: #fff;
    box-shadow: 0 8px 20px rgba(251, 191, 36, 0.4);
    border: 3px solid rgba(255, 255, 255, 0.3);
    animation: medal-glow 2s ease-in-out infinite;
}

@keyframes medal-glow {
    0%, 100% { box-shadow: 0 8px 20px rgba(251, 191, 36, 0.4); }
    50% { box-shadow: 0 8px 30px rgba(251, 191, 36, 0.6); }
}

.rank-level {
    margin-top: 8px;
    padding: 4px 12px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
}

.rank-details {
    flex: 1;
}

.rank-name-display {
    color: #fff;
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 5px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.rank-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
}

/* Leaderboard Position */
.leaderboard-position {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px 20px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.position-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
}

.position-info {
    flex: 1;
}

.position-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    margin-bottom: 3px;
}

.position-value {
    color: #fff;
    font-size: 24px;
    font-weight: 700;
}

.position-chart {
    font-size: 28px;
    color: rgba(255, 255, 255, 0.4);
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
    background: linear-gradient(135deg, #10b981, #059669);
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
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

/* Rank Progress */
.rank-progress-container {
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
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
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

.action-buttons .btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.action-buttons .btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4);
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

    .current-rank-display {
        flex-direction: column;
        text-align: center;
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
                const response = await fetch('{{ route('user.ranks.widget-data') }}');
                const result = await response.json();

                if (result.success) {
                    this.updateWidget(result.data);
                }
            } catch (error) {
                console.error('Error loading rank widget data:', error);
            }
        },

        updateWidget(data) {
            // Update current rank badge
            const rankBadge = document.getElementById('currentRankName');
            rankBadge.textContent = data.current_rank.name_th || data.current_rank.name;

            // Update rank display
            const rankLevel = document.getElementById('rankLevel');
            rankLevel.textContent = `Lv.${data.current_rank.level}`;

            const rankNameDisplay = document.getElementById('rankNameDisplay');
            rankNameDisplay.textContent = data.current_rank.name_th || data.current_rank.name;

            // Update leaderboard position
            const leaderboardPosition = document.getElementById('leaderboardPosition');
            leaderboardPosition.textContent = `#${data.leaderboard_position}`;

            // Update points
            document.getElementById('currentPoints').textContent = this.formatNumber(data.current_points);
            document.getElementById('requiredPoints').textContent = this.formatNumber(data.next_rank_points);

            // Update progress
            const progressFill = document.getElementById('progressFill');
            const progressPercentage = document.getElementById('rankPercentage');
            const percentage = data.progress_percentage || 0;
            progressFill.style.width = `${percentage}%`;
            progressPercentage.textContent = `${Math.round(percentage)}%`;

            // Update next rank info
            const nextRankName = document.getElementById('nextRankName');
            if (data.next_rank) {
                const remaining = data.next_rank_points - data.current_points;
                nextRankName.textContent = `เหลืออีก ${this.formatNumber(remaining)} แต้มจะถึง ${data.next_rank.name_th || data.next_rank.name}`;
            } else {
                nextRankName.textContent = 'คุณอยู่ในระดับสูงสุดแล้ว!';
            }

            // Update medal color based on rank
            this.updateMedalColor(data.current_rank.level);
        },

        updateMedalColor(level) {
            const medalCircle = document.getElementById('medalCircle');
            medalCircle.className = 'medal-circle';

            // You can customize colors based on rank levels
            if (level >= 10) {
                medalCircle.style.background = 'linear-gradient(135deg, #a855f7, #7c3aed)';
            } else if (level >= 7) {
                medalCircle.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
            } else if (level >= 4) {
                medalCircle.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            } else {
                medalCircle.style.background = 'linear-gradient(135deg, #fbbf24, #f59e0b)';
            }
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
