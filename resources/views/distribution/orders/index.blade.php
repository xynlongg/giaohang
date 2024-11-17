@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="pagetitle">
        <h1>Quản Lý Phân Phối Đơn Hàng</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Trang chủ</a></li>
                <li class="breadcrumb-item active">Đơn hàng đang phân phối</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                @if(auth()->user()->hasRole('local_distribution_staff'))
                                    <h5 class="card-title mb-0">
                                        Bưu cục phân phối: {{ auth()->user()->postOffices->first()->name ?? 'Chưa được gán' }}
                                    </h5>
                                @elseif(auth()->user()->hasRole('general_distribution_staff'))
                                    <h5 class="card-title mb-0">
                                        Nhân viên phân phối chung
                                    </h5>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span id="selectedCount" class="text-muted d-none">
                                    Đã chọn: <span class="fw-bold text-primary">0</span>
                                </span>
                                <button 
                                    id="batchUpdateBtn"
                                    class="btn btn-primary d-none"
                                    disabled
                                >
                                    <i class="bi bi-check2-circle me-1"></i>
                                    Xác nhận đã giao đến điểm đích
                                </button>
                            </div>
                        </div>

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-1"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <form id="batchUpdateForm" action="{{ route('distribution.batch-update') }}" method="POST">
                        @csrf
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="40">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>Mã đơn</th>
                                            <th>Điểm xuất phát</th>
                                            <th>Điểm đến</th>
                                            <th>Loại vận chuyển</th>
                                            <th>Khoảng cách</th>
                                            <th>Thời gian bàn giao</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($assignedOrders as $handover)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input 
                                                        class="form-check-input order-checkbox" 
                                                        type="checkbox" 
                                                        name="handover_ids[]" 
                                                        value="{{ $handover->id }}"
                                                    >
                                                </div>
                                            </td>
                                            <td class="fw-bold">
                                                #{{ $handover->order->tracking_number ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-building me-2 text-primary"></i>
                                                    {{ $handover->originPostOffice->name ?? ('Bưu cục ' . $handover->origin_post_office_id) }}
                                                </div>
                                            </td>
                                            <td>
                                            <div class="d-flex align-items-center">
                                                @if($handover->destination_warehouse_id)
                                                    <i class="bi bi-building-fill me-2 text-warning"></i>
                                                    {{ $handover->destinationWarehouse->name ?? 'Kho tổng ' . $handover->destination_warehouse_id }}
                                                @else
                                                    <i class="bi bi-building me-2 text-primary"></i>
                                                    {{ $handover->destinationPostOffice->name ?? 'Bưu cục ' . $handover->destination_post_office_id }}
                                                @endif
                                            </div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill 
                                                    @if($handover->shipping_type === 'cung_quan') 
                                                        bg-success
                                                    @elseif($handover->shipping_type === 'noi_thanh')
                                                        bg-primary  
                                                    @else
                                                        bg-warning
                                                    @endif">
                                                    <i class="bi bi-{{ $handover->shipping_type === 'cung_quan' ? 'geo-alt' : ($handover->shipping_type === 'noi_thanh' ? 'building' : 'truck') }} me-1"></i>
                                                    {{ $handover->shipping_type }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ number_format($handover->distance, 1) }} km
                                                </span>
                                            </td>
                                            <td>
                                                @if($handover->distributed_at)
                                                    {{ $handover->distributed_at->format('d/m/Y H:i') }}
                                                @else
                                                    <span class="text-muted">Chưa có</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill bg-info">
                                                    {{ $handover->status }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox h4 d-block mb-2"></i>
                                                Không có đơn hàng nào đang phân phối
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </form>

                        <div class="mt-4 d-flex justify-content-end">
                            {{ $assignedOrders->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const batchUpdateBtn = document.getElementById('batchUpdateBtn');
    const selectedCountElement = document.getElementById('selectedCount');
    const batchUpdateForm = document.getElementById('batchUpdateForm');

    function updateUI() {
        const checkedBoxes = Array.from(orderCheckboxes).filter(cb => cb.checked);
        const checkedCount = checkedBoxes.length;
        
        selectedCountElement.querySelector('span').textContent = checkedCount;
        selectedCountElement.classList.toggle('d-none', checkedCount === 0);
        
        batchUpdateBtn.classList.toggle('d-none', checkedCount === 0);
        batchUpdateBtn.disabled = checkedCount === 0;
        
        selectAllCheckbox.checked = checkedCount === orderCheckboxes.length && checkedCount !== 0;
    }

    selectAllCheckbox.addEventListener('change', function() {
        orderCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateUI();
    });

    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateUI);
    });

    batchUpdateBtn.addEventListener('click', async function(e) {
        e.preventDefault();

        const result = await Swal.fire({
            title: 'Xác nhận cập nhật',
            text: 'Bạn có chắc chắn muốn xác nhận đã giao các đơn hàng đến điểm đích?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                try {
                    const formData = new FormData(batchUpdateForm);
                    const response = await fetch(batchUpdateForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.message || 'Có lỗi xảy ra');
                    }

                    return data;
                } catch (error) {
                    Swal.showValidationMessage(`Có lỗi xảy ra: ${error.message}`);
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        });

        if (result.isConfirmed && result.value) {
            await Swal.fire({
                title: 'Thành công!',
                text: result.value.message,
                icon: 'success'
            });
            
            // Thêm delay nhỏ trước khi reload trang
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    });

    // Thêm tooltip cho các nút có title
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Xử lý đóng alert tự động sau 5 giây
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
@endpush
@endsection