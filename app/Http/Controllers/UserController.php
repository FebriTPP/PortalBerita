<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // kept only if password verify remains here
use App\Services\User\UserService;
use App\Models\User;
use App\Models\Comment;

class UserController extends Controller
{
    /** Constants retained for backward compatibility with views if referenced */
    private const MAX_AVATAR_SIZE = 2048; // KB
    private const ALLOWED_AVATAR_TYPES = ['jpeg', 'png', 'jpg', 'gif'];
    private const DEFAULT_AVATAR = '/avatar/default-avatar.png';
    private const AVATAR_STORAGE_PATH = 'avatars';

    public function __construct(private readonly UserService $userService) {}

    /**
     * Show user dashboard
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        $comments = Comment::where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['id','content','created_at','post_id','user_id']);

        return view('user.dashboard', [
            'user' => $user,
            'comments' => $comments,
        ]);
    }

    /**
     * Show profile (info + comment history)
     */
    public function showProfile(Request $request): View
    {
        $user = Auth::user();
        $comments = Comment::where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['id','content','created_at','post_id','user_id']);
        return view('user.dashboard', [
            'user' => $user,
            'comments' => $comments,
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
        $result = $this->userService->updateProfile($request);
        if ($result['success']) {
            return redirect()->route('user.profile.show')->with('success', $result['message']);
        }
        return redirect()->back()->with('error', $result['message'])->withInput();
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
        $result = $this->userService->updatePassword($request);
        if ($result['success']) {
            return redirect()->route('user.profile.show')->with('success', $result['message']);
        }
        if (!empty($result['errors'])) {
            return back()->withErrors($result['errors']);
        }
        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Validate profile update data
     */
    /* Validation, avatar & password logic moved to UserService */

    /**
     * Delete authenticated user's account
     */
    public function destroy(Request $request): RedirectResponse
    {
        $result = $this->userService->destroyAccount($request);
        if ($result['success']) {
            return redirect('/')->with('success', $result['message']);
        }
        return redirect()->back()->with('error', $result['message']);
    }

}
