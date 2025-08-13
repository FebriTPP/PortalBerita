<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\Admin\AdminCommentService;
use App\Services\Admin\AdminUserService;
use App\Services\Admin\AdminUserDetailService;
use App\Models\User;
use App\Models\Comment;

class AdminController extends Controller
{
    /**
     * NOTE: Constants retained for backward compatibility (views/tests) but logic moved to services.
     */
    private const USERS_PER_PAGE = 10;
    private const COMMENTS_PER_PAGE = 10;
    private const MAX_AVATAR_SIZE = 2048; // KB
    private const ALLOWED_AVATAR_TYPES = ['jpeg', 'png', 'jpg', 'gif'];
    private const DEFAULT_AVATAR = '/avatar/default-avatar.png';
    private const AVATAR_STORAGE_PATH = 'avatars';

    public function __construct(
        private readonly AdminAnalyticsService $analyticsService,
        private readonly AdminCommentService $commentService,
        private readonly AdminUserService $userService,
        private readonly AdminUserDetailService $userDetailService,
    ) {}

    /**
     * Show admin dashboard with user management and API status
     */
    public function dashboard(Request $request): View
    {
        $users = $this->userService->getUsersWithSearch($request->search);
        $apiStatus = $this->analyticsService->getApiHealthStatus();
        $analytics = $this->analyticsService->getAnalyticsData();
        $summary = $this->analyticsService->getQuickSummary();
        return view('admin.dashboard', compact('users', 'apiStatus', 'analytics', 'summary'));
    }

    /* All analytics helper logic moved to AdminAnalyticsService */

    /**
     * Show comments management page
     */
    public function komentarManajemen(Request $request): View
    {
        $comments = $this->commentService->getCommentsWithSearch($request->search);
        $newsData = $this->commentService->getNewsDataForComments($comments);
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
        $validatedData = $this->userService->validateUserData($request);
        $result = $this->userService->storeUser($validatedData, $request);
        if ($result['success']) {
            return redirect()->route('admin.dashboard')->with('success', $result['message']);
        }
        return redirect()->back()->with('error', $result['message'])->withInput();
    }

    /**
     * Delete user with security checks
     */
    public function deleteUser(User $user): RedirectResponse
    {
        $result = $this->userService->deleteUser($user);
        $flashType = $result['success'] ? 'success' : 'error';
        $redirect = $result['success'] ? redirect()->route('admin.dashboard') : redirect()->back();
        return $redirect->with($flashType, $result['message']);
    }

    /**
     * Show admin profile
     */
    /**
     * Edit admin profile
     */
    public function editProfile(int $id): View
    {
        $admin = $this->userService->findUserOrFail($id);
        return view('admin.profile.edit', compact('admin'));
    }

    /**
     * Update admin profile
     */
    public function updateProfile(Request $request, int $id): RedirectResponse
    {
        $result = $this->userService->updateProfile($request, $id);
        if ($result['success']) {
            return redirect()->route('admin.dashboard')->with('success', $result['message']);
        }
        return redirect()->back()->with('error', $result['message'])->withInput();
    }
    /* User & comment helper logic moved to AdminUserService / AdminCommentService */
}
