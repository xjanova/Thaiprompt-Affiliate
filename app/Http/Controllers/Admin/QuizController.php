<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuestionOption;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\LearningArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * Display quiz for student to take
     */
    public function show($id)
    {
        $user = Auth::user();
        $quiz = Quiz::with(['questions.options', 'article'])->findOrFail($id);

        // Check if quiz is active
        if (!$quiz->is_active) {
            return redirect()->back()->with('error', 'Quiz นี้ยังไม่เปิดใช้งาน');
        }

        // Check if user has remaining attempts
        if (!$quiz->hasRemainingAttempts($user->id)) {
            return redirect()->back()->with('error', 'คุณใช้จำนวนครั้งในการทำ Quiz นี้หมดแล้ว');
        }

        // Get user's previous attempts
        $previousAttempts = $quiz->userAttempts($user->id)->completed()->get();
        $bestAttempt = $quiz->bestAttempt($user->id);

        // Get or create new attempt
        $currentAttempt = QuizAttempt::firstOrCreate(
            [
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'completed_at' => null,
            ],
            [
                'started_at' => now(),
            ]
        );

        // Randomize questions if enabled
        $questions = $quiz->questions;
        if ($quiz->randomize_questions) {
            $questions = $questions->shuffle();
        }

        return view('admin.quiz.take', compact(
            'quiz',
            'questions',
            'currentAttempt',
            'previousAttempts',
            'bestAttempt'
        ));
    }

    /**
     * Submit quiz answers
     */
    public function submit(Request $request, $id)
    {
        $user = Auth::user();
        $quiz = Quiz::with('questions')->findOrFail($id);

        $validated = $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'answers' => 'required|array',
            'answers.*' => 'required',
        ]);

        $attempt = QuizAttempt::where('id', $validated['attempt_id'])
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->firstOrFail();

        // Check if already completed
        if ($attempt->isCompleted()) {
            return redirect()->back()->with('error', 'Quiz นี้ถูกส่งไปแล้ว');
        }

        DB::beginTransaction();
        try {
            // Save all answers
            foreach ($validated['answers'] as $questionId => $answer) {
                $question = QuizQuestion::findOrFail($questionId);

                $quizAnswer = new QuizAnswer();
                $quizAnswer->attempt_id = $attempt->id;
                $quizAnswer->question_id = $questionId;

                // Handle different question types
                switch ($question->type) {
                    case QuizQuestion::TYPE_MULTIPLE_CHOICE:
                    case QuizQuestion::TYPE_TRUE_FALSE:
                        $quizAnswer->option_id = $answer;
                        break;

                    case QuizQuestion::TYPE_MULTIPLE_ANSWER:
                        $quizAnswer->selected_options = is_array($answer) ? $answer : [$answer];
                        break;

                    case QuizQuestion::TYPE_SHORT_ANSWER:
                        $quizAnswer->answer_text = $answer;
                        break;
                }

                $quizAnswer->save();
                $quizAnswer->checkAndUpdateCorrectness();
            }

            // Mark attempt as completed and calculate score
            $attempt->markCompleted();

            DB::commit();

            if ($quiz->show_results_immediately) {
                return redirect()->route('admin.quiz.results', $attempt->id)
                    ->with('success', 'ส่ง Quiz สำเร็จ!');
            } else {
                return redirect()->route('admin.learning-center.article', $quiz->article->slug)
                    ->with('success', 'ส่ง Quiz สำเร็จ! ผลลัพธ์จะแสดงภายหลัง');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show quiz results
     */
    public function results($attemptId)
    {
        $user = Auth::user();
        $attempt = QuizAttempt::with([
            'quiz.questions.options',
            'quizAnswers.question.options',
            'quizAnswers.option'
        ])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        $quiz = $attempt->quiz;

        // Check if results should be shown
        if (!$quiz->show_results_immediately && !$attempt->isCompleted()) {
            return redirect()->back()->with('error', 'ผลลัพธ์ยังไม่พร้อมแสดง');
        }

        return view('admin.quiz.results', compact('attempt', 'quiz'));
    }

    /**
     * Show quiz list for an article
     */
    public function index($articleSlug)
    {
        $article = LearningArticle::where('slug', $articleSlug)->firstOrFail();
        $user = Auth::user();

        $quizzes = $article->activeQuizzes()
            ->withCount('questions')
            ->get()
            ->map(function ($quiz) use ($user) {
                $bestAttempt = $quiz->bestAttempt($user->id);
                $attemptCount = $quiz->userAttempts($user->id)->count();

                return [
                    'quiz' => $quiz,
                    'best_score' => $bestAttempt ? $bestAttempt->percentage : 0,
                    'attempts' => $attemptCount,
                    'has_attempts_left' => $quiz->hasRemainingAttempts($user->id),
                    'passed' => $bestAttempt ? $bestAttempt->passed : false,
                ];
            });

        return view('admin.quiz.index', compact('article', 'quizzes'));
    }
}
