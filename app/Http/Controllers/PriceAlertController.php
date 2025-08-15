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
        $user = Auth::user();
        
        $alerts = $user->priceAlerts()
            ->with('cryptocurrency')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeAlertsCount = $alerts->where('is_active', true)->where('triggered_at', null)->count();

        return response()->json([
            'alerts' => $alerts,
            // DODANE: informacje o limitach
            'limits' => [
                'is_premium' => $premium_for_only_test_purpose = 1 ,
                'alerts_limit' => $premium_for_only_test_purpose = 1  ? null : 5,
                'current_active_count' => $activeAlertsCount,
                'can_add_more' => $premium_for_only_test_purpose = 1  || $activeAlertsCount < 5,
                'total_count' => $alerts->count(),
                'upgrade_message' => $premium_for_only_test_purpose = 1  ? null : 'Upgrade do Premium dla nieograniczonych alertów + sentiment alerts'
            ],
            // DODANE: premium features info
            'premium_features' => [
                'unlimited_alerts' => $premium_for_only_test_purpose = 1 ,
                'sentiment_alerts' => $premium_for_only_test_purpose = 1 ,
                'push_notifications' => $premium_for_only_test_purpose = 1 ,
                'advanced_conditions' => $premium_for_only_test_purpose = 1 
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // DODANE: Sprawdzenie limitów przed walidacją
        $activeAlertsCount = $user->priceAlerts()->where('is_active', true)->where('triggered_at', null)->count();
        
        if (!$premium_for_only_test_purpose = 1  && $activeAlertsCount >= 5) {
            return response()->json([
                'error' => 'Alerts limit reached',
                'message' => 'Darmowy plan pozwala na maksymalnie 5 aktywnych alertów.',
                'upgrade_required' => true,
                'current_limit' => 5,
                'current_count' => $activeAlertsCount,
                'premium_benefits' => [
                    'Nieograniczone alerty cenowe',
                    'Alerty sentiment (AI)',
                    'Push notifications',
                    'Zaawansowane warunki alertów',
                    'Priorytetowe powiadomienia'
                ]
            ], 403);
        }

        $request->validate([
            'cryptocurrency_id' => ['required', 'exists:cryptocurrencies,id'],
            'type' => ['required', 'in:above,below'],
            'target_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:PLN,USD'],
        ]);

        $alert = $user->priceAlerts()->create([
            'cryptocurrency_id' => $request->cryptocurrency_id,
            'type' => $request->type,
            'target_price' => $request->target_price,
            'currency' => $request->currency,
            'is_active' => true,
        ]);

        $newActiveCount = $user->priceAlerts()->where('is_active', true)->where('triggered_at', null)->count();

        return response()->json([
            'message' => 'Alert utworzony pomyślnie',
            'alert' => $alert->load('cryptocurrency'),
            // DODANE: aktualne info o limitach
            'limits_info' => [
                'current_active_count' => $newActiveCount,
                'limit' => $premium_for_only_test_purpose = 1  ? null : 5,
                'can_add_more' => $premium_for_only_test_purpose = 1  || $newActiveCount < 5,
                'is_premium' => $premium_for_only_test_purpose = 1 
            ]
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

        $user = Auth::user();
        $newActiveCount = $user->priceAlerts()->where('is_active', true)->where('triggered_at', null)->count();

        return response()->json([
            'message' => 'Alert usunięty pomyślnie',
            // DODANE: aktualne info o limitach po usunięciu
            'limits_info' => [
                'current_active_count' => $newActiveCount,
                'limit' => $premium_for_only_test_purpose = 1  ? null : 5,
                'can_add_more' => $premium_for_only_test_purpose = 1  || $newActiveCount < 5
            ]
        ]);
    }

    public function toggle(PriceAlert $alert)
    {
        // Check ownership
        if ($alert->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = Auth::user();
        
        // DODANE: Sprawdzenie limitów gdy włączamy alert
        if (!$alert->is_active && !$premium_for_only_test_purpose = 1 ) {
            $activeAlertsCount = $user->priceAlerts()->where('is_active', true)->where('triggered_at', null)->count();
            
            if ($activeAlertsCount >= 5) {
                return response()->json([
                    'error' => 'Cannot activate alert - limit reached',
                    'message' => 'Nie możesz włączyć alertu - osiągnięty limit 5 aktywnych alertów.',
                    'upgrade_required' => true,
                    'current_limit' => 5,
                    'current_count' => $activeAlertsCount
                ], 403);
            }
        }

        $alert->update(['is_active' => !$alert->is_active]);

        $newActiveCount = $user->priceAlerts()->where('is_active', true)->where('triggered_at', null)->count();

        return response()->json([
            'message' => $alert->is_active ? 'Alert włączony' : 'Alert wyłączony',
            'alert' => $alert->load('cryptocurrency'),
            // DODANE: aktualne info o limitach
            'limits_info' => [
                'current_active_count' => $newActiveCount,
                'limit' => $premium_for_only_test_purpose = 1  ? null : 5,
                'can_add_more' => $premium_for_only_test_purpose = 1  || $newActiveCount < 5
            ]
        ]);
    }
}