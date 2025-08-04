<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\PriceAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PriceAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $alerts = auth()->user()->priceAlerts()->with('cryptocurrency')->get();
        $cryptocurrencies = Cryptocurrency::orderBy('name')->get();
        
        return view('alerts.index', compact('alerts', 'cryptocurrencies'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cryptocurrency_id' => 'required|exists:cryptocurrencies,id',
            'alert_type' => 'required|in:above,below',
            'target_price' => 'required|numeric|min:0.00000001',
            'currency' => 'required|in:PLN,USD',
            'email_notification' => 'boolean',
            'push_notification' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $alert = PriceAlert::create([
            'user_id' => auth()->id(),
            'cryptocurrency_id' => $request->cryptocurrency_id,
            'alert_type' => $request->alert_type,
            'target_price' => $request->target_price,
            'currency' => $request->currency,
            'email_notification' => $request->boolean('email_notification', true),
            'push_notification' => $request->boolean('push_notification', true),
        ]);

        return response()->json([
            'success' => true,
            'alert' => $alert->load('cryptocurrency')
        ]);
    }

    public function update(Request $request, PriceAlert $alert)
    {
        $this->authorize('update', $alert);

        $validator = Validator::make($request->all(), [
            'target_price' => 'required|numeric|min:0.00000001',
            'email_notification' => 'boolean',
            'push_notification' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $alert->update($request->only([
            'target_price', 
            'email_notification', 
            'push_notification', 
            'is_active'
        ]));

        return response()->json([
            'success' => true,
            'alert' => $alert->load('cryptocurrency')
        ]);
    }

    public function destroy(PriceAlert $alert)
    {
        $this->authorize('delete', $alert);
        
        $alert->delete();

        return response()->json(['success' => true]);
    }
}