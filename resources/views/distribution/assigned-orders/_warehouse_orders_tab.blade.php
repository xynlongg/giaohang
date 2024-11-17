<!-- Tab đơn cần chuyển kho -->
<div class="tab-pane fade" id="warehouse-orders">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title m-0">Danh Sách Đơn Cần Chuyển Kho</h5>
        <small class="text-muted">(Đơn ngoại thành & nội thành > 20km)</small>
        <div class="d-flex gap-2">
            <select id="destinationWarehouse" class="form-select">
                <option value="">Chọn kho đích...</option>
                @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}"
                    data-coordinates="{{ json_encode($warehouse->coordinates) }}"
                    data-address="{{ $warehouse->address }}">
                    {{ $warehouse->name }}
                </option>
                @endforeach
            </select>
            <button onclick="updateBulkArrival('warehouse')"
                class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-check2-all me-2"></i>
                <span>Cập nhật hàng loạt</span>
            </button>
        </div>
    </div>

    @forelse($warehouseOrders as $province => $handovers)
    <div class="card mb-3">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Tỉnh/Thành: {{ $province }}</h6>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                        onchange="selectOrders('warehouse', '{{ $province }}', this.checked)">
                    <label class="form-check-label">Chọn tất cả</label>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered datatable mb-0">
                <thead>
                    <tr>
                        <th width="40px">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    onchange="selectOrders('warehouse', '{{ $province }}', this.checked)">
                            </div>
                        </th>
                        <th>Mã đơn</th>
                        <th>Thông tin người nhận</th>
                        <th>Địa chỉ giao hàng</th>
                        <th>Loại</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($handovers as $handover)
                    <tr>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input warehouse-checkbox"
                                    type="checkbox"
                                    data-identifier="{{ $province }}"
                                    value="{{ $handover->id }}">
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold">{{ $handover->order->tracking_number }}</span>
                            <br>
                            <small class="text-muted">
                                {{ $handover->created_at->format('d/m/Y H:i') }}
                            </small>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $handover->order->receiver_name }}</div>
                            <div>{{ $handover->order->receiver_phone }}</div>
                        </td>
                        <td>{{ $handover->order->receiver_address }}</td>
                        <td>
                            @if($handover->shipping_type === 'noi_thanh')
                                <span class="badge bg-info">
                                    Nội thành ({{ number_format($handover->order->calculated_distance, 1) }}km)
                                </span>
                            @else
                                <span class="badge bg-secondary">Ngoại thành</span>
                            @endif
                        </td>
                        <td>
                            <button type="button"
                                onclick="updateArrivalStatus({{ $handover->id }}, 'warehouse')"
                                class="btn btn-sm btn-primary">
                                <i class="bi bi-check2"></i>
                                Cập nhật
                            </button>
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
            <p class="mb-0">Không có đơn hàng cần chuyển kho</p>
        </div>
    </div>
    @endforelse
</div>