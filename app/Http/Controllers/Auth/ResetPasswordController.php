<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    // Validation constants
    private const VALIDATION_MESSAGES = [
        'token.required' => 'Token reset password diperlukan.',
        'email.required' => 'Email diperlukan.',
        'email.email' => 'Format email tidak valid.',
        'password.required' => 'Password baru diperlukan.',
        'password.min' => 'Password harus memiliki minimal 8 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
    ];

    // Success and error messages
    private const SUCCESS_PASSWORD_RESET = 'Password berhasil direset. Silakan login dengan password baru.';
    private const ERROR_GENERAL = 'Gagal mereset password. Silakan coba lagi.';

    /**
     * Display the password reset form.
     *
     * @param Request $request
     * @param string $token
     * @return View
     */
    public function showResetForm(Request $request, string $token): View
    {
        $email = $request->email;

        // Log form access for security monitoring
        Log::info('Password reset form accessed', [
            'token' => $token,
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $email
        ]);
    }

    /**
     * Reset the user's password.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function reset(Request $request): RedirectResponse
    {
        try {
            // Validate the reset request
            $validatedData = $this->validateResetRequest($request);

            // Attempt to reset the password
            $status = $this->resetUserPassword($validatedData);

            // Handle the response
            return $this->handleResetResponse($status, $validatedData['email']);

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            $this->logPasswordResetError($request, $e);
            return back()->withErrors(['email' => self::ERROR_GENERAL])->withInput();
        }
    }

    /**
     * Validate the password reset request.
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    private function validateResetRequest(Request $request): array
    {
        return $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], self::VALIDATION_MESSAGES);
    }

    /**
     * Reset the user's password.
     *
     * @param array $data
     * @return string
     */
    private function resetUserPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function ($user, $password) {
                $this->updateUserPassword($user, $password);
                $this->logPasswordResetSuccess($user);
                event(new PasswordReset($user));
            }
        );

        return $status;
    }

    /**
     * Update user's password.
     *
     * @param $user
     * @param string $password
     * @return void
     */
    private function updateUserPassword($user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => null, // Clear remember token for security
        ])->save();
    }

    /**
     * Handle the password reset response.
     *
     * @param string $status
     * @param string $email
     * @return RedirectResponse
     */
    private function handleResetResponse(string $status, string $email): RedirectResponse
    {
        if ($status === Password::PASSWORD_RESET) {
            $this->logPasswordResetAttempt($email, $status, true);
            return redirect()->route('login')->with('success', self::SUCCESS_PASSWORD_RESET);
        }

        $this->logPasswordResetAttempt($email, $status, false);
        $errorMessage = $this->getErrorMessageForStatus($status);

        return back()->withErrors(['email' => $errorMessage])->withInput();
    }

    /**
     * Get appropriate error message for reset status.
     *
     * @param string $status
     * @return string
     */
    private function getErrorMessageForStatus(string $status): string
    {
        return match($status) {
            Password::INVALID_USER => 'Email tidak ditemukan dalam sistem.',
            Password::INVALID_TOKEN => 'Token reset password tidak valid atau sudah kedaluwarsa.',
            Password::RESET_THROTTLED => 'Terlalu banyak percobaan reset. Silakan tunggu beberapa saat.',
            default => self::ERROR_GENERAL,
        };
    }

    /**
     * Log password reset attempt.
     *
     * @param string $email
     * @param string $status
     * @param bool $success
     * @return void
     */
    private function logPasswordResetAttempt(string $email, string $status, bool $success): void
    {
        $level = $success ? 'info' : 'warning';
        $message = $success ? 'Password reset successful' : 'Password reset failed';

        Log::log($level, $message, [
            'email' => $email,
            'status' => $status,
            'success' => $success,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Log successful password reset.
     *
     * @param $user
     * @return void
     */
    private function logPasswordResetSuccess($user): void
    {
        Log::info('User password reset completed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
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
