<div class="row mb-4">
    {{-- Local Orders Stats --}}
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-geo-alt"></i>
            </div>
            <div class="stats-value">{{ $localOrders->count() ?? 0 }}</div>
            <div class="stats-label">Đơn hàng nội thành</div>
        </div>
    </div>

    {{-- Non-local Orders Stats --}}
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-truck"></i>
            </div>
            <div class="stats-value">{{ $nonLocalOrders->count() ?? 0 }}</div>
            <div class="stats-label">Đơn hàng ngoại thành</div>
        </div>
    </div>

    {{-- Local Staff Stats --}}
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-people"></i>
            </div>
            <div class="stats-value">{{ $localDistributors->count() ?? 0 }}</div>
            <div class="stats-label">NV nội thành</div>
        </div>
    </div>

    {{-- Non-local Staff Stats --}}
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-people"></i>
            </div>
            <div class="stats-value">{{ $nonLocalDistributors->count() ?? 0 }}</div>
            <div class="stats-label">NV ngoại thành</div>
        </div>
    </div>
</div>