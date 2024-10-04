    <tr data-order-id="{{ $order->id }}">
        <td>{{ $order->tracking_number }}</td>
        <td>{{ $order->sender_name }}</td>
        <td>{{ $order->receiver_name }}</td>
        <td>{{ $order->receiver_address }}</td>
        <td>{{ $order->current_location }}</td>
        <td>{{ $order->receiver_phone }}</td>
        <td>{{ number_format($order->total_amount) }} VND</td>
        <td>{{ $order->is_pickup_at_post_office ? 'Có' : 'Không' }}</td>
        <td>{{ $order->pickup_date->format('d/m/Y H:i') }}</td>
        <td>{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y H:i') : 'N/A' }}</td>
        <td>
            <span class="badge bg-{{ $order->status == 'pending' ? 'warning' : ($order->status == 'shipping' ? 'info' : ($order->status == 'completed' ? 'success' : 'danger')) }}">
                {{ ucfirst($order->status) }}
            </span>
        </td>
        <td>
            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">Xem</a>
            <a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-primary">Sửa</a>
            <a href="/orders/{{ $order->id }}/update" class="btn btn-sm btn-warning">Cập nhật</a>
            <form action="{{ route('orders.destroy', $order) }}" method="POST" style="display: inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">Xóa</button>
            </form>
        </td>
    </tr>