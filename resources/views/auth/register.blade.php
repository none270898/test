@extends('layouts.app')

@section('title', 'Rejestracja')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Rejestracja</h2>
            <p>Stwórz swoje konto CryptoNote.pl</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="name">Imię</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus 
                       class="form-control @error('name') error @enderror">
                @error('name')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required 
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
                <label for="password_confirmation">Potwierdź hasło</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required 
                       class="form-control">
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Zarejestruj się
            </button>

            <div class="auth-links">
                <a href="{{ route('login') }}">Masz już konto? Zaloguj się</a>
            </div>
        </form>
    </div>
</div>
@endsection