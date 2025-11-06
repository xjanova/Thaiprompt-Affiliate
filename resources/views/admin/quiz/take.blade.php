@extends('layouts.admin')

@section('title', $quiz->title . ' - Quiz')

@push('styles')
<style>
:root {
    --primary-color: #6366f1;
    --primary-dark: #4f46e5;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
}

body {
    background: var(--gray-50);
}

/* Quiz Container */
.quiz-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 32px 16px;
}

/* Quiz Header */
.quiz-header {
    background: white;
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    border: 1px solid var(--gray-200);
}

.quiz-title {
    font-size: 32px;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 12px;
}

.quiz-description {
    color: var(--gray-600);
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 24px;
}

.quiz-meta {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.quiz-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--gray-700);
    font-size: 14px;
    font-weight: 600;
}

.quiz-meta-item i {
    color: var(--primary-color);
}

/* Timer */
.quiz-timer {
    position: sticky;
    top: 20px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.timer-label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.timer-value {
    font-size: 48px;
    font-weight: 800;
    font-family: 'Courier New', monospace;
}

/* Question Card */
.question-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    border: 2px solid var(--gray-200);
    transition: all 0.3s ease;
}

.question-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}

.question-number {
    display: inline-block;
    background: var(--primary-color);
    color: white;
    padding: 6px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 16px;
}

.question-text {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 24px;
    line-height: 1.4;
}

.question-points {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--warning-color);
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    margin-left: 12px;
}

/* Options */
.options-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.option-item {
    margin-bottom: 12px;
}

.option-label {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    background: var(--gray-50);
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.option-label:hover {
    background: var(--gray-100);
    border-color: var(--primary-color);
}

.option-input {
    width: 20px;
    height: 20px;
    margin-right: 16px;
    cursor: pointer;
    accent-color: var(--primary-color);
}

.option-input:checked + .option-label {
    background: rgba(99, 102, 241, 0.1);
    border-color: var(--primary-color);
    font-weight: 600;
}

.option-text {
    flex: 1;
    font-size: 16px;
    color: var(--gray-900);
}

/* Short Answer Input */
.short-answer-input {
    width: 100%;
    padding: 16px;
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    font-size: 16px;
    transition: border-color 0.2s;
}

.short-answer-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

/* Progress Bar */
.progress-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid var(--gray-200);
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-700);
}

.progress-bar-wrapper {
    background: var(--gray-200);
    border-radius: 8px;
    height: 12px;
    overflow: hidden;
}

.progress-bar-fill {
    background: linear-gradient(90deg, var(--primary-color), var(--success-color));
    height: 100%;
    border-radius: 8px;
    transition: width 0.5s ease;
}

/* Submit Button */
.submit-section {
    background: white;
    border-radius: 16px;
    padding: 32px;
    text-align: center;
    border: 1px solid var(--gray-200);
}

.btn-submit {
    padding: 16px 48px;
    background: var(--success-color);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
}

.btn-submit:disabled {
    background: var(--gray-300);
    cursor: not-allowed;
    transform: none;
}

