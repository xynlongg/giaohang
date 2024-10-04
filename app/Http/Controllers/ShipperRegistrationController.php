<?php

namespace App\Http\Controllers;

use App\Models\PostOffice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Shipper;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpEmail;
use App\Mail\ShipperRegistrationApproved;
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

        $shipper = Shipper::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'cccd' => $validatedData['cccd'],
            'job_type' => $validatedData['job_type'],
            'city' => $validatedData['city'],
            'district' => $validatedData['district'],
            'status' => 'pending',
            'attendance_score' => 0,
            'vote_score' => 0,
            'operating_area' => json_encode(['city' => $validatedData['city'], 'district' => $validatedData['district']]),
        ]);

        Cache::forget('verified_email_' . $request->input('email'));

        return response()->json([
            'message' => 'Đăng ký Shipper thành công và đang chờ duyệt',
            'shipper' => $shipper
        ]);
    }


    public function reject($id)
    {
        $shipper = Shipper::findOrFail($id);

        if ($shipper->status !== 'pending') {
            return response()->json(['message' => 'Shipper này đã được xử lý trước đó'], 400);
        }

        $shipper->status = 'rejected';
        $shipper->save();

        // Gửi email thông báo từ chối
        Mail::to($shipper->email)->send(new RejectionNotification($shipper));

        return response()->json(['message' => 'Đã từ chối đăng ký shipper']);
        return view('orders.index', compact('orders', 'postOffices'));

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
    
        if ($shipper->status !== 'pending') {
            return response()->json(['message' => 'Shipper này đã được xử lý trước đó'], 400);
        }
    
        DB::beginTransaction();
    
        try {
            // Lấy thông tin khu vực hoạt động của shipper
            $operatingArea = json_decode($shipper->operating_area, true);
            $cityCode = $operatingArea['city'];
            $districtCode = $operatingArea['district'];
    
            // Lấy tên thành phố và quận huyện từ API
            $cityName = $this->getLocationNameFromAPI('p', $cityCode);
            $districtName = $this->getLocationNameFromAPI('d', $districtCode);
    
            // Tìm bưu cục phù hợp
            $matchingPostOffice = $this->findMatchingPostOffice($cityName, $districtName);
    
            if (!$matchingPostOffice) {
                throw new \Exception('Không tìm thấy bưu cục phù hợp cho khu vực hoạt động của shipper.');
            }
    
            // Cập nhật trạng thái shipper và đặt mật khẩu mặc định
            $defaultPassword = '123123123';
            $shipper->status = 'approved';
            $shipper->password = bcrypt($defaultPassword);
            $shipper->save();
    
            // Gán shipper cho bưu cục phù hợp
            DB::table('post_office_shippers')->insert([
                'post_office_id' => $matchingPostOffice->id,
                'shipper_id' => $shipper->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            DB::commit();
    
            // Gửi email thông báo
            Mail::to($shipper->email)->send(new ShipperRegistrationApproved($shipper, $defaultPassword));
    
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Đã chấp nhận đăng ký shipper, cấp mật khẩu mặc định và gán vào bưu cục phù hợp',
                    'shipper' => $shipper,
                    'assigned_post_office' => $matchingPostOffice
                ]);
            } else {
                return redirect()->route('orders.index')->with('success', 'Đã chấp nhận đăng ký shipper và gán vào bưu cục phù hợp thành công');
            }
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi chấp nhận đăng ký shipper: ' . $e->getMessage());
            return response()->json(['message' => 'Lỗi khi chấp nhận đăng ký: ' . $e->getMessage()], 500);
        }
    }
    
    protected function findMatchingPostOffice($cityName, $districtName)
    {
        $cityWithoutPrefix = $this->removePrefix($cityName);
        $districtWithoutPrefix = $this->removePrefix($districtName);
    
        // Tìm bưu cục theo quận/huyện
        $postOffice = PostOffice::where('district', 'like', "%{$districtName}%")
            ->orWhere('district', 'like', "%{$districtWithoutPrefix}%")
            ->first();
    
        // Nếu không tìm thấy theo quận/huyện, tìm theo tỉnh/thành phố
        if (!$postOffice) {
            $postOffice = PostOffice::where('province', 'like', "%{$cityName}%")
                ->orWhere('province', 'like', "%{$cityWithoutPrefix}%")
                ->first();
        }
    
        return $postOffice;
    }
    
    private function removePrefix($name)
    {
        $prefixes = ['Thành phố', 'Tỉnh', 'Quận', 'Huyện', 'Thị xã'];
        foreach ($prefixes as $prefix) {
            if (strpos($name, $prefix) === 0) {
                return trim(substr($name, strlen($prefix)));
            }
        }
        return $name;
    }

    protected function getLocationNameFromAPI($type, $code)
    {
        $response = Http::get("https://provinces.open-api.vn/api/{$type}/{$code}");
        if ($response->successful()) {
            return $response->json()['name'];
        }
        throw new \Exception("Không thể lấy tên địa điểm cho mã: {$code}");
    }

    protected function findNearestPostOffice($cityName, $districtName)
    {
        $cityWithoutPrefix = $this->removePrefix($cityName);
        $districtWithoutPrefix = $this->removePrefix($districtName);

        return PostOffice::where(function ($query) use ($cityName, $cityWithoutPrefix, $districtName, $districtWithoutPrefix) {
            $query->where('address', 'like', "%{$cityName}%")
                  ->orWhere('address', 'like', "%{$cityWithoutPrefix}%")
                  ->orWhere('address', 'like', "%{$districtName}%")
                  ->orWhere('address', 'like', "%{$districtWithoutPrefix}%");
        })->first();
    }

    
}