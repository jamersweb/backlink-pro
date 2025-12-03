<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentationController extends Controller
{
    /**
     * Show documentation page
     */
    public function index()
    {
        return Inertia::render('Documentation/Index', []);
    }
}

