<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VendorProductController extends Controller
{
    public function index()
    {
        return view('vendor.products.index');
    }

    public function create()
    {
        return view('vendor.products.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('vendor.products.index')->with('success', 'Product created');
    }

    public function show(string $id)
    {
        return view('vendor.products.show', compact('id'));
    }

    public function edit(string $id)
    {
        return view('vendor.products.edit', compact('id'));
    }

    public function update(Request $request, string $id)
    {
        return redirect()->route('vendor.products.index')->with('success', 'Product updated');
    }

    public function destroy(string $id)
    {
        return redirect()->route('vendor.products.index')->with('success', 'Product deleted');
    }
}
