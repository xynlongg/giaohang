<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $role = Role::create([
            'name' => $request->name,
        ]);

        return response()->json($role, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $role = Role::findOrFail($id);
        $role->update([
            'name' => $request->name,
        ]);

        return response()->json($role, 200);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(null, 204);
    }

    public function assignRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $roleId = $request->input('role_id');

        // Check if the user already has 3 roles
        if ($user->roles()->count() >= 3) {
            return response()->json(['message' => 'Người dùng đã có 3 vai trò. Không thể thêm vai trò mới.'], 400);
        }

        // Check if the user already has the role
        if ($user->roles()->where('role_user.role_id', $roleId)->exists()) {
            return response()->json(['message' => 'Người dùng đã có vai trò này. Vui lòng chọn vai trò khác.'], 400);
        }

        // Assign the role to the user
        $role = Role::findOrFail($roleId);
        $user->roles()->attach($role);

        return response()->json(['message' => 'Vai trò đã được phân thành công'], 200);
    }
    public function removeRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $roleId = intval($request->input('role_id')); // Chuyển đổi thành số nguyên
    
        // Log user roles for debugging
        Log::info('User Roles:', $user->roles->pluck('id')->toArray());
    
        // Check if the user has the role
        if (!$user->roles()->where('role_user.role_id', $roleId)->exists()) {
            return response()->json(['message' => 'Người dùng không có vai trò này.'], 400);
        }
    
        // Remove the role from the user
        $user->roles()->detach($roleId);
    
        return response()->json(['message' => 'Vai trò đã được xóa thành công'], 200);
    }
    
    
    // Method to get current roles of a user
    public function getUserRoles($userId)
    {
        $user = User::findOrFail($userId);
        $roles = $user->roles()->get();

        return response()->json($roles, 200);
    }

}
