<div class="tab-pane fade show active" id="confirm-arrival" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Đơn hàng chờ xác nhận</h5>
            <button type="button" id="confirmSelectedBtn" class="btn btn-success" disabled>
                <i class="bi bi-check-lg me-1"></i>
                Xác nhận đã nhận (<span id="selectedCount">0</span>)
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input select-all" data-type="confirm">
                            </th>
                            <th>Mã đơn hàng</th>
                            <th>Người gửi</th>
                            <th>Người nhận</th>
                            <th>Thời gian đến</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($newArrivals as $order)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input order-checkbox" 
                                       data-type="confirm"
                                       value="{{ $order->id }}">
                            </td>
                            <td>
                                <div class="fw-semibold">#{{ $order->order->tracking_number }}</div>
                                <small class="text-muted">
                                    KL: {{ $order->order->total_weight }}kg | 
                                    COD: {{ number_format($order->order->total_cod) }}đ
                                </small>
                            </td>
                            <td>
                                <div>{{ $order->order->sender_name }}</div>
                                <small class="text-muted">{{ $order->order->sender_phone }}</small>
                            </td>
                            <td>
                                <div>{{ $order->order->receiver_name }}</div>
                                <small class="text-muted">{{ $order->order->receiver_phone }}</small>
                            </td>
                            <td>{{ $order->entered_at ? Carbon\Carbon::parse($order->entered_at)->format('d/m/Y H:i') : 'N/A' }}</td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-success confirm-single"
                                        data-id="{{ $order->id }}"
                                        title="Xác nhận đã nhận">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-info"
                                        data-bs-toggle="modal"
                                        data-bs-target="#orderDetailModal{{ $order->id }}"
                                        title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>Không có đơn hàng chờ xác nhận</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>