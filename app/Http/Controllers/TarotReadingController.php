<?php

namespace App\Http\Controllers;

use App\Models\TarotCard;
use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Models\TarotReadingCategory;
use App\Models\TarotSpreadType;
use App\Models\TarotUserLimit;
use App\Models\TarotCardBackImage;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TarotReadingController extends Controller
{
    /**
     * Show the tarot reading homepage
     */
    public function index()
    {
        $categories = TarotReadingCategory::active()->ordered()->get();
        $cardBackImage = TarotCardBackImage::getDefault();

        return view('frontend.tarot.index', compact('categories', 'cardBackImage'));
    }

    /**
     * Show category selection and spread types
     */
    public function showCategory($slug)
    {
        $category = TarotReadingCategory::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $spreadTypes = TarotSpreadType::active()->ordered()->get();
        $cardBackImage = TarotCardBackImage::getDefault();

        // Check if user can use free reading
        $userId = Auth::id();
        $sessionId = session()->getId();
        $canUseFree = TarotUserLimit::canUseFreeReading($category->id, $userId, $sessionId);

        return view('frontend.tarot.category', compact('category', 'spreadTypes', 'cardBackImage', 'canUseFree'));
    }

    /**
     * Start a new reading
     */
    public function startReading(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:tarot_reading_categories,id',
            'spread_type_id' => 'required|exists:tarot_spread_types,id',
            'question' => 'nullable|string|max:500',
            'use_free' => 'boolean',
        ]);

        $category = TarotReadingCategory::findOrFail($request->category_id);
        $spreadType = TarotSpreadType::findOrFail($request->spread_type_id);

        $userId = Auth::id();
        $sessionId = session()->getId();

        // Check if free reading is available
        $isFree = false;
        if ($request->use_free && $category->is_free_first) {
            $canUseFree = TarotUserLimit::canUseFreeReading($category->id, $userId, $sessionId);
            if ($canUseFree) {
                $isFree = true;
            }
        }

        // If not free and category has price, check payment
        if (!$isFree && $category->price > 0) {
            // Return payment required response
            return response()->json([
                'requires_payment' => true,
                'amount' => $category->price,
                'category' => $category->name_th,
                'payment_url' => route('tarot.payment', [
                    'category' => $category->id,
                    'spread_type' => $spreadType->id,
                    'question' => $request->question,
                ])
            ]);
        }

        // Create the reading (without cards yet - user will select them)
        $reading = $this->createReading($category, $spreadType, $request->question, $isFree);

        // Store whether this is a free reading in session for card selection page
        session(['tarot_reading_' . $reading->id . '_is_free' => $isFree]);

        return response()->json([
            'success' => true,
            'reading_id' => $reading->id,
            'redirect_url' => route('tarot.select-cards', $reading->id)
        ]);
    }

    /**
     * Show reading result
     */
    public function showReading($id)
    {
        $reading = TarotReading::with(['category', 'spreadType', 'cards.card'])->findOrFail($id);

        // Check if user has access to this reading
        $userId = Auth::id();
        $sessionId = session()->getId();

        if (!$reading->belongsToUser($userId, $sessionId) && !Auth::check()) {
            abort(403, 'Unauthorized access to this reading');
        }

        $cardBackImage = TarotCardBackImage::getDefault();

        return view('frontend.tarot.reading', compact('reading', 'cardBackImage'));
    }

    /**
     * Save reading for logged-in users
     */
    public function saveReading($id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'You must be logged in to save readings'], 401);
        }

        $reading = TarotReading::findOrFail($id);

        // Check ownership
        if ($reading->user_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reading->is_saved = true;
        $reading->save();

        return response()->json(['success' => true, 'message' => 'Reading saved successfully']);
    }

    /**
     * Show user's reading history
     */
    public function history()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view your reading history');
        }

        $readings = TarotReading::with(['category', 'spreadType'])
            ->forUser(Auth::id())
            ->latest()
            ->paginate(20);

        return view('frontend.tarot.history', compact('readings'));
    }

    /**
     * Show saved readings
     */
    public function savedReadings()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view saved readings');
        }

        $readings = TarotReading::with(['category', 'spreadType'])
            ->forUser(Auth::id())
            ->saved()
            ->latest()
            ->paginate(20);

        return view('frontend.tarot.saved', compact('readings'));
    }

    /**
     * Payment page for paid readings
     */
    public function payment(Request $request)
    {
        $category = TarotReadingCategory::findOrFail($request->category);
        $spreadType = TarotSpreadType::findOrFail($request->spread_type);

        if ($category->price == 0) {
            return redirect()->route('tarot.category', $category->slug);
        }

        return view('frontend.tarot.payment', compact('category', 'spreadType'));
    }

    /**
     * Process payment and create reading
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:tarot_reading_categories,id',
            'spread_type_id' => 'required|exists:tarot_spread_types,id',
            'payment_method' => 'required|in:wallet,promptpay,credit_card,bank_transfer',
            'question' => 'nullable|string|max:500',
        ]);

        $category = TarotReadingCategory::findOrFail($request->category_id);
        $spreadType = TarotSpreadType::findOrFail($request->spread_type_id);

        $userId = Auth::id();

        // Create payment transaction
        $transaction = PaymentTransaction::create([
            'transaction_id' => 'TAROT-' . strtoupper(Str::random(12)),
            'user_id' => $userId,
            'type' => 'order_payment',
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'amount' => $category->price,
            'currency' => 'THB',
            'metadata' => [
                'type' => 'tarot_reading',
                'category_id' => $category->id,
                'spread_type_id' => $spreadType->id,
                'question' => $request->question,
            ],
        ]);

        // TODO: Integrate with actual payment gateway
        // For now, we'll mark as completed for testing
        $transaction->status = 'completed';
        $transaction->paid_at = now();
        $transaction->save();

        // Create the reading
        $reading = $this->createReading($category, $spreadType, $request->question, false, $category->price);

        // Select random cards
        $cards = $this->selectRandomCards($spreadType->card_count, $spreadType);

        // Create reading cards
        foreach ($cards as $index => $cardData) {
            TarotReadingCard::create([
                'reading_id' => $reading->id,
                'card_id' => $cardData['card']->id,
                'position' => $index + 1,
                'position_name' => $spreadType->getPositionName($index + 1),
                'is_reversed' => $cardData['is_reversed'],
            ]);
        }

        // Update user limits
        TarotUserLimit::incrementPaidReading(
            $category->id,
            $userId,
            session()->getId(),
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'reading_id' => $reading->id,
            'redirect_url' => route('tarot.reading.show', $reading->id)
        ]);
    }

    /**
     * Show interactive card selection page
     */
    public function showCardSelection($readingId)
    {
        $reading = TarotReading::with(['category', 'spreadType'])->findOrFail($readingId);

        // Check if user has access to this reading
        $userId = Auth::id();
        $sessionId = session()->getId();

        if (!$reading->belongsToUser($userId, $sessionId) && !Auth::check()) {
            abort(403, 'Unauthorized access to this reading');
        }

        // Check if cards are already selected
        if ($reading->cards()->count() > 0) {
            return redirect()->route('tarot.reading.show', $reading->id);
        }

        $spreadType = $reading->spreadType;
        $cardBackImage = TarotCardBackImage::getDefault();

        return view('frontend.tarot.select-cards', compact('reading', 'spreadType', 'cardBackImage', 'readingId'));
    }

    /**
     * Save user's card selection
     */
    public function saveCardSelection(Request $request)
    {
        $request->validate([
            'reading_id' => 'required|exists:tarot_readings,id',
            'selected_card_indices' => 'required|array',
        ]);

        $reading = TarotReading::with('spreadType')->findOrFail($request->reading_id);

        // Check if user has access
        $userId = Auth::id();
        $sessionId = session()->getId();

        if (!$reading->belongsToUser($userId, $sessionId) && !Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if cards are already selected
        if ($reading->cards()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cards already selected']);
        }

        // Validate correct number of cards
        if (count($request->selected_card_indices) != $reading->spreadType->card_count) {
            return response()->json(['success' => false, 'message' => 'Invalid number of cards selected']);
        }

        // Get all active tarot cards
        $allCards = TarotCard::active()->get();

        // Map selected indices to actual cards
        $selectedCardIndices = $request->selected_card_indices;

        foreach ($selectedCardIndices as $position => $cardIndex) {
            // Use the card index to select from the deck (simulate random selection based on user's choice)
            $card = $allCards->get($cardIndex % $allCards->count());
            $isReversed = rand(0, 1) === 1; // 50% chance of reversed

            TarotReadingCard::create([
                'reading_id' => $reading->id,
                'card_id' => $card->id,
                'position' => $position + 1,
                'position_name' => $reading->spreadType->getPositionName($position + 1),
                'is_reversed' => $isReversed,
            ]);
        }

        // Update user limits if free
        $isFree = session('tarot_reading_' . $reading->id . '_is_free', false);
        if ($isFree) {
            TarotUserLimit::incrementFreeReading(
                $reading->category_id,
                $userId,
                $sessionId,
                request()->ip()
            );

            // Clear session
            session()->forget('tarot_reading_' . $reading->id . '_is_free');
        }

        return response()->json([
            'success' => true,
            'reading_id' => $reading->id,
            'redirect_url' => route('tarot.reading.show', $reading->id)
        ]);
    }

    /**
     * Create a reading record
     */
    private function createReading($category, $spreadType, $question, $isFree, $amountPaid = 0)
    {
        return TarotReading::create([
            'user_id' => Auth::id(),
            'category_id' => $category->id,
            'spread_type_id' => $spreadType->id,
            'question' => $question,
            'is_free' => $isFree,
            'amount_paid' => $amountPaid,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Select random cards for reading
     */
    private function selectRandomCards($count, $spreadType)
    {
        $allCards = TarotCard::active()->get();
        $selectedCards = $allCards->random($count);

        $cards = [];
        foreach ($selectedCards as $index => $card) {
            $isReversed = rand(0, 1) === 1; // 50% chance of reversed

            $cards[] = [
                'card' => $card,
                'is_reversed' => $isReversed,
                'position' => $index + 1,
                'position_name' => $spreadType->getPositionName($index + 1),
            ];
        }

        return $cards;
    }

    /**
     * Get available card back images
     */
    public function getCardBackImages()
    {
        $images = TarotCardBackImage::active()->orderBy('sort_order')->get();

        return response()->json($images);
    }
}
