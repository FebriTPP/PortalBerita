<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LoginController extends Controller
{
    private LoginService $loginService;

    public function __construct(LoginService $loginService)
    {
        $this->loginService = $loginService;
    }

    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle user login.
     */
    public function login(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'max:255'],
            ], [
                'email.required' => 'Email diperlukan.',
                'email.email' => 'Format email tidak valid.',
                'email.max' => 'Email terlalu panjang.',
                'password.required' => 'Password diperlukan.',
                'password.max' => 'Password terlalu panjang.',
            ]);

            $credentials = [
                'email' => $request->email,
                'password' => $request->password,
            ];

            $result = $this->loginService->attemptLogin($credentials, $request);

            if (!$result['success']) {
                return $this->handleLoginFailure($result, $request);
            }

            return redirect($result['redirect_url']);

        } catch (\Exception $e) {
            $this->loginService->logLoginError($request, $e);
            
            return back()->withErrors([
                'email' => 'Terjadi kesalahan sistem. Silakan coba lagi.',
            ])->withInput($request->only('email'));
        }
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->loginService->logout($request);
        return redirect()->route('news.index');
    }

    /**
     * Handle login failure response
     */
    private function handleLoginFailure(array $result, Request $request): RedirectResponse
    {
        $errorMessages = [
            'rate_limit' => $result['message'],
            'invalid_credentials' => $result['message'],
        ];

        $message = $errorMessages[$result['type']] ?? 'Terjadi kesalahan. Silakan coba lagi.';

        return back()->withErrors([
            'email' => $message,
        ])->withInput($request->only('email'));
    }
}
