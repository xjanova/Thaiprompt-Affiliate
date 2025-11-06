@extends('layouts.admin')

@section('title', 'ผลลัพธ์ Quiz - ' . $quiz->title)

@push('styles')
<style>
:root {
    --primary-color: #6366f1;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
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

.results-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 32px 16px;
}

/* Score Card */
.score-card {
    background: linear-gradient(135deg, {{ $attempt->passed ? '#10b981' : '#ef4444' }} 0%, {{ $attempt->passed ? '#059669' : '#dc2626' }} 100%);
    border-radius: 24px;
    padding: 48px;
    color: white;
    text-align: center;
    margin-bottom: 32px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.score-icon {
    font-size: 80px;
    margin-bottom: 16px;
    animation: bounce 1s ease;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.score-label {
    font-size: 18px;
    opacity: 0.95;
    margin-bottom: 8px;
}

.score-value {
    font-size: 72px;
    font-weight: 800;
    margin-bottom: 16px;
}

.score-status {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 24px;
}

.score-details {
    display: flex;
    justify-content: center;
    gap: 32px;
    flex-wrap: wrap;
    opacity: 0.95;
}

.score-detail-item {
    font-size: 16px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid var(--gray-200);
}

.stat-label {
    font-size: 14px;
    color: var(--gray-600);
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    color: var(--gray-900);
}

/* Answer Review */
.answers-section {
    background: white;
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    border: 1px solid var(--gray-200);
}

.section-title {
    font-size: 24px;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 24px;
}

.answer-card {
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
}

.answer-card.correct {
    background: #f0fdf4;
    border-color: var(--success-color);
}

.answer-card.incorrect {
    background: #fef2f2;
    border-color: var(--danger-color);
}

.answer-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.question-number {
    background: var(--primary-color);
    color: white;
    padding: 6px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
}

.answer-status {
    padding: 6px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
}

.answer-status.correct {
    background: var(--success-color);
    color: white;
}

.answer-status.incorrect {
    background: var(--danger-color);
    color: white;
}

.question-text {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 16px;
}

.answer-option {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 15px;
}

.answer-option.selected {
    background: rgba(99, 102, 241, 0.1);
    border: 2px solid var(--primary-color);
    font-weight: 600;
}

.answer-option.correct-answer {
    background: rgba(16, 185, 129, 0.1);
    border: 2px solid var(--success-color);
    font-weight: 600;
}

.answer-option.wrong-answer {
    background: rgba(239, 68, 68, 0.1);
    border: 2px solid var(--danger-color);
}

.explanation {
    background: var(--gray-50);
    padding: 16px;
    border-left: 4px solid var(--primary-color);
    border-radius: 8px;
    margin-top: 16px;
}

.explanation-title {
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 8px;
}

/* Action Buttons */
.actions-section {
    text-align: center;
    padding: 32px;
    background: white;
    border-radius: 16px;
    border: 1px solid var(--gray-200);
}

.btn-primary {
    padding: 16px 32px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
    margin: 8px;
}

.btn-primary:hover {
    background: #4f46e5;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
}

.btn-secondary {
    padding: 16px 32px;
    background: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
    margin: 8px;
}

.btn-secondary:hover {
    background: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .score-card {
        padding: 32px 20px;
    }

    .score-value {
        font-size: 56px;
    }

    .score-icon {
        font-size: 60px;
    }
}
</style>
@endpush

@section('content')
<div class="results-container">
    <!-- Back Link -->
    <div class="mb-3">
        <a href="{{ route('admin.learning-center.article', $quiz->article->slug) }}" class="text-primary" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-2"></i>กลับไปยังคอร์ส
        </a>
    </div>

    <!-- Score Card -->
    <div class="score-card">
        <div class="score-icon">
            {{ $attempt->passed ? '🎉' : '😔' }}
        </div>
        <div class="score-label">คะแนนของคุณ</div>
        <div class="score-value">{{ number_format($attempt->percentage, 2) }}%</div>
        <div class="score-status">
            {{ $attempt->passed ? '✅ ผ่าน!' : '❌ ไม่ผ่าน' }}
        </div>
        <div class="score-details">
            <div class="score-detail-item">
                <i class="fas fa-check-circle me-2"></i>
                {{ $attempt->score }} / {{ $attempt->max_score }} คะแนน
            </div>
            <div class="score-detail-item">
                <i class="fas fa-clock me-2"></i>
                ใช้เวลา {{ $attempt->formatted_time_spent }}
            </div>
            <div class="score-detail-item">
                <i class="fas fa-trophy me-2"></i>
                ผ่าน {{ $quiz->passing_score }}%
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">คำตอบที่ถูกต้อง</div>
            <div class="stat-value" style="color: var(--success-color);">
                {{ $attempt->quizAnswers->where('is_correct', true)->count() }}
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">คำตอบที่ผิด</div>
            <div class="stat-value" style="color: var(--danger-color);">
                {{ $attempt->quizAnswers->where('is_correct', false)->count() }}
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">จำนวนคำถามทั้งหมด</div>
            <div class="stat-value">{{ $quiz->total_questions }}</div>
        </div>
    </div>

    <!-- Answer Review -->
    @if($quiz->show_correct_answers)
    <div class="answers-section">
        <h2 class="section-title">รีวิวคำตอบ</h2>

        @foreach($attempt->quizAnswers as $index => $answer)
        @php
            $question = $answer->question;
            $userOption = $answer->option;
            $correctOptions = $question->correctOptions;
        @endphp

        <div class="answer-card {{ $answer->is_correct ? 'correct' : 'incorrect' }}">
            <div class="answer-header">
                <span class="question-number">คำถามที่ {{ $index + 1 }}</span>
                <span class="answer-status {{ $answer->is_correct ? 'correct' : 'incorrect' }}">
                    {{ $answer->is_correct ? '✓ ถูกต้อง' : '✗ ผิด' }}
                </span>
            </div>

            <h3 class="question-text">{{ $question->question }}</h3>

            @if($question->type === 'multiple_choice' || $question->type === 'true_false')
                @foreach($question->options as $option)
                <div class="answer-option
                    {{ $option->id === $userOption?->id ? 'selected' : '' }}
                    {{ $option->is_correct ? 'correct-answer' : '' }}
                    {{ $option->id === $userOption?->id && !$answer->is_correct ? 'wrong-answer' : '' }}">
                    {{ $option->option_text }}
                    @if($option->is_correct)
                        <i class="fas fa-check-circle text-success ms-2"></i>
                    @endif
                    @if($option->id === $userOption?->id && !$answer->is_correct)
                        <i class="fas fa-times-circle text-danger ms-2"></i>
                    @endif
                </div>
                @endforeach

            @elseif($question->type === 'multiple_answer')
                @php
                    $selectedIds = $answer->selected_options ?? [];
                    $correctIds = $correctOptions->pluck('id')->toArray();
                @endphp
                @foreach($question->options as $option)
                <div class="answer-option
                    {{ in_array($option->id, $selectedIds) ? 'selected' : '' }}
                    {{ $option->is_correct ? 'correct-answer' : '' }}">
                    {{ $option->option_text }}
                    @if($option->is_correct)
                        <i class="fas fa-check-circle text-success ms-2"></i>
                    @endif
                </div>
                @endforeach

            @elseif($question->type === 'short_answer')
                <div class="answer-option selected">
                    <strong>คำตอบของคุณ:</strong><br>
                    {{ $answer->answer_text }}
                </div>
            @endif

            @if($question->explanation)
            <div class="explanation">
                <div class="explanation-title">💡 คำอธิบาย</div>
                <div>{{ $question->explanation }}</div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Actions -->
    <div class="actions-section">
        <h3 style="margin-bottom: 24px;">ต้องการทำอะไรต่อ?</h3>

        <div>
            <a href="{{ route('admin.learning-center.article', $quiz->article->slug) }}" class="btn-primary">
                <i class="fas fa-book me-2"></i>กลับไปยังคอร์ส
            </a>

            @if($quiz->hasRemainingAttempts(Auth::id()) && !$attempt->passed)
            <a href="{{ route('admin.quiz.show', $quiz->id) }}" class="btn-secondary">
                <i class="fas fa-redo me-2"></i>ทำ Quiz อีกครั้ง
            </a>
            @endif
        </div>

        @if(!$quiz->hasRemainingAttempts(Auth::id()))
        <p class="text-muted mt-3">คุณใช้จำนวนครั้งในการทำ Quiz นี้หมดแล้ว</p>
        @endif
    </div>
</div>
@endsection
