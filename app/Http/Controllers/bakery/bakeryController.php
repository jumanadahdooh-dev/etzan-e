<?php

namespace App\Http\Controllers\bakery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class bakeryController extends Controller
{
    public function index()
    {
        return view('bakery.index');
    }

    public function about()
    {
        return view('bakery.about');
    }

    public function menu()
    {
        return view('bakery.menu');
    }

    public function contact()
    {
        return view('bakery.contact');
    }

    public function productDetails()
    {
        return view('bakery.productDetails');
    }
}
