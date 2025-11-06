<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    <meta name="description" content="Certificate of Completion for {{ $certificate->course_title }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Certificate - {{ $certificate->student_name }}">
    <meta property="og:description" content="Certificate of Completion for {{ $certificate->course_title }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="Certificate - {{ $certificate->student_name }}">
    <meta property="twitter:description" content="Certificate of Completion for {{ $certificate->course_title }}">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .share-header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .share-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .share-subtitle {
            font-size: 16px;
            opacity: 0.95;
        }

        .certificate-wrapper {
            max-width: 1200px;
            width: 100%;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .action-bar {
            background: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            border-bottom: 2px solid #f3f4f6;
        }

        .verification-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #10b981;
            font-weight: 700;
            font-size: 14px;
        }

        .verification-badge i {
            font-size: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: white;
            color: #6366f1;
            border: 2px solid #6366f1;
        }

        .btn-secondary:hover {
            background: #6366f1;
            color: white;
        }

        .certificate-display {
            padding: 20px;
        }

        .footer-info {
            background: #f8f9fa;
            padding: 24px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        .verify-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }

        .verify-link:hover {
            text-decoration: underline;
        }

        @media print {
            .share-header,
            .action-bar,
            .footer-info {
                display: none !important;
            }

            body {
                background: white;
                padding: 0;
            }

            .certificate-wrapper {
                box-shadow: none;
                border-radius: 0;
            }
        }

        @media (max-width: 768px) {
            .share-title {
                font-size: 24px;
            }

            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons {
                width: 100%;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="share-header">
        <h1 class="share-title">🎓 Certificate Verification</h1>
        <p class="share-subtitle">This is an authentic certificate issued by ThaiPrompt Academy</p>
    </div>

    <div class="certificate-wrapper">
        <div class="action-bar">
            <div class="verification-badge">
                <i class="fas fa-check-circle"></i>
                <span>Verified Certificate</span>
            </div>

            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i>
                    Print
                </button>
                <button onclick="shareOnSocial()" class="btn btn-secondary">
                    <i class="fas fa-share-alt"></i>
                    Share
                </button>
            </div>
        </div>

        <div class="certificate-display">
            @include('certificates.template', ['certificate' => $certificate])
        </div>

        <div class="footer-info">
            <p><strong>Certificate Number:</strong> {{ $certificate->certificate_number }}</p>
            <p><strong>Issued Date:</strong> {{ $certificate->formatted_issued_date }}</p>
            <p class="mt-3">
                Verify this certificate at:
                <a href="{{ route('certificate.verify', $certificate->verification_code) }}" class="verify-link">
                    {{ route('certificate.verify', $certificate->verification_code) }}
                </a>
            </p>
            <p class="mt-2" style="font-size: 12px; opacity: 0.7;">
                © {{ date('Y') }} ThaiPrompt Academy. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        function shareOnSocial() {
            const url = window.location.href;
            const text = 'Check out my certificate: {{ $certificate->course_title }}';

            if (navigator.share) {
                navigator.share({
                    title: 'My Certificate',
                    text: text,
                    url: url
                }).catch(err => console.log('Error sharing:', err));
            } else {
                // Fallback: Copy to clipboard
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copied to clipboard!');
                });
            }
        }
    </script>
</body>
</html>
