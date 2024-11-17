<tr>
    @if(in_array($type, ['noi_thanh_tren_20km', 'ngoai_thanh', 'noi_thanh_duoi_20km']))
        <td>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="selected_orders[]" value="{{ $order->id }}">
            </div>
        </td>
    @endif
    <td>{{ $order->tracking_number }}</td>
    <td>{{ $order->sender_name }}</td>
    <td>{{ $order->receiver_name }}</td>
    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
    @if(in_array($type, ['noi_thanh_duoi_20km', 'noi_thanh_tren_20km']))
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
    @if($type == 'cung_quan')
        <td>
            <form action="{{ route('post_office.orders.assign_shipper_prepared', $order->id) }}" method="POST" class="assign-shipper-form">
                @csrf
                <select name="shipper_id" class="form-control form-control-sm" required>
                    <option value="">Chọn shipper</option>
                    @foreach($shippers as $shipper)
                        <option value="{{ $shipper->id }}">
                            {{ $shipper->name }} (Điểm: {{ $shipper->attendance_score + $shipper->vote_score }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm mt-1">Gán shipper</button>
            </form>
        </td>
    @elseif($type == 'noi_thanh_duoi_20km')
        <td>
            <button type="button" class="btn btn-sm btn-info dispatch-to-local disabled" data-order-id="{{ $order->id }}" disabled>
                Chờ chọn bưu cục và nhân viên
            </button>
        </td>
    @elseif(in_array($type, ['noi_thanh_tren_20km', 'ngoai_thanh']))
        <td>
            <button type="button" class="btn btn-sm btn-info dispatch-to-warehouse" data-order-id="{{ $order->id }}">
                Chuyển qua kho tổng
            </button>
        </td>
    @endif
</tr>