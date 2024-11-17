@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Quản lý đơn hàng lấy - {{ $postOffice->name }}</h1>

    <div id="realtime-notifications"></div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('post_office.orders.index', $postOffice->id) }}" method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3 mb-2">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 mb-2">
                <select name="status" class="form-control">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Đang chờ xử lý</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="picked_up" {{ request('status') == 'picked_up' ? 'selected' : '' }}>Đã lấy hàng</option>
                    <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>Đang vận chuyển</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Đã giao hàng</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            <div class="col-md-3 mb-2">
                <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
            </div>
        </div>
    </form>

    @if($orders->isEmpty())
        <div class="alert alert-info">Không có đơn hàng nào phù hợp với tiêu chí tìm kiếm.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped" id="orders-table">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Người gửi</th>
                        <th>Người nhận</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Shipper được gán</th>
                        <th>Gán shipper</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr data-order-id="{{ $order->id }}">
                        <td>{{ $order->tracking_number }}</td>
                        <td>{{ $order->sender_name }}</td>
                        <td>{{ $order->receiver_name }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td class="assigned-shipper">
                             @if($order->distributions->isNotEmpty() && $order->distributions->first()->shipper)
                                {{ $order->distributions->first()->shipper->name }}
                            @else
                                Chưa gán
                            @endif
                        </td>
                        <td>
                        <form action="{{ route('post_office.orders.assign_shipper', $order->id) }}" method="POST">
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
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css">
<style>
    @keyframes highlightRow {
        0% { background-color: #FFFF99; }
        100% { background-color: transparent; }
    }
    .modal {
        background-color: rgba(0, 0, 0, 0.5);
    }
    .modal-dialog {
        margin-top: 100px;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>
<script src="{{ mix('js/app.js') }}" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery chưa được tải. Vui lòng đảm bảo jQuery được tải trước khi chạy script này.');
        } else {
            // Khởi tạo DataTables
            $('#orders-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                }
            });

            // Khởi tạo Echo (đảm bảo rằng Laravel Echo đã được định nghĩa trong app.js)
            if (typeof window.Echo !== 'undefined') {
                // Lắng nghe sự kiện cập nhật nhân viên
                window.Echo.channel('staff-updates')
                    .listen('StaffUpdated', function(e) {
                        console.log('Sự kiện StaffUpdated:', e);
                        let notificationDiv = document.getElementById('realtime-notifications');
                        let notification = document.createElement('div');
                        notification.className = 'alert alert-info alert-dismissible fade show';
                        notification.innerHTML = `
                            ${e.message}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        `;
                        notificationDiv.appendChild(notification);
                        
                        // Tự động ẩn thông báo sau 5 giây
                        setTimeout(function() {
                            notification.remove();
                        }, 5000);
                    });

                // Lắng nghe sự kiện cập nhật đơn hàng
                window.Echo.channel('order-updates')
                    .listen('OrderUpdated', function(e) {
                        console.log('Sự kiện OrderUpdated:', e);
                        let orderRow = document.querySelector(`tr[data-order-id="${e.order.id}"]`);
                        if (orderRow) {
                            // Cập nhật thông tin đơn hàng
                            orderRow.querySelector('td:nth-child(4)').textContent = e.order.status;
                            orderRow.querySelector('.assigned-shipper').textContent = e.order.shipper_name || 'Chưa gán';

                            let notificationDiv = document.getElementById('realtime-notifications');
                            let notification = document.createElement('div');
                            notification.className = 'alert alert-info alert-dismissible fade show';
                            notification.innerHTML = `
                                Đơn hàng ${e.order.tracking_number} đã chuyển trạng thái thành ${e.order.status}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            `;
                            notificationDiv.appendChild(notification);

                            // Làm nổi bật hàng vừa được cập nhật
                            orderRow.style.animation = 'none';
                            orderRow.offsetHeight; // Trigger reflow
                            orderRow.style.animation = 'highlightRow 2s';
                        }
                    });
            } else {
                console.error('Echo is not defined. Make sure Laravel Echo is properly initialized in app.js');
            }

            // Xử lý sự kiện gán shipper
            $('form[action^="/post_office/orders/"]').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var url = form.attr('action');
                var shipperId = form.find('select[name="shipper_id"]').val();

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: form.serialize(),
                    success: function(response) {
                        console.log('Gán shipper thành công:', response);
                        // Cập nhật UI
                        var orderRow = form.closest('tr');
                        orderRow.find('.assigned-shipper').text(response.shipper_name);
                        // Hiển thị thông báo thành công
                        $('#realtime-notifications').append(
                            `<div class="alert alert-success alert-dismissible fade show">
                                Đã gán shipper thành công cho đơn hàng ${response.tracking_number}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>`
                        );
                    },
                    error: function(xhr) {
                        console.error('Lỗi khi gán shipper:', xhr.responseText);
                        // Hiển thị thông báo lỗi
                        $('#realtime-notifications').append(
                            `<div class="alert alert-danger alert-dismissible fade show">
                                Có lỗi xảy ra khi gán shipper. Vui lòng thử lại.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>`
                        );
                    }
                });
            });
        }
    });
</script>
@endpush