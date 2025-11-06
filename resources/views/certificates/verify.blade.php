<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .verification-card {
            background: white;
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            margin: 0 auto;
        }

        .verification-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 64px;
        }

        .verification-icon.success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .verification-icon.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .verification-icon.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .verification-title {
            font-size: 32px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 16px;
        }

        .verification-message {
            font-size: 18px;
            text-align: center;
            color: #6c757d;
            margin-bottom: 32px;
        }

        .certificate-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
            text-align: right;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-card">
            @if($found && $valid)
                <!-- Valid Certificate -->
                <div class="verification-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="verification-title text-success">ใบประกาศนียบัตรถูกต้อง</h1>
                <p class="verification-message">{{ $message }}</p>

                <div class="certificate-info">
                    <div class="info-row">
                        <span class="info-label">หมายเลขใบประกาศ:</span>
                        <span class="info-value">{{ $certificate->certificate_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ชื่อผู้รับ:</span>
                        <span class="info-value">{{ $certificate->student_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">คอร์ส:</span>
                        <span class="info-value">{{ $certificate->course_title }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">วันที่ออก:</span>
                        <span class="info-value">{{ $certificate->formatted_issued_date }}</span>
                    </div>
                    @if($certificate->quiz_score)
                    <div class="info-row">
                        <span class="info-label">คะแนน Quiz:</span>
                        <span class="info-value">{{ number_format($certificate->quiz_score, 2) }}%</span>
                    </div>
                    @endif
                </div>

                <a href="{{ route('certificate.share', $certificate->id) }}" class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>ดูใบประกาศนียบัตร
                </a>

            @elseif($found && !$valid)
                <!-- Revoked Certificate -->
                <div class="verification-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="verification-title text-warning">ใบประกาศถูกเพิกถอน</h1>
                <p class="verification-message">{{ $message }}</p>

                <div class="certificate-info">
                    <div class="info-row">
                        <span class="info-label">หมายเลขใบประกาศ:</span>
                        <span class="info-value">{{ $certificate->certificate_number }}</span>
                    </div>
                    @if($certificate->revoked_at)
                    <div class="info-row">
                        <span class="info-label">วันที่เพิกถอน:</span>
                        <span class="info-value">{{ $certificate->revoked_at->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    @if($certificate->revoked_reason)
                    <div class="info-row">
                        <span class="info-label">เหตุผล:</span>
                        <span class="info-value">{{ $certificate->revoked_reason }}</span>
                    </div>
                    @endif
                </div>

            @else
                <!-- Not Found -->
                <div class="verification-icon error">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h1 class="verification-title text-danger">ไม่พบใบประกาศ</h1>
                <p class="verification-message">{{ $message }}</p>

                <div class="alert alert-danger">
                    <i class="fas fa-info-circle me-2"></i>
                    กรุณาตรวจสอบรหัสยืนยันอีกครั้ง หรือติดต่อผู้ออกใบประกาศนียบัตร
                </div>
            @endif

            <div class="text-center mt-4">
                <a href="{{ route('admin.learning-center.index') }}" class="text-muted" style="text-decoration: none;">
                    <i class="fas fa-home me-2"></i>กลับหน้าหลัก
                </a>
            </div>
        </div>
    </div>
</body>
</html>
