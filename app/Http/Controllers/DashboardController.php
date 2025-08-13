<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
         \Log::info('Dashboard accessed for user: ' . auth()->id());
        return view('dashboard');
    }
}