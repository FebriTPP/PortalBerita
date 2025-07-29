<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Comment;

class AdminController extends Controller
{
    private const USERS_PER_PAGE = 10;
    private const COMMENTS_PER_PAGE = 10;
    private const MAX_AVATAR_SIZE = 2048; // KB
    private const ALLOWED_AVATAR_TYPES = ['jpeg', 'png', 'jpg', 'gif'];
    private const DEFAULT_AVATAR = '/avatar/default-avatar.png';
    private const AVATAR_STORAGE_PATH = 'avatars';

    /**
     * Show admin dashboard with user management
     */
    public function dashboard(Request $request): View
    {
        $users = $this->getUsersWithSearch($request->search);

        return view('admin.dashboard', compact('users'));
    }

    /**
     * Show comments management page
     */
    public function komentarManajemen(Request $request): View
    {
        $comments = $this->getCommentsWithSearch($request->search);

        // Get news data from API to match with comments
        $newsData = $this->getNewsDataForComments($comments);

        return view('admin.komentar.index', compact('comments', 'newsData'));
    }

    /**
     * Delete comment (admin only)
     */
    public function destroyComment(string $id): RedirectResponse
    {
        try {
            $comment = Comment::findOrFail($id);
            $comment->delete();

            Log::info('Comment deleted by admin', ['comment_id' => $id]);

            return redirect()->route('admin.komentar.index')
                ->with('success', 'Komentar berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error('Failed to delete comment', ['comment_id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Gagal menghapus komentar. Silakan coba lagi.');
        }
    }

    /**
     * Show create user form
     */
    public function createUser(): View
    {
        return view('admin.profile.create');
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $validatedData = $this->validateUserData($request);

        try {
            DB::beginTransaction();

            $avatarPath = $this->handleAvatarUpload($request);

            $tipe = $request->input('role') === 'admin' ? 'Admin' : 'User';

            User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'],
                'joined_at' => now(),
                'avatar_url' => $avatarPath,
                'email_verified_at' => now(),
            ]);

            DB::commit();

            Log::info('New user created', [
                'email' => $validatedData['email'],
                'role' => $validatedData['role']
            ]);

            return redirect()->route('admin.dashboard')
                ->with('success', "{$tipe} berhasil ditambahkan!");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create user', [
                'error' => $e->getMessage()
            ]);

            $tipe = $request->input('role') === 'admin' ? 'Admin' : 'User';

            return redirect()->back()
                ->with('error', "Gagal menambahkan {$tipe}. Silakan coba lagi.")
                ->withInput();
        }
    }

    /**
     * Delete user with security checks
     */
    public function deleteUser(User $user): RedirectResponse
    {
        if ($this->isProtectedUser($user)) {
            return redirect()->back()
                ->with('error', 'Tidak dapat menghapus admin atau user yang dilindungi!');
        }

        try {
            DB::beginTransaction();

            $this->deleteUserAvatar($user);
            $user->delete();

            DB::commit();

            Log::info('User deleted', ['user_id' => $user->id, 'email' => $user->email]);

            return redirect()->route('admin.dashboard')
                ->with('success', 'User berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete user', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Gagal menghapus user. Silakan coba lagi.');
        }
    }

    /**
     * Show admin profile
     */
    public function showProfile(int $id): View
    {
        $admin = $this->findUserOrFail($id);

        return view('admin.profile.show', compact('admin'));
    }

    /**
     * Edit admin profile
     */
    public function editProfile(int $id): View
    {
        $admin = $this->findUserOrFail($id);

        return view('admin.profile.edit', compact('admin'));
    }

    /**
     * Update admin profile
     */
    public function updateProfile(Request $request, int $id): RedirectResponse
    {
        $admin = $this->findUserOrFail($id);
        $validatedData = $this->validateProfileData($request);

        try {
            DB::beginTransaction();

            $admin->name = $validatedData['name'];

            $this->handleProfileAvatarUpdate($request, $admin);
            $this->handlePasswordUpdate($request, $admin, $validatedData);

            $admin->save();

            DB::commit();

            Log::info('Profile updated', ['user_id' => $admin->id]);

            return redirect()->route('admin.profile.show', $admin->id)
                ->with('success', 'Profil berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update profile', ['user_id' => $admin->id, 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Gagal memperbarui profil. Silakan coba lagi.')
                ->withInput();
        }
    }

    /**
     * Get users with optional search functionality
     */
    private function getUsersWithSearch(?string $search)
    {
        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        return $query->orderByDesc('created_at')->paginate(self::USERS_PER_PAGE);
    }

    /**
     * Get comments with optional search functionality
     */
    private function getCommentsWithSearch(?string $search)
    {
        return Comment::with(['user'])
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('content', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(self::COMMENTS_PER_PAGE);
    }

    /**
     * Get news data for comments from external API
     */
    private function getNewsDataForComments($comments): array
    {
        $newsData = [];

        // Get unique post IDs from comments
        $postIds = $comments->pluck('post_id')->unique()->toArray();

        if (empty($postIds)) {
            return $newsData;
        }

        try {
            // Get API key
            $apiKey = Cache::remember('winnicode_api_key', 3600, function () {
                $response = Http::timeout(10)->post('https://winnicode.com/api/login', [
                    'email' => 'dummy@dummy.com',
                    'password' => 'dummy'
                ]);

                return $response->successful() ? $response->json()['api_key'] ?? null : null;
            });

            if (!$apiKey) {
                return $newsData;
            }

            // Fetch news data
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->get('https://winnicode.com/api/publikasi-berita');

            if ($response->successful()) {
                $allNews = $response->json();

                // Create associative array with post_id as key
                foreach ($allNews as $news) {
                    if (in_array($news['id'], $postIds)) {
                        $newsData[$news['id']] = $news;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch news data for comments', ['error' => $e->getMessage()]);
        }

        return $newsData;
    }

    /**
     * Validate user creation data
     */
    private function validateUserData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,user'],
            'avatar' => ['nullable', 'image', 'mimes:' . implode(',', self::ALLOWED_AVATAR_TYPES), 'max:' . self::MAX_AVATAR_SIZE],
        ]);
    }

    /**
     * Validate profile update data
     */
    private function validateProfileData(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:' . implode(',', self::ALLOWED_AVATAR_TYPES), 'max:' . self::MAX_AVATAR_SIZE],
            'password' => ['nullable', 'confirmed', 'min:8', Rules\Password::defaults()],
            'password_confirmation' => ['nullable'],
            'remove_avatar' => ['nullable', 'in:0,1'],
        ];

        // Add current_password validation only if user wants to change password
        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'string'];
        }

        return $request->validate($rules, [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'current_password.required' => 'Password saat ini harus diisi untuk mengubah password.',
        ]);
    }

    /**
     * Handle avatar upload for new user
     */
    private function handleAvatarUpload(Request $request): ?string
    {
        if (!$request->hasFile('avatar')) {
            return null;
        }

        $avatar = $request->file('avatar');
        $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();
        $avatarPath = $avatar->storeAs(self::AVATAR_STORAGE_PATH, $avatarName, 'public');

        return '/storage/' . $avatarPath;
    }

    /**
     * Handle avatar operations during profile update
     */
    private function handleProfileAvatarUpdate(Request $request, User $user): void
    {
        if ($request->remove_avatar == '1') {
            $this->removeUserAvatar($user);
        } elseif ($request->hasFile('avatar')) {
            $this->deleteUserAvatar($user);
            $user->avatar_url = $this->handleAvatarUpload($request);
        }
    }

    /**
     * Remove user avatar (set to null)
     */
    private function removeUserAvatar(User $user): void
    {
        if ($this->hasCustomAvatar($user)) {
            $this->deleteAvatarFile($user->avatar_url);
        }
        $user->avatar_url = null;
    }

    /**
     * Delete user avatar file and record
     */
    private function deleteUserAvatar(User $user): void
    {
        if ($this->hasCustomAvatar($user)) {
            $this->deleteAvatarFile($user->avatar_url);
        }
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
     * Handle password update
     */
    private function handlePasswordUpdate(Request $request, User $user, array $validatedData): void
    {
        if (!empty($validatedData['password'])) {
            // Verify current password if provided
            if ($request->filled('current_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    throw new \Exception('Password saat ini tidak benar.');
                }
            }

            $user->password = Hash::make($validatedData['password']);
        }
    }

    /**
     * Check if user is protected from deletion
     */
    private function isProtectedUser(User $user): bool
    {
        // Protect admin users and current authenticated user
        return $user->role === 'admin' || $user->id === Auth::id();
    }

    /**
     * Find user by ID or fail
     */
    private function findUserOrFail(int $id): User
    {
        return User::findOrFail($id);
    }
}
