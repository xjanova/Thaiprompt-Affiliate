<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MlmDashboardController extends Controller
{
    public function index()
    {
        return view('mlm.dashboard');
    }

    public function network()
    {
        return view('mlm.network');
    }

    public function commissions()
    {
        return view('mlm.commissions');
    }

    public function referrals()
    {
        return view('mlm.referrals');
    }

    public function sendInvitation(Request $request)
    {
        return redirect()->route('mlm.referrals')->with('success', 'Invitation sent');
    }
}
