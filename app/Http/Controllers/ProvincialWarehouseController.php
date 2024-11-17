<?php

namespace App\Http\Controllers;

use App\Models\ProvincialWarehouse;
use App\Models\WarehouseStaff;
use App\Models\WarehouseUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

class ProvincialWarehouseController extends Controller
{
    public function index()
    {
        try {
            $warehouses = ProvincialWarehouse::withCount(['activeUsers'])->get();
            return view('provincial-warehouses.index', compact('warehouses'));
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách kho', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi tải danh sách kho.');
        }
    }

    public function create()
    {
        try {
            return view('provincial-warehouses.create');
        } catch (\Exception $e) {
            Log::error('Lỗi khi tải form tạo kho', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('provincial-warehouses.index')
                ->with('error', 'Có lỗi xảy ra khi tải form tạo kho.');
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'coordinates' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $coordinatesData = json_decode($validatedData['coordinates'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Lỗi khi xử lý dữ liệu tọa độ: ' . json_last_error_msg());
            }

            $warehouse = ProvincialWarehouse::create([
                'name' => $validatedData['name'],
                'address' => $validatedData['address'],
                'district' => $validatedData['district'],
                'province' => $validatedData['province'],
                'latitude' => $coordinatesData[1],
                'longitude' => $coordinatesData[0],
                'coordinates' => $validatedData['coordinates']
            ]);

            DB::commit();

            return redirect()->route('provincial-warehouses.index')
                ->with('success', 'Kho tổng đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi tạo kho tổng', [
                'input_data' => $validatedData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo kho tổng: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $provincialWarehouse = ProvincialWarehouse::with([
                'users.user.roles',
                'activeUsers.user.roles'
            ])->findOrFail($id);
            
            // Đếm số lượng nhân viên theo vai trò
            $staffCounts = [
                'warehouse_manager' => $provincialWarehouse->activeUsers
                    ->filter(fn($user) => $user->user->hasRole('warehouse_manager'))
                    ->count(),
                'warehouse_staff' => $provincialWarehouse->activeUsers
                    ->filter(fn($user) => $user->user->hasRole('warehouse_staff'))
                    ->count(),
                'warehouse_local_distributor' => $provincialWarehouse->activeUsers
                    ->filter(fn($user) => $user->user->hasRole('warehouse_local_distributor'))
                    ->count(),
                'warehouse_remote_distributor' => $provincialWarehouse->activeUsers
                    ->filter(fn($user) => $user->user->hasRole('warehouse_remote_distributor'))
                    ->count(),
            ];
    
            // Lấy danh sách user chưa được phân công hoặc không còn active
            $availableUsers = User::whereDoesntHave('warehouseUsers', function($query) {
                    $query->where('is_active', true)
                          ->whereNull('end_date');
                })
                ->whereHas('roles', function($query) {
                    $query->whereIn('name', [
                        'warehouse_staff',
                        'warehouse_manager',
                        'warehouse_local_distributor',
                        'warehouse_remote_distributor'
                    ]);
                })
                ->get();
            
            // Lấy danh sách roles cho warehouse
            $roles = Role::whereIn('name', [
                'warehouse_staff', 
                'warehouse_manager',
                'warehouse_local_distributor',
                'warehouse_remote_distributor'
            ])->get();
    
            return view('provincial-warehouses.show', compact(
                'provincialWarehouse',
                'availableUsers',
                'roles',
                'staffCounts'
            ));
    
        } catch (\Exception $e) {
            Log::error('Lỗi khi xem chi tiết kho', [
                'warehouse_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('provincial-warehouses.index')
                ->with('error', 'Không thể tải thông tin kho tổng: ' . $e->getMessage());
        }
    }

    public function assignStaff(Request $request, ProvincialWarehouse $provincialWarehouse)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => [
                'required',
                'in:warehouse_manager,warehouse_staff,warehouse_local_distributor,warehouse_remote_distributor'
            ],
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($validatedData['user_id']);

            // Vô hiệu hóa các phân công cũ nếu có
            WarehouseUser::where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNull('end_date')
                ->update([
                    'is_active' => false,
                    'end_date' => now()
                ]);

            // Tạo phân công mới
            WarehouseUser::create([
                'user_id' => $user->id,
                'warehouse_id' => $provincialWarehouse->id,
                'role' => $validatedData['role'],
                'is_active' => true,
                'start_date' => now()
            ]);

            // Cập nhật role cho user nếu cần
            if (!$user->hasRole($validatedData['role'])) {
                $role = Role::where('name', $validatedData['role'])->first();
                if ($role) {
                    $user->roles()->sync([$role->id]);
                }
            }

            DB::commit();

            return redirect()->route('provincial-warehouses.show', $provincialWarehouse)
                ->with('success', 'Nhân viên đã được phân công thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi phân công nhân viên', [
                'warehouse_id' => $provincialWarehouse->id,
                'input' => $validatedData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi phân công nhân viên: ' . $e->getMessage());
        }
    }

    public function edit(ProvincialWarehouse $provincialWarehouse)
    {
        try {
            return view('provincial-warehouses.edit', compact('provincialWarehouse'));
        } catch (\Exception $e) {
            Log::error('Lỗi khi tải form sửa kho', [
                'warehouse_id' => $provincialWarehouse->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('provincial-warehouses.index')
                ->with('error', 'Không thể tải form sửa kho.');
        }
    }

    public function update(Request $request, ProvincialWarehouse $provincialWarehouse)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'coordinates' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $coordinatesData = json_decode($validatedData['coordinates'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Lỗi khi xử lý dữ liệu tọa độ: ' . json_last_error_msg());
            }

            $provincialWarehouse->update([
                'name' => $validatedData['name'],
                'address' => $validatedData['address'],
                'district' => $validatedData['district'],
                'province' => $validatedData['province'],
                'latitude' => $coordinatesData[1],
                'longitude' => $coordinatesData[0],
                'coordinates' => $validatedData['coordinates']
            ]);

            DB::commit();

            return redirect()->route('provincial-warehouses.index')
                ->with('success', 'Kho tổng đã được cập nhật thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật kho tổng', [
                'warehouse_id' => $provincialWarehouse->id,
                'input' => $validatedData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật kho tổng: ' . $e->getMessage());
        }
    }

    public function destroy(ProvincialWarehouse $provincialWarehouse)
    {
        try {
            DB::beginTransaction();

            // Vô hiệu hóa tất cả nhân viên đang làm việc tại kho
            WarehouseUser::where('warehouse_id', $provincialWarehouse->id)
                ->where('is_active', true)
                ->whereNull('end_date')
                ->update([
                    'is_active' => false,
                    'end_date' => now()
                ]);

            $provincialWarehouse->delete();

            DB::commit();

            return redirect()->route('provincial-warehouses.index')
                ->with('success', 'Kho tổng đã được xóa thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa kho tổng', [
                'warehouse_id' => $provincialWarehouse->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi xóa kho tổng: ' . $e->getMessage());
        }
    }
}