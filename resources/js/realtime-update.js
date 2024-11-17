// public/js/realtime-updates.js

class RealtimeUpdates {
    constructor() {
        this.notifications = document.getElementById('realtime-notifications');
        this.staffTable = document.getElementById('staff-table');
        this.ordersTable = document.getElementById('orders-table');
        this.shippersData = this.getShippersData();
        this.initializeEcho();
    }

    getShippersData() {
        const shippersDataElement = document.getElementById('shippers-data');
        if (shippersDataElement) {
            try {
                return JSON.parse(shippersDataElement.textContent);
            } catch (error) {
                console.error('Error parsing shippers data:', error);
                return [];
            }
        }
        return [];
    }

    initializeEcho() {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('staff-updates')
                .listen('StaffUpdated', (e) => {
                    this.handleStaffUpdate(e);
                });
    
            window.Echo.channel('orders')
                .listen('.OrderCreated', (e) => {
                    this.handleNewOrder(e.order);
                })
                .listen('.OrderUpdated', (e) => {
                    this.handleOrderUpdate(e.order);
                })
                .listen('.OrderDeleted', (e) => {
                    this.handleOrderDelete(e.orderId);
                });
        } else {
            console.error('Echo is not defined. Make sure Laravel Echo is properly initialized.');
        }
    }

    showConfirmationDialog(message) {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = 'modal fade show';
            dialog.style.display = 'block';
            dialog.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Thông báo cập nhật</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                            <button type="button" class="btn btn-primary" id="confirmRefresh">Xác nhận</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(dialog);

            const confirmButton = dialog.querySelector('#confirmRefresh');
            const closeButtons = dialog.querySelectorAll('[data-dismiss="modal"]');

            confirmButton.addEventListener('click', () => {
                document.body.removeChild(dialog);
                resolve(true);
            });

            closeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    document.body.removeChild(dialog);
                    resolve(false);
                });
            });
        });
    }

    handleStaffUpdate(event) {
        console.log('Staff Update:', event);
        
        let message = event.message;
        if (event.action === 'post_office_assigned') {
            message = `Bạn đã được cập nhật thành nhân viên bưu cục của bưu cục ${event.postOfficeName}`;
        }
        
        if (event.action === 'post_office_assigned' || event.action === 'removed_from_post_office') {
            this.showConfirmationDialog(message).then((confirmed) => {
                if (confirmed) {
                    window.location.reload();
                }
            });
        } else {
            this.showNotification(message);
            if (this.staffTable) {
                const userRow = this.staffTable.querySelector(`tr[data-user-id="${event.userId}"]`);
                if (userRow) {
                    const roleCell = userRow.querySelector('td:nth-child(3)');
                    if (event.action === 'role_assigned' && roleCell) {
                        const selectElement = roleCell.querySelector('select');
                        if (selectElement) {
                            selectElement.value = event.newRole;
                        }
                    }
                }
            }
        }
    }

    handleNewOrder(order) {
        console.log('New Order:', order);
        this.showNotification(`Đơn hàng mới ${order.tracking_number} đã được tạo`);

        if (this.ordersTable) {
            this.addNewOrderRow(order);
        }
    }

    handleOrderDelete(orderId) {
        console.log('Order Deleted:', orderId);
        this.showNotification(`Đơn hàng đã bị xóa`);

        if (this.ordersTable) {
            const orderRow = this.ordersTable.querySelector(`tr[data-order-id="${orderId}"]`);
            if (orderRow) {
                orderRow.remove();
            }
        }
    }

    addNewOrderRow(order) {
        if (!this.ordersTable) {
            console.warn('Orders table not found');
            return;
        }

        const tbody = this.ordersTable.querySelector('tbody');
        if (!tbody) {
            console.warn('Table body not found');
            return;
        }

        const newRow = document.createElement('tr');
        newRow.dataset.orderId = order.id;
        newRow.innerHTML = this.createOrderRowHTML(order);
        tbody.insertBefore(newRow, tbody.firstChild);
        this.highlightRow(newRow);
    }

    updateOrderRow(row, order) {
        if (row) {
            row.innerHTML = this.createOrderRowHTML(order);
            this.highlightRow(row);
        }
    }

    createOrderRowHTML(order) {
        const shipperOptions = this.shippersData.map(shipper => 
            `<option value="${shipper.id}" ${order.shipper_id == shipper.id ? 'selected' : ''}>${shipper.name} (Điểm: ${shipper.attendance_score + shipper.vote_score})</option>`
        ).join('');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        return `
            <td>${order.tracking_number || ''}</td>
            <td>${order.sender_name || ''}</td>
            <td>${order.receiver_name || ''}</td>
            <td>${order.status || ''}</td>
            <td>${order.created_at ? new Date(order.created_at).toLocaleString('vi-VN') : ''}</td>
            <td class="assigned-shipper">${order.shipper_name || 'Chưa gán'}</td>
            <td>
                <form action="/post_office/orders/${order.id}/assign_shipper" method="POST">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <select name="shipper_id" class="form-control form-control-sm" required>
                        <option value="">Chọn shipper</option>
                        ${shipperOptions}
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm mt-1">Gán shipper</button>
                </form>
            </td>
        `;
    }

    highlightRow(row) {
        if (row) {
            row.style.animation = 'none';
            row.offsetHeight; // Trigger reflow
            row.style.animation = 'highlightRow 2s';
        }
    }

    handleOrderUpdate(order) {
        console.log('Order Update:', order);
        this.showNotification(`Đơn hàng ${order.tracking_number} đã được cập nhật`);

        if (this.ordersTable) {
            const orderRow = this.ordersTable.querySelector(`tr[data-order-id="${order.id}"]`);
            if (orderRow) {
                this.updateOrderRow(orderRow, order);
            } else {
                this.addNewOrderRow(order);
            }
        }
    }

    showNotification(message) {
        if (!this.notifications) {
            console.warn('Notifications container not found');
            return;
        }

        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show';
        notification.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        this.notifications.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Khởi tạo class khi document đã sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
    new RealtimeUpdates();
});