@extends('layouts.app2')

@section('content')
<div class="container">
    <div class="form-box">
        <h2>{{ __('Register') }}</h2>
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="input-box">
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                <label for="name">{{ __('Name') }}</label>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="input-box">
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                <label for="email">{{ __('Email Address') }}</label>
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="input-box">
                <input id="password" type="password" name="password" required autocomplete="new-password">
                <label for="password">{{ __('Password') }}</label>
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="input-box">
                <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password">
                <label for="password-confirm">{{ __('Confirm Password') }}</label>
            </div>
            <button type="submit">
                {{ __('Register') }}
            </button>
        </form>
        <div class="login-link">
            Already have an account? <a href="{{ route('login') }}">Login</a>
        </div>
    </div>
    <div class="theme-switch">
        <label class="switch">
            <input type="checkbox" id="themeToggle">
            <span class="slider"></span>
        </label>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

    :root {
        --background: #ffffff;
        --text: #333333;
        --input-border: #ff6666;
        --input-focus: #ff0000;
        --button-bg: #ff0000;
        --button-hover: #cc0000;
        --link-color: #ff0000;
        --switch-bg: #ff6666;
        --switch-checked: #ff0000;
    }

    .dark-mode {
        --background: #1a1a1a;
        --text: #ffffff;
        --input-border: #ff6666;
        --input-focus: #ff0000;
        --button-bg: #ff0000;
        --button-hover: #cc0000;
        --link-color: #ff6666;
        --switch-bg: #ff6666;
        --switch-checked: #ff0000;
    }

    body {
        font-family: 'Poppins', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: var(--background);
        transition: background 0.3s ease;
    }

    .container {
        position: relative;
        width: 380px;
        padding: 40px 30px;
        background: var(--background);
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .container::before,
    .container::after {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 380px;
        height: 420px;
        background: linear-gradient(0deg, transparent, var(--button-bg), var(--button-bg));
        transform-origin: bottom right;
        animation: animate 6s linear infinite;
    }

    .container::after {
        animation-delay: -3s;
    }

    @keyframes animate {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .form-box {
        position: relative;
        z-index: 10;
        background: var(--background);
        border-radius: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px;
        flex-direction: column;
    }

    h2 {
        font-size: 2em;
        color: var(--text);
        text-align: center;
        margin-bottom: 30px;
    }

    .input-box {
        position: relative;
        width: 100%;
        margin-top: 35px;
    }

    .input-box input {
        width: 100%;
        padding: 10px 0;
        font-size: 1em;
        color: var(--text);
        border: none;
        border-bottom: 2px solid var(--input-border);
        outline: none;
        background: transparent;
        transition: 0.5s;
    }

    .input-box label {
        position: absolute;
        top: 0;
        left: 0;
        padding: 10px 0;
        font-size: 1em;
        color: var(--text);
        pointer-events: none;
        transition: 0.5s;
    }

    .input-box input:focus ~ label,
    .input-box input:valid ~ label {
        top: -20px;
        left: 0;
        color: var(--input-focus);
        font-size: 0.8em;
    }

    .input-box input:focus,
    .input-box input:valid {
        border-bottom: 2px solid var(--input-focus);
    }

    button {
        width: 100%;
        height: 40px;
        background: var(--button-bg);
        border: none;
        outline: none;
        border-radius: 40px;
        cursor: pointer;
        font-size: 1em;
        color: #fff;
        font-weight: 500;
        transition: 0.3s;
        margin-top: 20px;
    }

    button:hover {
        background: var(--button-hover);
    }

    .login-link {
        font-size: 0.9em;
        color: var(--text);
        text-align: center;
        margin-top: 25px;
    }

    .login-link a {
        color: var(--link-color);
        text-decoration: none;
        font-weight: 600;
    }

    .login-link a:hover {
        text-decoration: underline;
    }

    .theme-switch {
        position: absolute;
        top: 20px;
        right: 20px;
    }

    .theme-switch input {
        display: none;
    }

    .slider {
        cursor: pointer;
        width: 50px;
        height: 25px;
        background-color: var(--switch-bg);
        display: block;
        border-radius: 25px;
        position: relative;
    }

    .slider:before {
        content: "";
        position: absolute;
        width: 21px;
        height: 21px;
        background-color: white;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: 0.3s;
    }

    .theme-switch input:checked + .slider {
        background-color: var(--switch-checked);
    }

    .theme-switch input:checked + .slider:before {
        transform: translateX(25px);
    }

    .invalid-feedback {
        color: #ff4136;
        font-size: 0.8em;
        margin-top: 5px;
    }
</style>

<script>
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;

    themeToggle.addEventListener('change', function() {
        if (this.checked) {
            body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
        }
    });

    // Check for saved theme preference or set a default
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        body.classList.add('dark-mode');
        themeToggle.checked = true;
    }
</script>
@endsection