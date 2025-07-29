<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Comment;

class UserController extends Controller
{
    private const MAX_AVATAR_SIZE = 2048; // KB
    private const ALLOWED_AVATAR_TYPES = ['jpeg', 'png', 'jpg', 'gif'];
    private const DEFAULT_AVATAR = '/avatar/default-avatar.png';
    private const AVATAR_STORAGE_PATH = 'avatars';

    /**
     * Show user dashboard
     */
    public function dashboard(): View
    {
        return view('user.dashboard', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Show edit profile form
     */
    public function editProfile(): View
    {
        return view('user.dashboard', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $validatedData = $this->validateProfileData($request);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $updateData = ['name' => $validatedData['name']];

            $this->handleAvatarUpdate($request, $user, $updateData);

            User::where('id', $user->id)->update($updateData);

            DB::commit();

            Log::info('Profile updated', ['user_id' => $user->id]);

            return redirect()->route('user.profile.show')
                ->with('success', 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update profile', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Gagal memperbarui profil. Silakan coba lagi.')
                ->withInput();
        }
    }

    /**
     * Show change password form
     */
    public function changePassword(): View
    {
        return view('user.dashboard', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validatedData = $this->validatePasswordData($request);

        if (!$this->verifyCurrentPassword($validatedData['current_password'])) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai']);
        }

        try {
            User::where('id', Auth::id())->update([
                'password' => Hash::make($validatedData['new_password'])
            ]);

            Log::info('Password updated', ['user_id' => Auth::id()]);

            return redirect()->route('user.profile.show')
                ->with('success', 'Password berhasil diubah!');
        } catch (\Exception $e) {
            Log::error('Failed to update password', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Gagal mengubah password. Silakan coba lagi.');
        }
    }

    /**
     * Validate profile update data
     */
    private function validateProfileData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:' . implode(',', self::ALLOWED_AVATAR_TYPES), 'max:' . self::MAX_AVATAR_SIZE],
            'remove_avatar' => ['nullable', 'in:0,1'],
        ]);
    }

    /**
     * Validate password update data
     */
    private function validatePasswordData(Request $request): array
    {
        return $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
    }

    /**
     * Verify current password
     */
    private function verifyCurrentPassword(string $currentPassword): bool
    {
        return Hash::check($currentPassword, Auth::user()->password);
    }

    /**
     * Handle avatar upload/removal operations
     */
    private function handleAvatarUpdate(Request $request, User $user, array &$updateData): void
    {
        if ($request->remove_avatar == '1') {
            $this->removeUserAvatar($user);
            $updateData['avatar_url'] = null;
        } elseif ($request->hasFile('avatar')) {
            $this->deleteUserAvatar($user);
            $updateData['avatar_url'] = $this->uploadNewAvatar($request->file('avatar'));
        }
    }

    /**
     * Remove user avatar (delete file)
     */
    private function removeUserAvatar(User $user): void
    {
        if ($this->hasCustomAvatar($user)) {
            $this->deleteAvatarFile($user->avatar_url);
        }
    }

    /**
     * Delete user's current avatar file
     */
    private function deleteUserAvatar(User $user): void
    {
        if ($this->hasCustomAvatar($user)) {
            $this->deleteAvatarFile($user->avatar_url);
        }
    }

    /**
     * Upload new avatar file
     */
    private function uploadNewAvatar($avatarFile): string
    {
        $avatarName = time() . '_' . uniqid() . '.' . $avatarFile->getClientOriginalExtension();
        $avatarPath = $avatarFile->storeAs(self::AVATAR_STORAGE_PATH, $avatarName, 'public');

        return '/storage/' . $avatarPath;
    }

    /**
     * Delete avatar file from storage
     */
    private function deleteAvatarFile(string $avatarUrl): void
    {
        $avatarPath = str_replace('/storage/', '', $avatarUrl);

        if (Storage::disk('public')->exists($avatarPath)) {
            Storage::disk('public')->delete($avatarPath);
        }
    }

    /**
     * Check if user has custom avatar (not default)
     */
    private function hasCustomAvatar(User $user): bool
    {
        return $user->avatar_url
            && strpos($user->avatar_url, '/storage/' . self::AVATAR_STORAGE_PATH . '/') === 0
            && $user->avatar_url !== self::DEFAULT_AVATAR;
    }

    /**
     * Delete authenticated user's account
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            DB::transaction(function () use ($user) {
                // Hapus data yang berkaitan (komentar)
                Comment::where('user_id', $user->id)->delete();

                // Hapus avatar jika ada
                $this->removeUserAvatar($user);

                // Alternative: Use model query instead of $user->delete()
                User::destroy($user->id);
                // OR
                // User::where('id', $user->id)->delete();
            });

            // Logout user dan invalidate session
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('User account deleted successfully', ['user_id' => $user->id]);

            return redirect('/')->with('success', 'Akun Anda berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Failed to delete user account', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Gagal menghapus akun.');
        }
    }

}
