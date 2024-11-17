<?php
namespace App\Http\Controllers;

use App\Models\Shipper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShipperAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $shipper = Shipper::where('email', $request->email)->first();

        if (!$shipper || !Hash::check($request->password, $shipper->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = Str::random(60);
        $shipper->api_token = $token;
        $shipper->save();

        return response()->json([
            'token' => $token,
            'shipper' => $shipper
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user('shipper'));
    }

       

  
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('shippers')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Reset password link sent on your email id.']);
        }

        return response()->json(['message' => 'Unable to send reset link'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::broker('shippers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($shipper, $password) {
                $shipper->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been successfully reset']);
        }

        return response()->json(['message' => 'Unable to reset password'], 400);
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|different:current_password',
            'confirm_password' => 'required|same:new_password',
        ]);

        $shipper = Auth::guard('shipper')->user();

        if (!$shipper) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!Hash::check($request->current_password, $shipper->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $shipper->password = Hash::make($request->new_password);
        $shipper->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            Log::info('Logout successful', ['shipper_id' => $request->user()->id]);
            return response()->json(['message' => 'Logged out successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred during logout'], 500);
        }
    }

    public function getCurrentShipper()
    {
        $shipper = Auth::guard('shipper')->user();
        if (!$shipper) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return response()->json($shipper->load('postOfficeShipper.postOffice'));
    }
}