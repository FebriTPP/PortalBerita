<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegistrationService
{
	/**
	 * Normalize incoming data (trim, lower-case email, hash password, add defaults)
	 */
	private function normalizeData(array $data): array
	{
		return [
			'name' => trim($data['name']),
			'email' => strtolower(trim($data['email'])),
			'password' => Hash::make($data['password']),
			'joined_at' => now(),
			'role' => 'user',
		];
	}

	/**
	 * Core registration workflow.
	 * Returns array: [success(bool), user(User|null), message(string)]
	 */
	public function register(array $validatedData, Request $request): array
	{
		try {
			$payload = $this->normalizeData($validatedData);

			$user = DB::transaction(fn () => User::create($payload));

			$this->logSuccess($user, $request);

			return [
				'success' => true,
				'user' => $user,
				'message' => 'Akun berhasil dibuat! Silakan login.'
			];
		} catch (\Throwable $e) {
			$this->logFailure($validatedData, $request, $e);
			return [
				'success' => false,
				'user' => null,
				'message' => 'Gagal membuat akun. Silakan coba lagi.',
				'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
			];
		}
	}

	/**
	 * Optional: simple availability check (case-insensitive)
	 */
	public function isEmailAvailable(string $email): bool
	{
		return ! User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->exists();
	}

	/**
	 * Aggregate quick stats for potential dashboard use.
	 */
	public function stats(): array
	{
		return [
			'total' => User::count(),
			'today' => User::whereDate('created_at', today())->count(),
			'week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
			'month' => User::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
		];
	}

	private function logSuccess(User $user, Request $request): void
	{
		Log::info('User registered successfully', [
			'user_id' => $user->id,
			'email' => $user->email,
			'name' => $user->name,
			'ip_address' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'timestamp' => now()->toDateTimeString(),
		]);
	}

	private function logFailure(array $data, Request $request, \Throwable $e): void
	{
		Log::error('User registration failed', [
			'email' => $data['email'] ?? null,
			'name'  => $data['name'] ?? null,
			'error' => $e->getMessage(),
			'ip_address' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'timestamp' => now()->toDateTimeString(),
		]);
	}
}

