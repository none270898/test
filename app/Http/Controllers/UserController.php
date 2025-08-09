<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get current user status including premium
     */
    public function status()
    {
        $user = Auth::user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'isPremium' => $user->isPremium(),
            'premium' => $user->premium,
            'premium_expires_at' => $user->premium_expires_at,
            'alerts_enabled' => $user->alerts_enabled,
            'email_notifications' => $user->email_notifications,
        ]);
    }
    
    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'alerts_enabled' => 'boolean',
            'email_notifications' => 'boolean',
        ]);
        
        $user = Auth::user();
        $user->update($request->only(['alerts_enabled', 'email_notifications']));
        
        return response()->json([
            'message' => 'Preferences updated successfully',
            'user' => $this->status()->getData()
        ]);
    }
}