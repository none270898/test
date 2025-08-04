@extends('layouts.app')

@section('title', 'Alerty Cenowe')

@section('content')
<div class="alerts">
    <div class="container">
        <div class="page-header">
            <h1>Alerty Cenowe</h1>
            <p>Ustaw powiadomienia o zmianach cen kryptowalut</p>
        </div>

        <div id="alerts-main">
            <price-alert-component 
                :initial-alerts="{{ $alerts->toJson() }}"
                :cryptocurrencies="{{ $cryptocurrencies->toJson() }}"
            ></price-alert-component>
        </div>
    </div>
</div>
@endsection
