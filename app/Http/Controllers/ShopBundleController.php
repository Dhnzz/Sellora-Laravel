<?php

namespace App\Http\Controllers;

use App\Models\ProductBundle;
use Illuminate\Http\Request;

class ShopBundleController
{
    public function index(ProductBundle $bundle)
    {
        return view('customer.bundle.checkout', compact('bundle'));
    }
}
