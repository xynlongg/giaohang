<script>
$(document).ready(function() {
    // Các element chính
    const localTab = {
        selectAll: $('.select-all[data-type="local"]'),
        checkboxes: $('.order-checkbox[data-type="local"]'),
        distributorSelect: $('#localDistributorSelect'),
        assignBtn: $('#assignLocalBtn'),
        countDisplay: $('#selectedLocalCount')
    };

    const nonLocalTab = {
        selectAll: $('.select-all[data-type="non-local"]'),
        checkboxes: $('.order-checkbox[data-type="non-local"]'),
        distributorSelect: $('#nonLocalDistributorSelect'),
        assignBtn: $('#assignNonLocalBtn'),
        countDisplay: $('#selectedNonLocalCount')
    };

    // Xử lý chọn tất cả
    function handleSelectAll(tab) {
        tab.selectAll.change(function() {
            tab.checkboxes.prop('checked', this.checked);
            updateUI(tab);
        });
    }

    // Xử lý chọn từng đơn
    function handleIndividualSelect(tab) {
        tab.checkboxes.change(function() {
            updateUI(tab);
        });
    }

    // Xử lý thay đổi nhân viên
    function handleDistributorSelect(tab) {
        tab.distributorSelect.change(function() {
            tab.assignBtn.prop('disabled', !this.value || !tab.checkboxes.filter(':checked').length);
        });
    }

    // Cập nhật UI
    // Cập nhật UI
    function updateUI(tab) {
        const checkedBoxes = tab.checkboxes.filter(':checked');
        const checkedCount = checkedBoxes.length;
        const totalBoxes = tab.checkboxes.length;

        // Cập nhật số lượng đã chọn
        tab.countDisplay.text(checkedCount);
        
        // Cập nhật trạng thái nút gán và select
        tab.distributorSelect.prop('disabled', checkedCount === 0);
        tab.assignBtn.prop('disabled', checkedCount === 0 || !tab.distributorSelect.val());

        // Cập nhật trạng thái checkbox chọn tất cả
        tab.selectAll.prop('checked', checkedCount === totalBoxes && checkedCount > 0);
        tab.selectAll.prop('indeterminate', checkedCount > 0 && checkedCount < totalBoxes); // Sửa syntax error ở đây
    }

    // Xử lý gán đơn hàng
    async function assignOrders(type, distributorId, orderIds) {
        try {
            // Hiển thị loading
            Swal.fire({
                title: 'Đang xử lý...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Gửi request
            const response = await fetch('{{ route("warehouse.orders.assign_distributor") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    distributor_id: distributorId,
                    order_ids: orderIds,
                    distribution_type: type
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }

            // Thông báo thành công
            await Swal.fire({
                title: 'Thành công',
                text: data.message,
                icon: 'success'
            });

            // Tải lại trang
            window.location.reload();

        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Lỗi',
                text: error.message,
                icon: 'error'
            });
        }
    }

    // Xử lý click nút gán
    function handleAssignButton(tab, type) {
        tab.assignBtn.click(async function() {
            const checkedOrders = tab.checkboxes.filter(':checked');
            const distributorId = tab.distributorSelect.val();

            if (!checkedOrders.length || !distributorId) {
                Swal.fire({
                    title: 'Lỗi',
                    text: 'Vui lòng chọn đơn hàng và nhân viên phân phối',
                    icon: 'error'
                });
                return;
            }

            const result = await Swal.fire({
                title: 'Xác nhận gán đơn hàng',
                text: `Bạn có chắc chắn muốn gán ${checkedOrders.length} đơn hàng cho nhân viên này?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Gán đơn',
                cancelButtonText: 'Hủy'
            });

            if (result.isConfirmed) {
                const orderIds = checkedOrders.map(function() {
                    return $(this).val();
                }).get();

                await assignOrders(type, distributorId, orderIds);
            }
        });
    }

    // Load danh sách đã gán
    async function loadAssignedOrders() {
    try {
        const response = await fetch('/warehouse/orders/assigned', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Không thể tải dữ liệu');
        }

        const tbody = $('#assignedOrdersTable tbody');
        
        if (!data.data.length) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <p class="mb-0">Chưa có đơn hàng nào được gán</p>
                    </td>
                </tr>
            `);
            return;
        }

        const rows = data.data.map(order => `
            <tr>
                <td>
                    <div class="font-semibold">#${order.tracking_number}</div>
                    <small class="text-gray-600">
                        KL: ${order.total_weight}kg | 
                        COD: ${formatCurrency(order.total_cod)}
                    </small>
                </td>
                <td>
                    <div>${order.receiver_name}</div>
                    <div class="text-sm text-gray-600">${order.receiver_phone}</div>
                    <div class="text-sm">${order.receiver_address}</div>
                    <div class="text-sm text-gray-600">${order.receiver_district}, ${order.receiver_province}</div>
                </td>
                <td>
                    <div>${order.distributor_name}</div>
                    <small class="text-gray-600">${order.distributor_phone}</small>
                </td>
                <td>
                    <span class="badge ${order.shipping_type === 'noi_thanh' ? 'bg-success' : 'bg-info'}">
                        ${order.shipping_type === 'noi_thanh' ? 'Nội thành' : 'Ngoại thành'}
                    </span>
                </td>
                <td>
                    ${order.shipping_type === 'noi_thanh' ? 
                        `<div>Bưu cục: ${order.pickup_location_id || 'Chưa có'}</div>` :
                        `<div>Kho: ${order.current_location || 'Chưa có'}</div>`
                    }
                </td>
                <td>${formatDateTime(order.assigned_at)}</td>
                <td>
                    <span class="badge ${getStatusBadgeClass(order.status)}">
                        ${getStatusText(order.status)}
                    </span>
                </td>
                <td>
                    <button type="button" 
                        class="btn btn-link p-0 text-primary"
                        onclick="viewOrderDetails(${order.id})">
                        Chi tiết
                    </button>
                </td>
            </tr>
        `).join('');

        tbody.html(rows);

    } catch (error) {
        console.error('Error:', error);
        $('#assignedOrdersTable tbody').html(`
            <tr>
                <td colspan="8" class="text-center text-red-600 py-4">
                    ${error.message}
                </td>
            </tr>
        `);
    }
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusBadgeClass(status) {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function getStatusText(status) {
    const texts = {
        'pending': 'Chờ xử lý',
        'in_progress': 'Đang giao',
        'completed': 'Hoàn thành',
        'failed': 'Thất bại'
    };
    return texts[status] || status;
}
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }
    // Xử lý tab xác nhận đơn đến
const confirmTab = {
    selectAll: $('.select-all[data-type="confirm"]'),
    checkboxes: $('.order-checkbox[data-type="confirm"]'),
    confirmBtn: $('#confirmSelectedBtn'),
    countDisplay: $('#selectedCount')
};

// Cập nhật UI cho tab xác nhận
function updateConfirmUI() {
    const checkedBoxes = confirmTab.checkboxes.filter(':checked');
    const checkedCount = checkedBoxes.length;
    const totalBoxes = confirmTab.checkboxes.length;

    confirmTab.countDisplay.text(checkedCount);
    confirmTab.confirmBtn.prop('disabled', checkedCount === 0);
    
    confirmTab.selectAll.prop('checked', checkedCount === totalBoxes && checkedCount > 0);
    confirmTab.selectAll.prop('indeterminate', checkedCount > 0 && checkedCount < totalBoxes);
}

// Xử lý chọn tất cả
confirmTab.selectAll.change(function() {
    confirmTab.checkboxes.prop('checked', this.checked);
    updateConfirmUI();
});

// Xử lý chọn từng đơn
confirmTab.checkboxes.change(function() {
    updateConfirmUI();
});

// Xử lý xác nhận hàng loạt
confirmTab.confirmBtn.click(async function() {
    const checkedOrders = confirmTab.checkboxes.filter(':checked');
    
    if (!checkedOrders.length) return;

    try {
        const result = await Swal.fire({
            title: 'Xác nhận đã nhận đơn hàng',
            text: `Bạn có chắc chắn xác nhận đã nhận ${checkedOrders.length} đơn hàng này?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy'
        });

        if (!result.isConfirmed) return;

        const orderIds = checkedOrders.map(function() {
            return $(this).val();
        }).get();

        // Gửi request xác nhận
        const response = await fetch('{{ route("warehouse.orders.confirm_bulk_arrival") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                order_ids: orderIds
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Có lỗi xảy ra');
        }

        await Swal.fire({
            title: 'Thành công', 
            text: data.message,
            icon: 'success'
        });

        window.location.reload();

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Lỗi',
            text: error.message,
            icon: 'error' 
        });
    }
});

// Xử lý xác nhận đơn lẻ
$('.confirm-single').click(async function() {
    const orderId = $(this).data('id');
    
    try {
        const result = await Swal.fire({
            title: 'Xác nhận đã nhận đơn hàng',
            text: 'Bạn có chắc chắn xác nhận đã nhận đơn hàng này?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy'
        });

        if (!result.isConfirmed) return;

        const response = await fetch(`{{ url('warehouse/orders') }}/${orderId}/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Có lỗi xảy ra');
        }

        await Swal.fire({
            title: 'Thành công',
            text: data.message,
            icon: 'success'
        });

        window.location.reload();

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Lỗi',
            text: error.message,
            icon: 'error'
        });
    }
});

    // Khởi tạo
    handleSelectAll(localTab);
    handleSelectAll(nonLocalTab);
    handleIndividualSelect(localTab);
    handleIndividualSelect(nonLocalTab);
    handleDistributorSelect(localTab);
    handleDistributorSelect(nonLocalTab);
    handleAssignButton(localTab, 'local');
    handleAssignButton(nonLocalTab, 'non-local');

    // Load danh sách đã gán khi mở modal
    $('#assignedOrdersModal').on('show.bs.modal', loadAssignedOrders);

    // Khởi tạo tooltips
    $('[title]').tooltip();
});

</script>