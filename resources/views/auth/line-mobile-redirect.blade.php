<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กำลังกลับสู่แอป - TP UltraAPP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0F0F23 0%, #1a1a3e 50%, #0F0F23 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .container {
            text-align: center;
            padding: 40px 20px;
            max-width: 400px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #00B900 0%, #00E000 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 185, 0, 0.3);
        }

        .logo svg {
            width: 48px;
            height: 48px;
            fill: white;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: #00B900;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 24px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #fff;
        }

        .message {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .manual-link {
            display: inline-block;
            background: linear-gradient(135deg, #00B900 0%, #00E000 100%);
            color: #fff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(0, 185, 0, 0.4);
            transition: all 0.3s ease;
        }

        .manual-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 185, 0, 0.5);
        }

        .hint {
            margin-top: 24px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
        }

        .error-container {
            background: rgba(255, 59, 48, 0.1);
            border: 1px solid rgba(255, 59, 48, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .error-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            background: rgba(255, 59, 48, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-icon svg {
            width: 24px;
            height: 24px;
            fill: #FF3B30;
        }
    </style>
</head>
<body>
    <div class="container">
        @if(str_contains($deepLink, 'error='))
            {{-- แสดง error UI --}}
            <div class="error-container">
                <div class="error-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>
                <h1>{{ $message }}</h1>
                <p class="message">กรุณากลับไปที่แอปและลองใหม่อีกครั้ง</p>
            </div>
        @else
            {{-- แสดง loading UI --}}
            <div class="logo">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12c0 4.75 3.3 8.72 7.74 9.75l.01-.01c.18.03.37.05.56.05.55 0 1-.45 1-1v-1.14c-3.57-.55-6.31-3.6-6.31-7.27C5 8.13 8.13 5 12 5s7 3.13 7 7c0 3.67-2.74 6.72-6.31 7.27V20.4c0 .55.45 1 1 1 .19 0 .38-.02.56-.05l.01.01C18.7 20.72 22 16.75 22 12c0-5.52-4.48-10-10-10z"/>
                </svg>
            </div>
            <div class="spinner"></div>
            <h1>{{ $message }}</h1>
            <p class="message">กรุณารอสักครู่ ระบบกำลังนำคุณกลับไปยังแอป TP UltraAPP</p>
        @endif

        <a href="{{ $deepLink }}" class="manual-link" id="open-app-btn">
            เปิดแอป TP UltraAPP
        </a>

        <p class="hint">
            หากไม่ได้เปิดแอปอัตโนมัติ กรุณากดปุ่มด้านบน
        </p>
    </div>

    <script>
        // พยายาม redirect ไป deep link อัตโนมัติ
        (function() {
            var deepLink = @json($deepLink);

            // รอ 500ms ก่อน redirect เพื่อให้ page render เสร็จ
            setTimeout(function() {
                try {
                    // ลอง redirect ไป deep link
                    window.location.href = deepLink;
                } catch (e) {
                    console.error('Failed to redirect to deep link:', e);
                }
            }, 500);

            // ถ้ายังอยู่ในหน้านี้หลังจาก 2 วินาที แสดงว่า deep link ไม่ทำงาน
            setTimeout(function() {
                // ซ่อน spinner และเปลี่ยน message
                var spinner = document.querySelector('.spinner');
                if (spinner) {
                    spinner.style.display = 'none';
                }
            }, 2000);
        })();
    </script>
</body>
</html>
