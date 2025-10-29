<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Models\Slider;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the homepage
     */
    public function index()
    {
        // Check if system needs setup
        if (!User::where('is_super_admin', true)->exists()) {
            return redirect()->route('setup.index');
        }

        $stats = [
            'total_users' => User::count(),
            'total_affiliates' => \App\Models\Affiliate::count(),
        ];

        // Get active sliders
        $sliders = Slider::active()->ordered()->get();

        return view('frontend.home', compact('stats', 'sliders'));
    }

    /**
     * Show the about page
     */
    public function about()
    {
        return view('frontend.about');
    }

    /**
     * Show the contact page
     */
    public function contact()
    {
        return view('frontend.contact');
    }
}
