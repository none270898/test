@extends('layouts.app')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Potwierdź email</h1>
            <p>Wysłaliśmy link aktywacyjny na Twój adres email. Sprawdź skrzynkę i kliknij w link, aby aktywować konto.</p>
        </div>

        @if (session('resent'))
            <div class="alert alert-success">
                Nowy link aktywacyjny został wysłany!
            </div>
        @endif

        <div class="verification-actions">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    Wyślij ponownie
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-link">
                    Wyloguj się
                </button>
            </form>
        </div>
    </div>
</div>
@endsection