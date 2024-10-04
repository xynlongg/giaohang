<?php

namespace App\Policies;

use App\Models\OrderCancellationRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderCancellationRequestPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderCancellationRequest $cancellationRequest)
    {
        // Kiểm tra xem người dùng có thuộc bưu cục của yêu cầu hủy đơn không
        return $user->postOffices->contains($cancellationRequest->postOffice);
    }

    public function process(User $user, OrderCancellationRequest $cancellationRequest)
    {
        // Chỉ cho phép quản lý bưu cục xử lý yêu cầu hủy đơn
        return $user->hasRole('post_office_manager') && $user->postOffices->contains($cancellationRequest->postOffice);
    }
}