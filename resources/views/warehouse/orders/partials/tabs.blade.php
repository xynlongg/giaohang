{{-- Tab Navigation --}}
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active d-flex align-items-center gap-2"
            id="local-tab"
            data-bs-toggle="tab" 
            data-bs-target="#local"
            type="button"
            role="tab">
            <i class="bi bi-geo-alt"></i>
            Nội thành
            <span class="badge bg-primary">{{ $localOrders->count() ?? 0 }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link d-flex align-items-center gap-2"
            id="non-local-tab"
            data-bs-toggle="tab"
            data-bs-target="#non-local" 
            type="button"
            role="tab">
            <i class="bi bi-truck"></i>
            Ngoại thành
            <span class="badge bg-secondary">{{ $nonLocalOrders->count() ?? 0 }}</span>
        </button>
    </li>
</ul>

{{-- Tab Content --}}
<div class="tab-content">
    {{-- Local Orders Tab --}}
    <div class="tab-pane fade show active" id="local" role="tabpanel">
        @include('warehouse.orders.partials.local-orders-table')
    </div>

    {{-- Non-local Orders Tab --}}
    <div class="tab-pane fade" id="non-local" role="tabpanel">
        @include('warehouse.orders.partials.non-local-orders-table')
    </div>
</div>