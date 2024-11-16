<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        try {
            $roles = Role::withCount('users')->get();
            Log::info('Fetched roles:', ['count' => $roles->count()]);
            
            if ($roles->isEmpty()) {
                Log::warning('Không tìm thấy vai trò nào trong cơ sở dữ liệu.');
                return response()->json([], 200);
            }
            
            return response()->json($roles);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách vai trò: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Có lỗi xảy ra khi lấy danh sách vai trò'], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
            ]);

            $role = Role::create([
                'name' => $request->name,
            ]);

            DB::commit();
            Log::info('Vai trò mới đã được tạo:', ['role' => $role->toArray()]);
            return response()->json($role, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi tạo vai trò mới: ' . $e->getMessage(), [
                'input' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Có lỗi xảy ra khi tạo vai trò mới'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $role = Role::findOrFail($id);

            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles')->ignore($role->id),
                ],
            ]);

            $role->update([
                'name' => $request->name,
            ]);

            DB::commit();
            Log::info('Vai trò đã được cập nhật:', ['role' => $role->toArray()]);
            return response()->json($role, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật vai trò: ' . $e->getMessage(), [
                'role_id' => $id,
                'input' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Có lỗi xảy ra khi cập nhật vai trò'], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $role = Role::findOrFail($id);
            
            if ($role->users()->count() > 0) {
                throw new \Exception('Không thể xóa vai trò đang được sử dụng bởi người dùng.');
            }

            $role->delete();
            DB::commit();
            Log::info('Vai trò đã được xóa:', ['role_id' => $id]);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa vai trò: ' . $e->getMessage(), [
                'role_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}