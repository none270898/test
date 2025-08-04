<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tygodniowy Raport Portfolio - CryptoNote.pl</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .portfolio-item { padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        .crypto-symbol { font-weight: bold; color: #1976d2; }
        .positive { color: #2e7d32; }
        .negative { color: #d32f2f; }
        .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Tygodniowy Raport Portfolio</h1>
            <p>Podsumowanie Twojego portfolio za ostatni tydzień</p>
        </div>

        <div class="summary">
            <h2>Podsumowanie</h2>
            <p><strong>Całkowita wartość:</strong> {{ number_format($portfolioData['total_value'], 2) }} PLN</p>
            <p><strong>Zysk/Strata:</strong> 
                <span class="{{ $portfolioData['profit_loss'] >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($portfolioData['profit_loss'], 2) }} PLN 
                    ({{ number_format($portfolioData['profit_loss_percentage'], 2) }}%)
                </span>
            </p>
        </div>

        <h3>Twoje Holdingsy</h3>
        @foreach($portfolioData['portfolios'] as $portfolio)
            <div class="portfolio-item">
                <div>
                    <span class="crypto-symbol">{{ $portfolio->cryptocurrency->symbol }}</span>
                    <br>
                    <small>{{ number_format($portfolio->amount, 4) }} {{ $portfolio->cryptocurrency->symbol }}</small>
                </div>
                <div style="text-align: right;">
                    <div>{{ number_format($portfolio->current_value, 2) }} PLN</div>
                    <div class="{{ $portfolio->profit_loss >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format($portfolio->profit_loss_percentage, 1) }}%
                    </div>
                </div>
            </div>
        @endforeach

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/portfolio') }}" style="background: #1976d2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                Zobacz Pełne Portfolio
            </a>
        </div>

        <div class="footer">
            <p>CryptoNote.pl - Tygodniowy raport portfolio</p>
            <p><small>Raport generowany automatycznie w każdą niedzielę</small></p>
        </div>
    </div>
</body>
</html>