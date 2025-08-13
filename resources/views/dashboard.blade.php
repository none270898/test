@extends('layouts.app')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Witaj, {{ auth()->user()->name }}!</h1>
        <div class="header-actions">
            @if(!auth()->user()->isPremium())
                <a href="{{route('premium.upgrade')}}" class="btn btn-premium">Upgrade do Premium</a>
            @endif
        </div>
    </div>

    <div id="dashboard-vue">
        <dashboard-component></dashboard-component>
    </div>
</div>
@endsection