<?php
//Quản lý nhân viên bưu cục, phân role, gán bưu cục
namespace App\Http\Controllers;

use App\Models\PostOffice;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Events\StaffUpdated;
use Illuminate\Support\Facades\DB;

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

        $postOfficeRoles = [
            'post_office_staff',
            'post_office_manager',
            'general_distribution_staff',
            'local_distribution_staff'
        ];
        $filteredUsers = $allUsers->filter(function ($user) use ($postOfficeRoles) {
            return $user->roles()->whereIn('name', $postOfficeRoles)->exists();
        });

        return view('post_offices.staff.index', compact('postOffices', 'filteredUsers', 'allUsers'));
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:post_office_staff,post_office_manager,general_distribution_staff,local_distribution_staff',
        ]);

        try {
            DB::beginTransaction();
            
            $role = Role::where('name', $request->role)->firstOrFail();
            $user->roles()->sync([$role->id]);

            // Nếu là nhân viên phân phối, đảm bảo họ được gán vào bưu cục
            if (in_array($request->role, ['local_distribution_staff', 'general_distribution_staff'])) {
                if (!$user->postOffices()->exists()) {
                    return redirect()->back()->with('error', 'Vui lòng gán bưu cục cho nhân viên phân phối');
                }
            }

            DB::commit();

            event(new StaffUpdated($user->id, 'role_assigned', "Quyền {$request->role} đã được gán cho {$user->name}"));

            return redirect()->route('post_offices.staff.index')
                ->with('success', 'Đã cập nhật quyền cho nhân viên thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function assignPostOffice(Request $request, User $user)
    {
        $request->validate([
            'post_office_id' => 'required|exists:post_offices,id',
        ]);

        $postOffice = PostOffice::findOrFail($request->post_office_id);
        $user->postOffices()->sync([$request->post_office_id]);

        event(new StaffUpdated(
            $user->id,
            'post_office_assigned',
            'Nhân viên đã được gán cho bưu cục',
            $postOffice->name
        ));
        return redirect()->route('post_offices.staff.index')->with('success', 'Bưu cục đã được gán cho nhân viên.');
    }

    public function removeFromPostOffice(User $user)
    {
        $postOfficeName = $user->postOffices->first()->name ?? 'Unknown';
        $user->postOffices()->detach();

        event(new StaffUpdated($user->id, 'removed_from_post_office', "{$user->name} đã bị xóa khỏi bưu cục {$postOfficeName}"));

        return redirect()->route('post_offices.staff.index')->with('success', 'Nhân viên đã được xóa khỏi bưu cục.');
    }

    public function getDistributionStaff(Request $request)
    {
        try {
            if ($request->has('post_office_id')) {
                // Lấy nhân viên phân phối local cho bưu cục
                $staff = User::whereHas('roles', function($query) {
                    $query->where('name', 'local_distribution_staff');
                })
                ->whereHas('postOffices', function($query) use ($request) {
                    $query->where('post_offices.id', $request->post_office_id);
                })
                ->select('id', 'name', 'email')
                ->get();

                return response()->json($staff);
            } 
            else if ($request->has('warehouse_id')) {
                // Lấy nhân viên phân phối general cho kho tổng
                $staff = User::whereHas('roles', function($query) {
                    $query->where('name', 'general_distribution_staff');
                })
                ->whereHas('warehouseStaff', function($query) use ($request) {
                    $query->where('provincial_warehouse_id', $request->warehouse_id);
                })
                ->select('id', 'name', 'email')
                ->get();

                return response()->json($staff);
            }

            return response()->json([]);
        } catch (\Exception $e) {
            Log::error('Error getting distribution staff: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}