<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NfcCard;
use App\Models\User;
use App\Services\NFC\NfcService;
use Illuminate\Http\Request;

class NfcCardController extends Controller
{
    protected NfcService $nfcService;

    public function __construct(NfcService $nfcService)
    {
        $this->nfcService = $nfcService;
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display a listing of NFC cards
     */
    public function index(Request $request)
    {
        $query = NfcCard::with(['user', 'wallet', 'linkedBy']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'linked') {
                $query->linked();
            } elseif ($request->status === 'unlinked') {
                $query->unlinked();
            } elseif ($request->status === 'active') {
                $query->active();
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('card_uid', 'like', "%{$search}%")
                    ->orWhere('card_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $cards = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.nfc.index', compact('cards'));
    }

    /**
     * Show the form for creating a new NFC card
     */
    public function create()
    {
        return view('admin.nfc.create');
    }

    /**
     * Register a new NFC card
     */
    public function store(Request $request)
    {
        $request->validate([
            'card_uid' => 'required|string|unique:nfc_cards,card_uid',
            'card_type' => 'required|in:standard,premium,vip',
            'card_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $card = $this->nfcService->registerCard(
            $request->card_uid,
            $request->only(['card_type', 'card_name', 'notes'])
        );

        return redirect()
            ->route('admin.nfc.show', $card)
            ->with('success', 'NFC card registered successfully');
    }

    /**
     * Display the specified NFC card
     */
    public function show(NfcCard $nfcCard)
    {
        $nfcCard->load(['user', 'wallet', 'linkedBy', 'transactions.user']);

        return view('admin.nfc.show', compact('nfcCard'));
    }

    /**
     * Show the form for linking card to user
     */
    public function linkForm(NfcCard $nfcCard)
    {
        $users = User::with('wallet')
            ->whereHas('wallet')
            ->orderBy('name')
            ->get();

        return view('admin.nfc.link', compact('nfcCard', 'users'));
    }

    /**
     * Link NFC card to user
     */
    public function link(Request $request, NfcCard $nfcCard)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $result = $this->nfcService->linkCardToUser(
            $nfcCard->card_uid,
            $request->user_id,
            auth()->user()
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.nfc.show', $nfcCard)
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Unlink NFC card from user
     */
    public function unlink(NfcCard $nfcCard)
    {
        $result = $this->nfcService->unlinkCard(
            $nfcCard->card_uid,
            auth()->user()
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.nfc.show', $nfcCard)
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Activate card
     */
    public function activate(NfcCard $nfcCard)
    {
        $this->nfcService->activateCard($nfcCard->card_uid);

        return back()->with('success', 'Card activated successfully');
    }

    /**
     * Deactivate card
     */
    public function deactivate(NfcCard $nfcCard)
    {
        $this->nfcService->deactivateCard($nfcCard->card_uid);

        return back()->with('success', 'Card deactivated successfully');
    }

    /**
     * Get card transactions
     */
    public function transactions(NfcCard $nfcCard)
    {
        $transactions = $nfcCard->transactions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.nfc.transactions', compact('nfcCard', 'transactions'));
    }

    /**
     * Delete NFC card
     */
    public function destroy(NfcCard $nfcCard)
    {
        if ($nfcCard->isLinked()) {
            return back()->with('error', 'Cannot delete linked card. Please unlink first.');
        }

        $nfcCard->delete();

        return redirect()
            ->route('admin.nfc.index')
            ->with('success', 'NFC card deleted successfully');
    }

    /**
     * Scan NFC card via Web NFC API
     */
    public function scan()
    {
        return view('admin.nfc.scan');
    }
}
