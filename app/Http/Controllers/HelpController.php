<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HelpController extends Controller
{
    /**
     * Show help/support page
     */
    public function index()
    {
        return Inertia::render('Help/Index', []);
    }
}

