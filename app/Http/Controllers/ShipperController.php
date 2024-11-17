<?php
//Shipper API shipper-app
namespace App\Http\Controllers;

use App\Models\Shipper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ShipperController extends Controller
{
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $shipper = auth('shipper')->user();

        if (!$shipper) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($shipper->avatar) {
                Storage::disk('public')->delete($shipper->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $shipper->avatar = $path;
            $shipper->save();

            return response()->json([
                'message' => 'Avatar uploaded successfully',
                'avatar' => Storage::url($path)
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:shippers,email,' . auth('shipper')->id(),
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $shipper = auth('shipper')->user();

        if (!$shipper) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $shipper->name = $request->name;
        $shipper->email = $request->email;
        $shipper->phone = $request->phone;
        $shipper->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'shipper' => $shipper
        ]);
    }
}