<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index()
    {
        return view('checkout.index');
    }

    public function process(Request $request)
    {
        return redirect()->route('checkout.success', ['order' => 1]);
    }

    public function success($order)
    {
        return view('checkout.success', compact('order'));
    }
}
