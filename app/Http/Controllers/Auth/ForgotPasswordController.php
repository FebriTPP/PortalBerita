<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    // Rate limiting constants
    private const MAX_ATTEMPTS = 5;
    private const DECAY_MINUTES = 60;

    // Validation constants
    private const VALIDATION_RULES = [
        'email' => ['required', 'email', 'max:255'],
    ];

    private const VALIDATION_MESSAGES = [
        'email.required' => 'Email diperlukan.',
        'email.email' => 'Format email tidak valid.',
        'email.max' => 'Email terlalu panjang.',
    ];

    // Error messages
    private const ERROR_RATE_LIMIT = 'Terlalu banyak percobaan. Silakan coba lagi dalam 1 jam.';
    private const ERROR_GENERAL = 'Gagal mengirim email reset password. Silakan coba lagi.';

    // Success messages
    private const SUCCESS_LINK_SENT = 'Link reset password telah dikirim ke email Anda.';

    /**
     * Show the form for requesting a password reset link.
     *
     * @return View
     */
    public function showLinkRequestForm(): View
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        try {
            // Check rate limiting first
            if ($this->hasTooManyAttempts($request)) {
                $this->logRateLimitExceeded($request);
                return back()->withErrors(['email' => self::ERROR_RATE_LIMIT]);
            }

            // Validate email input
            $validatedData = $this->validateEmailRequest($request);

            // Increment rate limiter attempts
            $this->incrementAttempts($request);

            // Send reset link
            $status = $this->sendPasswordResetLink($validatedData['email']);

            // Handle response based on status
            return $this->handleResetLinkResponse($status, $request);

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            $this->logPasswordResetError($request, $e);
            return back()->withErrors(['email' => self::ERROR_GENERAL])->withInput();
        }
    }

    /**
     * Validate email request data.
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    private function validateEmailRequest(Request $request): array
    {
        return $request->validate(self::VALIDATION_RULES, self::VALIDATION_MESSAGES);
    }

    /**
     * Send password reset link to email.
     *
     * @param string $email
     * @return string
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
     * Handle the response after sending reset link.
     *
     * @param string $status
     * @param Request $request
     * @return RedirectResponse
     */
    private function handleResetLinkResponse(string $status, Request $request): RedirectResponse
    {
        if ($status === Password::RESET_LINK_SENT) {
            // Clear rate limiter on successful send
            $this->clearAttempts($request);

            return back()->with('success', self::SUCCESS_LINK_SENT);
        }

        // Handle different error statuses
        $errorMessage = $this->getErrorMessageForStatus($status);

        return back()->withErrors(['email' => $errorMessage]);
    }

    /**
     * Get appropriate error message for password reset status.
     *
     * @param string $status
     * @return string
     */
    private function getErrorMessageForStatus(string $status): string
    {
        return match($status) {
            Password::INVALID_USER => 'Email tidak ditemukan dalam sistem.',
            Password::INVALID_TOKEN => 'Token reset password tidak valid.',
            Password::RESET_THROTTLED => 'Silakan tunggu sebelum mencoba lagi.',
            default => self::ERROR_GENERAL,
        };
    }

    /**
     * Check if the request has too many attempts.
     *
     * @param Request $request
     * @return bool
     */
    private function hasTooManyAttempts(Request $request): bool
    {
        $key = $this->getRateLimitKey($request);

        return RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS);
    }

    /**
     * Increment the rate limiter attempts.
     *
     * @param Request $request
     * @return void
     */
    private function incrementAttempts(Request $request): void
    {
        $key = $this->getRateLimitKey($request);

        RateLimiter::hit($key, self::DECAY_MINUTES * 60);
    }

    /**
     * Clear rate limiter attempts.
     *
     * @param Request $request
     * @return void
     */
    private function clearAttempts(Request $request): void
    {
        $key = $this->getRateLimitKey($request);

        RateLimiter::clear($key);
    }

    /**
     * Get the rate limiting key for the request.
     *
     * @param Request $request
     * @return string
     */
    private function getRateLimitKey(Request $request): string
    {
        return 'password_reset_' . $request->ip();
    }

    /**
     * Log password reset attempt.
     *
     * @param string $email
     * @param string $status
     * @return void
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
     * Log rate limit exceeded attempts.
     *
     * @param Request $request
     * @return void
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
     * Log password reset errors.
     *
     * @param Request $request
     * @param \Exception $exception
     * @return void
     */
    private function logPasswordResetError(Request $request, \Exception $exception): void
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
