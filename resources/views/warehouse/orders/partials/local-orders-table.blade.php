<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-geo-alt me-2"></i>
            Đơn hàng nội thành
        </h5>
        <div class="d-flex gap-2">
            <select id="localDistributorSelect" class="form-select" style="min-width: 250px;">
                <option value="">Chọn nhân viên nội thành...</option>
                @foreach($localDistributors as $distributor)
                <option value="{{ $distributor->id }}">
                    {{ $distributor->name }} ({{ $distributorOrderCounts[$distributor->id] ?? 0 }}/20 đơn)
                </option>
                @endforeach
            </select>
            <button id="assignLocalBtn" class="btn btn-primary" disabled>
                <i class="bi bi-person-check me-1"></i>
                Gán đơn (<span id="selectedLocalCount">0</span>)
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" class="form-check-input select-all" data-type="local">
                        </th>
                        <th>Mã đơn hàng</th>
                        <th>Người gửi</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ nhận</th>
                        <th>Thời gian vào kho</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($localOrders as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input order-checkbox" 
                                   data-type="local"
                                   value="{{ $order->id }}">
                        </td>
                        <td class="fw-semibold">#{{ $order->order->tracking_number }}</td>
                        <td>
                            <div>{{ $order->order->sender_name }}</div>
                            <small class="text-muted">{{ $order->order->sender_phone }}</small>
                        </td>
                        <td>
                            <div>{{ $order->order->receiver_name }}</div>
                            <small class="text-muted">{{ $order->order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->order->receiver_address }}</td>
                        <td>{{ $order->entered_at ? \Carbon\Carbon::parse($order->entered_at)->format('d/m/Y H:i') : 'N/A' }}</td>
                        <td>
                            <span class="badge bg-success">Đã nhập kho</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p class="mb-0">Không có đơn hàng nội thành</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>