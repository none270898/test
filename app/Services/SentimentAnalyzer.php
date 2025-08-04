<?php
// app/Services/SentimentAnalyzer.php
namespace App\Services;

class SentimentAnalyzer
{
    private $positiveWords = [
        'pump', 'moon', 'bullish', 'buy', 'hold', 'hodl', 'rocket', 'profit', 'gain',
        'wzrost', 'kupować', 'trzymać', 'zysk', 'przyszłość', 'inwestować', 'potencjał',
        'dobry', 'świetny', 'excellent', 'amazing', 'great', 'bull', 'lambo'
    ];

    private $negativeWords = [
        'dump', 'crash', 'bearish', 'sell', 'scam', 'loss', 'bear', 'drop', 'fall',
        'spadek', 'sprzedawać', 'strata', 'oszustwo', 'bubble', 'bańka', 'krach',
        'zły', 'kiepski', 'terrible', 'awful', 'bad', 'panic', 'panika', 'rip'
    ];

    private $neutralWords = [
        'analysis', 'chart', 'technical', 'support', 'resistance', 'volume',
        'analiza', 'wykres', 'wsparcie', 'opór', 'wolumen', 'techniczny'
    ];

    public function analyze($text)
    {
        $text = strtolower($text);
        $words = $this->tokenize($text);
        
        $positiveScore = 0;
        $negativeScore = 0;
        $totalWords = count($words);
        
        if ($totalWords === 0) {
            return 0;
        }

        foreach ($words as $word) {
            if (in_array($word, $this->positiveWords)) {
                $positiveScore++;
            } elseif (in_array($word, $this->negativeWords)) {
                $negativeScore++;
            }
        }

        // Normalizuj wynik do przedziału -1 do 1
        $score = ($positiveScore - $negativeScore) / $totalWords;
        
        // Wzmocnij sygnał jeśli jest dużo słów kluczowych
        $keywordDensity = ($positiveScore + $negativeScore) / $totalWords;
        if ($keywordDensity > 0.1) { // >10% słów to keywords
            $score *= 1.5;
        }

        return max(-1, min(1, $score));
    }

    private function tokenize($text)
    {
        // Usuń znaki interpunkcyjne i podziel na słowa
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        return array_map('strtolower', $words);
    }

    public function getBatchSentiment($texts)
    {
        $results = [];
        
        foreach ($texts as $text) {
            $results[] = $this->analyze($text);
        }
        
        return $results;
    }
}