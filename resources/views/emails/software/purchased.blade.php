<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ซื้อซอฟต์แวร์สำเร็จ</title>
    <style>
        body {
            font-family: 'Sarabun', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #4F46E5;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4F46E5;
            margin: 0;
            font-size: 28px;
        }
        .success-icon {
            font-size: 60px;
            color: #10B981;
            margin-bottom: 10px;
        }
        .product-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .product-info h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .license-box {
            background-color: #f9fafb;
            border: 2px dashed #4F46E5;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .license-key {
            background-color: #4F46E5;
            color: white;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
            margin: 10px 0;
            word-break: break-all;
        }
        .activation-code {
            background-color: #10B981;
            color: white;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 2px;
            margin: 10px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #4F46E5;
        }
        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
        }
        .download-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s;
        }
        .download-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .instructions {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .instructions h3 {
            color: #1e40af;
            margin-top: 0;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning strong {
            color: #92400e;
        }
        @media only screen and (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>ขอบคุณที่ซื้อซอฟต์แวร์!</h1>
            <p style="color: #6b7280; margin: 10px 0 0 0;">การสั่งซื้อของคุณสำเร็จเรียบร้อยแล้ว</p>
        </div>

        <!-- Greeting -->
        <p style="font-size: 16px;">สวัสดี คุณ <strong>{{ $buyerName }}</strong>,</p>

        <!-- Product Info -->
        <div class="product-info">
            <h2>{{ $productName }}</h2>
            <p style="margin: 0; opacity: 0.9;">คุณได้ทำการซื้อซอฟต์แวร์นี้สำเร็จแล้ว</p>
        </div>

        <!-- License Information -->
        <div class="license-box">
            <h3 style="margin-top: 0; color: #1f2937;">🔑 ข้อมูล License ของคุณ</h3>

            <p style="margin: 10px 0;"><strong>License Key:</strong></p>
            <div class="license-key">{{ $licenseKey }}</div>

            @if($activationCode)
                <p style="margin: 20px 0 10px 0;"><strong>Activation Code:</strong></p>
                <div class="activation-code">{{ $activationCode }}</div>
            @endif
        </div>

        <!-- License Details -->
        <div class="info-grid">
            @if($expiresAt)
                <div class="info-item">
                    <div class="info-label">วันหมดอายุ</div>
                    <div class="info-value">{{ $expiresAt }}</div>
                </div>
            @else
                <div class="info-item">
                    <div class="info-label">วันหมดอายุ</div>
                    <div class="info-value" style="color: #10B981;">ไม่มีกำหนด</div>
                </div>
            @endif

            <div class="info-item">
                <div class="info-label">Activations สูงสุด</div>
                <div class="info-value">{{ $maxActivations }} ครั้ง</div>
            </div>

            <div class="info-item">
                <div class="info-label">Downloads สูงสุด</div>
                <div class="info-value">{{ $maxDownloads }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">สถานะ</div>
                <div class="info-value" style="color: #10B981;">✓ พร้อมใช้งาน</div>
            </div>
        </div>

        <!-- Download Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $downloadUrl }}" class="download-button">
                ⬇️ ดาวน์โหลดซอฟต์แวร์
            </a>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <h3>📋 วิธีการติดตั้งและใช้งาน</h3>
            <ol>
                <li>คลิกปุ่ม "ดาวน์โหลดซอฟต์แวร์" ด้านบน</li>
                <li>แตกไฟล์ .zip ที่ดาวน์โหลดมา</li>
                <li>ติดตั้งซอฟต์แวร์ตามคู่มือที่แนบมาในไฟล์</li>
                <li>ใช้ License Key ด้านบนเพื่อ activate ซอฟต์แวร์</li>
                @if($activationCode)
                    <li>ใช้ Activation Code เมื่อระบบขอรหัสยืนยัน</li>
                @endif
            </ol>
        </div>

        <!-- Warning -->
        <div class="warning">
            <strong>⚠️ สำคัญ:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>กรุณาเก็บ License Key ไว้ในที่ปลอดภัย</li>
                <li>ห้ามแชร์ License Key ให้ผู้อื่น</li>
                <li>คุณสามารถดาวน์โหลดซอฟต์แวร์ได้ {{ $maxDownloads }}</li>
                <li>สามารถ activate ได้สูงสุด {{ $maxActivations }} อุปกรณ์</li>
            </ul>
        </div>

        <!-- Support -->
        <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #1f2937;">💬 ต้องการความช่วยเหลือ?</h3>
            <p style="margin: 10px 0;">หากคุณมีปัญหาหรือข้อสงสัยเกี่ยวกับการติดตั้งหรือใช้งาน</p>
            <p style="margin: 10px 0;">
                📧 Email: <a href="mailto:support@thaiprompt.com" style="color: #4F46E5;">support@thaiprompt.com</a><br>
                💬 LINE: <a href="https://line.me/ti/p/@thaiprompt" style="color: #4F46E5;">@thaiprompt</a>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>ขอบคุณที่ไว้วางใจเลือกใช้บริการของเรา</p>
            <p style="margin: 5px 0;"><strong>TP-Affiliate Team</strong></p>
            <p style="margin: 5px 0; font-size: 12px;">
                อีเมลนี้ส่งจากระบบอัตโนมัติ กรุณาอย่าตอบกลับ
            </p>
        </div>
    </div>
</body>
</html>
