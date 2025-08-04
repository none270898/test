<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Witamy w CryptoNote.pl</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .welcome-content { margin: 20px 0; }
        .features { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .feature { margin: 10px 0; padding: 10px 0; border-bottom: 1px solid #eee; }
        .cta { text-align: center; margin: 30px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Witamy w CryptoNote.pl!</h1>
            <p>Dzięki za rejestrację, {{ $user->name }}!</p>
        </div>

        <div class="welcome-content">
            <p>Cieszymy się, że dołączyłeś do społeczności CryptoNote.pl - pierwszego polskiego trackera portfolio kryptowalut z AI analizą trendów.</p>
            
            <h3>Co możesz teraz zrobić:</h3>
        </div>

        <div class="features">
            <div class="feature">
                <strong>📊 Dodaj swoje pierwsze kryptowaluty</strong><br>
                Śledź wartość swojego portfolio w czasie rzeczywistym
            </div>
            <div class="feature">
                <strong>🔔 Ustaw alerty cenowe</strong><br>
                Otrzymuj powiadomienia gdy ceny osiągną Twoje cele
            </div>
            <div class="feature">
                <strong>📈 Sprawdź najnowsze ceny</strong><br>
                Zobacz aktualne kursy wszystkich popularnych kryptowalut
            </div>
            <div class="feature">
                <strong>⭐ Rozważ plan Premium</strong><br>
                Uzyskaj dostęp do AI analizy trendów i zaawansowanych funkcji
            </div>
        </div>

        <div class="cta">
            <a href="{{ url('/dashboard') }}" style="background: #1976d2; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-size: 16px;">
                Przejdź do Dashboard
            </a>
        </div>

        <div class="footer">
            <p>Jeśli masz pytania, odpowiedz na tego maila - chętnie pomożemy!</p>
            <p>Zespół CryptoNote.pl</p>
        </div>
    </div>
</body>
</html>