@extends('layouts.app')

@section('content')
<div class="pagetitle">
    <h1>Đơn Hàng Được Phân Công</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Đơn Hàng Phân Công</li>
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
                            @if($currentPostOffice)
                            <div class="d-inline-block me-3">
                                <i class="bi bi-building me-2"></i>
                                <span>Bưu cục: {{ $currentPostOffice->name }}</span>
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
                                    {{ $recentlyCompletedHandovers->sum(function($hourGroup) { return $hourGroup->count(); }) }}
                                </span>
                            </a>
                        </li>
                      
                        <li class="nav-item flex-fill">
                            <a class="nav-link d-flex align-items-center justify-content-center"
                                data-bs-toggle="tab"
                                href="#warehouse-orders">
                                <i class="bi bi-truck me-1"></i>
                                <span>Đơn Cần Chuyển Kho</span>
                                <span class="badge bg-primary rounded-pill ms-2">
                                    {{ $warehouseOrders->sum(function($province) { return $province->count(); }) }}
                                </span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <!-- Tab đơn đã phân phối -->
                        @include('distribution.assigned-orders._completed_orders_tab')
                        
                        
                        <!-- Tab đơn cần chuyển kho -->
                        @include('distribution.assigned-orders._warehouse_orders_tab')
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
@push('scripts')
<!-- Required libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Biến globals
    let loadingDialog = null;

    // Utility Functions
    function showLoading() {
        Swal.fire({
            title: 'Đang xử lý...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        }).then((result) => {
            loadingDialog = result;
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

    // Order Selection Functions
    function selectOrders(type, identifier, checked) {
        try {
            $(`.${type}-checkbox[data-identifier="${identifier}"]`)
                .prop('checked', checked)
                .trigger('change');

            // Update table header select all
            $(`input[type="checkbox"][onchange="selectOrders('${type}', '${identifier}', this.checked)"]`)
                .prop('checked', checked);

            updateUICounters(type);
        } catch (error) {
            console.error('Error selecting orders:', error);
        }
    }

    // UI Update Functions
    function updateUICounters(type) {
        try {
            const checked = $(`.${type}-checkbox:checked`).length;
            const selectId = type === 'office' ? 'destinationOffice' : 'destinationWarehouse';
            const destination = $(`#${selectId}`).val();

            $(`[onclick="updateBulkArrival('${type}')"]`)
                .prop('disabled', !checked || !destination);
        } catch (error) {
            console.error('Error updating UI:', error);
        }
    }

    // API Functions
    async function updateArrivalStatus(handoverId, type) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const selectId = type === 'office' ? 'destinationOffice' : 'destinationWarehouse';
            const destinationId = $(`#${selectId}`).val();

            if (!destinationId) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: `Vui lòng chọn ${type === 'office' ? 'bưu cục' : 'kho'} đích`
                });
                return;
            }

            showLoading();

            const response = await fetch(`/distributor/assigned-orders/${handoverId}/update-arrival`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    destination_type: type === 'office' ? 'post_office' : 'warehouse',
                    destination_id: destinationId
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
            console.error('Error updating status:', error);

            await Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: error.message || 'Có lỗi xảy ra khi cập nhật'
            });
        }
    }

    async function updateBulkArrival(type) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const checkboxes = $(`.${type}-checkbox:checked`);
            const selectId = type === 'office' ? 'destinationOffice' : 'destinationWarehouse';

            if (!checkboxes.length) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Vui lòng chọn ít nhất một đơn hàng'
                });
                return;
            }

            const destination = $(`#${selectId}`).val();

            if (!destination) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: `Vui lòng chọn ${type === 'office' ? 'bưu cục' : 'kho'} đích`
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
            const response = await fetch('/distributor/assigned-orders/batch-update-arrival', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    handover_ids: handoverIds,
                    destination_type: type === 'office' ? 'post_office' : 'warehouse',
                    destination_id: destination
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
            console.error('Error bulk updating:', error);

            await Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: error.message || 'Có lỗi xảy ra khi cập nhật hàng loạt'
            });
        }
    }

    // Document Ready
    $(document).ready(function() {
        // Initialize DataTables
        try {
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
        } catch (error) {
            console.error('Error initializing DataTables:', error);
        }

        // Initialize tooltips
        try {
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(el => new bootstrap.Tooltip(el));
        } catch (error) {
            console.error('Error initializing tooltips:', error);
        }

        // Event bindings
        try {
            // Single checkbox changes
            $('.office-checkbox, .warehouse-checkbox').on('change', function() {
                const type = $(this).hasClass('office-checkbox') ? 'office' : 'warehouse';
                updateUICounters(type);
            });

            // Destination select changes
            $('#destinationOffice, #destinationWarehouse').on('change', function() {
                const type = this.id === 'destinationOffice' ? 'office' : 'warehouse';
                updateUICounters(type);
            });

            // Global search
            $('#orderSearch').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.datatable').each(function() {
                    const dataTable = $(this).DataTable();
                    dataTable.search(searchTerm).draw();
                });
            });

        } catch (error) {
            console.error('Error binding events:', error);
        }

        // Initial UI updates
        updateUICounters('office');
        updateUICounters('warehouse');
    });
</script>
@endpush

@push('styles')
<style>
    /* Responsive Table Styles */
    @media (max-width: 640px) {
        .table-responsive {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table-responsive thead {
            display: none;
        }

        .table-responsive tr {
            display: block;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.5rem 0;
        }

        .table-responsive td {
            display: flex;
            padding: 0.5rem 1rem;
            text-align: right;
            justify-content: space-between;
            align-items: center;
        }

        .table-responsive td::before {
            content: attr(data-label);
            font-weight: 500;
            margin-right: 1rem;
            text-align: left;
        }
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }

    /* Animation Classes */
    .fade-enter {
        opacity: 0;
        transform: translateY(-10px);
    }

    .fade-enter-active {
        opacity: 1;
        transform: translateY(0);
        transition: opacity 300ms, transform 300ms;
    }

    .fade-exit {
        opacity: 1;
        transform: translateY(0);
    }

    .fade-exit-active {
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 300ms, transform 300ms;
    }

    /* Additional Styles */
    .btn:disabled {
        cursor: not-allowed;
        opacity: 0.65;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }

    .form-select {
        min-width: 200px;
    }

    .table td {
        vertical-align: middle;
    }
</style>
@endpush