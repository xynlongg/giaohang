@extends('layouts.app')

@section('content')
<div class="pagetitle">
    <h1>Đơn Hàng Được Phân Công</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Đơn Hàng Phân Phối</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Location Info -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            @if($warehouse)
                            <div class="d-inline-block me-3">
                                <i class="bi bi-building me-2"></i>
                                <span>Kho: {{ $warehouse->name }}</span>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <div class="search-bar">
                                <div class="search-form d-flex align-items-center">
                                    <input type="text" id="orderSearch"
                                        class="form-control"
                                        placeholder="Tìm kiếm đơn hàng..."
                                        autocomplete="off">
                                    <button type="button" class="btn" title="Search">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs d-flex" role="tablist">
                        <li class="nav-item flex-fill">
                            <a class="nav-link d-flex align-items-center justify-content-center"
                                data-bs-toggle="tab"
                                href="#completed-orders">
                                <i class="bi bi-check-circle me-1"></i>
                                <span>Đơn Đã Phân Phối</span>
                                <span class="badge bg-success rounded-pill ms-2">
                                    {{ $completedHandovers->sum(function($hourGroup) { return $hourGroup->count(); }) }}
                                </span>
                            </a>
                        </li>
                        @if(auth()->user()->hasRole('warehouse_local_distributor'))
                        <li class="nav-item flex-fill">
                            <a class="nav-link active d-flex align-items-center justify-content-center"
                                data-bs-toggle="tab"
                                href="#local-orders">
                                <i class="bi bi-geo-alt me-1"></i>
                                <span>Đơn Nội Thành</span>
                                <span class="badge bg-primary rounded-pill ms-2">
                                    {{ $localOrders->sum(function($district) { return $district->count(); }) }}
                                </span>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasRole('warehouse_remote_distributor'))
                        <li class="nav-item flex-fill">
                            <a class="nav-link {{ !auth()->user()->hasRole('warehouse_local_distributor') ? 'active' : '' }} d-flex align-items-center justify-content-center"
                                data-bs-toggle="tab"
                                href="#remote-orders">
                                <i class="bi bi-truck me-1"></i>
                                <span>Đơn Ngoại Thành</span>
                                <span class="badge bg-primary rounded-pill ms-2">
                                    {{ $remoteOrders->sum(function($province) { return $province->count(); }) }}
                                </span>
                            </a>
                        </li>
                        @endif
                    </ul>

                    <div class="tab-content pt-3">
                        <!-- Tab đơn đã phân phối -->
                        @include('warehouse.distributor.orders._completed_tab')

                        <!-- Tab đơn nội thành -->
                        @if(auth()->user()->hasRole('warehouse_local_distributor'))
                            @include('warehouse.distributor.orders._local_tab')
                        @endif

                        <!-- Tab đơn ngoại thành -->
                        @if(auth()->user()->hasRole('warehouse_remote_distributor'))
                            @include('warehouse.distributor.orders._remote_tab')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="background: transparent; border: none;">
            <div class="modal-body text-center">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Đang xử lý...</span>
                </div>
                <div class="text-light mt-2">Đang xử lý...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
