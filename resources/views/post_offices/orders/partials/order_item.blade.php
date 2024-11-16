<li class="list-group-item">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            @if($type != 'cung_quan')
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="selected_orders[]" value="{{ $order->id }}">
                    <label class="form-check-label">
                        <strong>{{ $order->tracking_number }}</strong>
                    </label>
                </div>
            @else
                <strong>{{ $order->tracking_number }}</strong>
            @endif
            <p class="mb-0">{{ $order->sender_name }} → {{ $order->receiver_name }}</p>
            <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
            @if($order->status === 'ready_for_transfer')
                <br><small class="text-info">Sẽ chuyển đến bưu cục khác</small>
            @endif
            @if(in_array($type, ['noi_thanh_duoi_20km', 'noi_thanh_tren_20km']) && isset($order->calculated_distance))
                <br><small class="text-info">Khoảng cách đến điểm giao: {{ number_format($order->calculated_distance, 2) }} km</small>
            @endif
        </div>
        @if($type == 'cung_quan')
            <form action="{{ route('post_office.orders.assign_shipper_prepared', $order->id) }}" method="POST" class="assign-shipper-form">
                @csrf
                <select name="shipper_id" class="form-control form-control-sm mb-2" required>
                    <option value="">Chọn shipper</option>
                    @foreach($shippers as $shipper)
                        <option value="{{ $shipper->id }}">
                            {{ $shipper->name }} (Điểm: {{ $shipper->attendance_score + $shipper->vote_score }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Gán shipper</button>
            </form>
        @elseif($type == 'noi_thanh_duoi_20km')
            <div>
                <span class="badge badge-success">Chuyển trực tiếp</span>
                <small class="d-block text-muted">Chọn bưu cục & nhân viên phân phối ở phía trên</small>
            </div>
        @elseif($type == 'noi_thanh_tren_20km')
            <div>
                <span class="badge badge-warning">Qua kho tổng</span>
                <small class="d-block text-muted">Chọn kho tổng & nhân viên phân phối ở phía trên</small>
            </div>
        @elseif($type == 'ngoai_thanh')
            <div>
                <span class="badge badge-info">Chuyển qua kho tổng</span>
                <small class="d-block text-muted">Chọn kho tổng & nhân viên phân phối ở phía trên</small>
            </div>
        @endif
    </div>
</li>