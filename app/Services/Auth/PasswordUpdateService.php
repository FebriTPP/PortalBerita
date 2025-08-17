<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\View\View;

class PasswordUpdateService
{
    private const SUCCESS_MESSAGE = 'Password berhasil direset. Silakan login dengan password baru.';
    private const ERROR_GENERAL = 'Gagal mereset password. Silakan coba lagi.';

    /**
     * Build and return the reset password view with logging.
     */
    public function showResetFormView(Request $request, string $token): View
    {
        $email = $request->email;

        Log::info('Password reset form accessed', [
            'token' => $token,
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Reset password workflow.
     * Returns array: [success(bool), message(string)]
     */
    public function resetPassword(array $validated, Request $request): array
    {
        try {
            $status = Password::reset(
                $validated,
                function ($user, $password) use ($request) {
                    $this->updateUserPassword($user, $password);
                    $this->logPasswordResetSuccess($user, $request);
                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                $this->logPasswordResetAttempt($validated['email'], $status, true, $request);
                return [
                    'success' => true,
                    'message' => self::SUCCESS_MESSAGE,
                ];
            }

            $this->logPasswordResetAttempt($validated['email'], $status, false, $request);

            return [
                'success' => false,
                'message' => $this->getErrorMessageForStatus($status),
            ];

        } catch (\Throwable $e) {
            $this->logPasswordResetError($validated['email'] ?? null, $e, $request);
            return [
                'success' => false,
                'message' => self::ERROR_GENERAL,
                'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ];
        }
    }

    private function updateUserPassword($user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => null,
        ])->save();
    }

    private function getErrorMessageForStatus(string $status): string
    {
        return match($status) {
            Password::INVALID_USER => 'Email tidak ditemukan dalam sistem.',
            Password::INVALID_TOKEN => 'Token reset password tidak valid atau sudah kedaluwarsa.',
            Password::RESET_THROTTLED => 'Terlalu banyak percobaan reset. Silakan tunggu beberapa saat.',
            default => self::ERROR_GENERAL,
        };
    }

    private function logPasswordResetAttempt(string $email, string $status, bool $success, Request $request): void
    {
        Log::log($success ? 'info' : 'warning', $success ? 'Password reset successful' : 'Password reset failed', [
            'email' => $email,
            'status' => $status,
            'success' => $success,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    private function logPasswordResetSuccess($user, Request $request): void
    {
        Log::info('User password reset completed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    private function logPasswordResetError(?string $email, \Throwable $exception, Request $request): void
    {
        Log::error('Password reset error', [
            'email' => $email,
            'error' => $exception->getMessage(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
