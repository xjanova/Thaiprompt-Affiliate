<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .certificate-container {
            width: 1200px;
            max-width: 100%;
            background: white;
            padding: 80px 100px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 20px solid #f8f9fa;
            position: relative;
        }

        .certificate-border {
            position: absolute;
            top: 40px;
            left: 40px;
            right: 40px;
            bottom: 40px;
            border: 3px solid #667eea;
            pointer-events: none;
        }

        .certificate-border::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border: 1px solid #ddd;
        }

        .certificate-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .certificate-logo {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 8px;
            margin-bottom: 10px;
        }

        .certificate-subtitle {
            font-size: 20px;
            color: #6c757d;
            font-style: italic;
        }

        .certificate-body {
            text-align: center;
            padding: 40px 0;
        }

        .awarded-text {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 30px;
        }

        .student-name {
            font-size: 56px;
            font-weight: bold;
            color: #212529;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
            min-width: 500px;
        }

        .completion-text {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .course-title {
            font-size: 32px;
            font-weight: bold;
            color: #212529;
            margin: 30px 0;
            font-style: italic;
        }

        .certificate-details {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .detail-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .certificate-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 40px;
            border-top: 2px solid #e9ecef;
        }

        .signature-section {
            text-align: center;
            flex: 1;
        }

        .signature-line {
            width: 250px;
            height: 2px;
            background: #212529;
            margin: 0 auto 15px;
        }

        .signature-name {
            font-size: 18px;
            font-weight: bold;
            color: #212529;
            margin-bottom: 5px;
        }

        .signature-title {
            font-size: 14px;
            color: #6c757d;
            font-style: italic;
        }

        .certificate-meta {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }

        .certificate-number {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .verification-code {
            font-size: 12px;
            color: #adb5bd;
            font-family: 'Courier New', monospace;
        }

        .decorative-element {
            position: absolute;
            font-size: 200px;
            opacity: 0.03;
            color: #667eea;
            z-index: 0;
        }

        .decorative-element.top-left {
            top: -50px;
            left: -50px;
        }

        .decorative-element.bottom-right {
            bottom: -50px;
            right: -50px;
        }

        .achievement-badge {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .achievement-badge-text {
            color: white;
            font-size: 48px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .certificate-container {
                box-shadow: none;
                border: none;
            }
        }

        @media (max-width: 768px) {
            .certificate-container {
                padding: 40px 30px;
            }

            .certificate-title {
                font-size: 32px;
                letter-spacing: 4px;
            }

            .student-name {
                font-size: 36px;
                min-width: auto;
            }

            .course-title {
                font-size: 24px;
            }

            .certificate-footer {
                flex-direction: column;
                gap: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border"></div>
        <div class="decorative-element top-left">🎓</div>
        <div class="decorative-element bottom-right">⭐</div>

        <!-- Header -->
        <div class="certificate-header">
            <div class="certificate-logo">🎓</div>
            <h1 class="certificate-title">Certificate</h1>
            <p class="certificate-subtitle">of Completion</p>
        </div>

        <!-- Body -->
        <div class="certificate-body">
            <div class="achievement-badge">
                <div class="achievement-badge-text">✓</div>
            </div>

            <p class="awarded-text">This certificate is proudly awarded to</p>

            <h2 class="student-name">{{ $certificate->student_name }}</h2>

            <p class="completion-text">
                For successfully completing the course
            </p>

            <h3 class="course-title">"{{ $certificate->course_title }}"</h3>

            <p class="completion-text">
                With dedication and excellence in learning
            </p>

            <!-- Details -->
            <div class="certificate-details">
                <div class="detail-item">
                    <div class="detail-label">Completion Date</div>
                    <div class="detail-value">{{ $certificate->formatted_issued_date }}</div>
                </div>

                @if($certificate->total_hours > 0)
                <div class="detail-item">
                    <div class="detail-label">Total Hours</div>
                    <div class="detail-value">{{ $certificate->total_hours }}h</div>
                </div>
                @endif

                @if($certificate->quiz_score)
                <div class="detail-item">
                    <div class="detail-label">Quiz Score</div>
                    <div class="detail-value">{{ number_format($certificate->quiz_score, 1) }}%</div>
                </div>
                @endif

                <div class="detail-item">
                    <div class="detail-label">Completion</div>
                    <div class="detail-value">{{ number_format($certificate->completion_percentage, 0) }}%</div>
                </div>
            </div>
        </div>

        <!-- Footer with Signatures -->
        <div class="certificate-footer">
            <div class="signature-section">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $certificate->metadata['instructor'] ?? 'Academy Team' }}</div>
                <div class="signature-title">Course Instructor</div>
            </div>

            <div class="signature-section">
                <div class="signature-line"></div>
                <div class="signature-name">ThaiPrompt Academy</div>
                <div class="signature-title">Platform Director</div>
            </div>
        </div>

        <!-- Certificate Meta -->
        <div class="certificate-meta">
            <div class="certificate-number">
                Certificate No: <strong>{{ $certificate->certificate_number }}</strong>
            </div>
            <div class="verification-code">
                Verification Code: {{ $certificate->verification_code }}
            </div>
            <div class="verification-code">
                Verify at: {{ url('/certificate/verify/' . $certificate->verification_code) }}
            </div>
        </div>
    </div>
</body>
</html>
