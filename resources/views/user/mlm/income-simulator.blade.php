@extends('layouts.user-v4')

@section('title', 'จำลองรายได้ MLM - เครื่องมือวางแผนรายได้')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="incomeSimulator()" x-init="init()">

    {{-- ── หัวข้อ (Hero) ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:24px;"><i class="fas fa-calculator" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">💰 จำลองรายได้ MLM</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">วางแผนและคาดการณ์รายได้ของคุณอย่างแม่นยำ • ใช้ค่าจริงจากระบบ MLM</div>
            </div>
            <a href="{{ route('user.dashboard') }}" class="tp-icon-btn" title="กลับหน้าหลัก"><i class="fas fa-arrow-left"></i></a>
        </div>
    </div>

    {{-- ── การตั้งค่าระบบ MLM (อ่านอย่างเดียว) ──────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
            <div class="tp-section-h">⚙️ การตั้งค่าระบบ MLM</div>
            <button @click="showSettingsHelp = !showSettingsHelp" class="tp-btn tp-btn-sm">
                <i class="fas fa-question-circle"></i>
                <span x-text="showSettingsHelp ? 'ซ่อนคำอธิบาย' : 'ดูคำอธิบาย'"></span>
            </button>
        </div>

        {{-- Settings Grid --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;" x-show="settings">
            <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">🔢 PV Rate</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:6px; color:var(--deep1);" x-text="settings ? (settings.global_pv_rate || '1.00') : '1.00'"></div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">PV ต่อ 1 บาท</div>
            </div>

            <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">📊 Unilevel Depth</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:6px; color:#5689b8;" x-text="settings ? (settings.unilevel_max_depth || '10') : '10'"></div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">ชั้นสูงสุด</div>
            </div>

            <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">💰 Binary Commission</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:6px; color:#5aa07e;">฿<span x-text="settings ? (settings.binary_pair_commission || '0') : '0'"></span></div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">ต่อ 1 คู่</div>
            </div>

            <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">🔄 Pairing Type</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:6px; color:#e0a52e;" x-text="settings ? (settings.binary_pairing_type || '1:1') : '1:1'"></div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">แบบจับคู่</div>
            </div>
        </div>

        {{-- Help Text --}}
        <div x-show="showSettingsHelp" x-transition style="margin-top:14px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
            <div style="font-size:13px; font-weight:600; margin-bottom:8px;">💡 คำอธิบาย: ค่าเหล่านี้มาจากการตั้งค่าจริงของระบบ MLM</div>
            <ul style="font-size:12.5px; color:var(--ink2); line-height:1.8; padding-left:18px; margin:0;">
                <li><strong>PV Rate:</strong> อัตราแปลงยอดขายเป็น PV (Personal Volume)</li>
                <li><strong>Unilevel Depth:</strong> ระบบจะคำนวณค่าคอมมิชชั่นลึกสูงสุดกี่ชั้น</li>
                <li><strong>Binary Commission:</strong> ค่าคอมมิชชั่นที่ได้ต่อการจับคู่ 1 คู่</li>
                <li><strong>Pairing Type:</strong> สัดส่วนการจับคู่ระหว่างขาซ้าย-ขวา (1:1 หรือ 2:1)</li>
            </ul>
        </div>
    </div>

    {{-- ── แผงป้อนข้อมูล (Input Panel) ──────────────────────── --}}
    <div class="tp-card" style="padding:18px;">

        {{-- Rank Selection --}}
        <div style="margin-bottom:18px; padding:16px; border-radius:14px; box-shadow:var(--inset-sm);
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent2) 14%, transparent), transparent 70%);">
            <label style="display:flex; align-items:center; flex-wrap:wrap; gap:8px; font-size:13px; font-weight:700; margin-bottom:12px;">
                <span class="tp-tile" style="width:30px; height:30px; border-radius:10px; font-size:15px;">⭐</span>
                ระดับยศของคุณ (Rank)
                <span style="font-size:11px; font-weight:400; color:var(--ink2); margin-left:auto;">• ไม่บังคับ - ถ้าไม่เลือกจะคำนวณแบบพื้นฐาน</span>
            </label>

            <select x-model="selectedRankId" @change="updateRankInfo()" class="tp-input" style="font-weight:600;">
                <option value="">ไม่เลือกยศ (คำนวณแบบพื้นฐาน)</option>
                <template x-for="rank in ranks" :key="rank.id">
                    <option :value="rank.id"
                            x-text="`${rank.name_th || rank.name} - ${rank.commission_rate}% + ${rank.bonus_multiplier}x โบนัส`">
                    </option>
                </template>
            </select>

            {{-- Rank Info Display --}}
            <div x-show="selectedRank" x-transition style="margin-top:14px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
                    <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm);">
                        <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">ค่าคอมมิชชั่นพื้นฐาน</div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:4px; color:var(--deep1);"><span x-text="selectedRank ? selectedRank.commission_rate : 0"></span>%</div>
                        <div style="font-size:11px; color:var(--ink2); margin-top:2px;">จาก PV ส่วนตัว</div>
                    </div>

                    <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm);">
                        <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">ตัวคูณโบนัส</div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:4px; color:#5689b8;"><span x-text="selectedRank ? selectedRank.bonus_multiplier.toFixed(2) : 1"></span>x</div>
                        <div style="font-size:11px; color:var(--ink2); margin-top:2px;">คูณ Unilevel + Binary</div>
                    </div>

                    <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm);">
                        <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">ระดับยศ</div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:4px; color:#e0a52e;"><span x-text="selectedRank ? selectedRank.level : 1"></span> / 5</div>
                        <div style="font-size:11px; color:var(--ink2); margin-top:2px;">Level ปัจจุบัน</div>
                    </div>
                </div>
            </div>

            {{-- No Rank Selected Info --}}
            <div x-show="!selectedRank" x-transition style="margin-top:14px;">
                <div style="display:flex; align-items:flex-start; gap:12px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:15px; background:#5689b8; flex-shrink:0;">💡</span>
                    <div style="font-size:13px; color:var(--ink2);">
                        <strong style="color:var(--ink);">ไม่ได้เลือกยศ:</strong> จะคำนวณรายได้แบบพื้นฐาน
                        <div style="margin-top:5px;">
                            • ไม่มีค่าคอมมิชชั่นส่วนตัว (Personal Commission)<br>
                            • Unilevel และ Binary จะไม่มีตัวคูณโบนัสจากยศ (multiplier = 1.0x)
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Grid --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
            {{-- Personal Sales --}}
            <div>
                <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700; margin-bottom:7px;">
                    <span class="tp-tile" style="width:24px; height:24px; border-radius:8px; font-size:12px;">💵</span>
                    ยอดขายของคุณ
                    <button @click="showTooltip('personal')" class="tp-icon-btn" style="margin-left:auto; width:28px; height:28px;" title="คำอธิบาย"><i class="fas fa-info-circle" style="font-size:12px;"></i></button>
                </label>
                <input type="number" x-model.number="inputs.personalSales" step="1000" min="0" class="tp-input" style="font-weight:600;">
                <div style="font-size:11px; color:var(--ink2); margin-top:4px;">บาท/เดือน</div>
            </div>

            {{-- Team Size --}}
            <div>
                <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700; margin-bottom:7px;">
                    <span class="tp-tile" style="width:24px; height:24px; border-radius:8px; font-size:12px;">👥</span>
                    จำนวนสมาชิกในทีม
                    <button @click="showTooltip('team')" class="tp-icon-btn" style="margin-left:auto; width:28px; height:28px;" title="คำอธิบาย"><i class="fas fa-info-circle" style="font-size:12px;"></i></button>
                </label>
                <input type="number" x-model.number="inputs.teamSize" step="1" min="0" class="tp-input" style="font-weight:600;">
                <div style="font-size:11px; color:var(--ink2); margin-top:4px;">คน</div>
            </div>

            {{-- Team Avg Sales --}}
            <div>
                <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700; margin-bottom:7px;">
                    <span class="tp-tile" style="width:24px; height:24px; border-radius:8px; font-size:12px;">🛒</span>
                    ยอดขายเฉลี่ยต่อคน
                    <button @click="showTooltip('avg')" class="tp-icon-btn" style="margin-left:auto; width:28px; height:28px;" title="คำอธิบาย"><i class="fas fa-info-circle" style="font-size:12px;"></i></button>
                </label>
                <input type="number" x-model.number="inputs.teamAvgSales" step="500" min="0" class="tp-input" style="font-weight:600;">
                <div style="font-size:11px; color:var(--ink2); margin-top:4px;">บาท/คน/เดือน</div>
            </div>

            {{-- Months --}}
            <div>
                <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700; margin-bottom:7px;">
                    <span class="tp-tile" style="width:24px; height:24px; border-radius:8px; font-size:12px;">📅</span>
                    ระยะเวลา
                    <button @click="showTooltip('months')" class="tp-icon-btn" style="margin-left:auto; width:28px; height:28px;" title="คำอธิบาย"><i class="fas fa-info-circle" style="font-size:12px;"></i></button>
                </label>
                <input type="number" x-model.number="inputs.months" step="1" min="1" max="60" class="tp-input" style="font-weight:600;">
                <div style="font-size:11px; color:var(--ink2); margin-top:4px;">เดือน (1-60)</div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div style="margin-top:18px; display:flex; flex-wrap:wrap; gap:12px;">
            <button @click="startSimulation()" :disabled="isRunning" class="tp-btn tp-btn-primary" style="flex:1; min-width:200px; justify-content:center;">
                <i class="fas" :class="isRunning ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                <span x-text="isRunning ? 'กำลังจำลอง...' : '▶️ เริ่มจำลอง'"></span>
            </button>

            <button @click="resetSimulation()" class="tp-btn">
                🔄 รีเซ็ต
            </button>

            <button @click="printResults()" x-show="showResults" class="tp-btn" style="color:#fff; background:#5aa07e; border-color:#5aa07e;">
                🖨️ พิมพ์
            </button>
        </div>

        {{-- Quick Tips --}}
        <div style="margin-top:16px; display:flex; align-items:flex-start; gap:12px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
            <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:15px; flex-shrink:0;">💡</span>
            <div style="font-size:12.5px; color:var(--ink2);">
                <strong style="color:var(--deep1);">เคล็ดลับ:</strong>
                ลองปรับค่าต่างๆ เพื่อดูว่าแต่ละปัจจัยส่งผลต่อรายได้อย่างไร •
                เลือกยศให้ตรงกับเป้าหมาย •
                ยอดขายทีมมีผลมากต่อ Binary และ Unilevel
            </div>
        </div>
    </div>

    {{-- ── Animation Container ──────────────────────────────── --}}
    <div x-show="showAnimation" x-transition style="display:flex; flex-direction:column; gap:18px;">

        {{-- Current Month Display --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="text-align:center; padding:32px 24px;
                        background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 20%, transparent), color-mix(in srgb, var(--accent2) 14%, transparent) 80%);">
                <div style="font-size:16px; font-weight:700; margin-bottom:6px; color:var(--ink2);">กำลังจำลอง...</div>
                <div class="tp-num" style="font-size:clamp(40px,9vw,68px); font-weight:800; color:var(--deep1);">เดือนที่ <span x-text="currentMonth"></span></div>
                <div style="font-size:16px; color:var(--ink2); margin-top:6px;">จาก <span x-text="inputs.months"></span> เดือน</div>
            </div>
        </div>

        {{-- Real-time Income Display --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
            {{-- Personal Commission --}}
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:10px; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:10px;">
                    <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:15px; background:#5aa07e;">📦</span>
                    คอมมิชชั่นส่วนตัว
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800; color:#5aa07e;">฿<span x-text="formatNumber(currentIncome.personal)"></span></div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;" x-text="selectedRank ? `จาก PV × ${selectedRank.commission_rate}%` : 'ต้องมียศ'"></div>
            </div>

            {{-- Team Commission --}}
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:10px; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:10px;">
                    <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:15px; background:#5689b8;">👥</span>
                    คอมมิชชั่นจากทีม
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800; color:#5689b8;">฿<span x-text="formatNumber(currentIncome.team)"></span></div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">Unilevel + Binary</div>
            </div>

            {{-- Total Income --}}
            <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm);
                        background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
                <div style="display:flex; align-items:center; gap:10px; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:10px;">
                    <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:15px;">💰</span>
                    รวมคอมมิชชั่น
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800; color:var(--deep1);">฿<span x-text="formatNumber(currentIncome.total)"></span></div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">รวมทั้งหมดเดือนนี้</div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; justify-content:space-between; font-size:12.5px; font-weight:700; margin-bottom:8px;">
                <span style="color:var(--ink2);">ความคืบหน้า</span>
                <span class="tp-num" x-text="`${progress}%`"></span>
            </div>
            <div style="height:18px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                <div style="height:100%; border-radius:20px; background:linear-gradient(90deg, var(--accent1), var(--accent2)); transition:width .5s ease;"
                     :style="`width: ${progress}%`"></div>
            </div>
        </div>
    </div>

    {{-- ── Results Container ────────────────────────────────── --}}
    <div x-show="showResults" x-transition style="display:flex; flex-direction:column; gap:18px;">

        {{-- Strategic Insights --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:14px;">🎯 คำแนะนำเชิงกลยุทธ์</div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:12px;" x-show="insights.length > 0">
                <template x-for="(insight, index) in insights" :key="index">
                    <div style="padding:14px; border-radius:14px; box-shadow:var(--inset-sm);"
                         :style="`border-left:4px solid ${
                             insight.type === 'success' ? '#5aa07e' :
                             insight.type === 'info' ? '#5689b8' :
                             insight.type === 'warning' ? '#e0a52e' :
                             'var(--accent1)'
                         };`">
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <div style="font-size:22px; flex-shrink:0;" x-text="insight.icon"></div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:700; font-size:13.5px; margin-bottom:3px;" x-text="insight.title"></div>
                                <div style="font-size:12.5px; color:var(--ink2);" x-text="insight.message"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Key Metrics --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
            <div class="tp-card" style="padding:18px; text-align:center;">
                <div style="font-size:36px; margin-bottom:8px;">💎</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; color:#e0a52e;">฿<span x-text="formatNumber(metrics.avgMonthly)"></span></div>
                <div style="font-size:12px; font-weight:600; color:var(--ink2); margin-top:4px;">รายได้เฉลี่ย/เดือน</div>
            </div>

            <div class="tp-card" style="padding:18px; text-align:center;">
                <div style="font-size:36px; margin-bottom:8px;">🎯</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; color:#5aa07e;">฿<span x-text="formatNumber(metrics.bestMonth)"></span></div>
                <div style="font-size:12px; font-weight:600; color:var(--ink2); margin-top:4px;">เดือนที่ได้สูงสุด</div>
            </div>

            <div class="tp-card" style="padding:18px; text-align:center;">
                <div style="font-size:36px; margin-bottom:8px;">⚡</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; color:#5689b8;"><span x-text="formatNumber(metrics.growthRate)"></span>%</div>
                <div style="font-size:12px; font-weight:600; color:var(--ink2); margin-top:4px;">อัตราการเติบโต</div>
            </div>

            <div class="tp-card" style="padding:18px; text-align:center;">
                <div style="font-size:36px; margin-bottom:8px;">🚀</div>
                <div class="tp-num" style="font-size:24px; font-weight:800; color:var(--deep1);">฿<span x-text="formatNumber(metrics.yearlyProjection)"></span></div>
                <div style="font-size:12px; font-weight:600; color:var(--ink2); margin-top:4px;">คาดการณ์รายได้/ปี</div>
            </div>
        </div>

        {{-- Monthly Breakdown Table --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:18px 20px;">
                <div class="tp-section-h">📊 สรุปรายได้แต่ละเดือน</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">เดือน</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">ยอดขายตัวเอง</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">ยอดขายทีม</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">Com.ส่วนตัว</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">Unilevel</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">Binary</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">รวม</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">สะสม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(month, index) in monthlyData" :key="index">
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:12px 16px; font-weight:600; color:var(--ink); white-space:nowrap;">เดือน <span x-text="month.month"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; color:var(--ink2); white-space:nowrap;">฿<span x-text="formatNumber(month.personalSales)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; color:var(--ink2); white-space:nowrap;">฿<span x-text="formatNumber(month.teamSales)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; font-weight:600; color:#5aa07e; white-space:nowrap;">฿<span x-text="formatNumber(month.personal)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; font-weight:600; color:#5689b8; white-space:nowrap;">฿<span x-text="formatNumber(month.unilevel)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; font-weight:600; color:var(--accent1); white-space:nowrap;">฿<span x-text="formatNumber(month.binary)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; font-weight:800; color:var(--deep1); white-space:nowrap;">฿<span x-text="formatNumber(month.total)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; color:var(--ink2); white-space:nowrap;">฿<span x-text="formatNumber(month.accumulated)"></span></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid color-mix(in srgb, var(--ink2) 20%, transparent); box-shadow:var(--inset-sm); font-weight:800;">
                            <td colspan="6" style="padding:14px 16px; text-align:right; color:var(--ink);">รวมทั้งหมด:</td>
                            <td class="tp-num" style="padding:14px 16px; text-align:right; color:var(--deep1);">฿<span x-text="formatNumber(metrics.grandTotal)"></span></td>
                            <td style="padding:14px 16px;"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Charts (Chart.js — กราฟจริงสำหรับฟังก์ชันหลัก) --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px;">
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:14px;">📈 กราฟการเติบโตของรายได้</div>
                <div style="position:relative; height:300px;">
                    <canvas id="income-chart"></canvas>
                </div>
            </div>

            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:14px;">🥧 สัดส่วนรายได้</div>
                <div style="position:relative; height:300px;">
                    <canvas id="breakdown-chart"></canvas>
                </div>
            </div>
        </div>

        {{-- Call to Action --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="text-align:center; padding:28px 24px;
                        background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), color-mix(in srgb, var(--accent2) 12%, transparent) 80%);">
                <h3 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0 0 8px;">🎉 พร้อมเริ่มต้นหรือยัง?</h3>
                <div style="font-size:14px; color:var(--ink2); margin-bottom:18px;">นี่คือโอกาสของคุณที่จะสร้างรายได้แบบ Passive Income</div>
                <div style="display:flex; flex-wrap:wrap; gap:12px; justify-content:center;">
                    @if(\Illuminate\Support\Facades\Route::has('user.mlm.dashboard'))
                        <a href="{{ route('user.mlm.dashboard') }}" class="tp-btn tp-btn-primary">เริ่มต้นเลย!</a>
                    @endif
                    <button @click="startSimulation()" class="tp-btn">จำลองอีกครั้ง</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tooltip Modal ────────────────────────────────────── --}}
    <div x-show="tooltip.show" x-transition @click.outside="tooltip.show = false"
         style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:16px;"
         x-cloak>
        <div @click="tooltip.show = false" style="position:absolute; inset:0; background:rgba(0,0,0,.5); backdrop-filter:blur(4px);"></div>

        <div class="tp-card" style="position:relative; max-width:520px; width:100%; padding:22px;">
            <button @click="tooltip.show = false" class="tp-icon-btn" style="position:absolute; top:14px; right:14px;" title="ปิด"><i class="fas fa-times"></i></button>

            <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:22px;">💡</span>
                <h3 class="tp-num" style="font-size:18px; font-weight:800; margin:0;" x-text="tooltip.title"></h3>
            </div>

            <div style="font-size:13.5px; color:var(--ink2); line-height:1.7;" x-html="tooltip.content"></div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/**
 * Income Simulator Component - จำลองรายได้ MLM
 * ใช้ Alpine.js + Chart.js (Theme V4)
 *
 * @returns {object} Alpine component
 */
function incomeSimulator() {
    return {
        // ===== State =====
        settings: null,
        ranks: [],
        selectedRankId: '',
        selectedRank: null,

        inputs: {
            personalSales: 10000,
            teamSize: 10,
            teamAvgSales: 5000,
            months: 12,
        },

        isRunning: false,
        showAnimation: false,
        showResults: false,
        showSettingsHelp: false,

        currentMonth: 0,
        progress: 0,

        currentIncome: {
            personal: 0,
            team: 0,
            total: 0,
        },

        monthlyData: [],

        metrics: {
            avgMonthly: 0,
            bestMonth: 0,
            growthRate: 0,
            yearlyProjection: 0,
            grandTotal: 0,
        },

        insights: [],

        tooltip: {
            show: false,
            title: '',
            content: '',
        },

        incomeChart: null,
        breakdownChart: null,

        // ===== Lifecycle =====

        /**
         * เริ่มต้น component
         */
        async init() {
            await this.loadSettings();
            await this.loadRanks();
        },

        // ===== Methods =====

        /**
         * โหลดการตั้งค่า MLM
         */
        async loadSettings() {
            try {
                const response = await fetch('{{ route("user.mlm.settings") }}');
                const data = await response.json();
                this.settings = data.settings;
            } catch (error) {
                console.error('Failed to load settings:', error);
                this.settings = this.getDefaultSettings();
            }
        },

        /**
         * โหลด Ranks
         */
        async loadRanks() {
            try {
                const response = await fetch('/api/v1/ranks');
                const data = await response.json();
                this.ranks = data.data || data;
            } catch (error) {
                console.error('Failed to load ranks:', error);
                this.ranks = [];
            }
        },

        /**
         * อัพเดทข้อมูล Rank ที่เลือก
         */
        updateRankInfo() {
            if (!this.selectedRankId) {
                this.selectedRank = null;
                return;
            }

            this.selectedRank = this.ranks.find(r => r.id == this.selectedRankId);
        },

        /**
         * เริ่มการจำลอง
         */
        async startSimulation() {
            if (this.isRunning) return;

            this.isRunning = true;
            this.showAnimation = true;
            this.showResults = false;
            this.monthlyData = [];
            this.currentMonth = 0;
            this.progress = 0;

            // รัน simulation แต่ละเดือน
            for (let month = 1; month <= this.inputs.months; month++) {
                this.currentMonth = month;
                await this.simulateMonth(month);
                this.updateProgress(month, this.inputs.months);
                await this.sleep(800);
            }

            this.isRunning = false;
            this.showAnimation = false;
            this.showResults = true;

            this.calculateMetrics();
            this.generateInsights();

            // สร้าง charts
            await this.$nextTick();
            this.createCharts();
        },

        /**
         * จำลองรายได้ 1 เดือน
         * ⚠️ IMPORTANT: เรียก API เพื่อคำนวณ commission
         * ใช้ logic เดียวกับ Admin Calculator เพื่อความถูกต้อง
         */
        async simulateMonth(month) {
            // คำนวณการเติบโตของทีม (3% ต่อเดือน)
            const growthFactor = 1 + (month * 0.03);
            const currentTeamSize = Math.floor(this.inputs.teamSize * growthFactor);
            const totalTeamSales = currentTeamSize * this.inputs.teamAvgSales;

            // คำนวณ PV สำหรับ display
            const pvRate = parseFloat(this.settings.global_pv_rate || 1);
            const personalPv = this.inputs.personalSales * pvRate;
            const teamPv = totalTeamSales * pvRate;

            // ตัวคูณจาก Rank
            const rankMultiplier = this.selectedRank ? parseFloat(this.selectedRank.bonus_multiplier || 1) : 1;
            const rankCommissionRate = this.selectedRank ? parseFloat(this.selectedRank.commission_rate || 0) : 0;

            // ดึง Binary left ratio จาก settings
            const leftRatio = parseFloat(this.settings.binary_default_left_ratio || 50);

            // คำนวณ Binary pairs (ประมาณ)
            const leftLegPv = teamPv * (leftRatio / 100);
            const rightLegPv = teamPv * ((100 - leftRatio) / 100);
            const weakerLegPv = Math.min(leftLegPv, rightLegPv);
            const pairingType = this.settings.binary_pairing_type || '1:1';
            const pairRatio = pairingType === '2:1' ? 2 : 1;
            const estimatedPairs = Math.floor(weakerLegPv / pairRatio);

            // เรียก API เพื่อคำนวณ commission (ใช้ logic เดียวกับ Admin)
            let unilevelCommission = 0;
            let binaryCommission = 0;

            try {
                const response = await fetch('{{ route("user.mlm.preview-calculation") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        sales_amount: totalTeamSales,
                        team_size: currentTeamSize,
                        member_depth: parseInt(this.settings.unilevel_max_depth || 10),
                        binary_pairs: estimatedPairs,
                        left_ratio: leftRatio,
                        rank_multiplier: rankMultiplier,
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    unilevelCommission = result.calculation.unilevel_commission || 0;
                    binaryCommission = result.calculation.binary_commission || 0;
                }
            } catch (error) {
                console.error('API Error:', error);
                // Fallback: ไม่มี commission ถ้า API ล้มเหลว
            }

            // คำนวณ Personal Commission
            const personalCommission = (personalPv * rankCommissionRate) / 100;

            // รวม (API จัดการ overpay protection แล้ว)
            const totalIncome = personalCommission + unilevelCommission + binaryCommission;

            // อัพเดท current income (สำหรับ animation)
            this.currentIncome.personal = personalCommission;
            this.currentIncome.team = unilevelCommission + binaryCommission;
            this.currentIncome.total = totalIncome;

            // เก็บข้อมูลเดือนนี้
            const accumulated = this.monthlyData.reduce((sum, m) => sum + m.total, 0) + totalIncome;

            this.monthlyData.push({
                month,
                personalSales: this.inputs.personalSales,
                teamSales: totalTeamSales,
                teamSize: currentTeamSize,
                personalPv,
                teamPv,
                unilevel: unilevelCommission,
                binary: binaryCommission,
                personal: personalCommission,
                rankMultiplier,
                total: totalIncome,
                accumulated
            });
        },

        /**
         * อัพเดท progress bar
         */
        updateProgress(current, total) {
            this.progress = Math.floor((current / total) * 100);
        },

        /**
         * คำนวณ metrics
         */
        calculateMetrics() {
            const total = this.monthlyData.reduce((sum, m) => sum + m.total, 0);
            const avg = total / this.monthlyData.length;
            const best = Math.max(...this.monthlyData.map(m => m.total));

            let growthRate = 0;
            if (this.monthlyData.length > 1) {
                const first = this.monthlyData[0].total;
                const last = this.monthlyData[this.monthlyData.length - 1].total;
                growthRate = first > 0 ? ((last - first) / first) * 100 : 0;
            }

            this.metrics = {
                avgMonthly: avg,
                bestMonth: best,
                growthRate: growthRate,
                yearlyProjection: avg * 12,
                grandTotal: total,
            };
        },

        /**
         * สร้างคำแนะนำเชิงกลยุทธ์
         */
        generateInsights() {
            this.insights = [];

            // Insight 1: Rank ที่แนะนำ
            if (!this.selectedRank) {
                this.insights.push({
                    type: 'warning',
                    icon: '⭐',
                    title: 'แนะนำให้เลือกยศ',
                    message: 'คุณจะได้รับคอมมิชชั่นส่วนตัวและตัวคูณโบนัสเพิ่มขึ้น ทำให้รายได้สูงขึ้นมาก'
                });
            } else {
                const avgPersonal = this.monthlyData.reduce((sum, m) => sum + m.personal, 0) / this.monthlyData.length;
                const percentOfTotal = (avgPersonal / this.metrics.avgMonthly) * 100;

                if (percentOfTotal < 20) {
                    this.insights.push({
                        type: 'tip',
                        icon: '💼',
                        title: 'เพิ่มยอดขายส่วนตัว',
                        message: `คอมมิชชั่นส่วนตัวของคุณคิดเป็น ${percentOfTotal.toFixed(0)}% เท่านั้น ลองเพิ่มยอดขายส่วนตัวเพื่อรายได้ที่มั่นคงกว่า`
                    });
                }
            }

            // Insight 2: Team Size
            const teamGrowth = this.monthlyData.length > 1
                ? ((this.monthlyData[this.monthlyData.length - 1].teamSize - this.monthlyData[0].teamSize) / this.monthlyData[0].teamSize) * 100
                : 0;

            if (teamGrowth > 30) {
                this.insights.push({
                    type: 'success',
                    icon: '🚀',
                    title: 'การเติบโตดีเยี่ยม',
                    message: `ทีมของคุณเติบโต ${teamGrowth.toFixed(0)}%! เป็นสัญญาณที่ดีมาก รักษาจังหวะนี้ไว้`
                });
            } else if (teamGrowth < 10) {
                this.insights.push({
                    type: 'info',
                    icon: '👥',
                    title: 'โฟกัสการสร้างทีม',
                    message: 'ทีมเติบโตช้า ลองเพิ่มกิจกรรมสร้างทีมและฝึกอบรมสมาชิก เพื่อเร่งการเติบโต'
                });
            }

            // Insight 3: Binary vs Unilevel
            const totalBinary = this.monthlyData.reduce((sum, m) => sum + m.binary, 0);
            const totalUnilevel = this.monthlyData.reduce((sum, m) => sum + m.unilevel, 0);

            if (totalBinary > totalUnilevel * 1.5) {
                this.insights.push({
                    type: 'success',
                    icon: '⚖️',
                    title: 'Binary ทรงตัวดี',
                    message: 'ขาซ้าย-ขวาของคุณสมดุลดี ทำให้ได้ Binary สูง รักษาความสมดุลนี้ไว้'
                });
            } else if (totalUnilevel > totalBinary * 2) {
                this.insights.push({
                    type: 'tip',
                    icon: '🔄',
                    title: 'ปรับสมดุล Binary',
                    message: 'ลองวางทีมให้สมดุลระหว่างขาซ้าย-ขวา เพื่อเพิ่มรายได้จาก Binary'
                });
            }

            // Insight 4: Growth Strategy
            if (this.metrics.growthRate < 5) {
                this.insights.push({
                    type: 'warning',
                    icon: '📈',
                    title: 'เร่งการเติบโต',
                    message: 'อัตราการเติบโตต่ำ ลองเพิ่มกิจกรรมการตลาด ฝึกอบรมทีม และสร้างแรงจูงใจ'
                });
            }

            // Insight 5: Yearly Projection
            if (this.metrics.yearlyProjection > 500000) {
                this.insights.push({
                    type: 'success',
                    icon: '💎',
                    title: 'เป้าหมายระยะยาว',
                    message: `รายได้คาดการณ์ ฿${this.formatNumber(this.metrics.yearlyProjection)}/ปี เยี่ยมมาก! รักษาสม่ำเสมอและวางแผนภาษี`
                });
            }
        },

        /**
         * สร้าง Charts
         */
        createCharts() {
            // Income Growth Chart
            const ctx1 = document.getElementById('income-chart');
            if (this.incomeChart) this.incomeChart.destroy();

            this.incomeChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: this.monthlyData.map(m => `เดือน ${m.month}`),
                    datasets: [{
                        label: 'รายได้รวม',
                        data: this.monthlyData.map(m => m.total),
                        borderColor: 'rgb(147, 51, 234)',
                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                        tension: 0.4,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '฿' + this.formatNumber(value)
                            }
                        }
                    }
                }
            });

            // Breakdown Pie Chart
            const ctx2 = document.getElementById('breakdown-chart');
            if (this.breakdownChart) this.breakdownChart.destroy();

            const totalPersonal = this.monthlyData.reduce((sum, m) => sum + m.personal, 0);
            const totalUnilevel = this.monthlyData.reduce((sum, m) => sum + m.unilevel, 0);
            const totalBinary = this.monthlyData.reduce((sum, m) => sum + m.binary, 0);

            this.breakdownChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Com.ส่วนตัว', 'Unilevel', 'Binary'],
                    datasets: [{
                        data: [totalPersonal, totalUnilevel, totalBinary],
                        backgroundColor: [
                            'rgb(34, 197, 94)',
                            'rgb(59, 130, 246)',
                            'rgb(99, 102, 241)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    return context.label + ': ฿' + this.formatNumber(context.parsed);
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * รีเซ็ต
         */
        resetSimulation() {
            this.monthlyData = [];
            this.isRunning = false;
            this.showAnimation = false;
            this.showResults = false;
            this.currentMonth = 0;
            this.progress = 0;
            this.currentIncome = { personal: 0, team: 0, total: 0 };
            this.metrics = { avgMonthly: 0, bestMonth: 0, growthRate: 0, yearlyProjection: 0, grandTotal: 0 };
            this.insights = [];
        },

        /**
         * พิมพ์ผลลัพธ์
         */
        printResults() {
            window.print();
        },

        /**
         * แสดง tooltip
         */
        showTooltip(type) {
            const tooltips = {
                personal: {
                    title: 'ยอดขายของคุณ',
                    content: '<p style="margin-bottom:8px;"><strong>คือ:</strong> ยอดขายที่คุณทำเองแต่ละเดือน</p>' +
                            '<p style="margin-bottom:8px;"><strong>ใช้เพื่อ:</strong> คำนวณค่าคอมมิชชั่นส่วนตัว (Personal Commission) ตามเปอร์เซ็นต์ของยศที่เลือก</p>' +
                            '<p style="color:var(--deep1);"><strong>💡 เคล็ดลับ:</strong> ยิ่งยอดขายส่วนตัวสูง รายได้ก็จะมั่นคงและไม่ขึ้นกับทีมเพียงอย่างเดียว</p>'
                },
                team: {
                    title: 'จำนวนสมาชิกในทีม',
                    content: '<p style="margin-bottom:8px;"><strong>คือ:</strong> จำนวนสมาชิกทั้งหมดในทีมของคุณ (ทุกชั้น)</p>' +
                            '<p style="margin-bottom:8px;"><strong>ใช้เพื่อ:</strong> คำนวณยอดขายรวมของทีม และค่าคอมมิชชั่น Unilevel + Binary</p>' +
                            '<p style="color:var(--deep1);"><strong>💡 เคล็ดลับ:</strong> ทีมจะเติบโต 3% ต่อเดือนโดยอัตโนมัติในการจำลอง</p>'
                },
                avg: {
                    title: 'ยอดขายเฉลี่ยต่อคน',
                    content: '<p style="margin-bottom:8px;"><strong>คือ:</strong> ยอดขายเฉลี่ยที่แต่ละคนในทีมทำได้ต่อเดือน</p>' +
                            '<p style="margin-bottom:8px;"><strong>ใช้เพื่อ:</strong> คำนวณยอดขายรวมทั้งทีม = จำนวนคน × ยอดขายเฉลี่ย</p>' +
                            '<p style="color:var(--deep1);"><strong>💡 เคล็ดลับ:</strong> ค่านี้สะท้อนคุณภาพทีม ฝึกอบรมทีมให้ขายเก่ง จะเพิ่มรายได้ทั้งทีม</p>'
                },
                months: {
                    title: 'ระยะเวลา',
                    content: '<p style="margin-bottom:8px;"><strong>คือ:</strong> จำนวนเดือนที่ต้องการจำลอง (1-60 เดือน)</p>' +
                            '<p style="margin-bottom:8px;"><strong>ใช้เพื่อ:</strong> ดูการเติบโตระยะยาว และวางแผนเป้าหมาย</p>' +
                            '<p style="color:var(--deep1);"><strong>💡 เคล็ดลับ:</strong> แนะนำ 12-24 เดือนเพื่อดูภาพชัดเจน</p>'
                },
            };

            if (tooltips[type]) {
                this.tooltip = {
                    show: true,
                    ...tooltips[type]
                };
            }
        },

        /**
         * Format number
         */
        formatNumber(num) {
            return Math.floor(num).toLocaleString();
        },

        /**
         * Sleep
         */
        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        /**
         * Default settings (fallback)
         */
        getDefaultSettings() {
            return {
                global_pv_rate: 1,
                unilevel_max_depth: 10,
                unilevel_levels: [],
                binary_pair_commission: 100,
                binary_pairing_type: '1:1',
                binary_max_pairs_per_day: null,
                binary_max_commission_per_day: null,
                overpay_protection_enabled: true,
                max_commission_percentage: 50,
            };
        },
    };
}

// Export for global use
window.incomeSimulator = incomeSimulator;
</script>
@endpush
@endsection
