<div class="tab-pane fade show active" id="pending" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Đơn hàng chờ xác nhận</h5>
                    <div class="d-flex align-items-center gap-3">
                        <span id="pendingSelectedCount" class="text-muted d-none">
                            Đã chọn: <span class="fw-bold text-primary">0</span>
                        </span>
                        <button
                            id="confirmArrivalBtn"
                            class="btn btn-primary d-none"
                            disabled>
                            <i class="bi bi-check2-circle me-1"></i>
                            Xác nhận đã đến
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <form id="confirmArrivalForm" action="{{ route('post_office.receiving.confirm_arrival') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllPending">
                                            </div>
                                        </th>
                                        <th>Mã đơn hàng</th>
                                        <th>Bưu cục gốc</th>
                                        <th>Nhân viên phân phối</th>
                                        <th>Thời gian bàn giao</th>
                                        <th>Khoảng cách</th>
                                        <th>Loại vận chuyển</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($incomingHandovers as $handover)
                                    <tr class="handover-row">
                                        <td>
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input pending-checkbox"
                                                    type="checkbox"
                                                    name="handover_ids[]"
                                                    value="{{ $handover->id }}">
                                            </div>
                                        </td>
                                        <td class="fw-semibold">
                                            #{{ $handover->order->tracking_number }}
                                        </td>
                                        <td>{{ $handover->originPostOffice->name }}</td>
                                        <td>{{ $handover->distributionStaff->name }}</td>
                                        <td>
                                            @if($handover->assigned_at)
                                            {{ \Carbon\Carbon::parse($handover->assigned_at)->format('d/m/Y H:i') }}
                                            @else
                                            <span class="text-muted">Chưa có</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($handover->distance, 1) }} km</td>
                                        <td>
                                            <span class="badge rounded-pill 
                                                @if($handover->shipping_type === 'cung_quan') 
                                                    bg-success
                                                @elseif($handover->shipping_type === 'noi_thanh')
                                                    bg-primary  
                                                @else
                                                    bg-warning
                                                @endif">
                                                {{ $handover->shipping_type === 'cung_quan' ? 'Cùng quận' : 
                                                   ($handover->shipping_type === 'noi_thanh' ? 'Nội thành' : 'Ngoại thành') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ $handover->status === 'in_transit' ? 'Đang vận chuyển' : 'Đã hoàn thành' }}
                                            </span>
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#orderDetailModal{{ $handover->id }}"
                                                title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox display-6"></i>
                                                <p class="mb-0">Không có đơn hàng nào chờ xác nhận</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                @if($incomingHandovers->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-end">
                        {{ $incomingHandovers->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>