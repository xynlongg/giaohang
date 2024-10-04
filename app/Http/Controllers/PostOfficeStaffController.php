<?php
//Quản lý nhân viên bưu cục, phân role, gán bưu cục
namespace App\Http\Controllers;

use App\Models\PostOffice;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class PostOfficeStaffController extends Controller
{
    public function index(Request $request)
    {
        $postOffices = PostOffice::all();
        $query = User::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $allUsers = $query->get();

        $postOfficeRoles = ['post_office_staff', 'post_office_manager'];
        $filteredUsers = $allUsers->filter(function ($user) use ($postOfficeRoles) {
            return $user->roles()->whereIn('name', $postOfficeRoles)->exists();
        });

        return view('post_offices.staff.index', compact('postOffices', 'filteredUsers', 'allUsers'));
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:post_office_staff,post_office_manager',
        ]);

        $role = Role::where('name', $request->role)->firstOrFail();
        $user->roles()->sync([$role->id]);

        return redirect()->route('post_offices.staff.index')->with('success', 'Role đã được gán cho nhân viên.');
    }

    public function assignPostOffice(Request $request, User $user)
    {
        $request->validate([
            'post_office_id' => 'required|exists:post_offices,id',
        ]);

        $user->postOffices()->sync([$request->post_office_id]);

        return redirect()->route('post_offices.staff.index')->with('success', 'Bưu cục đã được gán cho nhân viên.');
    }

    public function removeFromPostOffice(User $user)
    {
        $user->postOffices()->detach();
        return redirect()->route('post_offices.staff.index')->with('success', 'Nhân viên đã được xóa khỏi bưu cục.');
    }
}