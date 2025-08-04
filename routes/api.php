<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CryptocurrencyController;
use App\Http\Controllers\Api\PortfolioController as ApiPortfolioController;

Route::prefix('v1')->group(function () {
    // Public API routes
    Route::get('/cryptocurrencies', [CryptocurrencyController::class, 'index']);
    Route::get('/cryptocurrencies/{cryptocurrency}', [CryptocurrencyController::class, 'show']);
    Route::get('/prices', [CryptocurrencyController::class, 'prices']);
    
    // Protected API routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/portfolio', [ApiPortfolioController::class, 'index']);
        Route::get('/portfolio/summary', [ApiPortfolioController::class, 'summary']);
    });
});