{{-- Modal Danh sách đã gán --}}
<div class="modal fade" id="assignedOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Danh sách đơn hàng đã gán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="assignedOrdersTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Mã đơn hàng</th>  
                                <th>Shipper</th>
                                <th>Người phân công</th>
                                <th>Trạng thái</th>
                                <th>Thời gian gán</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Đang tải...</span>
                                    </div>
                                </td>
                            </tr>
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