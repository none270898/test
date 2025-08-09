<?php
// routes/api.php

use App\Http\Controllers\CryptocurrencyController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PriceAlertController;
use App\Http\Controllers\SentimentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // User status
    Route::get('user-status', [UserController::class, 'status']);
    Route::put('user-preferences', [UserController::class, 'updatePreferences']);
    
    // Portfolio routes - explicit routes instead of apiResource
    Route::get('portfolio', [PortfolioController::class, 'index']);
    Route::post('portfolio', [PortfolioController::class, 'store']);
    Route::put('portfolio/{holding}', [PortfolioController::class, 'update']);
    Route::delete('portfolio/{holding}', [PortfolioController::class, 'destroy']);
    
    // Price Alerts routes
    Route::get('alerts', [PriceAlertController::class, 'index']);
    Route::post('alerts', [PriceAlertController::class, 'store']);
    Route::put('alerts/{alert}', [PriceAlertController::class, 'update']);
    Route::delete('alerts/{alert}', [PriceAlertController::class, 'destroy']);
    Route::post('alerts/{alert}/toggle', [PriceAlertController::class, 'toggle']);
    
    // Watchlist routes
    Route::get('watchlist', [WatchlistController::class, 'index']);
    Route::post('watchlist', [WatchlistController::class, 'store']);
    Route::put('watchlist/{watchlist}', [WatchlistController::class, 'update']);
    Route::delete('watchlist/{watchlist}', [WatchlistController::class, 'destroy']);
    Route::post('watchlist/bulk-add', [WatchlistController::class, 'bulkAdd']);
    
    // Discovery routes (some require Premium)
    Route::prefix('discovery')->group(function () {
        Route::get('trending', [DiscoveryController::class, 'trending']);
        Route::get('search', [DiscoveryController::class, 'search']);
        Route::get('stats', [DiscoveryController::class, 'stats'])->middleware('premium');
        Route::get('history/{coinGeckoId}', [DiscoveryController::class, 'history'])->middleware('premium');
    });
    
    // AI Sentiment Analysis routes (Premium only)
    Route::middleware('premium')->prefix('sentiment')->group(function () {
        Route::get('dashboard', [SentimentController::class, 'dashboard']);
        Route::get('crypto/{coinGeckoId}', [SentimentController::class, 'cryptoTrend']);
        Route::get('sources', [SentimentController::class, 'sources']);
        Route::get('search', [SentimentController::class, 'search']);
    });
    
    // Cryptocurrency routes
    Route::get('cryptocurrencies', [CryptocurrencyController::class, 'index']);
    Route::get('cryptocurrencies/search', [CryptocurrencyController::class, 'search']);
});
