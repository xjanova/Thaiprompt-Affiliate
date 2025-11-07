<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TarotCard;
use App\Models\TarotCardBackImage;
use App\Models\TarotReadingCategory;
use App\Models\TarotSpreadType;
use App\Models\TarotReading;
use App\Models\TarotSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TarotManagementController extends Controller
{
    /**
     * Show tarot dashboard
     */
    public function index()
    {
        $stats = [
            'total_readings' => TarotReading::count(),
            'today_readings' => TarotReading::today()->count(),
            'free_readings' => TarotReading::free()->count(),
            'paid_readings' => TarotReading::paid()->count(),
            'total_revenue' => TarotReading::paid()->sum('amount_paid'),
            'total_cards' => TarotCard::count(),
            'active_cards' => TarotCard::active()->count(),
            'categories_count' => TarotReadingCategory::count(),
        ];

        $recent_readings = TarotReading::with(['user', 'category', 'spreadType'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.tarot.index', compact('stats', 'recent_readings'));
    }

    /**
     * Cards Management
     */
    public function cardsIndex()
    {
        $cards = TarotCard::orderBy('type')->orderBy('suit')->orderBy('number')->paginate(30);

        return view('admin.tarot.cards.index', compact('cards'));
    }

    public function cardsCreate()
    {
        return view('admin.tarot.cards.create');
    }

    public function cardsStore(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_th' => 'required|string|max:255',
            'type' => 'required|in:major_arcana,minor_arcana',
            'suit' => 'nullable|in:wands,cups,swords,pentacles',
            'number' => 'nullable|integer',
            'description_en' => 'nullable|string',
            'description_th' => 'nullable|string',
            'upright_meaning_en' => 'nullable|string',
            'upright_meaning_th' => 'nullable|string',
            'reversed_meaning_en' => 'nullable|string',
            'reversed_meaning_th' => 'nullable|string',
            'keywords_en' => 'nullable|string',
            'keywords_th' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $data = $request->except(['image', 'keywords_en', 'keywords_th']);

        // Handle keywords
        if ($request->keywords_en) {
            $data['keywords_en'] = array_map('trim', explode(',', $request->keywords_en));
        }
        if ($request->keywords_th) {
            $data['keywords_th'] = array_map('trim', explode(',', $request->keywords_th));
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('tarot/cards', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        TarotCard::create($data);

        return redirect()->route('admin.tarot.cards.index')
            ->with('success', 'Card created successfully');
    }

    public function cardsEdit($id)
    {
        $card = TarotCard::findOrFail($id);

        return view('admin.tarot.cards.edit', compact('card'));
    }

    public function cardsUpdate(Request $request, $id)
    {
        $card = TarotCard::findOrFail($id);

        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_th' => 'required|string|max:255',
            'type' => 'required|in:major_arcana,minor_arcana',
            'suit' => 'nullable|in:wands,cups,swords,pentacles',
            'number' => 'nullable|integer',
            'description_en' => 'nullable|string',
            'description_th' => 'nullable|string',
            'upright_meaning_en' => 'nullable|string',
            'upright_meaning_th' => 'nullable|string',
            'reversed_meaning_en' => 'nullable|string',
            'reversed_meaning_th' => 'nullable|string',
            'keywords_en' => 'nullable|string',
            'keywords_th' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $data = $request->except(['image', 'keywords_en', 'keywords_th']);

        // Handle keywords
        if ($request->keywords_en) {
            $data['keywords_en'] = array_map('trim', explode(',', $request->keywords_en));
        }
        if ($request->keywords_th) {
            $data['keywords_th'] = array_map('trim', explode(',', $request->keywords_th));
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($card->image_url) {
                $oldPath = str_replace('/storage/', '', $card->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('tarot/cards', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $card->update($data);

        return redirect()->route('admin.tarot.cards.index')
            ->with('success', 'Card updated successfully');
    }

    public function cardsDestroy($id)
    {
        $card = TarotCard::findOrFail($id);

        // Delete image
        if ($card->image_url) {
            $oldPath = str_replace('/storage/', '', $card->image_url);
            Storage::disk('public')->delete($oldPath);
        }

        $card->delete();

        return redirect()->route('admin.tarot.cards.index')
            ->with('success', 'Card deleted successfully');
    }

    /**
     * Categories Management
     */
    public function categoriesIndex()
    {
        $categories = TarotReadingCategory::ordered()->get();

        return view('admin.tarot.categories.index', compact('categories'));
    }

    public function categoriesCreate()
    {
        return view('admin.tarot.categories.create');
    }

    public function categoriesStore(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_th' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:tarot_reading_categories,slug',
            'description_en' => 'nullable|string',
            'description_th' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'price' => 'required|numeric|min:0',
            'is_free_first' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->all();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($request->name_en);
        }

        TarotReadingCategory::create($data);

        return redirect()->route('admin.tarot.categories.index')
            ->with('success', 'Category created successfully');
    }

    public function categoriesEdit($id)
    {
        $category = TarotReadingCategory::findOrFail($id);

        return view('admin.tarot.categories.edit', compact('category'));
    }

    public function categoriesUpdate(Request $request, $id)
    {
        $category = TarotReadingCategory::findOrFail($id);

        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_th' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:tarot_reading_categories,slug,' . $id,
            'description_en' => 'nullable|string',
            'description_th' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'price' => 'required|numeric|min:0',
            'is_free_first' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->all();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($request->name_en);
        }

        $category->update($data);

        return redirect()->route('admin.tarot.categories.index')
            ->with('success', 'Category updated successfully');
    }

    public function categoriesDestroy($id)
    {
        $category = TarotReadingCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.tarot.categories.index')
            ->with('success', 'Category deleted successfully');
    }

    /**
     * Card Back Images Management
     */
    public function cardBacksIndex()
    {
        $cardBacks = TarotCardBackImage::orderBy('sort_order')->get();

        return view('admin.tarot.card-backs.index', compact('cardBacks'));
    }

    public function cardBacksStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|max:2048',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $path = $request->file('image')->store('tarot/card-backs', 'public');

        TarotCardBackImage::create([
            'name' => $request->name,
            'image_url' => '/storage/' . $path,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => TarotCardBackImage::max('sort_order') + 1,
        ]);

        return redirect()->route('admin.tarot.card-backs.index')
            ->with('success', 'Card back image uploaded successfully');
    }

    public function cardBacksSetDefault($id)
    {
        $cardBack = TarotCardBackImage::findOrFail($id);

        // Remove default from all others
        TarotCardBackImage::where('id', '!=', $id)->update(['is_default' => false]);

        $cardBack->is_default = true;
        $cardBack->save();

        return redirect()->route('admin.tarot.card-backs.index')
            ->with('success', 'Default card back set successfully');
    }

    public function cardBacksDestroy($id)
    {
        $cardBack = TarotCardBackImage::findOrFail($id);

        if ($cardBack->is_default) {
            return redirect()->route('admin.tarot.card-backs.index')
                ->with('error', 'Cannot delete the default card back image');
        }

        // Delete image file
        if ($cardBack->image_url) {
            $oldPath = str_replace('/storage/', '', $cardBack->image_url);
            Storage::disk('public')->delete($oldPath);
        }

        $cardBack->delete();

        return redirect()->route('admin.tarot.card-backs.index')
            ->with('success', 'Card back image deleted successfully');
    }

    /**
     * Spread Types Management
     */
    public function spreadTypesIndex()
    {
        $spreadTypes = TarotSpreadType::ordered()->get();

        return view('admin.tarot.spread-types.index', compact('spreadTypes'));
    }

    /**
     * Settings Management
     */
    public function settings()
    {
        $settings = TarotSetting::all()->pluck('value', 'key');

        return view('admin.tarot.settings', compact('settings'));
    }

    public function settingsUpdate(Request $request)
    {
        $request->validate([
            'enable_tarot_system' => 'boolean',
            'allow_guest_readings' => 'boolean',
            'show_reversed_cards' => 'boolean',
            'enable_ai_interpretation' => 'boolean',
            'save_readings_days' => 'nullable|integer|min:1',
            'animation_speed' => 'nullable|in:slow,medium,fast',
        ]);

        foreach ($request->all() as $key => $value) {
            if ($key === '_token') continue;

            $type = 'string';
            if (in_array($key, ['enable_tarot_system', 'allow_guest_readings', 'show_reversed_cards', 'enable_ai_interpretation'])) {
                $type = 'boolean';
                $value = $request->boolean($key);
            } elseif (in_array($key, ['save_readings_days'])) {
                $type = 'integer';
            }

            TarotSetting::set($key, $value, $type);
        }

        return redirect()->route('admin.tarot.settings')
            ->with('success', 'Settings updated successfully');
    }

    /**
     * Readings Management
     */
    public function readingsIndex()
    {
        $readings = TarotReading::with(['user', 'category', 'spreadType'])
            ->latest()
            ->paginate(50);

        return view('admin.tarot.readings.index', compact('readings'));
    }

    public function readingsShow($id)
    {
        $reading = TarotReading::with(['user', 'category', 'spreadType', 'cards.card'])->findOrFail($id);

        return view('admin.tarot.readings.show', compact('reading'));
    }

    public function readingsDestroy($id)
    {
        $reading = TarotReading::findOrFail($id);
        $reading->delete();

        return redirect()->route('admin.tarot.readings.index')
            ->with('success', 'Reading deleted successfully');
    }

    /**
     * Analytics
     */
    public function analytics()
    {
        // Get reading statistics
        $stats = [
            'total_readings' => TarotReading::count(),
            'free_readings' => TarotReading::free()->count(),
            'paid_readings' => TarotReading::paid()->count(),
            'total_revenue' => TarotReading::paid()->sum('amount_paid'),
            'avg_reading_price' => TarotReading::paid()->avg('amount_paid'),
        ];

        // Readings by category
        $readingsByCategory = TarotReading::selectRaw('category_id, count(*) as count, sum(amount_paid) as revenue')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        // Readings by spread type
        $readingsBySpread = TarotReading::selectRaw('spread_type_id, count(*) as count')
            ->groupBy('spread_type_id')
            ->with('spreadType')
            ->get();

        // Most popular cards
        $popularCards = \DB::table('tarot_reading_cards')
            ->select('card_id', \DB::raw('count(*) as count'))
            ->groupBy('card_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.tarot.analytics', compact(
            'stats',
            'readingsByCategory',
            'readingsBySpread',
            'popularCards'
        ));
    }
}
