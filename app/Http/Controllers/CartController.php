<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        return view('cart.index');
    }

    public function add(Request $request)
    {
        return redirect()->route('cart.index')->with('success', 'Product added to cart');
    }

    public function update(Request $request, $item)
    {
        return redirect()->route('cart.index')->with('success', 'Cart updated');
    }

    public function remove($item)
    {
        return redirect()->route('cart.index')->with('success', 'Item removed from cart');
    }
}
