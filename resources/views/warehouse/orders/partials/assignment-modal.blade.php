<div class="modal fade" id="assignedOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-xl"> <!-- Tăng kích thước modal -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Danh sách đơn hàng đã gán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="assignedOrdersTable">
                        <thead>
                            <tr>
                                <th>Mã đơn hàng</th>
                                <th>Thông tin người nhận</th>
                                <th>Nhân viên phân phối</th>
                                <th>Loại phân phối</th>
                                <th>Điểm đích</th>
                                <th>Thời gian gán</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Data will be loaded by AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>