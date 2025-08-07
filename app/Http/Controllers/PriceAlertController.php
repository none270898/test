<?php
// app/Http/Controllers/PriceAlertController.php

namespace App\Http\Controllers;

use App\Models\PriceAlert;
use App\Models\Cryptocurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PriceAlertController extends Controller
{
    public function index()
    {
        $alerts = Auth::user()->priceAlerts()
            ->with('cryptocurrency')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($alerts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cryptocurrency_id' => ['required', 'exists:cryptocurrencies,id'],
            'type' => ['required', 'in:above,below'],
            'target_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:PLN,USD'],
        ]);

        $alert = Auth::user()->priceAlerts()->create([
            'cryptocurrency_id' => $request->cryptocurrency_id,
            'type' => $request->type,
            'target_price' => $request->target_price,
            'currency' => $request->currency,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Alert utworzony pomyślnie',
            'alert' => $alert->load('cryptocurrency')
        ], 201);
    }

    public function update(Request $request, PriceAlert $alert)
    {
        // Check ownership
        if ($alert->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type' => ['sometimes', 'in:above,below'],
            'target_price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'in:PLN,USD'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $alert->update($request->only(['type', 'target_price', 'currency', 'is_active']));

        return response()->json([
            'message' => 'Alert zaktualizowany pomyślnie',
            'alert' => $alert->load('cryptocurrency')
        ]);
    }

    public function destroy(PriceAlert $alert)
    {
        // Check ownership
        if ($alert->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $alert->delete();

        return response()->json(['message' => 'Alert usunięty pomyślnie']);
    }

    public function toggle(PriceAlert $alert)
    {
        // Check ownership
        if ($alert->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $alert->update(['is_active' => !$alert->is_active]);

        return response()->json([
            'message' => $alert->is_active ? 'Alert włączony' : 'Alert wyłączony',
            'alert' => $alert->load('cryptocurrency')
        ]);
    }
}