function showLoading() {
    Swal.fire({
        title: 'Đang xử lý...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
}

function hideLoading() {
    if (Swal.isVisible()) {
        Swal.close();
    }
}

function showNotification(message, type = 'success') {
    Swal.fire({
        icon: type,
        title: type === 'success' ? 'Thành công' : 'Lỗi',
        text: message,
        timer: 2000,
        showConfirmButton: false,
        position: 'top-end',
        toast: true
    });
}

// Hàm chọn đơn nội thành
function selectLocalOrders(district, checked) {
    $(`.local-order-checkbox[data-district="${district}"]`)
        .prop('checked', checked)
        .trigger('change');
    updateLocalUICounters();
}

// Hàm chọn đơn ngoại thành
function selectRemoteOrders(province, checked) {
    $(`.remote-order-checkbox[data-province="${province}"]`)
        .prop('checked', checked)
        .trigger('change');
    updateRemoteUICounters();
}

// Cập nhật UI
function updateLocalUICounters() {
    const checked = $('.local-order-checkbox:checked').length;
    const destination = $('#localDestinationOffice').val();
    $('[onclick="updateLocalDelivery()"]').prop('disabled', !checked || !destination);
}

function updateRemoteUICounters() {
    const checked = $('.remote-order-checkbox:checked').length;
    const destination = $('#remoteDestinationWarehouse').val();
    $('[onclick="updateRemoteDelivery()"]').prop('disabled', !checked || !destination);
}

// Cập nhật đơn hàng nội thành
async function updateLocalDelivery() {
    try {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const checkboxes = $('.local-order-checkbox:checked');
        const postOfficeId = $('#localDestinationOffice').val();

        if (!checkboxes.length) {
            await Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Vui lòng chọn ít nhất một đơn hàng'
            });
            return;
        }

        if (!postOfficeId) {
            await Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Vui lòng chọn bưu cục đích'
            });
            return;
        }

        const result = await Swal.fire({
            title: 'Xác nhận cập nhật',
            text: `Bạn có chắc muốn cập nhật ${checkboxes.length} đơn hàng đã chọn?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Cập nhật',
            cancelButtonText: 'Hủy'
        });

        if (!result.isConfirmed) return;

        showLoading();

        const handoverIds = Array.from(checkboxes).map(cb => $(cb).val());
        const response = await fetch('{{ route("warehouse.distributor.orders.update-local") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                handover_ids: handoverIds,
                post_office_id: postOfficeId
            })
        });

        hideLoading();

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Có lỗi xảy ra');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Thành công',
            text: data.message,
            timer: 1500
        });

        window.location.reload();

    } catch (error) {
        hideLoading();
        console.error('Error updating local delivery:', error);

        await Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: error.message || 'Có lỗi xảy ra khi cập nhật'
        });
    }
}

// Cập nhật đơn hàng ngoại thành
async function updateRemoteDelivery() {
    try {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const checkboxes = $('.remote-order-checkbox:checked');
        const warehouseId = $('#remoteDestinationWarehouse').val();

        if (!checkboxes.length) {
            await Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Vui lòng chọn ít nhất một đơn hàng'
            });
            return;
        }

        if (!warehouseId) {
            await Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Vui lòng chọn kho đích'
            });
            return;
        }

        const result = await Swal.fire({
            title: 'Xác nhận cập nhật',
            text: `Bạn có chắc muốn cập nhật ${checkboxes.length} đơn hàng đã chọn?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Cập nhật',
            cancelButtonText: 'Hủy'
        });

        if (!result.isConfirmed) return;

        showLoading();

        const handoverIds = Array.from(checkboxes).map(cb => $(cb).val());
        const response = await fetch('{{ route("warehouse.distributor.orders.update-remote") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                handover_ids: handoverIds,
                warehouse_id: warehouseId
            })
        });

        hideLoading();

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Có lỗi xảy ra');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Thành công',
            text: data.message,
            timer: 1500
        });

        window.location.reload();

    } catch (error) {
        hideLoading();
        console.error('Error updating remote delivery:', error);

        await Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: error.message || 'Có lỗi xảy ra khi cập nhật'
        });
    }
}

// Event bindings
$(document).ready(function() {
    // Initialize DataTables
    $('.datatable').each(function() {
        $(this).DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json'
            },
            pageLength: 10,
            ordering: true,
            responsive: true,
            dom: 'lfrtp'
        });
    });

    // Bind events
    $('.local-order-checkbox').on('change', updateLocalUICounters);
    $('.remote-order-checkbox').on('change', updateRemoteUICounters);
    $('#localDestinationOffice').on('change', updateLocalUICounters);
    $('#remoteDestinationWarehouse').on('change', updateRemoteUICounters);

    // Initial UI updates
    updateLocalUICounters();
    updateRemoteUICounters();
});
</script>
@endpush