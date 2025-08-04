<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        
        return view('subscription.index', compact('user'));
    }

    public function upgrade(Request $request)
    {
        $user = auth()->user();
        
        // Tutaj normalnie byłaby integracja z bramką płatności
        // Na potrzeby demo ustawiamy od razu premium
        $user->update([
            'subscription_plan' => 'premium',
            'subscription_expires_at' => Carbon::now()->addMonth(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Konto zostało przełączone na plan Premium!'
        ]);
    }

    public function cancel(Request $request)
    {
        $user = auth()->user();
        
        $user->update([
            'subscription_plan' => 'free',
            'subscription_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subskrypcja została anulowana.'
        ]);
    }
}