@extends('layouts.app')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Resetuj hasło</h1>
            <p>Podaj swój adres email, a wyślemy Ci link do resetowania hasła.</p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Wyślij link
            </button>
        </form>

        <div class="auth-footer">
            <p><a href="{{ route('login') }}">Powrót do logowania</a></p>
        </div>
    </div>
</div>
@endsection