<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index()
    {
        return view('wallet.index');
    }

    public function transactions()
    {
        return view('wallet.transactions');
    }

    public function withdraw(Request $request)
    {
        return redirect()->route('wallet.withdrawals')->with('success', 'Withdrawal request submitted');
    }

    public function withdrawals()
    {
        return view('wallet.withdrawals');
    }
}
