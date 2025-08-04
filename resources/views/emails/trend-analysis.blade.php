<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Analiza Trendu - CryptoNote.pl</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .trend-info { background: #f3e5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .crypto-name { font-size: 24px; font-weight: bold; color: #7b1fa2; }
        .trend-direction { font-size: 20px; font-weight: bold; }
        .trend-up { color: #2e7d32; }
        .trend-down { color: #d32f2f; }
        .trend-neutral { color: #f57c00; }
        .confidence { font-size: 18px; margin: 10px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 Analiza Trendu AI</h1>
            <p>Nowa analiza sentiment dla Twojej kryptowaluty</p>
        </div>

        <div class="trend-info">
            <div class="crypto-name">{{ $crypto->name }} ({{ $crypto->symbol }})</div>
            
            <div class="trend-direction {{ 'trend-' . $analysis->trend_direction }}">
                {{ $analysis->getTrendEmoji() }}
                @if($analysis->trend_direction === 'up')
                    Trend Wzrostowy
                @elseif($analysis->trend_direction === 'down')
                    Trend Spadkowy
                @else
                    Trend Neutralny
                @endif
            </div>
            
            <div class="confidence">
                <strong>Pewność:</strong> {{ $analysis->confidence_score }}% ({{ $analysis->getConfidenceLabel() }})
            </div>
            
            <p><strong>Liczba wzmianek:</strong> {{ $analysis->mention_count }}</p>
            <p><strong>Średni sentiment:</strong> {{ number_format($analysis->sentiment_avg, 2) }}</p>
            
            <p><strong>Źródła:</strong></p>
            <ul>
                @foreach($analysis->source_breakdown as $source => $sentiment)
                    <li>{{ ucfirst($source) }}: {{ number_format($sentiment, 2) }}</li>
                @endforeach
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/trends') }}" style="background: #7b1fa2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                Zobacz Pełną Analizę
            </a>
        </div>

        <div class="footer">
            <p>CryptoNote.pl Premium - AI Analiza Trendów</p>
            <p><small>Ta funkcja jest dostępna tylko dla użytkowników Premium</small></p>
        </div>
    </div>
</body>
</html>