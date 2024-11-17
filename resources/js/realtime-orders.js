import Echo from 'laravel-echo';
import io from 'socket.io-client';

window.io = io;

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded');
    initializeEcho();
});

function initializeEcho() {
    window.Echo = new Echo({
        broadcaster: 'socket.io',
        host: `${window.location.protocol}//${window.location.hostname}:6001`,
        transports: ['websocket', 'polling']
    });

    console.log('Echo initialized with host:', `${window.location.protocol}//${window.location.hostname}:6001`);

    window.Echo.connector.socket.on('connect_error', (error) => {
        console.error('Connection error:', error);
    });
    
    window.Echo.connector.socket.on('connect_timeout', (timeout) => {
        console.error('Connection timeout:', timeout);
    });

    window.Echo.connector.socket.on('connect', () => {
        console.log('Connected to Echo server');
    });

    window.Echo.connector.socket.on('disconnect', () => {
        console.log('Disconnected from Echo server');
    });

    window.Echo.connector.socket.on('error', (error) => {
        console.error('Echo connection error:', error);
    });

    console.log('Subscribing to orders channel');

    window.Echo.channel('orders')
    .listen('.OrderCreated', (e) => {
        console.log('OrderCreated event received:', e);
        addNewOrderToTable(e.order);
    })
    .listen('.import-orders', (e) => {
        console.log('ImportOrderCreated event received:', e);
        addNewOrderToTable(e.order);
    })
    .listen('.order-updated', (e) => {
        console.log('OrderUpdated event received:', e);
        updateOrderInTable(e.order);
    })
    .listen('.order-deleted', (e) => {
        console.log('OrderDeleted event received:', e);
        handleOrderDeleted(e.orderId);
    });
    document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded');
    initializeEcho();
});

function initializeEcho() {
    window.Echo = new Echo({
        broadcaster: 'socket.io',
        host: `${window.location.protocol}//${window.location.hostname}:6001`,
    });

    window.Echo.channel('orders')
        .listen('.new-order', (e) => {
            console.log('New order received:', e.order);
            addNewOrderToTable(e.order);
        });
}
}

function addNewOrderToTable(order) {
    const tableBody = document.querySelector('#orders-table-body');
    if (tableBody) {
        const newRow = createOrderRow(order);
        tableBody.insertAdjacentHTML('afterbegin', newRow);
        highlightRow(tableBody.firstElementChild);
        showNewOrderNotification();
    } else {
        console.error('Table body not found');
    }
}

function createOrderRow(order) {
    return `
    <tr data-order-id="${order.id}">
        <td>${order.tracking_number}</td>
        <td>${order.sender_name}</td>
        <td>${order.receiver_name}</td>
        <td>${order.receiver_address}</td>
        <td>${order.current_location ? order.current_location.address : ''}</td>
        <td>${order.receiver_phone}</td>
        <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(order.total_amount)}</td>
        <td>${order.is_pickup_at_post_office ? 'Có' : 'Không'}</td>
        <td>${new Date(order.pickup_date).toLocaleString('vi-VN')}</td>
        <td>${order.delivery_date ? new Date(order.delivery_date).toLocaleString('vi-VN') : 'N/A'}</td>
        <td>
            <span class="badge bg-${getStatusClass(order.status)}">
                ${capitalizeFirstLetter(order.status)}
            </span>
        </td>
        <td>
            <a href="/orders/${order.id}" class="btn btn-sm btn-info">Xem</a>
            <a href="/orders/${order.id}/edit" class="btn btn-sm btn-primary">Sửa</a>
            <a href="/orders/${order.id}/update" class="btn btn-sm btn-warning">Cập nhật</a>
            <form action="/orders/${order.id}" method="POST" style="display: inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">Xóa</button>
            </form>
        </td>
    </tr>
    `;
}
function updateOrderInTable(order) {
    const existingRow = document.querySelector(`tr[data-order-id="${order.id}"]`);
    if (existingRow) {
        const updatedRow = createOrderRow(order);
        existingRow.outerHTML = updatedRow;
        highlightRow(document.querySelector(`tr[data-order-id="${order.id}"]`));
        showUpdateOrderNotification();
    } else {
        console.error('Order row not found for update');
    }
}
function showDeleteOrderNotification() {
    const notification = document.getElementById('delete-order-notification');
    if (notification) {
        notification.textContent = 'Một đơn hàng đã bị xóa.';
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }
}
function removeOrderFromTable(orderId) {
    const rowToRemove = document.querySelector(`tr[data-order-id="${orderId}"]`);
    if (rowToRemove) {
        rowToRemove.remove();
        showDeleteOrderNotification();
    } else {
        console.error('Order row not found for deletion');
    }
}
function handleOrderDeleted(orderId) {
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    if (row) {
        row.remove();
        Swal.fire({
            title: 'Đơn hàng đã bị xóa',
            text: 'Một đơn hàng vừa bị xóa khỏi hệ thống.',
            icon: 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
}


function getStatusClass(status) {
    switch (status) {
        case 'pending': return 'warning';
        case 'shipping': return 'info';
        case 'completed': return 'success';
        default: return 'danger';
    }
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function highlightRow(row) {
    row.style.animation = 'none';
    row.offsetHeight; // Trigger reflow
    row.style.animation = 'highlightRow 2s';
}

function showNewOrderNotification() {
    const notification = document.getElementById('new-order-notification');
    if (notification) {
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000); // Hide notification after 5 seconds
    }
}