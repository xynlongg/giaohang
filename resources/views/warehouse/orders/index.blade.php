@extends('layouts.app')

@push('styles')
<style>
    .page-content {
        padding: 1.5rem;
        min-height: calc(100vh - 60px);
    }

    .nav-tabs {
        border-bottom: 2px solid #e9ecef;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 0.75rem 1rem;
        font-weight: 500;
        position: relative;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        font-weight: 600;
        background: none;
        border: none;
    }

    .nav-tabs .nav-link.active:after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: #0d6efd;
    }

    .card {
        border: none;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
    }

    .card-header {
        background: white;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
    }

    .empty-state {
        padding: 3rem;
        text-align: center;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state p {
        margin: 0;
        font-size: 0.875rem;
    }

    .stats-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1.5rem;
        height: 100%;
    }

    .stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .stats-value {
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        line-height: 1;
    }

    .stats-label {
        color: #6c757d;
        font-size: 0.875rem;
    }

    .table > :not(caption) > * > * {
        padding: 1rem;
    }

    .table > tbody > tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .distributor-select {
        min-width: 250px;
    }

    .badge {
        padding: 0.5em 0.75em;
    }

    .order-type {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .order-type.local {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }

    .order-type.non-local {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
</style>
@endpush

@section('content')
<div class="page-content">
    {{-- Gọi header partial --}}
    @include('warehouse.orders.partials.header')

    {{-- Gọi stats partial --}}
    @include('warehouse.orders.partials.stats')

    {{-- Card chính --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Danh sách đơn hàng</h5>
            <button type="button" 
                class="btn btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#assignedOrdersModal">
                <i class="bi bi-list-check me-1"></i>
                Xem danh sách đã gán
            </button>
        </div>

        <div class="card-body">
            {{-- Gọi tabs partial --}} 
            @include('warehouse.orders.partials.tabs')
            @include('warehouse.orders.partials.confirm-orders-tab')
        </div>
    </div>
</div>

{{-- Gọi modal partials --}}
@include('warehouse.orders.partials.order-detail-modal')
@include('warehouse.orders.partials.assignment-modal')

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Gọi scripts partial --}}
@include('warehouse.orders.partials.scripts')
@endpush