<?php

namespace App\Http\Controllers;

use App\Models\ShipperProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Shipper;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpEmail;
use App\Mail\InterviewInvitation;
use App\Mail\RejectionNotification;

class ShipperRegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        return view('shipper.register');
    }

    public function index()
    {
        $shippers = Shipper::paginate(10); // Phân trang, mỗi trang 10 item
        return view('shipper.index', compact('shippers'));
    }

    public function show($id)
    {
        $shipper = Shipper::findOrFail($id);
    
        // Lấy thông tin tên tỉnh từ API
        $cityResponse = Http::get("https://provinces.open-api.vn/api/p/{$shipper->city}");
        $cityName = $cityResponse->successful() ? ($cityResponse->json()['name'] ?? 'Không xác định') : 'Không xác định';
    
        // Lấy thông tin tên huyện từ API
        $districtResponse = Http::get("https://provinces.open-api.vn/api/d/{$shipper->district}");
        $districtName = $districtResponse->successful() ? ($districtResponse->json()['name'] ?? 'Không xác định') : 'Không xác định';
    
        // Truyền các biến vào view
        return view('shipper.show', compact('shipper', 'cityName', 'districtName'));
    }
    

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
    
        $email = $request->input('email');
        $otp = rand(100000, 999999);
        
        \Log::info("Attempting to send OTP to email: " . $email);
        
        Cache::put('otp_' . $email, $otp, now()->addMinutes(5));
        
        try {
            Mail::to($email)->send(new SendOtpEmail($otp));
            \Log::info("OTP sent successfully to: " . $email);
            return response()->json(['message' => 'OTP đã được gửi đến email của bạn.']);
        } catch (\Exception $e) {
            \Log::error('Gửi email thất bại: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            if (config('app.debug')) {
                return response()->json([
                    'message' => 'Debug: Không thể gửi OTP qua email. OTP là: ' . $otp,
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return response()->json(['message' => 'Không thể gửi OTP. Vui lòng thử lại sau.'], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);

        $email = $request->input('email');
        $otp = $request->input('otp');
        
        $cachedOtp = Cache::get('otp_' . $email);
        
        if (!$cachedOtp) {
            return response()->json(['message' => 'OTP đã hết hạn hoặc không tồn tại'], 400);
        }
        
        if ($otp == $cachedOtp) {
            Cache::forget('otp_' . $email);
            Cache::put('verified_email_' . $email, true, now()->addHour());
            return response()->json(['message' => 'Xác thực OTP thành công']);
        } else {
            return response()->json(['message' => 'OTP không hợp lệ'], 400);
        }
    }
    
    public function register(Request $request)
    {
        if (!Cache::has('verified_email_' . $request->input('email'))) {
            return response()->json(['message' => 'Email chưa được xác thực'], 400);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:shippers,email',
            'phone' => 'required|string', 
            'cccd' => 'required|string|unique:shippers,cccd',
            'job_type' => 'required|in:tech_shipper,truck_driver,goods_handler',
            'city' => 'required|string',
            'district' => 'required|string',
        ]);
        
        $shipper = Shipper::create($validatedData);
        
        Cache::forget('verified_email_' . $request->input('email'));
        
        return response()->json(['message' => 'Đăng ký Shipper thành công', 'shipper' => $shipper]);
    }

    public function getCities()
    {
        try {
            $response = Http::get('https://provinces.open-api.vn/api/p/');
            
            if ($response->successful()) {
                $provinces = $response->json();
                return response()->json($provinces);
            } else {
                \Log::error('Failed to fetch provinces: ' . $response->body());
                return response()->json(['message' => 'Không thể lấy danh sách tỉnh/thành phố'], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Exception when fetching provinces: ' . $e->getMessage());
            return response()->json(['message' => 'Đã xảy ra lỗi khi lấy danh sách tỉnh/thành phố'], 500);
        }
    }
    
    public function getDistricts($provinceCode)
    {
        try {
            $response = Http::get("https://provinces.open-api.vn/api/p/{$provinceCode}?depth=2");
            
            if ($response->successful()) {
                $data = $response->json();
                $districts = $data['districts'] ?? [];
                return response()->json([
                    'province' => $data['name'],
                    'districts' => $districts
                ]);
            } else {
                \Log::error('Failed to fetch districts: ' . $response->body());
                return response()->json(['message' => 'Không thể lấy danh sách quận/huyện'], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Exception when fetching districts: ' . $e->getMessage());
            return response()->json(['message' => 'Đã xảy ra lỗi khi lấy danh sách quận/huyện'], 500);
        }
    }
    
    public function approve($id)
    {
        $shipper = Shipper::findOrFail($id);
        $shipper->status = 'approved';
        $shipper->save();

        // Gửi email mời phỏng vấn
        Mail::to($shipper->email)->send(new InterviewInvitation($shipper));

        return redirect()->route('shippers.show', $id)->with('success', 'Đã duyệt đăng ký và gửi email mời phỏng vấn.');
    }

    public function reject($id)
    {
        $shipper = Shipper::findOrFail($id);
        $shipper->status = 'rejected';
        $shipper->save();

        // Gửi email thông báo từ chối
        Mail::to($shipper->email)->send(new RejectionNotification($shipper));

        return redirect()->route('shippers.show', $id)->with('success', 'Đã từ chối đăng ký và gửi email thông báo.');
    }
    
}