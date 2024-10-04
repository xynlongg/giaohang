<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;


class LoginController extends Controller
{
    use AuthenticatesUsers;


    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        Log::info('Login attempt with data: ' . json_encode($request->all()));
        Log::info('CSRF Token in request: ' . $request->input('_token'));
        Log::info('Session ID: ' . $request->session()->getId());

        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    // Override the authenticated method
    protected function sendLoginResponse(Request $request)
    {
        Log::info('Sending login response');
        $request->session()->regenerate();
        Log::info('Session regenerated. New Session ID: ' . $request->session()->getId());
        Log::info('Session data after login: ' . json_encode($request->session()->all()));
        Log::info('User ID after login: ' . auth()->id());                                                                              
        
        $this->clearLoginAttempts($request);
    
        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }
    
        Log::info('About to redirect to: ' . $this->redirectPath());
        return $request->wantsJson()
                    ? new JsonResponse([], 204)
                    : redirect()->intended($this->redirectPath());
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        Log::info('Sending failed login response');
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
   
    public function username()
    {
        return 'email';
    }
    // Override the redirectPath method if needed
    protected $redirectTo = '/dashboard';  // hoặc '/home' hoặc bất kỳ đường dẫn nào bạn muốn

    protected function attemptLogin(Request $request)
    {
        $result = $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
        Log::info('Login attempt result: ' . ($result ? 'success' : 'failure'));
        return $result;
    }
    
    public function redirectPath()
    {
        \Log::info('Redirect path called. Path: ' . $this->redirectTo);
        return $this->redirectTo;
    }

    protected function authenticated(Request $request, $user)
    {
        Log::info('User authenticated in LoginController. User ID: ' . $user->id);
        Log::info('Session ID after login: ' . $request->session()->getId());
    }
    
  
}