@extends('layouts.app')

@section('title', 'Logowanie')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Logowanie</h2>
            <p>Zaloguj się do swojego konta CryptoNote.pl</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus 
                       class="form-control @error('email') error @enderror">
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input id="password" type="password" name="password" required 
                       class="form-control @error('password') error @enderror">
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span class="checkmark"></span>
                    Zapamiętaj mnie
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Zaloguj się
            </button>

            <div class="auth-links">
                <a href="{{ route('password.request') }}">Zapomniałeś hasła?</a>
                <a href="{{ route('register') }}">Nie masz konta? Zarejestruj się</a>
            </div>
        </form>
    </div>
</div>
@endsection