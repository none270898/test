@extends('layouts.app')

@section('title', 'Resetowanie hasła')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Resetowanie hasła</h2>
            <p>Podaj swój adres email, a wyślemy Ci link do resetowania hasła</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="email">Adres Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus 
                       class="form-control @error('email') error @enderror">
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Wyślij link resetowania
            </button>

            <div class="auth-links">
                <a href="{{ route('login') }}">Wróć do logowania</a>
            </div>
        </form>
    </div>
</div>
@endsection