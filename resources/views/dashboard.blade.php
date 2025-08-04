@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard">
    <div class="container">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Witaj z powrotem, {{ auth()->user()->name }}!</p>
        </div>

        <div id="dashboard-main">
            <dashboard-component 
                :portfolio-value="{{ $portfolioValue }}"
                :portfolio-count="{{ $portfolioCount }}"
                :alerts-count="{{ $alertsCount }}"
                :top-cryptos="{{ $topCryptos->toJson() }}"
                :trend-analyses="{{ $trendAnalyses->toJson() }}"
                :is-premium="{{ auth()->user()->isPremium() ? 'true' : 'false' }}"
            ></dashboard-component>
        </div>
    </div>
</div>
@endsection