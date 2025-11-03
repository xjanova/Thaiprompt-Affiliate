<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Services\AI\DocumentProcessorService;
use App\Services\AI\TextChunkingService;
use App\Services\AI\EmbeddingService;
use App\Services\AI\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KnowledgeBaseController extends Controller
{
    private DocumentProcessorService $documentProcessor;
    private TextChunkingService $chunkingService;
    private EmbeddingService $embeddingService;
    private RagService $ragService;

    public function __construct()
    {
        $this->documentProcessor = new DocumentProcessorService();
        $this->chunkingService = new TextChunkingService();
        $this->embeddingService = new EmbeddingService();
        $this->ragService = new RagService();
    }

    /**
     * Display knowledge bases for a bot
     */
    public function index($botId)
    {
        $bot = AiBotProfile::findOrFail($botId);

        // Check permission
        if ($bot->owner_id !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access to bot knowledge bases');
        }

        $knowledgeBases = $bot->knowledgeBases()
            ->with('chunks')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = $this->ragService->getStatistics($bot);

        return view('admin.knowledge-bases.index', compact('bot', 'knowledgeBases', 'stats'));
    }

    /**
     * Show create form
     */
    public function create($botId)
    {
        $bot = AiBotProfile::findOrFail($botId);

        if ($bot->owner_id !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        return view('admin.knowledge-bases.create', compact('bot'));
    }

    /**
     * Store new knowledge base
     */
    public function store(Request $request, $botId)
    {
        $bot = AiBotProfile::findOrFail($botId);

        if ($bot->owner_id !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:text,pdf,docx,url,file',
            'content' => 'required_if:type,text|string',
            'url' => 'required_if:type,url|url',
            'file' => 'required_if:type,file,pdf,docx|file|max:10240', // 10MB
        ]);

        try {
            $knowledgeBase = new KnowledgeBase();
            $knowledgeBase->bot_profile_id = $bot->id;
            $knowledgeBase->name = $validated['name'];
            $knowledgeBase->description = $validated['description'] ?? null;
            $knowledgeBase->type = $validated['type'];
            $knowledgeBase->status = 'pending';

            // Handle different types
            if ($validated['type'] === 'text') {
                $knowledgeBase->raw_content = $validated['content'];
            } elseif ($validated['type'] === 'url') {
                $knowledgeBase->source_url = $validated['url'];
            } elseif (in_array($validated['type'], ['file', 'pdf', 'docx'])) {
                $file = $request->file('file');
                $path = $file->store('knowledge-bases', 'public');
                $knowledgeBase->file_path = $path;
                $knowledgeBase->file_name = $file->getClientOriginalName();
                $knowledgeBase->file_size = $file->getSize();
            }

            $knowledgeBase->save();

            // Process asynchronously (or synchronously for now)
            $this->processKnowledgeBase($knowledgeBase);

            return redirect()
                ->route('admin.knowledge-bases.show', [$botId, $knowledgeBase->id])
                ->with('success', 'Knowledge Base created and processing started');
        } catch (\Exception $e) {
            Log::error('Knowledge base creation failed', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create knowledge base: ' . $e->getMessage());
        }
    }

    /**
     * Show knowledge base details
     */
    public function show($botId, $id)
    {
        $bot = AiBotProfile::findOrFail($botId);
        $knowledgeBase = KnowledgeBase::where('bot_profile_id', $bot->id)
            ->findOrFail($id);

        if ($bot->owner_id !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $chunks = $knowledgeBase->chunks()
            ->orderBy('chunk_index')
            ->paginate(20);

        return view('admin.knowledge-bases.show', compact('bot', 'knowledgeBase', 'chunks'));
    }

    /**
     * Delete knowledge base
     */
    public function destroy($botId, $id)
    {
        $bot = AiBotProfile::findOrFail($botId);
        $knowledgeBase = KnowledgeBase::where('bot_profile_id', $bot->id)
            ->findOrFail($id);

        if ($bot->owner_id !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        // Delete file if exists
        if ($knowledgeBase->file_path && Storage::disk('public')->exists($knowledgeBase->file_path)) {
            Storage::disk('public')->delete($knowledgeBase->file_path);
        }

        $knowledgeBase->delete();

        return redirect()
            ->route('admin.knowledge-bases.index', $botId)
            ->with('success', 'Knowledge Base deleted successfully');
    }

    /**
     * Test search
     */
    public function testSearch(Request $request, $botId)
    {
        $bot = AiBotProfile::findOrFail($botId);

        if ($bot->owner_id !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'query' => 'required|string|min:3',
            'top_k' => 'nullable|integer|min:1|max:10',
        ]);

        $query = $validated['query'];
        $topK = $validated['top_k'] ?? 5;

        $result = $this->ragService->search($bot, $query, $topK);

        return response()->json([
            'success' => true,
            'query' => $query,
            'chunks' => $result['chunks']->map(function ($chunk) {
                return [
                    'id' => $chunk->id,
                    'content' => $chunk->content,
                    'similarity_score' => $chunk->similarity_score,
                    'knowledge_base_name' => $chunk->knowledgeBase->name,
                    'chunk_index' => $chunk->chunk_index,
                ];
            }),
            'search_time_ms' => $result['search_time_ms'],
            'avg_similarity' => $result['avg_similarity'],
            'max_similarity' => $result['max_similarity'],
        ]);
    }

    /**
     * Process knowledge base (extract, chunk, embed)
     */
    private function processKnowledgeBase(KnowledgeBase $knowledgeBase): void
    {
        try {
            $knowledgeBase->status = 'processing';
            $knowledgeBase->save();

            // Step 1: Extract text
            $content = $this->extractContent($knowledgeBase);

            if (empty($content)) {
                throw new \Exception('No content extracted from document');
            }

            $knowledgeBase->raw_content = $content;

            // Step 2: Chunk text
            $chunks = $this->chunkingService->chunkTextWithTokens($content);

            if (empty($chunks)) {
                throw new \Exception('No chunks created from content');
            }

            // Step 3: Create embeddings and save chunks
            foreach ($chunks as $chunkData) {
                $embedding = $this->embeddingService->createEmbedding($chunkData['content']);

                if ($embedding['success']) {
                    KnowledgeChunk::create([
                        'knowledge_base_id' => $knowledgeBase->id,
                        'content' => $chunkData['content'],
                        'chunk_index' => $chunkData['chunk_index'],
                        'token_count' => $chunkData['token_count'],
                        'start_position' => $chunkData['start_position'],
                        'end_position' => $chunkData['end_position'],
                        'embedding' => $embedding['embedding'],
                        'embedding_dimension' => $embedding['dimension'],
                    ]);
                }
            }

            // Update knowledge base
            $knowledgeBase->total_chunks = count($chunks);
            $knowledgeBase->total_tokens = array_sum(array_column($chunks, 'token_count'));
            $knowledgeBase->status = 'completed';
            $knowledgeBase->processed_at = now();
            $knowledgeBase->save();
        } catch (\Exception $e) {
            $knowledgeBase->status = 'failed';
            $knowledgeBase->error_message = $e->getMessage();
            $knowledgeBase->save();

            Log::error('Knowledge base processing failed', [
                'id' => $knowledgeBase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract content based on type
     */
    private function extractContent(KnowledgeBase $knowledgeBase): string
    {
        switch ($knowledgeBase->type) {
            case 'text':
                return $knowledgeBase->raw_content;

            case 'url':
                $result = $this->documentProcessor->processUrl($knowledgeBase->source_url);
                return $result['success'] ? $result['content'] : '';

            case 'file':
            case 'pdf':
            case 'docx':
                $filePath = storage_path('app/public/' . $knowledgeBase->file_path);
                $result = $this->documentProcessor->processFile($filePath);
                return $result['success'] ? $result['content'] : '';

            default:
                return '';
        }
    }
}
