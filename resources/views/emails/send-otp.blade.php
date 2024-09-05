<!DOCTYPE html>
<html>
<head>
    <style>
        .email-container {
            font-family: Arial, sans-serif;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }

        .logo {
            display: block;
            margin: 0 auto 20px;
            max-width: 150px;
        }

        .content {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Thêm logo vào email -->
        <img src="{{ $message->embed(public_path('img/logo1.png')) }}" alt="Logo" class="logo">
        
        <h1>Chào bạn!</h1>
        <p>Cảm ơn bạn đã đăng ký làm shipper. Dưới đây là mã OTP của bạn:</p>
        <h2>{{ $otp }}</h2>
        <p>Vui lòng nhập mã OTP này để hoàn tất quá trình đăng ký.</p>

        <p>Trân trọng,</p>
        <p>LongXyn Delivery</p>
        
    </div>
</body>
</html>
