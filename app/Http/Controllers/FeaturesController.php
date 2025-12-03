<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class FeaturesController extends Controller
{
    /**
     * Show features page
     */
    public function index()
    {
        return Inertia::render('Features/Index');
    }
}
