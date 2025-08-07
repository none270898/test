
<?php
// routes/api.php

use App\Http\Controllers\CryptocurrencyController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PriceAlertController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
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
    
    // Cryptocurrency routes
    Route::get('cryptocurrencies', [CryptocurrencyController::class, 'index']);
    Route::get('cryptocurrencies/search', [CryptocurrencyController::class, 'search']);
});