<tr data-order-id="{{ $order->id }}">
    <td>{{ $order->tracking_number }}</td>
    <td>{{ $order->sender_name }}</td>
    <td>{{ $order->receiver_name }}</td>
    <td>{{ $order->receiver_address }}</td>
    <td>{{ is_object($order->current_location) ? $order->current_location->address : $order->current_location }}</td>
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
        @if(Auth::user()->hasRole('admin'))
                        @csrf
                        @method('DELETE')
            <button type="button" class="btn btn-sm btn-danger delete-order" data-order-id="{{ $order->id }}">Xóa</button>
        @endif
    </td>
</tr>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-order');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            confirmDelete(orderId);
        });
    });
});

function confirmDelete(orderId) {
    Swal.fire({
        title: 'Bạn có chắc chắn?',
        text: "Bạn sẽ không thể hoàn tác hành động này!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Đồng ý, xóa nó!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteOrder(orderId);
        }
    });
}

function deleteOrder(orderId) {
    fetch(`/orders/${orderId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire(
                'Đã xóa!',
                'Đơn hàng đã được xóa thành công.',
                'success'
            );
            // Xóa hàng khỏi bảng
            const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
            if (row) {
                row.remove();
            }
        } else {
            Swal.fire(
                'Lỗi!',
                data.error || 'Có lỗi xảy ra khi xóa đơn hàng.',
                'error'
            );
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire(
            'Lỗi!',
            'Có lỗi xảy ra khi xóa đơn hàng.',
            'error'
        );
    });
}
</script>
@endpush