/* Warning Alert */
.alert-warning {
    background: #fef3c7;
    border-left: 4px solid var(--warning-color);
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

/* Responsive */
@media (max-width: 768px) {
    .quiz-container {
        padding: 16px 12px;
    }

    .quiz-header,
    .question-card {
        padding: 20px;
    }

    .quiz-title {
        font-size: 24px;
    }

    .question-text {
        font-size: 18px;
    }

    .timer-value {
        font-size: 36px;
    }
}
</style>
@endpush

@section('content')
<div class="quiz-container">
    <!-- Back Link -->
    <div class="mb-3">
        <a href="{{ route('admin.learning-center.article', $quiz->article->slug) }}" class="text-primary" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-2"></i>กลับไปยังคอร์ส
        </a>
    </div>

    <!-- Quiz Header -->
    <div class="quiz-header">
        <h1 class="quiz-title">{{ $quiz->title }}</h1>

        @if($quiz->description)
        <p class="quiz-description">{{ $quiz->description }}</p>
        @endif

        <div class="quiz-meta">
            <div class="quiz-meta-item">
                <i class="fas fa-question-circle"></i>
                <span>{{ $quiz->total_questions }} คำถาม</span>
            </div>
            @if($quiz->time_limit)
            <div class="quiz-meta-item">
                <i class="fas fa-clock"></i>
                <span>{{ $quiz->time_limit }} นาที</span>
            </div>
            @endif
            <div class="quiz-meta-item">
                <i class="fas fa-trophy"></i>
                <span>คะแนนผ่าน: {{ $quiz->passing_score }}%</span>
            </div>
            @if($quiz->max_attempts)
            <div class="quiz-meta-item">
                <i class="fas fa-redo"></i>
                <span>ทำได้สูงสุด {{ $quiz->max_attempts }} ครั้ง</span>
            </div>
            @endif
        </div>

        @if($previousAttempts->count() > 0)
        <div class="alert-warning mt-3">
            <strong>คุณเคยทำ Quiz นี้แล้ว {{ $previousAttempts->count() }} ครั้ง</strong>
            @if($bestAttempt)
            <br>คะแนนที่ดีที่สุด: <strong>{{ number_format($bestAttempt->percentage, 2) }}%</strong>
            ({{ $bestAttempt->passed ? '✅ ผ่าน' : '❌ ไม่ผ่าน' }})
            @endif
        </div>
        @endif
    </div>

    <!-- Timer (if applicable) -->
    @if($quiz->time_limit)
    <div class="quiz-timer">
        <div class="timer-label">เวลาที่เหลือ</div>
        <div class="timer-value" id="timer">{{ $quiz->time_limit }}:00</div>
    </div>
    @endif

    <!-- Progress -->
    <div class="progress-section">
        <div class="progress-label">
            <span>ความก้าวหน้า</span>
            <span id="progress-text">0 / {{ $quiz->total_questions }}</span>
        </div>
        <div class="progress-bar-wrapper">
            <div class="progress-bar-fill" id="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <!-- Quiz Form -->
    <form method="POST" action="{{ route('admin.quiz.submit', $quiz->id) }}" id="quiz-form">
        @csrf
        <input type="hidden" name="attempt_id" value="{{ $currentAttempt->id }}">

        @foreach($questions as $index => $question)
        <div class="question-card">
            <div class="question-number">คำถามที่ {{ $index + 1 }}</div>
            <span class="question-points">
                <i class="fas fa-star"></i> {{ $question->points }} คะแนน
            </span>

            <h3 class="question-text">{{ $question->question }}</h3>

            @if($question->image_url)
            <div class="mb-3">
                <img src="{{ $question->image_url }}" alt="Question Image" style="max-width: 100%; border-radius: 8px;">
            </div>
            @endif

            @if($question->type === 'multiple_choice' || $question->type === 'true_false')
            <!-- Multiple Choice / True False -->
            <ul class="options-list">
                @foreach($question->options as $option)
                <li class="option-item">
                    <input type="radio"
                           name="answers[{{ $question->id }}]"
                           value="{{ $option->id }}"
                           id="option-{{ $option->id }}"
                           class="option-input d-none"
                           required
                           onchange="updateProgress()">
                    <label for="option-{{ $option->id }}" class="option-label">
                        <span class="option-text">{{ $option->option_text }}</span>
                    </label>
                </li>
                @endforeach
            </ul>

            @elseif($question->type === 'multiple_answer')
            <!-- Multiple Answer -->
            <ul class="options-list">
                @foreach($question->options as $option)
                <li class="option-item">
                    <input type="checkbox"
                           name="answers[{{ $question->id }}][]"
                           value="{{ $option->id }}"
                           id="option-{{ $option->id }}"
                           class="option-input d-none"
                           onchange="updateProgress()">
                    <label for="option-{{ $option->id }}" class="option-label">
                        <span class="option-text">{{ $option->option_text }}</span>
                    </label>
                </li>
                @endforeach
            </ul>
            <small class="text-muted">* เลือกได้มากกว่า 1 ข้อ</small>

            @elseif($question->type === 'short_answer')
            <!-- Short Answer -->
            <textarea
                name="answers[{{ $question->id }}]"
                class="short-answer-input"
                rows="4"
                placeholder="พิมพ์คำตอบของคุณที่นี่..."
                required
                onchange="updateProgress()"></textarea>
            @endif
        </div>
        @endforeach

        <!-- Submit Section -->
        <div class="submit-section">
            <h3 style="margin-bottom: 16px;">พร้อมส่ง Quiz แล้วหรือยัง?</h3>
            <p class="text-muted mb-4">โปรดตรวจสอบคำตอบของคุณอีกครั้งก่อนส่ง</p>
            <button type="submit" class="btn-submit" id="submit-btn">
                <i class="fas fa-paper-plane me-2"></i>ส่ง Quiz
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const totalQuestions = {{ $quiz->total_questions }};
    let timeLimit = {{ $quiz->time_limit ?? 0 }};
    let timeRemaining = timeLimit * 60; // Convert to seconds

    // Timer
    @if($quiz->time_limit)
    function updateTimer() {
        if (timeRemaining <= 0) {
            $('#quiz-form').submit();
            return;
        }

        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        $('#timer').text(`${minutes}:${seconds.toString().padStart(2, '0')}`);

        if (timeRemaining <= 60) {
            $('#timer').css('color', '#ef4444');
        }

        timeRemaining--;
    }

    setInterval(updateTimer, 1000);
    @endif

    // Progress tracking
    window.updateProgress = function() {
        let answered = 0;

        // Count answered questions
        $('.question-card').each(function() {
            const questionId = $(this).find('input[name^="answers"]').attr('name');

            if (questionId) {
                const inputs = $(this).find('input[name^="answers"]');

                // Check radio buttons
                if (inputs.filter(':checked').length > 0) {
                    answered++;
                }
                // Check textareas
                else if ($(this).find('textarea').length > 0 && $(this).find('textarea').val().trim() !== '') {
                    answered++;
                }
            }
        });

        const percentage = (answered / totalQuestions) * 100;
        $('#progress-bar').css('width', percentage + '%');
        $('#progress-text').text(`${answered} / ${totalQuestions}`);

        // Enable submit button if all answered
        if (answered === totalQuestions) {
            $('#submit-btn').prop('disabled', false);
        }
    };

    // Form submission confirmation
    $('#quiz-form').on('submit', function(e) {
        if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการส่ง Quiz? คุณจะไม่สามารถแก้ไขคำตอบได้อีก')) {
            e.preventDefault();
        }
    });

    // Prevent accidental page leave
    window.addEventListener('beforeunload', function (e) {
        e.preventDefault();
        e.returnValue = '';
    });

    // Initial progress update
    updateProgress();
});
</script>
@endpush
