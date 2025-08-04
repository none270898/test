@extends('layouts.app')

@section('title', 'Analiza Trendów AI')

@section('content')
<div class="trends">
    <div class="container">
        <div class="page-header">
            <h1>Analiza Trendów AI</h1>
            <p>Sentiment analysis polskiego rynku kryptowalut</p>
            <span class="premium-badge">Premium</span>
        </div>

        <div id="trends-main">
            <trend-analysis-component 
                :trend-analyses="{{ $trendAnalyses->toJson() }}"
            ></trend-analysis-component>
        </div>
    </div>
</div>
@endsection