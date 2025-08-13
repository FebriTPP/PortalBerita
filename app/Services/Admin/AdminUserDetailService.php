<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\Cache;

class AdminUserDetailService
{
    private const CACHE_TTL = 60; // seconds

    public function getDetailData(User $user): array
    {
        return Cache::remember("admin_user_detail:{$user->id}", self::CACHE_TTL, function () use ($user) {
            return [
                'user' => $user,
                'stats' => $this->buildStats($user),
                'recent_comments' => $this->recentComments($user),
                'login_info' => $this->loginInfo($user),
            ];
        });
    }

    private function buildStats(User $user): array
    {
        return [
            'total_comments' => Comment::where('user_id', $user->id)->count(),
            'comments_last_7d' => Comment::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(7))->count(),
            'first_activity' => Comment::where('user_id', $user->id)->min('created_at'),
            'last_activity' => Comment::where('user_id', $user->id)->max('created_at'),
        ];
    }

    private function recentComments(User $user)
    {
        return Comment::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get(['id','content','created_at','post_id','user_id']);
    }

    private function loginInfo(User $user): array
    {
        return [
            'last_login_at' => $user->last_login_at ?? null,
            'login_count' => $user->login_count ?? null,
        ];
    }
}
