<div class="tab-pane fade" id="confirmed" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Đơn hàng chờ phân công</h5>
            <div class="d-flex align-items-center gap-3">
                <button type="button"
                    class="btn btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#assignedOrdersModal">
                    <i class="bi bi-list-ul me-1"></i>
                    Danh sách đã gán
                </button>
                @include('post_offices.receiving.partials.assigned_orders_modal')
                <span id="confirmedSelectedCount" class="text-muted d-none">
                    Đã chọn: <span class="fw-bold text-primary">0</span>
                </span>
                <div class="input-group" style="width: 300px;">
                    <select id="shipperSelect" class="form-select" disabled>
                        <option value="">Chọn shipper...</option>
                        @foreach($availableShippers as $shipper)
                        <option value="{{ $shipper->id }}">
                            {{ $shipper->name }} ({{ $shipper->active_orders_count }}/20 đơn)
                        </option>
                        @endforeach
                    </select>
                    <button
                        id="assignShipperBtn"
                        class="btn btn-primary d-none"
                        disabled>
                        <i class="bi bi-person-check me-1"></i>
                        Phân công
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <form id="assignShipperForm" action="{{ route('post_office.receiving.assign_shipper') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAllConfirmed">
                                    </div>
                                </th>
                                <th>Mã đơn hàng</th>
                                <th>Thời gian xác nhận</th>
                                <th>Bưu cục gốc</th>
                                <th>Khoảng cách</th>
                                <th>Loại vận chuyển</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($confirmedOrders as $order)
                            <tr class="order-row">
                                <td>
                                    <div class="form-check">
                                        <input
                                            class="form-check-input confirmed-checkbox"
                                            type="checkbox"
                                            name="order_ids[]"
                                            value="{{ $order->id }}">
                                    </div>
                                </td>
                                <td class="fw-semibold">#{{ $order->tracking_number }}</td>
                                <td>{{ $order->updated_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($order->lastHandover)
                                    {{ $order->lastHandover->originPostOffice->name }}
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ number_format($order->distance ?? 0, 1) }} km</td>
                                <td>
                                    <span class="badge rounded-pill 
                                @if($order->shipping_type === 'cung_quan') 
                                    bg-success
                                @elseif($order->shipping_type === 'noi_thanh')
                                    bg-primary  
                                @else
                                    bg-warning
                                @endif">
                                        {{ $order->shipping_type === 'cung_quan' ? 'Cùng quận' : 
                                   ($order->shipping_type === 'noi_thanh' ? 'Nội thành' : 'Ngoại thành') }}
                                    </span>
                                </td>
                                <td>
                                    <button
                                        type="button"
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
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox display-6"></i>
                                        <p class="mb-0">Không có đơn hàng nào chờ phân công</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
        @if($confirmedOrders->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-end">
                {{ $confirmedOrders->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Order Detail Modals --}}
@foreach($confirmedOrders as $order)
<div class="modal fade" id="orderDetailModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đơn hàng #{{ $order->tracking_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Thông tin vận chuyển</h6>
                                <div class="mt-3">
                                    {{-- Chi tiết thông tin vận chuyển --}}
                                    @include('post_offices.receiving.partials.shipping_info', ['item' => $order])
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Thông tin chuyển phát</h6>
                                <div class="mt-3">
                                    <p class="mb-2">
                                        <i class="bi bi-building me-2"></i>
                                        <strong>Bưu cục hiện tại:</strong> {{ $order->currentLocation->name ?? 'N/A' }}
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-clock me-2"></i>
                                        <strong>Thời gian xác nhận:</strong>
                                        {{ $order->updated_at->format('d/m/Y H:i') }}
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