<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::with('roles')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|array', // Assuming role is an array of role IDs
        ]);

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
            ]);

            // Gán role cho user
            $user->roles()->sync($validatedData['role']); // Ensure role is an array of role IDs

            return response()->json($user->load('roles'));
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role' => 'required|array', // Assuming role is an array of role IDs
        ]);

        try {
            $user = User::findOrFail($id);
            $user->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
            ]);

            // Cập nhật roles cho user
            $user->roles()->sync($validatedData['role']); // Ensure role is an array of role IDs

            return response()->json($user->load('roles'));
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Show the profile of the current authenticated user.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('users/profile', compact('user'));
    }
    
    public function updateProfile(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $request->user()->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::find($request->user()->id);

        if ($user) {
            $updateData = [];

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $file_name = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads'), $file_name);
                $updateData['avatar'] = '/' . $file_name;
            }
            if ($request->filled('name')) {
                $updateData['name'] = $request->input('name');
            }

            if ($request->filled('email')) {
                $updateData['email'] = $request->input('email');
            }
            $user->update($updateData);
            return redirect()->route('user.profile')->with('success', 'Cập nhật thành công');
        }

        return redirect()->route('user.profile')->with('error', 'Có lỗi xảy ra.');
    }
    
    public function getUserWithRoles($id)
    {
        $user = User::with('roles')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'roles' => $user->roles->toArray(),
        ]);
    }
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'Logged out successfully');
    }   
}
