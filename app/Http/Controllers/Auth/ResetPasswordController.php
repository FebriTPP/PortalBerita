<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Services\Auth\PasswordUpdateService;

class ResetPasswordController extends Controller
{
    public function __construct(private PasswordUpdateService $passwordUpdateService) {}
    // Validation constants
    private const VALIDATION_MESSAGES = [
        'token.required' => 'Token reset password diperlukan.',
        'email.required' => 'Email diperlukan.',
        'email.email' => 'Format email tidak valid.',
        'password.required' => 'Password baru diperlukan.',
        'password.min' => 'Password harus memiliki minimal 8 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
    ];

    /**
     * Display the password reset form.
     *
     * @param Request $request
     * @param string $token
     * @return View
     */
    public function showResetForm(Request $request, string $token): View
    {
        return $this->passwordUpdateService->showResetFormView($request, $token);
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
            $validated = $this->validateResetRequest($request);
            $result = $this->passwordUpdateService->resetPassword($validated, $request);

            if ($result['success']) {
                return redirect()->route('login')->with('success', $result['message']);
            }

            return back()->withErrors(['email' => $result['message']])->withInput();

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
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
    // Removed internal password reset logic & logging in favor of service abstraction
}
