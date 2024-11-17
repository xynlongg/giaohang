@extends('layouts.app')

@push('styles')
<style>
    .page-content {
        padding: 1.5rem;
        min-height: calc(100vh - 60px);
    }

    /* Tab styles */
    .nav-tabs {
        border-bottom: 2px solid #e9ecef;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 0.75rem 1rem;
        font-weight: 500;
        position: relative;
    }

    .nav-tabs .nav-link:hover {
        color: #0d6efd;
        border: none;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        font-weight: 600;
        background: none;
        border: none;
    }

    .nav-tabs .nav-link.active:after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: #0d6efd;
    }

    /* Card styles */
    .card {
        border: none;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
    }

    .card-header {
        background: white;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Table styles */
    .table> :not(caption)>*>* {
        padding: 1rem;
    }

    .table thead th {
        background: #f8f9fa;
        color: #495057;
        font-weight: 600;
        white-space: nowrap;
    }

    .handover-row {
        transition: all 0.2s;
    }

    .handover-row:hover {
        background-color: #f8f9fa;
    }

    .handover-row.selected {
        background-color: #e7f2ff !important;
    }

    /* Badge styles */
    .badge {
        padding: 0.5em 1em;
        font-weight: 500;
    }

    /* Button styles */
    .btn-group-action {
        display: flex;
        gap: 0.5rem;
    }

    .btn-info {
        color: white;
        background: #0dcaf0;
    }

    .btn-info:hover {
        color: white;
        background: #0bb5da;
    }

    /* Modal styles */
    .modal-lg {
        max-width: 1000px;
    }

    .modal-body {
        max-height: calc(100vh - 210px);
        overflow-y: auto;
    }

    .modal .card {
        box-shadow: none;
        border: 1px solid #e9ecef;
    }

    .modal .card-title {
        font-size: 1rem;
        color: #495057;
        margin-bottom: 1rem;
    }

    /* Loading overlay */
    #loadingOverlay:not(.d-none) {
        opacity: 1;
    }

    /* Empty state */
    .empty-state {
        padding: 3rem;
        text-align: center;
    }

    .empty-state i {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 1rem;
    }

    /* Stats card */
    .stats-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .stats-value {
        font-size: 2rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .stats-label {
        color: #6c757d;
        font-size: 0.875rem;
    }

    /* Additional styles */
    @media (max-width: 768px) {
        .page-content {
            padding: 1rem;
        }

        .nav-tabs {
            gap: 0.5rem;
        }

        .card-body {
            padding: 1rem;
        }

        .table td,
        .table th {
            min-width: 120px;
        }

        .btn-group-action {
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<div class="page-content">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Xác Nhận Đơn Hàng Đến</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">
                            <i class="bi bi-house"></i> Trang chủ
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Đơn hàng đến</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <div class="stats-mini">
                <div class="fw-bold">{{ $incomingHandovers->count() }}</div>
                <small>Chờ xác nhận</small>
            </div>
            <div class="stats-mini">
                <div class="fw-bold">{{ $confirmedOrders->count() }}</div>
                <small>Chờ phân công</small>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-primary bg-opacity-10 text-primary mb-2">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stats-value">{{ $incomingHandovers->total() }}</div>
                <div class="stats-label">Tổng đơn hàng đến</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-success bg-opacity-10 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stats-value">{{ $confirmedOrders->total() }}</div>
                <div class="stats-label">Đã xác nhận</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-info bg-opacity-10 text-info mb-2">
                    <i class="bi bi-person"></i>
                </div>
                <div class="stats-value">{{ $availableShippers->count() }}</div>
                <div class="stats-label">Shipper khả dụng</div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button
                class="nav-link active d-flex align-items-center gap-2"
                id="pending-tab"
                data-bs-toggle="tab"
                data-bs-target="#pending"
                type="button"
                role="tab">
                <i class="bi bi-hourglass"></i>
                Chờ xác nhận đến
                <span class="badge bg-danger">{{ $incomingHandovers->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button
                class="nav-link d-flex align-items-center gap-2"
                id="confirmed-tab"
                data-bs-toggle="tab"
                data-bs-target="#confirmed"
                type="button"
                role="tab">
                <i class="bi bi-person-check"></i>
                Chờ phân công shipper
                <span class="badge bg-warning">{{ $confirmedOrders->count() }}</span>
            </button>
        </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content">
        {{-- Pending Orders Tab --}}
        @include('post_offices.receiving.pending_orders_tab')

        {{-- Confirmed Orders Tab --}}
        @include('post_offices.receiving.confirmed_orders_tab')

    </div>

    {{-- Order Detail Modals --}}
    @foreach($incomingHandovers as $handover)
    <div class="modal fade" id="orderDetailModal{{ $handover->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết đơn hàng #{{ $handover->order->tracking_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">Thông tin vận chuyển</h6>
                                    <div class="mt-3">
                                        <p class="mb-2">
                                            <i class="bi bi-truck me-2"></i>
                                            <strong>Loại vận chuyển:</strong>
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
                                        </p>
                                        <p class="mb-2">
                                            <i class="bi bi-arrows-move me-2"></i>
                                            <strong>Khoảng cách:</strong> {{ number_format($handover->distance, 1) }} km
                                        </p>
                                        <p class="mb-2">
                                            <i class="bi bi-box-seam me-2"></i>
                                            <strong>Khối lượng:</strong> {{ number_format($handover->order->weight ?? 0, 2) }} kg
                                        </p>
                                        <p class="mb-0">
                                            <i class="bi bi-currency-dollar me-2"></i>
                                            <strong>COD:</strong> {{ number_format($handover->order->cod_amount ?? 0) }} VNĐ
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">Thông tin bưu cục</h6>
                                    <div class="mt-3">
                                        <p class="mb-2">
                                            <i class="bi bi-building me-2"></i>
                                            <strong>Bưu cục gốc:</strong> {{ $handover->originPostOffice->name ?? 'N/A' }}
                                        </p>
                                        <p class="mb-2">
                                            <i class="bi bi-person-badge me-2"></i>
                                            <strong>Nhân viên phân phối:</strong> {{ $handover->distributionStaff->name ?? 'N/A' }}
                                        </p>
                                        <p class="mb-0">
                                            <i class="bi bi-clock me-2"></i>
                                            <strong>Thời gian bàn giao:</strong>
                                            @if($handover->assigned_at)
                                            {{ $handover->assigned_at->format('d/m/Y H:i') }}
                                            @else
                                            <span class="text-muted">Chưa có</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    @if($handover->status === 'in_transit')
                    <button
                        type="button"
                        class="btn btn-primary single-confirm-btn"
                        data-handover-id="{{ $handover->id }}"
                        data-order-tab="pending">
                        <i class="bi bi-check2-circle me-1"></i>
                        Xác nhận đơn này
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Order Detail Modals for Confirmed Orders --}}
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
                                        <p class="mb-2">
                                            <i class="bi bi-truck me-2"></i>
                                            <strong>Loại vận chuyển:</strong>
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
                                        </p>
                                        <p class="mb-2">
                                            <i class="bi bi-arrows-move me-2"></i>
                                            <strong>Khoảng cách:</strong> {{ number_format($order->distance ?? 0, 1) }} km
                                        </p>
                                        <p class="mb-2">
                                            <i class="bi bi-box-seam me-2"></i>
                                            <strong>Khối lượng:</strong> {{ number_format($order->weight ?? 0, 2) }} kg
                                        </p>
                                        <p class="mb-0">
                                            <i class="bi bi-currency-dollar me-2"></i>
                                            <strong>COD:</strong> {{ number_format($order->cod_amount ?? 0) }} VNĐ
                                        </p>
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

    {{-- Loading Overlay --}}
    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none">
        <div class="position-absolute top-50 start-50 translate-middle text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang xử lý...</span>
            </div>
            <div class="mt-2 text-primary">Đang xử lý...</div>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

{{-- Bootstrap Bundle with Popper --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

{{-- DataTables --}}
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

{{-- Moment.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Utility Functions
        function getElement(id) {
            return document.getElementById(id);
        }

        function getElements(selector) {
            return document.querySelectorAll(selector);
        }

        // Common Elements
        const loadingOverlay = getElement('loadingOverlay');

        // Pending Tab Elements
        const pendingTab = {
            selectAll: getElement('selectAllPending'),
            checkboxes: getElements('.pending-checkbox'),
            selectedCount: getElement('pendingSelectedCount'),
            confirmBtn: getElement('confirmArrivalBtn'),
            form: getElement('confirmArrivalForm')
        };

        // Confirmed Tab Elements
        const confirmedTab = {
            selectAll: getElement('selectAllConfirmed'),
            checkboxes: getElements('.confirmed-checkbox'),
            selectedCount: getElement('confirmedSelectedCount'),
            shipperSelect: getElement('shipperSelect'),
            assignBtn: getElement('assignShipperBtn'),
            form: getElement('assignShipperForm')
        };

        // UI Functions
        function showLoading() {
            if (loadingOverlay) {
                loadingOverlay.classList.remove('d-none');
            }
        }

        function hideLoading() {
            if (loadingOverlay) {
                loadingOverlay.classList.add('d-none');
            }
        }

        function updatePendingTabUI() {
            if (!pendingTab.selectedCount || !pendingTab.confirmBtn || !pendingTab.selectAll) return;

            const checkedBoxes = Array.from(pendingTab.checkboxes).filter(cb => cb.checked);
            const checkedCount = checkedBoxes.length;

            // Update count badge
            const countSpan = pendingTab.selectedCount.querySelector('span');
            if (countSpan) {
                countSpan.textContent = checkedCount;
            }
            pendingTab.selectedCount.classList.toggle('d-none', checkedCount === 0);

            // Update confirm button
            pendingTab.confirmBtn.classList.toggle('d-none', checkedCount === 0);
            pendingTab.confirmBtn.disabled = checkedCount === 0;

            // Update select all checkbox
            pendingTab.selectAll.checked = checkedCount === pendingTab.checkboxes.length && checkedCount > 0;
            pendingTab.selectAll.indeterminate = checkedCount > 0 && checkedCount < pendingTab.checkboxes.length;

            // Update row styles
            pendingTab.checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (row) {
                    row.classList.toggle('selected', checkbox.checked);
                }
            });
        }

        function updateConfirmedTabUI() {
            if (!confirmedTab.selectedCount || !confirmedTab.shipperSelect || !confirmedTab.assignBtn) return;

            const checkedBoxes = Array.from(confirmedTab.checkboxes).filter(cb => cb.checked);
            const checkedCount = checkedBoxes.length;

            // Update count badge
            const countSpan = confirmedTab.selectedCount.querySelector('span');
            if (countSpan) {
                countSpan.textContent = checkedCount;
            }
            confirmedTab.selectedCount.classList.toggle('d-none', checkedCount === 0);

            // Update shipper select and assign button
            confirmedTab.shipperSelect.disabled = checkedCount === 0;
            confirmedTab.assignBtn.classList.toggle('d-none', checkedCount === 0);
            confirmedTab.assignBtn.disabled = checkedCount === 0 || !confirmedTab.shipperSelect.value;

            // Update select all checkbox
            confirmedTab.selectAll.checked = checkedCount === confirmedTab.checkboxes.length && checkedCount > 0;
            confirmedTab.selectAll.indeterminate = checkedCount > 0 && checkedCount < confirmedTab.checkboxes.length;

            // Update row styles
            confirmedTab.checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (row) {
                    row.classList.toggle('selected', checkbox.checked);
                }
            });
        }

        // Event Handlers
        function handleSelectAll(tab) {
            if (!tab.selectAll) return;

            tab.selectAll.addEventListener('change', function() {
                tab.checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                tab === pendingTab ? updatePendingTabUI() : updateConfirmedTabUI();
            });

            tab.checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    tab === pendingTab ? updatePendingTabUI() : updateConfirmedTabUI();
                });
            });
        }

        function showLoading() {
            if (loadingOverlay) {
                loadingOverlay.classList.remove('d-none');
            }
        }

        function hideLoading() {
            if (loadingOverlay) {
                loadingOverlay.classList.add('d-none');
            }
        }

        async function handleFormSubmit(formElement) {
            if (!formElement) return;

            try {
                showLoading();
                const formData = new FormData(formElement);

                // Add shipper_id if it exists in confirmed tab
                if (confirmedTab.shipperSelect && confirmedTab.shipperSelect.value) {
                    formData.append('shipper_id', confirmedTab.shipperSelect.value);
                }

                const response = await fetch(formElement.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                // Ẩn loading ngay sau khi nhận được response
                hideLoading();

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                if (!data.success) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }

                await Swal.fire({
                    title: 'Thành công!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonText: 'Đóng'
                });

                // Reload trang sau khi đóng dialog thành công
                window.location.reload();

            } catch (error) {
                hideLoading(); // Đảm bảo ẩn loading khi có lỗi
                await Swal.fire({
                    title: 'Lỗi',
                    text: error.message,
                    icon: 'error',
                    confirmButtonText: 'Đóng'
                });
            }
        }


        // Confirm Arrival Button Handler
        if (pendingTab.confirmBtn && pendingTab.form) {
            pendingTab.confirmBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const result = await Swal.fire({
                    title: 'Xác nhận đơn hàng',
                    text: 'Bạn có chắc chắn muốn xác nhận các đơn hàng đã chọn đã đến bưu cục?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Xác nhận',
                    cancelButtonText: 'Hủy'
                });

                if (result.isConfirmed) {
                    await handleFormSubmit(pendingTab.form);
                }
            });
        }

        // Assign Shipper Button Handler
        if (confirmedTab.assignBtn && confirmedTab.form) {
            confirmedTab.assignBtn.addEventListener('click', async (e) => {
                e.preventDefault();

                if (!confirmedTab.shipperSelect.value) {
                    await Swal.fire({
                        title: 'Lỗi',
                        text: 'Vui lòng chọn shipper trước khi phân công',
                        icon: 'error',
                        confirmButtonText: 'Đóng'
                    });
                    return;
                }

                const result = await Swal.fire({
                    title: 'Phân công shipper',
                    text: 'Bạn có chắc chắn muốn phân công các đơn hàng đã chọn cho shipper này?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Phân công',
                    cancelButtonText: 'Hủy'
                });

                if (result.isConfirmed) {
                    await handleFormSubmit(confirmedTab.form);
                }
            });
        }

        // Shipper Select Handler
        if (confirmedTab.shipperSelect) {
            confirmedTab.shipperSelect.addEventListener('change', updateConfirmedTabUI);
        }

        // Single Order Confirm Buttons
        getElements('.single-confirm-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const handoverId = this.dataset.handoverId;
                const orderTab = this.dataset.orderTab;

                const checkbox = document.querySelector(`input[value="${handoverId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    if (orderTab === 'pending') {
                        updatePendingTabUI();
                        pendingTab.confirmBtn?.click();
                    } else {
                        updateConfirmedTabUI();
                        confirmedTab.shipperSelect?.focus();
                    }
                }

                // Close modal
                const modal = button.closest('.modal');
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });
        });

        getElements('.single-confirm-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const handoverId = this.dataset.handoverId;
                const orderTab = this.dataset.orderTab;

                const checkbox = document.querySelector(`input[value="${handoverId}"]`);
                if (checkbox) {
                    checkbox.checked = true;

                    // Close modal first
                    const modal = button.closest('.modal');
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }

                    if (orderTab === 'pending') {
                        updatePendingTabUI();
                        pendingTab.confirmBtn?.click();
                    } else {
                        updateConfirmedTabUI();
                        confirmedTab.shipperSelect?.focus();
                    }
                }
            });
        });

        // Tab Switch Handler
        const tabElements = getElements('[data-bs-toggle="tab"]');
        tabElements.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                const targetId = e.target.getAttribute('data-bs-target');
                if (targetId === '#pending') {
                    updatePendingTabUI();
                } else if (targetId === '#confirmed') {
                    updateConfirmedTabUI();
                }
            });
        });

        // Initialize event handlers
        handleSelectAll(pendingTab);
        handleSelectAll(confirmedTab);

        // Initialize tooltips
        const tooltips = getElements('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => {
            new bootstrap.Tooltip(el);
        });
        let isLoading = false; // Flag để tránh gọi nhiều lần

        async function loadAssignedOrders() {
            if (isLoading) return; // Nếu đang loading thì không gọi tiếp
            
            const tableBody = document.getElementById('assignedOrdersTable').getElementsByTagName('tbody')[0];
            if (!tableBody) return;

            try {
                isLoading = true;
                showLoading();

                const response = await fetch('/post-office/receiving/assigned-orders', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                // Set timeout để tránh loading quá lâu
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('Request timeout')), 10000);
                });

                const data = await Promise.race([
                    response.json(),
                    timeoutPromise
                ]);

                hideLoading();

                if (!data.success) {
                    throw new Error(data.message || 'Không thể tải dữ liệu');
                }

                const orders = data.data.data || [];
                
                // Build HTML một lần duy nhất thay vì nối chuỗi nhiều lần
                const rows = orders.map(order => `
                    <tr>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-bold">${order.tracking_number || 'N/A'}</span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold">${order.shipper_name || 'N/A'}</span>
                                <small class="text-muted">${order.shipper_phone || ''}</small>
                            </div>
                        </td>
                        <td>${order.distributor_name || 'N/A'}</td>
                        <td>
                            <span class="badge ${getStatusBadgeClass(order.status)}">
                                ${getStatusText(order.status)}
                            </span>
                        </td>
                        <td>${moment(order.distributed_at).format('DD/MM/YYYY HH:mm')}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-info" 
                                        onclick="viewOrderDetail(${order.order_id})" 
                                        title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                tableBody.innerHTML = rows.length ? rows : `
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="empty-state">
                                <i class="bi bi-inbox display-6"></i>
                                <p class="mb-0">Chưa có đơn hàng nào được gán</p>
                            </div>
                        </td>
                    </tr>
                `;

            } catch (error) {
                console.error('Error:', error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            ${error.message === 'Request timeout' ? 
                            'Không thể tải dữ liệu, vui lòng thử lại' : 
                            'Có lỗi xảy ra: ' + error.message}
                        </td>
                    </tr>
                `;
            } finally {
                hideLoading();
                isLoading = false;
            }
        }
        const loadingState = {
            timer: null,
            minLoadingTime: 500,  // Thời gian loading tối thiểu
            startTime: 0
        };


        // Thêm vào phần script
        function loadAssignedOrders() {
            const tableBody = document.getElementById('assignedOrdersTable').getElementsByTagName('tbody')[0];
            showLoading();

            fetch('/post-office/receiving/assigned-orders', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success && data.data && data.data.data) {
                    let html = '';
                    if (data.data.data.length > 0) {
                        data.data.data.forEach(order => {
                            html += `
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">${order.tracking_number || 'N/A'}</span>
                                            <small class="text-muted">COD: ${new Intl.NumberFormat('vi-VN').format(order.cod_amount || 0)}đ</small>
                                            <small class="text-muted">KL: ${order.weight || 0}kg</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">${order.shipper_name || 'N/A'}</span>
                                            <small class="text-muted">${order.shipper_phone || ''}</small>
                                        </div>
                                    </td>
                                    <td>${order.distributor_name || 'N/A'}</td>
                                    <td>
                                        <span class="badge ${getStatusBadgeClass(order.status)}">
                                            ${getStatusText(order.status)}
                                        </span>
                                    </td>
                                    <td>${moment(order.distributed_at).format('DD/MM/YYYY HH:mm')}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info" 
                                                    onclick="viewOrderDetail(${order.order_id})" 
                                                    title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        html = `
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox display-6"></i>
                                        <p class="mb-0">Chưa có đơn hàng nào được gán</p>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }
                    tableBody.innerHTML = html;

                    // Log dữ liệu để debug
                    console.log('Loaded assigned orders:', data.data);
                } else {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-danger">
                                ${data.message || 'Không thể tải dữ liệu'}
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            Có lỗi xảy ra khi tải dữ liệu: ${error.message}
                        </td>
                    </tr>
                `;
            });
        }

        // Show/hide loading overlay
        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (!overlay) return;
            
            loadingState.startTime = Date.now();
            overlay.classList.remove('d-none');
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (!overlay) return;

            const elapsedTime = Date.now() - loadingState.startTime;
            const remainingTime = Math.max(0, loadingState.minLoadingTime - elapsedTime);

            // Đảm bảo loading hiển thị ít nhất minLoadingTime
            clearTimeout(loadingState.timer);
            loadingState.timer = setTimeout(() => {
                overlay.classList.add('d-none');
            }, remainingTime);
        }

        // Helper functions
        function getStatusBadgeClass(status) {
            const statusClasses = {
                'assigned': 'bg-warning',
                'in_delivery': 'bg-info',
                'completed': 'bg-success',
                'cancelled': 'bg-danger',
                'default': 'bg-secondary'
            };
            return statusClasses[status] || statusClasses.default;
        }

        function getStatusText(status) {
            const statusText = {
                'assigned': 'Đã phân công',
                'in_delivery': 'Đang giao hàng',
                'completed': 'Hoàn thành',
                'cancelled': 'Đã hủy'
            };
            return statusText[status] || status;
        }

        const modalConfig = {
            lastOpenTime: 0,
            minTimeBetweenOpens: 1000  // 1 giây giữa các lần mở modal
        };
        // Add modal event listener
        const assignedOrdersModal = document.getElementById('assignedOrdersModal');
        if (assignedOrdersModal) {
            assignedOrdersModal.addEventListener('show.bs.modal', (event) => {
                const now = Date.now();
                if (now - modalConfig.lastOpenTime < modalConfig.minTimeBetweenOpens) {
                    event.preventDefault();
                    return;
                }
                modalConfig.lastOpenTime = now;
                loadAssignedOrders();
            });

            // Prevent multiple clicks
            const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"]');
            modalTriggers.forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    if (isLoading) {
                        e.preventDefault();
                        return;
                    }
                });
            });
        }

        // Error handling for view and track functions
        function viewOrderDetail(orderId) {
            try {
                console.log('Opening order detail:', orderId);
                // TODO: Implement view logic
            } catch (error) {
                console.error('Error viewing order:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể xem chi tiết đơn hàng'
                });
            }
        }

        function trackOrder(orderId) {
            try {
                console.log('Tracking order:', orderId);
                // TODO: Implement tracking logic
            } catch (error) {
                console.error('Error tracking order:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể theo dõi đơn hàng'
                });
            }
        }

        // Initialize tooltips
        $(document).ready(function() {
            try {
                $('[title]').tooltip();
            } catch (error) {
                console.error('Error initializing tooltips:', error);
            }
        });

        // Initialize UI states
        updatePendingTabUI();
        updateConfirmedTabUI();
    });
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    /* Thêm vào phần styles */
    .modal-body {
        max-height: calc(100vh - 210px);
        overflow-y: auto;
    }

    .badge {
        font-weight: 500;
    }

    .empty-state {
        padding: 2rem;
        text-align: center;
        color: #6c757d;
    }

    .table .form-check {
        margin: 0;
    }

    .btn-info {
        color: #fff;
    }

    .btn-info:hover {
        color: #fff;
        background-color: #0dcaf0;
    }
    /* Use hardware acceleration for animations */
    .modal.fade {
        will-change: transform, opacity;
        backface-visibility: hidden;
        transform: translateZ(0);
    }
    #loadingOverlay {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(4px);
        z-index: 9999;
        will-change: opacity;
        transition: opacity 0.2s ease-in-out;
        opacity: 0;
    }

    .handover-row.selected td {
        background-color: #e8f4ff;
    }

    .card-body {
        padding: 1.5rem;
    }

    .modal .card {
        box-shadow: none;
        border: 1px solid rgba(0, 0, 0, .125);
    }

    .modal .card-title {
        color: #495057;
        font-size: 1rem;
        margin-bottom: 0;
    }

    .pagination {
        margin-bottom: 0;
    }
</style>
@endpush
@endsection