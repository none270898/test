{{-- resources/views/admin/dashboard.blade.php --}}

@extends('layouts.app')

@section('title', 'Admin Panel - CryptoNote')

@section('content')
<div class="admin-container">
    <div class="admin-header">
        <h1>🔧 Admin Panel</h1>
        <div class="admin-user">
            <span>{{ auth()->user()->name }}</span>
            <span class="admin-badge">ADMIN</span>
        </div>
    </div>

    <div id="admin-dashboard">
        <admin-dashboard-component></admin-dashboard-component>
    </div>
</div>

<style>
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.admin-header h1 {
    color: #1e293b;
    font-size: 2.5rem;
    margin: 0;
}

.admin-user {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 600;
    color: #64748b;
}

.admin-badge {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
}

@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .admin-header h1 {
        font-size: 2rem;
    }
}
</style>
@endsection