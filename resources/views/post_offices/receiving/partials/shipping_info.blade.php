{{-- resources/views/post_offices/receiving/partials/shipping_info.blade.php --}}
<p class="mb-2">
    <i class="bi bi-truck me-2"></i>
    <strong>Loại vận chuyển:</strong>
    <span class="badge rounded-pill 
    @if($item->shipping_type === 'cung_quan') 
        bg-success
    @elseif($item->shipping_type === 'noi_thanh')
        bg-primary  
    @else
        bg-warning
    @endif">
        {{ $item->shipping_type === 'cung_quan' ? 'Cùng quận' : 
           ($item->shipping_type === 'noi_thanh' ? 'Nội thành' : 'Ngoại thành') }}
    </span>
</p>
<p class="mb-2">
    <i class="bi bi-arrows-move me-2"></i>
    <strong>Khoảng cách:</strong> {{ number_format($item->distance ?? 0, 1) }} km
</p>
<p class="mb-2">
    <i class="bi bi-box-seam me-2"></i>
    <strong>Khối lượng:</strong> {{ number_format($item->weight ?? 0, 2) }} kg
</p>
<p class="mb-0">
    <i class="bi bi-currency-dollar me-2"></i>
    <strong>COD:</strong> {{ number_format($item->cod_amount ?? 0) }} VNĐ
</p>