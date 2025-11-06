<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuestionOption;
use App\Models\LearningArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizManagementController extends Controller
{
    /**
     * Display quiz list for management
     */
    public function index()
    {
        $user = Auth::user();

        // Get all quizzes created by this user or for their articles
        $quizzes = Quiz::with(['article'])
            ->whereHas('article', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->withCount(['questions', 'attempts'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.quiz-management.index', compact('quizzes'));
    }

    /**
     * Show create quiz form
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // Get articles created by this user
        $articles = LearningArticle::where('created_by', $user->id)
            ->orderBy('title')
            ->get();

        // Pre-select article if provided
        $selectedArticleId = $request->get('article_id');

        return view('admin.quiz-management.create', compact('articles', 'selectedArticleId'));
    }

    /**
     * Store new quiz
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'article_id' => 'required|exists:learning_articles,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_score' => 'required|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'nullable|integer|min:1',
            'randomize_questions' => 'boolean',
            'show_results_immediately' => 'boolean',
            'show_correct_answers' => 'boolean',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,true_false,multiple_answer,short_answer',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.points' => 'required|integer|min:1',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.text' => 'required|string',
            'questions.*.options.*.is_correct' => 'required|boolean',
        ]);

        // Verify user owns the article
        $article = LearningArticle::where('id', $validated['article_id'])
            ->where('created_by', $user->id)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Create quiz
            $quiz = Quiz::create([
                'article_id' => $validated['article_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'passing_score' => $validated['passing_score'],
                'time_limit' => $validated['time_limit'] ?? null,
                'max_attempts' => $validated['max_attempts'] ?? null,
                'randomize_questions' => $validated['randomize_questions'] ?? false,
                'show_results_immediately' => $validated['show_results_immediately'] ?? true,
                'show_correct_answers' => $validated['show_correct_answers'] ?? true,
                'is_required' => $validated['is_required'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Create questions and options
            foreach ($validated['questions'] as $index => $questionData) {
                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'type' => $questionData['type'],
                    'question' => $questionData['question'],
                    'explanation' => $questionData['explanation'] ?? null,
                    'points' => $questionData['points'],
                    'order' => $index,
                ]);

                // Create options
                foreach ($questionData['options'] as $optionIndex => $optionData) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['text'],
                        'is_correct' => $optionData['is_correct'],
                        'order' => $optionIndex,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.quiz-management.show', $quiz->id)
                ->with('success', 'สร้าง Quiz สำเร็จ!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show quiz details
     */
    public function show($id)
    {
        $user = Auth::user();

        $quiz = Quiz::with(['article', 'questions.options'])
            ->whereHas('article', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->findOrFail($id);

        // Get statistics
        $stats = [
            'total_attempts' => $quiz->attempts()->count(),
            'completed_attempts' => $quiz->attempts()->completed()->count(),
            'passed_attempts' => $quiz->attempts()->passed()->count(),
            'avg_score' => $quiz->attempts()->completed()->avg('percentage'),
            'total_questions' => $quiz->questions()->count(),
        ];

        return view('admin.quiz-management.show', compact('quiz', 'stats'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $user = Auth::user();

        $quiz = Quiz::with(['questions.options'])
            ->whereHas('article', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->findOrFail($id);

        $articles = LearningArticle::where('created_by', $user->id)
            ->orderBy('title')
            ->get();

        return view('admin.quiz-management.edit', compact('quiz', 'articles'));
    }

    /**
     * Update quiz
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $quiz = Quiz::whereHas('article', function ($query) use ($user) {
            $query->where('created_by', $user->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_score' => 'required|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'nullable|integer|min:1',
            'randomize_questions' => 'boolean',
            'show_results_immediately' => 'boolean',
            'show_correct_answers' => 'boolean',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $quiz->update($validated);

        return redirect()->route('admin.quiz-management.show', $quiz->id)
            ->with('success', 'อัพเดต Quiz สำเร็จ!');
    }

    /**
     * Delete quiz
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $quiz = Quiz::whereHas('article', function ($query) use ($user) {
            $query->where('created_by', $user->id);
        })->findOrFail($id);

        $quiz->delete();

        return redirect()->route('admin.quiz-management.index')
            ->with('success', 'ลบ Quiz สำเร็จ!');
    }

    /**
     * View quiz attempts and student answers
     */
    public function attempts($id)
    {
        $user = Auth::user();

        $quiz = Quiz::with(['article'])
            ->whereHas('article', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->findOrFail($id);

        $attempts = $quiz->attempts()
            ->with(['user'])
            ->completed()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.quiz-management.attempts', compact('quiz', 'attempts'));
    }
}
