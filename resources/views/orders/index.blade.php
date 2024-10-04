    @extends('layouts.app')

    @section('content')
    <div class="container">
        <h1>Danh sách Đơn Hàng</h1>

        <div class="mb-3">
            <a href="{{ route('orders.create') }}" class="btn btn-primary">Tạo Đơn Hàng Mới</a>
            <a href="{{ route('orders.import') }}" class="btn btn-success">Import Đơn Hàng Bằng Excel</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form id="search-form" action="{{ route('orders.index') }}" method="GET">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <select name="status" class="form-control">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Đang xử lý</option>
                                <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao hàng</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Đặt lại</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Tracking Number</th>
                        <th>Người gửi</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ nhận hàng</th>
                        <th>Địa chỉ hiện tại</th>
                        <th>Số điện thoại</th>
                        <th>Tiền COD</th>
                        <th>Lấy tại bưu cục</th>
                        <th>Ngày gửi</th>
                        <th>Ngày nhận dự kiến</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="orders-table-body">
                    @foreach($orders as $order)
                        @include('orders.partials.order_row', ['order' => $order])
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $orders->links() }}
        </div>
    </div>
    @endsection

    @push('scripts')
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
console.log('Script bắt đầu thực thi');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM đã sẵn sàng');

    if (!window.Pusher) {
        console.error('Pusher không được tải. Kiểm tra kết nối mạng và script Pusher.');
        return;
    }

    try {
        console.log('Cấu hình Pusher:', {
            key: '{{ env('PUSHER_APP_KEY') }}',
            cluster: '{{ env('PUSHER_APP_CLUSTER') }}'
        });

        const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
            cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
            encrypted: true
        });

        console.log('Pusher đã được khởi tạo');

        const channel = pusher.subscribe('orders');
        console.log('Đã đăng ký kênh orders');

        channel.bind('pusher:subscription_succeeded', function() {
            console.log('Đăng ký kênh orders thành công');
        });

        channel.bind('pusher:subscription_error', function(status) {
            console.error('Lỗi khi đăng ký kênh orders:', status);
        });

        channel.bind('OrderCreated', function(data) {
            console.log('Sự kiện OrderCreated nhận được:', data);
            try {
                const order = data.order;
                console.log('Dữ liệu đơn hàng:', order);

                const newRow = createOrderRow(order);
                console.log('HTML mới được tạo:', newRow);

                const tbody = document.getElementById('orders-table-body');
                if (!tbody) {
                    console.error('Không tìm thấy phần tử orders-table-body');
                    return;
                }

                tbody.insertAdjacentHTML('afterbegin', newRow);
                console.log('Đã chèn hàng mới vào bảng');

                highlightRow(tbody.firstElementChild);
                console.log('Đã làm nổi bật hàng mới');
            } catch (error) {
                console.error('Lỗi khi xử lý sự kiện OrderCreated:', error);
            }
        });

        function createOrderRow(order) {
            try {
                const statusClass = getStatusClass(order.status);
                const pickupDate = formatDate(order.pickup_date);
                const deliveryDate = order.delivery_date ? formatDate(order.delivery_date) : 'N/A';

                return `
                <tr data-order-id="${order.id}">
                    <td>${order.tracking_number}</td>
                    <td>${order.sender_name}</td>
                    <td>${order.receiver_name}</td>
                    <td>${order.receiver_address}</td>
                    <td>${order.current_location}</td>
                    <td>${order.receiver_phone}</td>
                    <td>${new Intl.NumberFormat('vi-VN').format(order.total_amount)} VND</td>
                    <td>${order.is_pickup_at_post_office ? 'Có' : 'Không'}</td>
                    <td>${pickupDate}</td>
                    <td>${deliveryDate}</td>
                    <td>
                        <span class="badge bg-${statusClass}">
                            ${capitalizeFirstLetter(order.status)}
                        </span>
                    </td>
                    <td>
                        <a href="/orders/${order.id}" class="btn btn-sm btn-info">Xem</a>
                        <a href="/orders/${order.id}/edit" class="btn btn-sm btn-primary">Sửa</a>
                        <a href="/orders/${order.id}/update" class="btn btn-sm btn-warning">Cập nhật</a>
                        <form action="/orders/${order.id}" method="POST" style="display: inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            `;
            } catch (error) {
                console.error('Lỗi khi tạo hàng đơn hàng:', error);
                return '';
            }
        }

        function getStatusClass(status) {
            switch (status) {
                case 'pending': return 'warning';
                case 'shipping': return 'info';
                case 'completed': return 'success';
                default: return 'danger';
            }
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('vi-VN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

       
        function capitalizeFirstLetter(string) {
            if (typeof string !== 'string') return '';
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function highlightRow(row) {
            if (row) {
                row.style.animation = 'highlightRow 2s';
            } else {
                console.error('Không thể làm nổi bật hàng: phần tử không tồn tại');
            }
        }

    } catch (error) {
        console.error('Lỗi khi khởi tạo Pusher:', error);
    }
});

// Thêm một event listener cho window load event
window.addEventListener('load', function() {
    console.log('Trang đã tải hoàn toàn');
});

</script>

<style>
@keyframes highlightRow {
    0% { background-color: #ffff99; }
    100% { background-color: transparent; }
}
</style>
@endpush