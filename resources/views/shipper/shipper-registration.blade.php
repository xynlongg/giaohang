@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Đăng ký Shipper</h2>
    <form id="shipperForm">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" required>
            <button type="button" class="btn btn-primary mt-2" id="sendOtp">Gửi mã OTP</button>
        </div>
        <div id="otpSection" style="display: none;">
            <div class="mb-3">
                <label for="otp" class="form-label">Mã OTP</label>
                <input type="text" class="form-control" id="otp" required>
                <button type="button" class="btn btn-primary mt-2" id="verifyOtp">Xác nhận OTP</button>
            </div>
        </div>
        <div id="additionalFields" style="display: none;">
            <div class="mb-3">
                <label for="name" class="form-label">Tên</label>
                <input type="text" class="form-control" id="name" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="tel" class="form-control" id="phone" required>
            </div>
            <div class="mb-3">
                <label for="cccd" class="form-label">Căn cước công dân</label>
                <input type="text" class="form-control" id="cccd" required>
            </div>
            <div class="mb-3">
                <label for="jobType" class="form-label">Công việc đăng ký</label>
                <select class="form-select" id="jobType" required>
                    <option value="tech_shipper">Shipper công nghệ</option>
                    <option value="truck_driver">Tài xế xe tải</option>
                    <option value="goods_handler">Xử lý hàng hóa</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="city" class="form-label">Tỉnh/Thành phố</label>
                <select class="form-select" id="city" required>
                    <option value="">Chọn tỉnh/thành phố</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="district" class="form-label">Quận/Huyện</label>
                <select class="form-select" id="district" required disabled>
                    <option value="">Chọn quận/huyện</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Đăng ký</button>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Thiết lập CSRF token cho Axios
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let isEmailVerified = false;

    async function fetchCities() {
        const citySelect = document.getElementById('city');
        try {
            const response = await axios.get('/get-cities');
            response.data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.code;
                option.textContent = province.name;
                citySelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error fetching cities:', error);
            alert('Lỗi khi tải danh sách tỉnh/thành phố');
        }
    }

    async function fetchDistricts(provinceCode) {
        const districtSelect = document.getElementById('district');
        districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
        districtSelect.disabled = true;

        if (provinceCode) {
            try {
                const response = await axios.get(`/get-districts/${provinceCode}`);
                response.data.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.code;
                    option.textContent = district.name;
                    districtSelect.appendChild(option);
                });
                districtSelect.disabled = false;
            } catch (error) {
                console.error('Error fetching districts:', error);
                alert('Lỗi khi lấy danh sách quận/huyện');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', fetchCities);

    document.getElementById('city').addEventListener('change', (e) => {
        fetchDistricts(e.target.value);
    });

    document.getElementById('sendOtp').addEventListener('click', async () => {
        const email = document.getElementById('email').value;
        if (!email) {
            alert('Vui lòng nhập địa chỉ email');
            return;
        }
        try {
            const response = await axios.post('/send-otp', { email });
            alert(response.data.message);
            document.getElementById('otpSection').style.display = 'block';
        } catch (error) {
            alert('Lỗi khi gửi OTP: ' + (error.response?.data?.message || 'Đã xảy ra lỗi'));
        }
    });

    document.getElementById('verifyOtp').addEventListener('click', async () => {
        const email = document.getElementById('email').value;
        const otp = document.getElementById('otp').value;
        if (!email || !otp) {
            alert('Vui lòng nhập đầy đủ email và mã OTP');
            return;
        }
        try {
            const response = await axios.post('/verify-otp', { email, otp });
            alert(response.data.message);
            document.getElementById('additionalFields').style.display = 'block';
            isEmailVerified = true;
        } catch (error) {
            alert('Lỗi khi xác minh OTP: ' + (error.response?.data?.message || 'Đã xảy ra lỗi'));
        }
    });

    document.getElementById('shipperForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!isEmailVerified) {
            alert('Vui lòng xác thực email trước khi đăng ký');
            return;
        }
        const formData = {
            email: document.getElementById('email').value,
            name: document.getElementById('name').value,
            phone: document.getElementById('phone').value,
            cccd: document.getElementById('cccd').value,
            job_type: document.getElementById('jobType').value,
            city: document.getElementById('city').value,
            district: document.getElementById('district').value
        };
        try {
            const response = await axios.post('/register-shipper', formData);
            alert('Đăng ký thành công!');
            window.location.href = '/dashboard'; // Chuyển hướng sau khi đăng ký thành công
        } catch (error) {
            alert('Lỗi khi đăng ký: ' + (error.response?.data?.message || 'Đã xảy ra lỗi'));
        }
    });
</script>
@endpush
@endsection