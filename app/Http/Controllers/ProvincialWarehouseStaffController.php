<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProvincialWarehouse;
use App\Models\WarehouseUser;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Events\WarehouseStaffUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ProvincialWarehouseStaffController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $users = User::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        })->paginate(10);

        $roles = Role::where('name', 'warehouse_staff')
            ->orWhere('name', 'warehouse_manager')
            ->get();
        $provincialWarehouses = ProvincialWarehouse::all();

        return view(
            'provincial-warehouses.staff.index',
            compact('users', 'roles', 'provincialWarehouses')
        );
    }


    public function assignRole(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'role' => 'required|in:warehouse_staff,warehouse_manager'
        ]);

        $user->syncRoles([$validatedData['role']]);

        event(new WarehouseStaffUpdated(
            $user->id,
            "Vai trò của {$user->name} đã được cập nhật thành {$validatedData['role']}"
        ));

        return back()->with('success', 'Vai trò đã được cập nhật.');
    }

    public function searchStaff(Request $request)
    {
        try {
            Log::info('Searching staff with query: ' . $request->input('q'));
    
            $search = $request->input('q');
    
            // Lấy users và role của họ
            $users = User::with('roles')
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->select('id', 'name', 'email')
                ->limit(10)
                ->get();
    
            $formattedUsers = $users->map(function($user) {
                $roles = $user->roles->pluck('name')->toArray();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roles
                ];
            });
    
            Log::info('Found users:', [
                'count' => $users->count(),
                'users' => $formattedUsers->toArray()
            ]);
    
            return response()->json($formattedUsers);
    
        } catch (\Exception $e) {
            Log::error('Error in searchStaff: ' . $e->getMessage());
            return response()->json(['error' => 'Error searching users'], 500);
        }
    }

    public function assignWarehouse(Request $request, User $user)
    {
        try {
            $validatedData = $request->validate([
                'provincial_warehouse_id' => 'required|exists:provincial_warehouses,id',
                'role' => ['required', 'in:warehouse_staff,warehouse_manager,warehouse_local_distributor,warehouse_remote_distributor']
            ]);

            DB::beginTransaction();

            // Tìm role 
            $role = Role::where('name', $validatedData['role'])->first();
            if (!$role) {
                throw new \Exception('Không tìm thấy vai trò');
            }

            // Gán role cho user
            $user->roles()->sync([$role->id]);

            // Tạo staff code dựa trên role
            $staffCode = $this->generateStaffCode($validatedData['role']);

            // Lưu vào bảng warehouse_users
            $warehouseUser = new WarehouseUser([
                'user_id' => $user->id,
                'warehouse_id' => $validatedData['provincial_warehouse_id'],
                'staff_code' => $staffCode,
                'start_date' => now(),
                'is_manager' => $validatedData['role'] === 'warehouse_manager',
                'is_active' => true
            ]);

            $warehouseUser->save();

            DB::commit();

            return back()->with('success', 'Đã phân công nhân viên vào kho tổng thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi phân công vào kho: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi phân công nhân viên.');
        }
    }
    private function generateStaffCode($role)
    {
        $prefix = match ($role) {
            'warehouse_manager' => 'WM',
            'warehouse_staff' => 'WS',
            'warehouse_local_distributor' => 'WLD',
            'warehouse_remote_distributor' => 'WRD',
            default => 'WS'
        };

        $timestamp = now()->format('ymdHis');
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

        return $prefix . $timestamp . $random;
    }

    public function removeFromWarehouse(User $user)
    {
        try {
            DB::beginTransaction();

            // Xóa khỏi warehouse_users
            WarehouseUser::where('user_id', $user->id)->delete();

            // Xóa role warehouse
            $warehouseRoles = Role::whereIn('name', [
                'warehouse_staff',
                'warehouse_manager',
                'warehouse_local_distributor',
                'warehouse_remote_distributor'
            ])->pluck('id');

            $user->roles()->detach($warehouseRoles);

            DB::commit();
            return back()->with('success', 'Đã xóa nhân viên khỏi kho tổng.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa khỏi kho: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xóa nhân viên.');
        }
    }
    private function getRoleBadgeColor($role)
    {
        return match ($role) {
            'warehouse_manager' => 'primary',
            'warehouse_staff' => 'info',
            'warehouse_local_distributor' => 'success',
            'warehouse_remote_distributor' => 'warning',
            default => 'secondary'
        };
    }

    private function getRoleDisplayName($role)
    {
        return match ($role) {
            'warehouse_manager' => 'Quản lý kho',
            'warehouse_staff' => 'Nhân viên kho',
            'warehouse_local_distributor' => 'NV phân phối nội thành',
            'warehouse_remote_distributor' => 'NV phân phối ngoại thành',
            default => 'Chưa phân quyền'
        };
    }
  

    public function searchAvailableUsers(Request $request)
    {
        $search = $request->input('q');

        $users = User::where(function ($query) {
            $query->doesntHave('warehouseStaff')
                ->orWhereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['warehouse_staff', 'warehouse_manager']);
                });
        })
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->get();

        return response()->json($users);
    }
}
