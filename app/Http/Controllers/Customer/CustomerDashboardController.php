<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        return view('customer.dashboard');
    }

    public function orders()
    {
        return view('customer.orders');
    }

    public function orderDetail($order)
    {
        return view('customer.order-detail', compact('order'));
    }

    public function profile()
    {
        return view('customer.profile');
    }

    public function updateProfile(Request $request)
    {
        return redirect()->route('customer.profile')->with('success', 'Profile updated');
    }
}
