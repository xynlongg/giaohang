@extends('layouts.app')

@section('content')
<div class="container">
    <div class="completed-orders-header mb-4">
        <h2 class="text-primary fw-bold">Đơn Hàng Đã Hoàn Thành</h2>
        <p class="text-muted">Đánh giá trải nghiệm giao hàng của bạn</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        @forelse ($completedOrders as $order)
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <div class="order-info">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="order-status-badge me-3">
                                            <span class="badge bg-success rounded-pill">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Đã hoàn thành
                                            </span>
                                        </div>
                                        <h5 class="mb-0 tracking-number">
                                            #{{ $order->tracking_number }}
                                        </h5>
                                    </div>
                                    <div class="order-details">
                                        <div class="info-item mb-2">
                                            <i class="fas fa-user text-primary me-2"></i>
                                            <strong>Người nhận:</strong> 
                                            <span>{{ $order->receiver_name }}</span>
                                        </div>
                                        <div class="info-item mb-2">
                                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                            <strong>Địa chỉ:</strong>
                                            <span>{{ $order->receiver_address }}</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-check text-success me-2"></i>
                                            <strong>Hoàn thành:</strong>
                                            <span>{{ $order->updated_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                @if($order->distributions->isNotEmpty() && $order->distributions->first()->shipper)
                                    <div class="shipper-rating p-3">
                                        <div class="shipper-info mb-3">
                                            <div class="d-flex align-items-center">
                                            <div class="shipper-avatar me-3">
                                                    <div class="avatar-container">
                                                        <img src="{{ $order->distributions->first()->shipper->avatar 
                                                            ? asset('storage/' . $order->distributions->first()->shipper->avatar) 
                                                            : asset('images/default-avatar.png') }}" 
                                                            class="avatar-image"
                                                            alt="Shipper avatar">
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        {{ $order->distributions->first()->shipper->name }}
                                                    </h6>
                                                    <small class="text-muted">Shipper</small>
                                                </div>
                                            </div>
                                        </div>

                                        @if(!$order->shipperRating)
                                            <div class="rating-section" id="rating-{{ $order->id }}">
                                                <h6 class="rating-title mb-3">Đánh giá dịch vụ giao hàng</h6>
                                                <div class="star-rating mb-3">
                                                    <div class="stars">
                                                        @for($i = 5; $i >= 1; $i--)
                                                            <input type="radio" 
                                                                name="rating-{{ $order->id }}" 
                                                                value="{{ $i }}" 
                                                                id="star{{ $i }}-{{ $order->id }}"
                                                                class="star-input">
                                                            <label for="star{{ $i }}-{{ $order->id }}" 
                                                                class="star-label">
                                                                <i class="fas fa-star"></i>
                                                            </label>
                                                        @endfor
                                                    </div>
                                                </div>
                                                <div class="comment-section">
                                                    <textarea class="form-control mb-3" 
                                                        id="comment-{{ $order->id }}"
                                                        placeholder="Chia sẻ trải nghiệm của bạn về dịch vụ giao hàng..." 
                                                        rows="2"></textarea>
                                                    <button class="btn btn-primary w-100" 
                                                        onclick="submitRating({{ $order->id }})">
                                                        <i class="fas fa-paper-plane me-2"></i>
                                                        Gửi đánh giá
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="rating-display">
                                                <h6 class="rating-title mb-3">Đánh giá của bạn</h6>
                                                <div class="stars-display mb-2">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="fas fa-star {{ $i <= $order->shipperRating->rating ? 'text-warning' : 'text-muted' }}"></i>
                                                    @endfor
                                                    <span class="ms-2 text-muted">
                                                        {{ $order->shipperRating->created_at->format('d/m/Y H:i') }}
                                                    </span>
                                                </div>
                                                @if($order->shipperRating->comment)
                                                    <div class="comment-display p-3 bg-light rounded">
                                                        <i class="fas fa-quote-left text-muted me-2"></i>
                                                        {{ $order->shipperRating->comment }}
                                                        <i class="fas fa-quote-right text-muted ms-2"></i>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-box-open fa-3x mb-3"></i>
                    <h5>Chưa có đơn hàng nào hoàn thành</h5>
                    <p class="mb-0">Các đơn hàng đã hoàn thành sẽ xuất hiện tại đây</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $completedOrders->links() }}
    </div>
</div>

@push('styles')
<style>
.completed-orders-header {
    text-align: center;
    padding: 2rem 0;
}

.order-info {
    position: relative;
}

.tracking-number {
    color: #2c3e50;
    font-weight: 600;
}

.info-item {
    color: #555;
    font-size: 0.95rem;
}

.shipper-rating {
    background-color: #f8f9fa;
    border-radius: 1rem;
}

.star-rating {
    display: flex;
    justify-content: center;
}

.stars {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
}

.star-input {
    display: none;
}

.star-label {
    cursor: pointer;
    padding: 0 0.2rem;
    font-size: 1.8rem;
    color: #ddd;
    transition: all 0.2s ease;
}

.star-label:hover,
.star-label:hover ~ .star-label,
.star-input:checked ~ .star-label {
    color: #ffc107;
}

.stars-display .fa-star {
    font-size: 1.2rem;
    margin: 0 0.1rem;
}

.comment-display {
    font-style: italic;
    color: #666;
}

/* Hover effects */
.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

/* Button styles */
.btn-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #0056b3, #003980);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.2);
}

/* Badge styles */
.badge {
    padding: 0.5rem 1rem;
    font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-md-6.border-end {
        border: none !important;
        margin-bottom: 2rem;
    }
}
.shipper-avatar {
    position: relative;
}

.avatar-container {
    width: 50px;
    height: 50px;
    position: relative;
    overflow: hidden;
    border-radius: 50%;
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.avatar-image {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Giữ tỉ lệ và fill container */
    object-position: center; /* Căn giữa ảnh */
    position: absolute;
    top: 0;
    left: 0;
}

/* Optional: Thêm border khi hover */
.avatar-container:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Optional: Thêm transition cho smooth hover effect */
.avatar-container, .avatar-image {
    transition: all 0.2s ease-in-out;
}
</style>
@endpush

@push('scripts')
<script>
function submitRating(orderId) {
    const rating = document.querySelector(`input[name="rating-${orderId}"]:checked`)?.value;
    const comment = document.querySelector(`#comment-${orderId}`).value;
    
    if (!rating) {
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Vui lòng chọn số sao đánh giá!'
        });
        return;
    }

    // Show loading
    Swal.fire({
        title: 'Đang xử lý...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/orders/${orderId}/rate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ rating, comment })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: 'Cảm ơn bạn đã đánh giá!',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: error.message || 'Có lỗi xảy ra khi gửi đánh giá'
        });
    });
}

// Thêm hiệu ứng hover cho sao
document.querySelectorAll('.star-label').forEach(label => {
    label.addEventListener('mouseover', () => {
        const stars = label.parentElement.children;
        const selectedValue = label.previousElementSibling.value;
        
        for (let star of stars) {
            if (star.tagName === 'LABEL') {
                const starValue = star.previousElementSibling.value;
                star.style.transform = starValue <= selectedValue ? 'scale(1.2)' : 'scale(1)';
            }
        }
    });

    label.addEventListener('mouseout', () => {
        const stars = label.parentElement.children;
        for (let star of stars) {
            if (star.tagName === 'LABEL') {
                star.style.transform = 'scale(1)';
            }
        }
    });
});
</script>
@endpush
@endsection