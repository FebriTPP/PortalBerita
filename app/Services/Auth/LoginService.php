<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LoginService
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const DECAY_MINUTES = 15;

    /**
     * Attempt to authenticate user
     */
    public function attemptLogin(array $credentials, Request $request): array
    {
        // Check rate limiting
        if ($this->hasTooManyAttempts($request)) {
            $this->logRateLimitExceeded($request);
            return [
                'success' => false,
                'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.',
                'type' => 'rate_limit',
                'retry_after' => $this->getTimeUntilReset($request)
            ];
        }

        // Increment attempts
        $this->incrementAttempts($request);

        // Normalize credentials
        $credentials['email'] = strtolower(trim($credentials['email']));

        // Attempt authentication
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            $this->handleSuccessfulLogin($user, $request);

            return [
                'success' => true,
                'user' => $user,
                'redirect_url' => $this->getRedirectUrl($user),
                'type' => 'success'
            ];
        }

        $this->logFailedLogin($credentials['email'], $request);

        return [
            'success' => false,
            'message' => 'Email atau password salah.',
            'type' => 'invalid_credentials',
            'remaining_attempts' => $this->getRemainingAttempts($request)
        ];
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): void
    {
        $user = Auth::user();
        $this->logLogout($user, $request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Check if user can make login attempt
     */
    public function canMakeLoginAttempt(Request $request): bool
    {
        return !$this->hasTooManyAttempts($request);
    }

    /**
     * Get remaining login attempts
     */
    public function getRemainingAttempts(Request $request): int
    {
        $key = $this->getRateLimitKey($request);
        $attempts = RateLimiter::attempts($key);
        return max(0, self::MAX_LOGIN_ATTEMPTS - $attempts);
    }

    /**
     * Get time until rate limit reset (in seconds)
     */
    public function getTimeUntilReset(Request $request): int
    {
        $key = $this->getRateLimitKey($request);
        return RateLimiter::availableIn($key);
    }

    /**
     * Handle successful login actions
     */
    private function handleSuccessfulLogin($user, Request $request): void
    {
        $this->clearAttempts($request);
        $this->logSuccessfulLogin($user, $request);
    }

    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl($user): string
    {
        return match($user->role) {
            'admin' => route('admin.dashboard'),
            'user' => route('news.index'),
            default => route('news.index'),
        };
    }

    /**
     * Check if the request has too many attempts
     */
    private function hasTooManyAttempts(Request $request): bool
    {
        $key = $this->getRateLimitKey($request);
        return RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS);
    }

    /**
     * Increment the rate limiter attempts
     */
    private function incrementAttempts(Request $request): void
    {
        $key = $this->getRateLimitKey($request);
        RateLimiter::hit($key, self::DECAY_MINUTES * 60);
    }

    /**
     * Clear rate limiter attempts
     */
    private function clearAttempts(Request $request): void
    {
        $key = $this->getRateLimitKey($request);
        RateLimiter::clear($key);
    }

    /**
     * Get the rate limiting key for the request
     */
    private function getRateLimitKey(Request $request): string
    {
        return 'login_attempts_' . $request->ip();
    }

    /**
     * Log successful login
     */
    private function logSuccessfulLogin($user, Request $request): void
    {
        Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Log failed login attempt
     */
    private function logFailedLogin(string $email, Request $request): void
    {
        Log::warning('Failed login attempt', [
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Log logout activity
     */
    private function logLogout($user, Request $request): void
    {
        Log::info('User logged out', [
            'user_id' => $user?->id ?? 'unknown',
            'email' => $user?->email ?? 'unknown',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Log rate limit exceeded
     */
    private function logRateLimitExceeded(Request $request): void
    {
        Log::warning('Login rate limit exceeded', [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email' => $request->input('email'),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Log login errors
     */
    public function logLoginError(Request $request, \Exception $exception): void
    {
        Log::error('Login error', [
            'email' => $request->input('email'),
            'error' => $exception->getMessage(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
