<div class="card">
    <div class="card-body">
        <div class="form-group">
            <label>Chọn {{ $type == 'noi_thanh_duoi_20km' ? 'bưu cục' : 'kho tổng' }} đích:</label>
            <select name="{{ $type == 'noi_thanh_duoi_20km' ? 'target_post_office_id' : 'target_warehouse_id' }}" 
                    class="form-control target-location" required>
                <option value="">Chọn {{ $type == 'noi_thanh_duoi_20km' ? 'bưu cục' : 'kho tổng' }}</option>
                @if($type == 'noi_thanh_duoi_20km')
                    @foreach($postOffices[$district] ?? [] as $localPostOffice)
                        <option value="{{ $localPostOffice->id }}">{{ $localPostOffice->name }}</option>
                    @endforeach
                @else
                    @foreach($provincialWarehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="form-group staff-selection" style="display:none;">
            <label>Chọn nhân viên phân phối:</label>
            <select name="{{ $type == 'noi_thanh_duoi_20km' ? 'local_distribution_staff_id' : 'general_distribution_staff_id' }}" 
                    class="form-control" required>
                <option value="">Chọn nhân viên phân phối</option>
            </select>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input select-all-orders">
                            <label class="form-check-label">Tất cả</label>
                        </div>
                    </th>
                    <th>Mã đơn hàng</th>
                    <th>Người gửi</th>
                    <th>Người nhận</th>
                    <th>Thời gian</th>
                    @if($type == 'noi_thanh_tren_20km')
                        <th>Khoảng cách</th>
                    @endif
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_orders[]" value="{{ $order->id }}">
                            </div>
                        </td>
                        <td>{{ $order->tracking_number }}</td>
                        <td>{{ $order->sender_name }}</td>
                        <td>{{ $order->receiver_name }}</td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        @if($type == 'noi_thanh_tren_20km')
                            <td>{{ number_format($order->calculated_distance, 2) }} km</td>
                        @endif
                        <td>
                            @switch($order->status)
                                @case('arrived_at_destination_post_office')
                                    <span class="badge badge-success">Đã đến bưu cục đích</span>
                                    @break
                                @case('ready_for_transfer')
                                    <span class="badge badge-info">Sẽ chuyển đến bưu cục khác</span>
                                    @break
                                @case('transferring_to_provincial_warehouse')
                                    <span class="badge badge-warning">Đang chuyển đến kho tổng</span>
                                    @break
                                @default
                                    <span class="badge badge-secondary">{{ $order->status }}</span>
                            @endswitch
                        </td>
                        <td>
                            @if(in_array($type, ['noi_thanh_tren_20km', 'ngoai_thanh']))
                                <button type="button" class="btn btn-sm btn-warning dispatch-to-warehouse" 
                                        data-order-id="{{ $order->id }}"
                                        disabled>
                                    Đợi chọn kho & nhân viên
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            <button type="submit" class="btn btn-success dispatch-btn" style="display:none;">
                Điều phối đơn hàng đã chọn đến {{ $type == 'noi_thanh_duoi_20km' ? 'bưu cục' : 'kho tổng' }}
            </button>
        </div>
    </div>
</div>