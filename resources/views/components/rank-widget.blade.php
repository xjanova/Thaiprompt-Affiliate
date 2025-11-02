{{-- Rank Widget - แสดงสถานะและความก้าวหน้าของระดับ --}}
<div class="rank-widget" id="rankWidget">
    <div class="rank-container">
        {{-- Header with Icon --}}
        <div class="rank-header">
            <div class="header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon-trophy" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <div class="header-content">
                <h3 class="header-title">ระดับของคุณ</h3>
                <span class="rank-badge" id="currentRankName">กำลังโหลด...</span>
            </div>
        </div>

        {{-- Compact Body --}}
        <div class="rank-body">
            {{-- Main Rank Info Grid --}}
            <div class="rank-info-grid">
                {{-- Current Rank --}}
                <div class="info-card rank-card">
                    <div class="medal-circle" id="medalCircle">
                        <svg xmlns="http://www.w3.org/2000/svg" class="medal-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <div class="info-content">
                        <span class="info-label">ระดับปัจจุบัน</span>
                        <span class="info-value" id="rankNameDisplay">---</span>
                        <span class="rank-level-badge" id="rankLevel">Lv.--</span>
                    </div>
                </div>

                {{-- Leaderboard Position --}}
                <div class="info-card position-card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 4v12l-4-2-4 2V4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="info-content">
                        <span class="info-label">อันดับ</span>
                        <span class="info-value position-value" id="leaderboardPosition">#--</span>
                    </div>
                </div>

                {{-- Current Points --}}
                <div class="info-card points-card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="info-content">
                        <span class="info-label">แต้มปัจจุบัน</span>
                        <span class="info-value" id="currentPoints">0</span>
                    </div>
                </div>

                {{-- Target Points --}}
                <div class="info-card target-card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="info-content">
                        <span class="info-label">เป้าหมาย</span>
                        <span class="info-value" id="requiredPoints">0</span>
                    </div>
                </div>
            </div>

            {{-- Compact Progress Bar --}}
            <div class="rank-progress-compact">
                <div class="progress-header">
                    <span class="progress-text" id="nextRankName">เหลืออีก 0 แต้ม</span>
                    <span class="progress-percentage" id="rankPercentage">0%</span>
                </div>
                <div class="progress-bar-slim">
                    <div class="progress-fill" id="progressFill">
                        <div class="progress-shine"></div>
                    </div>
                </div>
            </div>

            {{-- Compact Action Buttons --}}
            <div class="action-links">
                <a href="{{ route('user.ranks.dashboard') }}" class="action-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="link-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>รายละเอียด</span>
                </a>
                <a href="{{ route('user.ranks.progress') }}" class="action-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="link-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>ความคืบหน้า</span>
                </a>
                <a href="{{ route('user.ranks.leaderboard') }}" class="action-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="link-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    <span>กระดานผู้นำ</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.rank-widget {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.rank-container {
    background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark .rank-container {
    background: linear-gradient(135deg, #d97706 0%, #c2410c 100%);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}

.rank-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
}

/* Compact Header */
.rank-header {
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

.icon-trophy {
    width: 24px;
    height: 24px;
    color: #fff;
    animation: trophy-pulse 2s ease-in-out infinite;
}

@keyframes trophy-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.9; }
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

.rank-badge {
    padding: 4px 12px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}

/* Compact Body */
.rank-body {
    padding: 16px;
}

/* Info Grid - 2x2 Layout */
.rank-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

.info-card {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    transition: all 0.2s ease;
}

.info-card:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: translateY(-1px);
}

/* Rank Card - Spans 2 columns */
.rank-card {
    grid-column: span 2;
    display: flex;
    align-items: center;
    gap: 12px;
}

.medal-circle {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.3);
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
}

.medal-icon {
    width: 28px;
    height: 28px;
    color: #fff;
}

.info-content {
    flex: 1;
    min-width: 0;
}

.info-label {
    display: block;
    color: rgba(255, 255, 255, 0.8);
    font-size: 11px;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    display: block;
    color: #fff;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.2;
}

.rank-level-badge {
    display: inline-block;
    margin-top: 4px;
    padding: 2px 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
}

/* Small Cards */
.position-card, .points-card, .target-card {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.card-icon {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-icon .icon {
    width: 20px;
    height: 20px;
    color: #fff;
}

.position-value {
    font-size: 20px !important;
}

/* Compact Progress */
.rank-progress-compact {
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
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
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
    .rank-info-grid {
        grid-template-columns: 1fr;
    }

    .rank-card {
        grid-column: span 1;
    }

    .action-links {
        flex-direction: column;
    }

    .action-link {
        flex-direction: row;
        justify-content: center;
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
