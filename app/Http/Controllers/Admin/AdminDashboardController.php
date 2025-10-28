<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function users()
    {
        return view('admin.users');
    }

    public function vendors()
    {
        return view('admin.vendors.index');
    }

    public function orders()
    {
        return view('admin.orders');
    }

    public function products()
    {
        return view('admin.products');
    }

    public function commissions()
    {
        return view('admin.commissions');
    }

    public function withdrawals()
    {
        return view('admin.withdrawals');
    }

    public function approveVendor($vendor)
    {
        return redirect()->route('admin.vendors')->with('success', 'Vendor approved');
    }

    public function rejectVendor($vendor)
    {
        return redirect()->route('admin.vendors')->with('success', 'Vendor rejected');
    }

    public function approveWithdrawal($withdrawal)
    {
        return redirect()->route('admin.withdrawals')->with('success', 'Withdrawal approved');
    }

    public function rejectWithdrawal($withdrawal)
    {
        return redirect()->route('admin.withdrawals')->with('success', 'Withdrawal rejected');
    }
}
