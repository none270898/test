@extends('layouts.app')

@section('title', 'Resetowanie hasła')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Nowe hasło</h2>
            <p>Wprowadź nowe hasło dla swojego konta</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">Adres Email</label>
                <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus 
                       class="form-control @error('email') error @enderror">
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Nowe hasło</label>
                <input id="password" type="password" name="password" required 
                       class="form-control @error('password') error @enderror">
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password-confirm">Potwierdź nowe hasło</label>
                <input id="password-confirm" type="password" name="password_confirmation" required 
                       class="form-control">
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Zapisz nowe hasło
            </button>
        </form>
    </div>
</div>
@endsection