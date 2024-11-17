<div class="tab-pane fade {{ !auth()->user()->hasRole('warehouse_local_distributor') ? 'show active' : '' }}" 
    id="remote-orders">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="card-title mb-0">Danh Sách Đơn Ngoại Thành</h5>
            <small class="text-muted">Phân phối đến kho tổng đích</small>
        </div>
        <div class="d-flex gap-2">
            <select id="remoteDestinationWarehouse" class="form-select">
                <option value="">Chọn kho đích...</option>
                @foreach($warehouses as $province => $provinceWarehouses)
                <optgroup label="Tỉnh/Thành: {{ $province }}">
                    @foreach($provinceWarehouses as $warehouse)
                    <option value="{{ $warehouse->id }}"
                        data-coordinates="{{ json_encode($warehouse->coordinates) }}"
                        data-province="{{ $warehouse->province }}"
                        data-address="{{ $warehouse->address }}">
                        {{ $warehouse->name }}
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
            <button onclick="updateRemoteDelivery()"
                class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-check2-all me-2"></i>
                <span>Cập nhật hàng loạt</span>
            </button>
        </div>
    </div>

    @forelse($remoteOrders as $province => $handovers)
    <div class="card mb-3">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-geo-alt me-2"></i>
                    Tỉnh/Thành: {{ $province }}
                    <span class="badge bg-info ms-2">{{ $handovers->count() }} đơn</span>
                </h6>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                        onchange="selectRemoteOrders('{{ $province }}', this.checked)">
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
                                    onchange="selectRemoteOrders('{{ $province }}', this.checked)">
                            </div>
                        </th>
                        <th>Mã đơn</th>
                        <th>Thông tin người nhận</th>
                        <th>Địa chỉ giao hàng</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($handovers as $handover)
                    <tr>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input remote-order-checkbox"
                                    type="checkbox"
                                    data-province="{{ $province }}"
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
                            <button type="button"
                                onclick="updateSingleRemoteDelivery({{ $handover->id }})"
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
    <div class="text-center py-5">
        <div class="text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p class="mb-0">Không có đơn hàng ngoại thành</p>
        </div>
    </div>
    @endforelse
</div>