<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
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
    private const ERROR_GENERAL = 'Gagal mengirim email reset password. Silakan coba lagi.';

    public function __construct(private PasswordResetService $passwordResetService) {}

    /**
     * Show the form for requesting a password reset link.
     */
    public function showLinkRequestForm(): View
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        try {
            // Validate email input
            $validatedData = $this->validateEmailRequest($request);

            // Send reset link via service
            $result = $this->passwordResetService->sendResetLink(
                $validatedData['email'],
                $request
            );

            // Handle response based on result
            if ($result['success']) {
                return back()->with('success', $result['message']);
            }

            return back()->withErrors(['email' => $result['message']])->withInput();

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            $this->passwordResetService->logPasswordResetError($request, $e);
            return back()->withErrors(['email' => self::ERROR_GENERAL])->withInput();
        }
    }

    /**
     * Validate email request data.
     */
    private function validateEmailRequest(Request $request): array
    {
        return $request->validate(self::VALIDATION_RULES, self::VALIDATION_MESSAGES);
    }
}
