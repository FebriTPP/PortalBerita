<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CommentService
{
    // Validation constraints
    public const MIN_CONTENT_LENGTH = 3;
    public const MAX_CONTENT_LENGTH = 500;

    // Roles
    public const ADMIN_ROLE = 'admin';

    // Messages
    public const ERROR_UNAUTHORIZED = 'Anda tidak memiliki izin untuk menghapus komentar ini.';
    public const ERROR_COMMENT_NOT_FOUND = 'Komentar tidak ditemukan.';
    public const ERROR_CREATE_FAILED = 'Gagal menambahkan komentar. Silakan coba lagi.';
    public const ERROR_DELETE_FAILED = 'Gagal menghapus komentar. Silakan coba lagi.';
    public const SUCCESS_COMMENT_ADDED = 'Komentar berhasil ditambahkan.';
    public const SUCCESS_COMMENT_DELETED = 'Komentar berhasil dihapus.';

    /**
     * Validate incoming comment data.
     * @throws ValidationException
     */
    public function validate(Request $request): array
    {
        return $request->validate([
            'post_id' => 'required|string|max:255',
            'content' => 'required|string|min:' . self::MIN_CONTENT_LENGTH . '|max:' . self::MAX_CONTENT_LENGTH,
        ], [
            'post_id.required' => 'Post ID diperlukan.',
            'post_id.string' => 'Post ID harus berupa string.',
            'post_id.max' => 'Post ID terlalu panjang.',
            'content.required' => 'Konten komentar diperlukan.',
            'content.string' => 'Konten komentar harus berupa teks.',
            'content.min' => 'Konten komentar minimal ' . self::MIN_CONTENT_LENGTH . ' karakter.',
            'content.max' => 'Konten komentar maksimal ' . self::MAX_CONTENT_LENGTH . ' karakter.',
        ]);
    }

    /**
     * Store a new comment.
     */
    public function store(array $validated): array
    {
        try {
            DB::beginTransaction();
            $comment = Comment::create([
                'post_id' => $validated['post_id'],
                'user_id' => Auth::id(),
                'content' => trim($validated['content']),
            ]);
            DB::commit();
            $this->logCommentAction('created', $comment->id, Auth::id());
            return ['success' => true, 'message' => self::SUCCESS_COMMENT_ADDED, 'comment' => $comment];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create comment', [
                'user_id' => Auth::id(),
                'post_id' => $validated['post_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => self::ERROR_CREATE_FAILED];
        }
    }

    /**
     * Delete a comment if allowed.
     */
    public function delete(?Comment $comment, ?User $actor): array
    {
        if (!$comment || !$comment->exists) {
            return ['success' => false, 'message' => self::ERROR_COMMENT_NOT_FOUND];
        }
        if (!$actor) {
            return ['success' => false, 'message' => self::ERROR_UNAUTHORIZED];
        }
        if (!$this->canDeleteComment($comment, $actor)) {
            $this->logUnauthorizedAction('delete_comment', $comment->id, $actor->id);
            return ['success' => false, 'message' => self::ERROR_UNAUTHORIZED];
        }
        try {
            DB::beginTransaction();
            $commentId = $comment->id;
            $originalUserId = $comment->user_id;
            $comment->delete();
            DB::commit();
            $this->logCommentAction('deleted', $commentId, $actor->id, $originalUserId);
            return ['success' => true, 'message' => self::SUCCESS_COMMENT_DELETED];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to delete comment', [
                'comment_id' => $comment->id ?? null,
                'user_id' => $actor->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => self::ERROR_DELETE_FAILED];
        }
    }

    /** Permission check */
    private function canDeleteComment(Comment $comment, User $user): bool
    { return $user->id === $comment->user_id || $user->role === self::ADMIN_ROLE; }

    /** Log actions */
    private function logCommentAction(string $action, int $commentId, int $userId, ?int $originalUserId = null): void
    {
        $log = [
            'action' => $action,
            'comment_id' => $commentId,
            'user_id' => $userId,
            'timestamp' => now()->toDateTimeString(),
        ];
        if ($originalUserId && $originalUserId !== $userId) {
            $log['original_user_id'] = $originalUserId;
            $log['admin_action'] = true;
        }
        Log::info("Comment {$action}", $log);
    }

    /** Log unauthorized attempts */
    private function logUnauthorizedAction(string $action, int $commentId, int $userId): void
    {
        Log::warning('Unauthorized comment action attempted', [
            'action' => $action,
            'comment_id' => $commentId,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
