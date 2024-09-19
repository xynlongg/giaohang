<?php
// app/Http/Controllers/ShipperAvatarController.php

namespace App\Http\Controllers;

use App\Models\Shipper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShipperAvatarController extends Controller
{
    public function update(Request $request, Shipper $shipper)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($shipper->avatar) {
                Storage::disk('public')->delete($shipper->avatar);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $shipper->avatar = $path;
            $shipper->save();

            return response()->json([
                'message' => 'Avatar updated successfully',
                'avatar_url' => Storage::url($path)
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }
}