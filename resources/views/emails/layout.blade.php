<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - CryptoNote.pl</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .email-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .email-logo {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .email-logo .brand-icon {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            margin-right: 0.5rem;
        }
        
        .email-body {
            padding: 2rem;
        }
        
        .greeting {
            font-size: 1.25rem;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .content {
            color: #4b5563;
            margin-bottom: 2rem;
            line-height: 1.8;
        }
        
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white !important;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin: 1rem 0;
            transition: transform 0.2s;
        }
        
        .button:hover {
            transform: translateY(-2px);
        }
        
        .button-container {
            text-align: center;
            margin: 2rem 0;
        }
        
        .footer-text {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .email-footer {
            background: #f3f4f6;
            padding: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .link-alternative {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.875rem;
            color: #6b7280;
            word-break: break-all;
        }
        
        .warning-box {
            background: #fef3cd;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 1rem;
            margin: 1.5rem 0;
        }
        
        .warning-box p {
            color: #92400e;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .crypto-stats {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
        .crypto-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .email-header,
            .email-body {
                padding: 1.5rem;
            }
            
            .button {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="email-logo">
                <span class="brand-icon">₿</span>CryptoNote.pl
            </div>
            <p>@yield('header-subtitle', 'Portfolio Crypto dla Polaków')</p>
        </div>
        
        <div class="email-body">
            @yield('content')
        </div>
        
        <div class="email-footer">
            <p>© {{ date('Y') }} CryptoNote.pl - Zarządzaj swoim portfolio krypto</p>
            <p>Ten email został wysłany automatycznie. Prosimy nie odpowiadać na tę wiadomość.</p>
        </div>
    </div>
</body>
</html>