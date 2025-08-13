<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Services\Auth\RegistrationService;

class RegisterController extends Controller
{
    public function __construct(private RegistrationService $registrationService) {}
    // Validation constants
    private const VALIDATION_RULES = [
        'name' => ['required', 'string', 'max:255', 'min:2'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:8', 'max:255'],
    ];

    private const VALIDATION_MESSAGES = [
        'name.required' => 'Nama lengkap diperlukan.',
        'name.min' => 'Nama harus memiliki minimal 2 karakter.',
        'name.max' => 'Nama terlalu panjang.',
        'email.required' => 'Email diperlukan.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email ini sudah terdaftar.',
        'email.max' => 'Email terlalu panjang.',
        'password.required' => 'Password diperlukan.',
        'password.min' => 'Password harus memiliki minimal 8 karakter.',
        'password.max' => 'Password terlalu panjang.',
    ];

    /**
     * Show the registration form.
     *
     * @return View
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle user registration.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function register(Request $request): RedirectResponse
    {
        try {
            $validated = $this->validateRegistration($request);
            $result = $this->registrationService->register($validated, $request);

            if ($result['success']) {
                return redirect()->route('login')->with('success', $result['message']);
            }

            return back()->withErrors(['email' => $result['message']])->withInput();

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        }
    }

    /**
     * Validate registration request data.
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    private function validateRegistration(Request $request): array
    {
        return $request->validate(self::VALIDATION_RULES, self::VALIDATION_MESSAGES);
    }

    /**
     * Create a new user account.
     *
     * @param array $data
     * @return User
     * @throws \Exception
     */
    // Removed createUser & logging methods now handled in service
}
