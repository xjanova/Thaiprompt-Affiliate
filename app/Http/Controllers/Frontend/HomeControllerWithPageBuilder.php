<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PageBuilder;
use App\Services\PageBuilderService;
use Illuminate\Http\Request;

class HomeControllerWithPageBuilder extends Controller
{
    protected PageBuilderService $pageBuilderService;

    public function __construct(PageBuilderService $pageBuilderService)
    {
        $this->pageBuilderService = $pageBuilderService;
    }

    /**
     * Show the homepage using Page Builder
     */
    public function index()
    {
        // Check if system needs setup
        if (!User::where('is_super_admin', true)->exists()) {
            return redirect()->route('setup.index');
        }

        // Get homepage from Page Builder
        $homepage = $this->pageBuilderService->getPageByType('homepage');

        // If Page Builder homepage doesn't exist, return old view
        if (!$homepage) {
            return $this->fallbackToOldHomepage();
        }

        // Get render data from Page Builder
        $renderData = $this->pageBuilderService->getRenderData($homepage);

        return view('frontend.home-pagebuilder', compact('renderData'));
    }

    /**
     * Fallback to old homepage if Page Builder not set up
     */
    protected function fallbackToOldHomepage()
    {
        // Import old HomeController logic here for backward compatibility
        return app(\App\Http\Controllers\Frontend\HomeController::class)->index();
    }
}
