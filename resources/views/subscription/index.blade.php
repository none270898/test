@extends('layouts.app')

@section('title', 'Subskrypcja')

@section('content')
<div class="subscription">
    <div class="container">
        <div class="page-header">
            <h1>Twoja Subskrypcja</h1>
            @if(auth()->user()->isPremium())
                <p class="status-active">Plan Premium aktywny do {{ auth()->user()->subscription_expires_at->format('d.m.Y') }}</p>
            @else
                <p>Aktualnie korzystasz z darmowego planu</p>
            @endif
        </div>

        <div id="subscription-main">
            <subscription-component 
                :user="{{ auth()->user()->toJson() }}"
                :is-premium="{{ auth()->user()->isPremium() ? 'true' : 'false' }}"
            ></subscription-component>
        </div>
    </div>
</div>
@endsection