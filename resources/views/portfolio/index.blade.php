@extends('layouts.app')

@section('title', 'Portfolio')

@section('content')
<div class="portfolio">
    <div class="container">
        <div class="page-header">
            <h1>Moje Portfolio</h1>
            <p>Zarządzaj swoimi inwestycjami w kryptowaluty</p>
        </div>

        <div id="portfolio-main">
            <portfolio-component 
                :initial-portfolios="{{ $portfolios->toJson() }}"
                :cryptocurrencies="{{ $cryptocurrencies->toJson() }}"
            ></portfolio-component>
        </div>
    </div>
</div>
@endsection