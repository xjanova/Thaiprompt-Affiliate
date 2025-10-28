<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    public function index()
    {
        return view('vendor.dashboard');
    }

    public function sales()
    {
        return view('vendor.sales');
    }

    public function earnings()
    {
        return view('vendor.earnings');
    }

    public function pos()
    {
        return view('vendor.pos');
    }
}
