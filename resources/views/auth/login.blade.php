@extends('layouts.app')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Logowanie</h1>
            <p>Zaloguj się do swojego konta CryptoNote.pl</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input id="password" type="password" name="password" required>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember">
                    <span class="checkmark"></span>
                    Zapamiętaj mnie
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Zaloguj się
            </button>
        </form>

        <div class="auth-footer">
            <p><a href="{{ route('password.request') }}">Zapomniałeś hasła?</a></p>
            <p>Nie masz konta? <a href="{{ route('register') }}">Zarejestruj się</a></p>
        </div>
    </div>
</div>
@endsection