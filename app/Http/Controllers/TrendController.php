<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\TrendAnalysis;
use App\Models\SentimentData;
use Illuminate\Http\Request;

class TrendController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('premium')->only(['index', 'show']);
    }

    public function index()
    {
        $trendAnalyses = TrendAnalysis::with('cryptocurrency')
            ->latest()
            ->paginate(20);

        return view('trends.index', compact('trendAnalyses'));
    }

    public function show(Cryptocurrency $cryptocurrency)
    {
        $latestAnalysis = $cryptocurrency->trendAnalyses()->latest()->first();
        
        $sentimentHistory = SentimentData::whereJsonContains('keywords', $cryptocurrency->symbol)
            ->where('published_at', '>=', now()->subDays(7))
            ->orderBy('published_at')
            ->get()
            ->groupBy(function($item) {
                return $item->published_at->format('Y-m-d');
            })
            ->map(function($dayData) {
                return [
                    'avg_sentiment' => $dayData->avg('sentiment_score'),
                    'mention_count' => $dayData->count(),
                ];
            });

        return view('trends.show', compact('cryptocurrency', 'latestAnalysis', 'sentimentHistory'));
    }
}