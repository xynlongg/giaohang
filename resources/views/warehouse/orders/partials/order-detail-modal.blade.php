@foreach($warehouseOrders as $order)
<div class="modal fade" id="orderDetailModal{{ $order->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đơn hàng #{{ $order->order->tracking_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Thông tin người gửi</h6>
                                <div class="mt-3">
                                    <p class="mb-2">
                                        <i class="bi bi-person me-2"></i>
                                        <strong>Tên:</strong> {{ $order->order->sender_name }}
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-telephone me-2"></i>
                                        <strong>Số điện thoại:</strong> {{ $order->order->sender_phone }}
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <strong>Địa chỉ:</strong> {{ $order->order->sender_address }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Thông tin người nhận</h6>
                                <div class="mt-3">
                                    <p class="mb-2">
                                        <i class="bi bi-person me-2"></i>
                                        <strong>Tên:</strong> {{ $order->order->receiver_name }}
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-telephone me-2"></i>
                                        <strong>Số điện thoại:</strong> {{ $order->order->receiver_phone }}
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <strong>Địa chỉ:</strong> {{ $order->order->receiver_address }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endforeach