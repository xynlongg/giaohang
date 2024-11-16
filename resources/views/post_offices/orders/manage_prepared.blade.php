    @extends('layouts.app')

    @section('content')
    <div class="container-fluid">
        <h1 class="mb-4">Quản lý đơn hàng đã đến bưu cục - {{ $postOffice->name }}</h1>

        <div id="realtime-notifications"></div>

        @if(session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
        @endif

        <ul class="nav nav-tabs" id="orderTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="cung-quan-tab" data-toggle="tab" href="#cung-quan" role="tab">
                    Đơn hàng cùng quận ({{ $shippingCategories['cung_quan']->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="noi-thanh-duoi-20km-tab" data-toggle="tab" href="#noi-thanh-duoi-20km" role="tab">
                    Nội thành (≤20km) ({{ array_reduce($noithanhDuoi20kmByDistrict, function($carry, $district) { return $carry + count($district); }, 0) }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="noi-thanh-tren-20km-tab" data-toggle="tab" href="#noi-thanh-tren-20km" role="tab">
                    Nội thành (>20km) ({{ $shippingCategories['noi_thanh_tren_20km']->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="ngoai-thanh-tab" data-toggle="tab" href="#ngoai-thanh" role="tab">
                    Ngoại thành ({{ $shippingCategories['ngoai_thanh']->count() }})
                </a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="orderTabsContent">
            <!-- Đơn hàng cùng quận -->
            <div class="tab-pane fade show active" id="cung-quan" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Mã đơn hàng</th>
                                    <th>Người gửi</th>
                                    <th>Người nhận</th>
                                    <th>Thời gian</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shippingCategories['cung_quan'] as $order)
                                @include('post_offices.orders.partials.order_row', ['order' => $order, 'type' => 'cung_quan', 'shippers' => $shippers])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Đơn hàng nội thành (≤20km) -->
            <div class="tab-pane fade" id="noi-thanh-duoi-20km" role="tabpanel">
                @foreach($noithanhDuoi20kmByDistrict as $district => $orders)
                <h5 class="mt-3">{{ $district }} ({{ $orders->count() }} đơn hàng)</h5>
                <form class="dispatch-local-form mb-3" action="{{ route('post_office.orders.dispatch_to_local') }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="required">Chọn bưu cục đích:</label>
                                <select name="target_post_office_id" class="form-control target-location" required>
                                    <option value="">Chọn bưu cục đích</option>
                                    @foreach($postOffices as $district => $districtPostOffices)
                                    <optgroup label="Quận/Huyện: {{ $district }}">
                                        @foreach($districtPostOffices as $postOffice)
                                        <option value="{{ $postOffice->id }}"
                                            data-district="{{ $postOffice->district }}"
                                            data-province="{{ $postOffice->province }}"
                                            data-ward="{{ $postOffice->ward }}">
                                            {{ $postOffice->name }}
                                            - {{ $postOffice->address }}
                                            @if($postOffice->ward)
                                            (Phường/Xã: {{ $postOffice->ward }})
                                            @endif
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn bưu cục đích</div>
                                <small class="form-text text-muted">
                                    Chỉ hiển thị các bưu cục trong khu vực gần với địa chỉ người nhận
                                </small>
                            </div>
                            <div class="form-group staff-selection" style="display:none;">
                                <label class="required">
                                    Chọn nhân viên phân phối
                                    <small class="text-muted">- Nhân viên của bưu cục hiện tại</small>
                                </label>
                                <div class="position-relative">
                                    <select name="local_distribution_staff_id" class="form-control staff-select" required>
                                        <option value="">Chọn nhân viên phân phối</option>
                                    </select>
                                    <div class="spinner-border spinner-border-sm position-absolute" style="right: 10px; top: 12px; display: none" role="status">
                                        <span class="sr-only">Đang tải...</span>
                                    </div>
                                </div>
                                <div class="invalid-feedback">Vui lòng chọn nhân viên phân phối</div>
                            </div>

                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input select-all-orders" id="select-all-{{ $district }}">
                                                <label class="custom-control-label" for="select-all-{{ $district }}"></label>
                                            </div>
                                        </th>
                                        <th>Mã đơn hàng</th>
                                        <th>Người gửi</th>
                                        <th>Người nhận</th>
                                        <th>Thời gian</th>
                                        <th>Khoảng cách</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                    <tr>
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input order-checkbox"
                                                    name="selected_orders[]" value="{{ $order->id }}"
                                                    id="order-{{ $order->id }}">
                                                <label class="custom-control-label" for="order-{{ $order->id }}"></label>
                                            </div>
                                        </td>
                                        <td>{{ $order->tracking_number }}</td>
                                        <td>
                                            <div>{{ $order->sender_name }}</div>
                                            <small class="text-muted">{{ $order->sender_phone }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $order->receiver_name }}</div>
                                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                                        </td>
                                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ number_format($order->calculated_distance, 1) }} km
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $order->status_color }}">
                                                {{ $order->status }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="bi bi-inbox h4 d-block mb-2"></i>
                                            Không có đơn hàng nào
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary dispatch-btn" style="display:none;">
                                    <i class="bi bi-send"></i> Điều phối đơn hàng đã chọn
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                @endforeach
            </div>

            <!-- Đơn hàng nội thành (>20km) và ngoại thành -->
            @foreach(['noi-thanh-tren-20km' => 'Nội thành (>20km)', 'ngoai-thanh' => 'Ngoại thành'] as $id => $title)
            <div class="tab-pane fade" id="{{ $id }}" role="tabpanel">
                <form class="bulk-dispatch-form" action="{{ route('post_office.orders.bulk_dispatch_to_warehouse') }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Chọn kho tổng đích:</label>
                                <select name="target_warehouse_id" class="form-control target-location" required>
                                    <option value="">Chọn kho tổng</option>
                                    @foreach($provincialWarehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group staff-selection" style="display:none;">
                                <label>Chọn nhân viên phân phối:</label>
                                <select name="general_distribution_staff_id" class="form-control" required>
                                    <option value="">Chọn nhân viên phân phối</option>
                                </select>
                            </div>

                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input select-all-orders">
                                                <label class="form-check-label">Tất cả</label>
                                            </div>
                                        </th>
                                        <th>Mã đơn hàng</th>
                                        <th>Người gửi</th>
                                        <th>Người nhận</th>
                                        <th>Thời gian</th>
                                        @if($id === 'noi-thanh-tren-20km')
                                        <th>Khoảng cách</th>
                                        @endif
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shippingCategories[str_replace('-', '_', $id)] as $order)
                                    @include('post_offices.orders.partials.order_row', [
                                    'order' => $order,
                                    'type' => str_replace('-', '_', $id)
                                    ])
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-success dispatch-btn" style="display:none;">
                                    Điều phối đơn hàng đã chọn đến kho tổng
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endsection
    @push('scripts')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            // Xử lý khi chọn địa điểm đích (bưu cục hoặc kho tổng)
            $('.target-location').on('change', function() {
                var form = $(this).closest('form');
                var staffSelect = form.find('.staff-selection select');
                var locationId = $(this).val();
                var locationType = form.hasClass('dispatch-local-form') ? 'bưu cục' : 'kho tổng';

                console.log('LocationId selected:', locationId);

                if (!locationId) {
                    form.find('.staff-selection').hide();
                    form.find('.dispatch-btn').hide();
                    return;
                }

                form.find('.staff-selection').show();
                staffSelect.prop('disabled', true).empty()
                    .append('<option value="">Đang tải danh sách nhân viên...</option>');

                $.ajax({
                    url: '/post-office/orders/get-distribution-staff',
                    type: 'GET',
                    data: form.hasClass('dispatch-local-form') ? {
                        post_office_id: locationId
                    } : {
                        warehouse_id: locationId
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Response:', response);

                        staffSelect.empty().prop('disabled', false)
                            .append('<option value="">Chọn nhân viên phân phối</option>');

                        if (response.success && response.data && response.data.length > 0) {
                            console.log('Found staff members:', response.data);

                            response.data.forEach(function(staff) {
                                var roleText = '';
                                if (staff.roles && staff.roles.length > 0) {
                                    roleText = ' - [' + staff.roles.join(', ') + ']';
                                }

                                staffSelect.append(
                                    `<option value="${staff.id}">
                                        ${staff.name} (${staff.email})${staff.phone ? ' - ' + staff.phone : ''}${roleText}
                                    </option>`
                                );
                            });
                        } else {
                            staffSelect.append(
                                `<option value="" disabled>Không tìm thấy nhân viên phân phối cho ${locationType} này</option>`
                            );
                        }

                        updateDispatchButtonVisibility(form);
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });

                        staffSelect.empty()
                            .append(`<option value="">Lỗi tải danh sách nhân viên</option>`)
                            .prop('disabled', true);

                        showNotification('error',
                            `Lỗi tải danh sách nhân viên phân phối của ${locationType}: ${error}`
                        );
                    }
                });
            });

            // Xử lý khi chọn nhân viên
            $('.staff-selection select').on('change', function() {
                var form = $(this).closest('form');
                updateDispatchButtonVisibility(form);
            });

            // Xử lý gán shipper
            $('.assign-shipper-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        form.closest('tr').fadeOut(500, function() {
                            $(this).remove();
                            updateOrderCounts();
                        });
                        showNotification('success', `Đã gán shipper thành công cho đơn hàng ${response.tracking_number}`);
                    },
                    error: function(xhr) {
                        console.error('Lỗi khi gán shipper:', xhr.responseText);
                        showNotification('error', 'Có lỗi xảy ra khi gán shipper. Vui lòng thử lại.');
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            // Xử lý điều phối hàng loạt đến kho tổng
            $('.bulk-dispatch-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);

                if (!validateDispatchForm(form)) return;

                var submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        handleDispatchResponse(response);
                    },
                    error: handleDispatchError,
                    complete: function() {
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            // Xử lý điều phối đến bưu cục địa phương
            $('.dispatch-local-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);

                // Log form data trước khi gửi
                var formData = new FormData(form[0]);
                console.log('Form data before submit:', {
                    selected_orders: formData.getAll('selected_orders[]'),
                    target_post_office_id: formData.get('target_post_office_id'),
                    local_distribution_staff_id: formData.get('local_distribution_staff_id'),
                    general_distribution_staff_id: formData.get('general_distribution_staff_id')
                });

                if (!validateDispatchForm(form)) return;

                var submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        console.log('Response:', response);
                        handleDispatchResponse(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        handleDispatchError(xhr);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            // Xử lý điều phối đơn lẻ đến kho tổng
            $('.dispatch-to-warehouse').on('click', function() {
                var button = $(this);
                var orderId = button.data('order-id');
                var form = button.closest('form');
                var warehouseId = form.find('select[name="target_warehouse_id"]').val();
                var staffId = form.find('select[name="general_distribution_staff_id"]').val();

                if (!warehouseId || !staffId) {
                    showNotification('error', 'Vui lòng chọn kho tổng và nhân viên phân phối trước khi điều phối.');
                    return;
                }

                button.prop('disabled', true);

                $.ajax({
                    url: '/post-office/orders/dispatch-single-to-warehouse',
                    method: 'POST',
                    data: {
                        order_id: orderId,
                        warehouse_id: warehouseId,
                        distribution_staff_id: staffId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            button.closest('tr').fadeOut(500, function() {
                                $(this).remove();
                                updateOrderCounts();
                            });
                            showNotification('success', response.message);
                        } else {
                            handleDispatchError({
                                responseJSON: response
                            });
                        }
                    },
                    error: handleDispatchError,
                    complete: function() {
                        button.prop('disabled', false);
                    }
                });
            });

            // Xử lý chọn tất cả đơn hàng
            $('.select-all-orders').on('change', function() {
                var form = $(this).closest('form');
                form.find('input[name="selected_orders[]"]').prop('checked', this.checked);
                updateDispatchButtonVisibility(form);
            });

            // Các hàm tiện ích
            function validateDispatchForm(form) {
                var isValid = true;
                var messages = [];

                // Check đơn hàng được chọn
                if (form.find('input[name="selected_orders[]"]:checked').length === 0) {
                    messages.push('Vui lòng chọn ít nhất một đơn hàng');
                    isValid = false;
                }

                // Check địa điểm đích
                var locationSelect = form.find('.target-location');
                if (!locationSelect.val()) {
                    messages.push('Vui lòng chọn bưu cục đích');
                    isValid = false;
                }

                // Check nhân viên phân phối
                var staffId = form.find('select[name="local_distribution_staff_id"]').val() ||
                    form.find('select[name="general_distribution_staff_id"]').val();
                if (!staffId) {
                    messages.push('Vui lòng chọn nhân viên phân phối');
                    isValid = false;
                }

                if (!isValid) {
                    showNotification('error', messages.join('\n'));
                    console.error('Form validation failed:', messages);
                }

                return isValid;
            }

            function updateDispatchButtonVisibility(form) {
                var hasSelectedOrders = form.find('input[name="selected_orders[]"]:checked').length > 0;
                var hasSelectedLocation = form.find('.target-location').val() !== '';
                var hasSelectedStaff = form.find('.staff-selection select').val() !== '';

                if (hasSelectedOrders && hasSelectedLocation && hasSelectedStaff) {
                    form.find('.dispatch-btn').show();
                } else {
                    form.find('.dispatch-btn').hide();
                }
            }

            function handleDispatchResponse(response) {
                if (response.success) {
                    showNotification('success', response.message);
                    if (response.failed_orders && response.failed_orders.length > 0) {
                        var failMessage = 'Các đơn hàng sau không thể điều phối:\n';
                        response.failed_orders.forEach(function(order) {
                            failMessage += `- ${order.tracking_number}: ${order.reason}\n`;
                        });
                        showNotification('warning', failMessage);
                    }
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    showNotification('error', response.error || 'Có lỗi xảy ra');
                }
            }

            function handleDispatchError(xhr) {
                var errorMessage = 'Có lỗi xảy ra khi điều phối đơn hàng';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                showNotification('error', errorMessage);
            }

            function showNotification(type, message) {
                var alertClass = type === 'success' ? 'alert-success' :
                    type === 'error' ? 'alert-danger' :
                    type === 'warning' ? 'alert-warning' : 'alert-info';

                var notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <pre style="margin: 0">${message}</pre>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `).appendTo('#realtime-notifications');

                // Tự động xóa thông báo sau 5 giây
                setTimeout(function() {
                    notification.fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }

            function updateOrderCounts() {
                $('#orderTabs .nav-link').each(function() {
                    var tabId = $(this).attr('href');
                    var count = $(tabId).find('tbody tr').length;
                    $(this).text(function(_, text) {
                        return text.replace(/\(\d+\)/, `(${count})`);
                    });
                });
            }
            $('#orderTabs a[data-toggle="tab"]').on('click', function(e) {
                e.preventDefault();
                $(this).tab('show');
            });

            // Xử lý khi tab được hiển thị
            $('#orderTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var targetTabId = $(e.target).attr('href');
                $(targetTabId).find('.table').trigger('resize'); // Nếu có bảng cần refresh
                updateOrderCounts(); // Cập nhật số lượng đơn hàng
            });
            // Khởi tạo
            $('[data-toggle="tooltip"]').tooltip();

            $('.bulk-dispatch-form, .dispatch-local-form').each(function() {
                updateDispatchButtonVisibility($(this));
            });

            // Xử lý khi chuyển tab
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                updateOrderCounts();
            });

            // Xử lý khi form được reset
            $('form').on('reset', function() {
                var form = $(this);
                setTimeout(function() {
                    form.find('.staff-selection').hide();
                    form.find('.dispatch-btn').hide();
                }, 0);
            });
        });
    </script>
    @endpush