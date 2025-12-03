<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AboutController extends Controller
{
    /**
     * Show about page
     */
    public function index()
    {
        return Inertia::render('About/Index');
    }
}
