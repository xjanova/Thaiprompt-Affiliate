<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPIX Whitepaper v{{ $data['version'] }}</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', 'Kanit', sans-serif;
            line-height: 1.6;
            color: #1a202c;
            font-size: 11pt;
        }

        .page {
            page-break-after: always;
            padding: 40px;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        /* Cover Page */
        .cover {
            text-align: center;
            padding: 80px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            width: 200px;
            height: 200px;
            margin: 0 auto 40px;
        }

        .cover h1 {
            font-size: 48pt;
            font-weight: 900;
            margin-bottom: 20px;
        }

        .cover .subtitle {
            font-size: 24pt;
            margin-bottom: 40px;
        }

        .cover .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 60px;
        }

        .stat-box {
            padding: 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .stat-box .value {
            font-size: 28pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-box .label {
            font-size: 12pt;
        }

        /* Headers */
        h2 {
            font-size: 24pt;
            font-weight: 700;
            color: #667eea;
            margin: 30px 0 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        h3 {
            font-size: 18pt;
            font-weight: 600;
            color: #764ba2;
            margin: 20px 0 15px;
        }

        h4 {
            font-size: 14pt;
            font-weight: 600;
            color: #1a202c;
            margin: 15px 0 10px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f7fafc;
            font-weight: 600;
            color: #667eea;
        }

        /* Lists */
        ul, ol {
            margin: 15px 0 15px 30px;
        }

        li {
            margin: 8px 0;
        }

        /* Box */
        .box {
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            background: #f7fafc;
            border-left: 4px solid #667eea;
        }

        .box.success {
            background: #f0fff4;
            border-left-color: #48bb78;
        }

        .box.warning {
            background: #fffbeb;
            border-left-color: #f59e0b;
        }

        .box.error {
            background: #fef2f2;
            border-left-color: #ef4444;
        }

        /* Grid */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px 0;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 10pt;
            color: #718096;
        }

        /* Print Specific */
        @media print {
            .page {
                page-break-after: always;
            }

            .no-break {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    {{-- Cover Page --}}
    <div class="page cover">
        <div>
            <img src="{{ public_path('images/tpix-logo.svg') }}" alt="TPIX Logo" class="logo">

            <h1>TPIX</h1>

            <div class="subtitle">
                Native Cryptocurrency<br>
                Blockchain ของตัวเอง สำหรับระบบนิเวศน์ Thaiprompt Affiliate
            </div>

            <div class="stats">
                <div class="stat-box">
                    <div class="value">7B</div>
                    <div class="label">Total Supply</div>
                </div>
                <div class="stat-box">
                    <div class="value">2s</div>
                    <div class="label">Block Time</div>
                </div>
                <div class="stat-box">
                    <div class="value">1,500</div>
                    <div class="label">TPS</div>
                </div>
                <div class="stat-box">
                    <div class="value">IBFT</div>
                    <div class="label">Consensus</div>
                </div>
            </div>

            <div style="margin-top: 60px;">
                <p>Version {{ $data['version'] }} | {{ $data['date'] }}</p>
            </div>
        </div>
    </div>

    {{-- Table of Contents --}}
    <div class="page">
        <h2>สารบัญ</h2>

        <ol style="font-size: 12pt; line-height: 2;">
            <li>ภาพรวมโครงการ</li>
            <li>ปัญหาและแนวทางแก้ไข</li>
            <li>เทคโนโลยีและสถาปัตยกรรม</li>
            <li>ระบบนิเวศน์ TPIX</li>
            <li>โทเค็นโนมิกส์</li>
            <li>Decentralized Exchange (DEX)</li>
            <li>Use Cases</li>
            <li>Roadmap</li>
            <li>ทีมและพันธมิตร</li>
            <li>สรุป</li>
        </ol>
    </div>

    {{-- Overview --}}
    <div class="page">
        <h2>1. ภาพรวมโครงการ</h2>

        <p style="font-size: 12pt; margin-bottom: 20px;">
            <strong>TPIX (Thaiprompt Affiliate Blockchain)</strong> เป็นระบบ blockchain ที่พัฒนาขึ้นเฉพาะ
            สำหรับระบบนิเวศน์ของ Thaiprompt Affiliate โดยมีเหรียญ <strong>TPIX</strong> เป็น
            <strong>Native Coin</strong> หลักของระบบ
        </p>

        <h3>เปรียบเทียบ TPIX กับ Token อื่น</h3>

        <table>
            <thead>
                <tr>
                    <th>คุณสมบัติ</th>
                    <th style="text-align: center;">Token (ERC20)</th>
                    <th style="text-align: center;">TPIX Native Coin</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Gas Fee</td>
                    <td style="text-align: center;">ต้องใช้ ETH/BNB</td>
                    <td style="text-align: center;"><strong>ใช้ TPIX</strong></td>
                </tr>
                <tr>
                    <td>การควบคุม</td>
                    <td style="text-align: center;">ขึ้นกับ Ethereum/BSC</td>
                    <td style="text-align: center;"><strong>ควบคุมเต็มที่</strong></td>
                </tr>
                <tr>
                    <td>ค่าธรรมเนียม</td>
                    <td style="text-align: center;">สูง</td>
                    <td style="text-align: center;"><strong>ต่ำ (กำหนดเอง)</strong></td>
                </tr>
                <tr>
                    <td>ความเร็ว</td>
                    <td style="text-align: center;">12-15 วินาที</td>
                    <td style="text-align: center;"><strong>2 วินาที</strong></td>
                </tr>
                <tr>
                    <td>Smart Contracts</td>
                    <td style="text-align: center;">รองรับ</td>
                    <td style="text-align: center;"><strong>รองรับ</strong></td>
                </tr>
            </tbody>
        </table>

        <h3>จุดเด่นหลัก</h3>

        <div class="grid-3 no-break">
            <div class="box">
                <h4>⚡ Lightning Fast</h4>
                <p>Block time เพียง 2 วินาที รองรับ ~1,500 TPS</p>
            </div>
            <div class="box">
                <h4>🔒 Secure & Decentralized</h4>
                <p>IBFT Consensus (Proof of Stake) Byzantine Fault Tolerant</p>
            </div>
            <div class="box">
                <h4>💰 Fixed Supply</h4>
                <p>Total supply 7B TPIX ไม่สามารถสร้างเพิ่มได้</p>
            </div>
        </div>
    </div>

    {{-- Problem & Solution --}}
    <div class="page">
        <h2>2. ปัญหาและแนวทางแก้ไข</h2>

        <div class="grid-2">
            <div>
                <h3 style="color: #ef4444;">❌ ปัญหาของ Crypto ปัจจุบัน</h3>

                <div class="box error">
                    <h4>1. ค่า Gas Fee สูง</h4>
                    <p>Token บน Ethereum ต้องใช้ ETH เป็นค่าแก๊ส ซึ่งมีราคาแพงมาก (บางครั้งถึง $50-100 ต่อ transaction)</p>
                </div>

                <div class="box error">
                    <h4>2. ไม่สามารถควบคุมระบบได้</h4>
                    <p>Token บน BSC/Ethereum ต้องพึ่งพาเครือข่ายอื่น ไม่สามารถปรับแต่งกฎเกณฑ์หรือค่าธรรมเนียมได้</p>
                </div>

                <div class="box error">
                    <h4>3. ช้าและมีข้อจำกัด</h4>
                    <p>Block time ช้า (12-15 วินาที) และมี TPS จำกัด ทำให้ไม่เหมาะกับการใช้งานจริง</p>
                </div>
            </div>

            <div>
                <h3 style="color: #48bb78;">✅ แนวทางแก้ไขด้วย TPIX</h3>

                <div class="box success">
                    <h4>1. ค่าธรรมเนียมต่ำมาก</h4>
                    <p>ใช้ TPIX เป็นค่าแก๊ส ราคาเพียง ~0.000021 TPIX (~0.42 สตางค์)</p>
                </div>

                <div class="box success">
                    <h4>2. ควบคุมได้เต็มที่</h4>
                    <p>Blockchain ของตัวเอง ปรับแต่งได้ทุกอย่าง จาก consensus mechanism ไปจนถึงค่าธรรมเนียม</p>
                </div>

                <div class="box success">
                    <h4>3. รวดเร็วและ Scalable</h4>
                    <p>Block time เพียง 2 วินาที รองรับ ~1,500 TPS เหมาะกับการใช้งานจริง</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Technology --}}
    <div class="page">
        <h2>3. เทคโนโลยีและสถาปัตยกรรม</h2>

        <h3>Blockchain Specifications</h3>

        <table>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
            </tr>
            @foreach([
                ['Chain ID', '7000'],
                ['Network Name', 'TPIX Network'],
                ['Native Coin', 'TPIX'],
                ['Total Supply', '7,000,000,000'],
                ['Decimals', '18'],
                ['Block Time', '2 seconds'],
                ['Consensus', 'IBFT (Byzantine Fault Tolerant)'],
                ['VM', 'EVM (Ethereum Virtual Machine)'],
                ['TPS', '~1,500 transactions/second'],
            ] as $spec)
            <tr>
                <td>{{ $spec[0] }}</td>
                <td><strong>{{ $spec[1] }}</strong></td>
            </tr>
            @endforeach
        </table>

        <h3>Technology Stack</h3>

        <div class="grid-2">
            @foreach($data['tech_stack'] as $category => $technologies)
            <div class="box">
                <h4>{{ ucfirst(str_replace('_', ' ', $category)) }}</h4>
                <ul style="margin-top: 10px;">
                    @foreach($technologies as $name => $description)
                    <li><strong>{{ $name }}</strong>: {{ $description }}</li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Tokenomics --}}
    <div class="page">
        <h2>5. โทเค็นโนมิกส์</h2>

        <div class="box" style="text-align: center; background: linear-gradient(135deg, #f7fafc 0%, #e6f7ff 100%); padding: 30px;">
            <h3 style="font-size: 36pt; color: #667eea; margin-bottom: 10px;">7,000,000,000</h3>
            <p style="font-size: 14pt;">Total Supply (Fixed)</p>
            <p style="margin-top: 10px;">ไม่สามารถสร้างเพิ่มได้ | Deflationary Economics</p>
        </div>

        <h3>การกระจาย Token</h3>

        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th style="text-align: right;">Percentage</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['tokenomics']['distribution'] as $dist)
                <tr>
                    <td><strong>{{ $dist['name'] }}</strong></td>
                    <td style="text-align: right;"><strong>{{ $dist['percentage'] }}%</strong></td>
                    <td style="text-align: right;">{{ number_format($dist['amount']) }} TPIX</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Use Cases --}}
    <div class="page">
        <h2>7. Use Cases</h2>

        @foreach(array_chunk($data['use_cases'], 2) as $useCaseChunk)
        <div class="grid-2 no-break">
            @foreach($useCaseChunk as $useCase)
            <div class="box">
                <h4>{{ $useCase['title'] }}</h4>
                <p style="margin-bottom: 10px;">{{ $useCase['description'] }}</p>
                <ul style="font-size: 10pt;">
                    @foreach($useCase['features'] as $feature)
                    <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    {{-- Roadmap --}}
    <div class="page">
        <h2>8. Roadmap</h2>

        @foreach($data['roadmap'] as $phase)
        <div class="box no-break">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0;">{{ $phase['phase'] }}</h4>
                <span style="padding: 5px 15px; background: {{ $phase['status'] === 'completed' ? '#dcfce7' : ($phase['status'] === 'in_progress' ? '#dbeafe' : '#f3f4f6') }}; border-radius: 20px; font-size: 10pt; font-weight: bold;">
                    {{ $phase['quarter'] }}
                </span>
            </div>

            <ul>
                @foreach($phase['milestones'] as $milestone)
                <li>{{ $milestone }}</li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>

    {{-- Conclusion --}}
    <div class="page">
        <h2>10. สรุป</h2>

        <p style="font-size: 12pt; line-height: 1.8; margin-bottom: 20px;">
            <strong>TPIX</strong> คือ Native Cryptocurrency ที่มี Blockchain ของตัวเอง
            ซึ่งแก้ปัญหาของ cryptocurrency ปัจจุบันได้อย่างครบถ้วน ไม่ว่าจะเป็นค่าธรรมเนียมที่สูง
            ความช้า หรือการไม่สามารถควบคุมระบบได้เต็มที่
        </p>

        <p style="font-size: 12pt; line-height: 1.8; margin-bottom: 20px;">
            ด้วย <strong>Total Supply 7,000,000,000 TPIX</strong> ที่ถูกกำหนดตายตัว
            <strong>Block Time เพียง 2 วินาที</strong> และ <strong>IBFT Consensus</strong>
            ทำให้ TPIX เหมาะสำหรับการใช้งานจริงในระบบนิเวศน์ Thaiprompt Affiliate
        </p>

        <p style="font-size: 12pt; line-height: 1.8; margin-bottom: 20px;">
            ระบบ DEX, Staking Pools, Token Management และการเชื่อมต่อกับระบบต่าง ๆ
            ทำให้ TPIX เป็นมากกว่า cryptocurrency แต่เป็น <strong>Complete Blockchain Ecosystem</strong>
            ที่พร้อมรองรับการเติบโตในอนาคต
        </p>

        <div class="box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; padding: 40px; margin-top: 40px;">
            <h3 style="color: white; font-size: 20pt; margin-bottom: 20px;">
                พร้อมเป็นส่วนหนึ่งของ TPIX Ecosystem หรือยัง?
            </h3>

            <div class="grid-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 30px;">
                <div>
                    <div style="font-size: 32pt; font-weight: bold;">7B</div>
                    <div style="font-size: 11pt;">Total Supply</div>
                </div>
                <div>
                    <div style="font-size: 32pt; font-weight: bold;">2s</div>
                    <div style="font-size: 11pt;">Block Time</div>
                </div>
                <div>
                    <div style="font-size: 32pt; font-weight: bold;">1,500</div>
                    <div style="font-size: 11pt;">TPS</div>
                </div>
                <div>
                    <div style="font-size: 32pt; font-weight: bold;">∞</div>
                    <div style="font-size: 11pt;">Possibilities</div>
                </div>
            </div>
        </div>

        <div class="footer" style="margin-top: 60px;">
            <p>&copy; {{ date('Y') }} TPIX Blockchain. All rights reserved.</p>
            <p style="margin-top: 10px;">Version {{ $data['version'] }} | {{ $data['date'] }}</p>
            <p style="margin-top: 10px;">Website: tpix.network | Email: support@tpix.com</p>
        </div>
    </div>

</body>
</html>
