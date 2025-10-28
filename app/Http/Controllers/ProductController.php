<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index');
    }

    public function show($slug)
    {
        return view('products.show', compact('slug'));
    }

    public function category($slug)
    {
        return view('products.category', compact('slug'));
    }

    public function vendors()
    {
        return view('vendors.index');
    }

    public function vendor($slug)
    {
        return view('vendor.show', compact('slug'));
    }
}
