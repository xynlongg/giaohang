<div class="tab-pane fade" id="completed-orders">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title m-0">Đơn Hàng Đã Phân Phối (Hôm Nay)</h5>
    </div>

    @forelse($completedHandovers as $hour => $handovers)
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h6 class="mb-0">Thời gian: {{ $hour }}</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered datatable mb-0">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Thông tin người nhận</th>
                        <th>Địa chỉ giao hàng</th>
                        <th>Đích đến</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($handovers as $handover)
                    <tr>
                        <td>
                            <span class="fw-bold">{{ $handover->order->tracking_number }}</span>
                            <br>
                            <small class="text-muted">
                                {{ $handover->shipping_type === 'noi_thanh' ? 'Nội thành' : 'Ngoại thành' }}
                                @if($handover->shipping_type === 'noi_thanh')
                                    ({{ number_format($handover->order->calculated_distance, 1) }}km)
                                @endif
                            </small>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $handover->order->receiver_name }}</div>
                            <div>{{ $handover->order->receiver_phone }}</div>
                        </td>
                        <td>{{ $handover->order->receiver_address }}</td>
                        <td>
                            @if($handover->destinationPostOffice)
                            <span class="badge bg-info">
                                <i class="bi bi-building me-1"></i>
                                {{ $handover->destinationPostOffice->name }}
                            </span>
                            @elseif($handover->destinationWarehouse) 
                            <span class="badge bg-secondary">
                                <i class="bi bi-house-door me-1"></i>
                                {{ $handover->destinationWarehouse->name }}
                            </span>
                            @endif
                        </td>
                        <td>
                            {{ $handover->completed_at->format('H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center py-4">
        <div class="text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p class="mb-0">Chưa có đơn hàng nào được phân phối hôm nay</p>
        </div>
    </div>
    @endforelse
</div>