<html>
<head>
    <meta charset="utf-8">
    <title>Alert Cenowy - CryptoNote.pl</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .alert-info { background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .crypto-name { font-size: 24px; font-weight: bold; color: #1976d2; }
        .price { font-size: 28px; font-weight: bold; color: #2e7d32; }
        .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Alert Cenowy</h1>
            <p>Twój alert cenowy został uruchomiony!</p>
        </div>

        <div class="alert-info">
            <div class="crypto-name">{{ $crypto->name }} ({{ $crypto->symbol }})</div>
            <div class="price">{{ number_format($currentPrice, 2) }} {{ $alert->currency }}</div>
            <p><strong>Typ alertu:</strong> {{ $alert->alert_type === 'above' ? 'Powyżej' : 'Poniżej' }}</p>
            <p><strong>Cena docelowa:</strong> {{ number_format($alert->target_price, 2) }} {{ $alert->currency }}</p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/portfolio') }}" style="background: #1976d2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                Zobacz Portfolio
            </a>
        </div>

        <div class="footer">
            <p>CryptoNote.pl - Twój portfolio kryptowalut</p>
            <p><a href="{{ url('/alerts') }}">Zarządzaj alertami</a></p>
        </div>
    </div>
</body>
</html>