<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetService
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_MINUTES = 60;

    /**
     * Send password reset link to email
     */
    public function sendResetLink(string $email, Request $request): array
    {
        // Check rate limiting
        if ($this->hasTooManyAttempts($request)) {
            $this->logRateLimitExceeded($request);
            return [
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 1 jam.',
                'type' => 'rate_limit'
            ];
        }

        // Increment attempts
        $this->incrementAttempts($request);

        // Send reset link
        $status = $this->sendPasswordResetLink($email);

        // Handle response
        return $this->handleResetResponse($status, $request);
    }

    /**
     * Check if user can make password reset request
     */
    public function canMakeRequest(Request $request): bool
    {
        return !$this->hasTooManyAttempts($request);
    }

    /**
     * Get remaining attempts for rate limiting
     */
    public function getRemainingAttempts(Request $request): int
    {
        $key = $this->getRateLimitKey($request);
        $attempts = RateLimiter::attempts($key);
        return max(0, self::MAX_ATTEMPTS - $attempts);
    }

    /**
     * Send password reset link to email
     */
    private function sendPasswordResetLink(string $email): string
    {
        // Normalize email
        $email = strtolower(trim($email));

        $status = Password::sendResetLink(['email' => $email]);

        // Log the attempt
        $this->logPasswordResetAttempt($email, $status);

        return $status;
    }

    /**
     * Handle the response after sending reset link
     */
    private function handleResetResponse(string $status, Request $request): array
    {
        if ($status === Password::RESET_LINK_SENT) {
            // Clear rate limiter on successful send
            $this->clearAttempts($request);

            return [
                'success' => true,
                'message' => 'Link reset password telah dikirim ke email Anda.',
                'type' => 'success'
            ];
        }

        // Handle different error statuses
        $errorMessage = $this->getErrorMessageForStatus($status);

        return [
            'success' => false,
            'message' => $errorMessage,
            'type' => 'validation'
        ];
    }

    /**
     * Get appropriate error message for password reset status
     */
    private function getErrorMessageForStatus(string $status): string
    {
        return match($status) {
            Password::INVALID_USER => 'Email tidak ditemukan dalam sistem.',
            Password::INVALID_TOKEN => 'Token reset password tidak valid.',
            Password::RESET_THROTTLED => 'Silakan tunggu sebelum mencoba lagi.',
            default => 'Gagal mengirim email reset password. Silakan coba lagi.',
        };
    }

    /**
     * Check if the request has too many attempts
     */
    private function hasTooManyAttempts(Request $request): bool
    {
        $key = $this->getRateLimitKey($request);
        return RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS);
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
        return 'password_reset_' . $request->ip();
    }

    /**
     * Log password reset attempt
     */
    private function logPasswordResetAttempt(string $email, string $status): void
    {
        Log::info('Password reset link requested', [
            'email' => $email,
            'status' => $status,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Log rate limit exceeded attempts
     */
    private function logRateLimitExceeded(Request $request): void
    {
        Log::warning('Password reset rate limit exceeded', [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email' => $request->input('email'),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Log password reset errors
     */
    public function logPasswordResetError(Request $request, \Exception $exception): void
    {
        Log::error('Password reset error', [
            'email' => $request->input('email'),
            'error' => $exception->getMessage(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
