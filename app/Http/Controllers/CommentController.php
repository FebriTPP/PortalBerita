<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $commentService) {}

    /**
     * Store a newly created comment in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $this->commentService->validate($request);
            $result = $this->commentService->store($validated);
            if ($result['success']) {
                return redirect()->back()->with('success', $result['message']);
            }
            return redirect()->back()->with('error', $result['message'])->withInput();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        }
    }

    /**
     * Remove the specified comment from storage.
     *
     * @param Comment $comment
     * @return RedirectResponse
     */
    public function destroy(Comment $comment): RedirectResponse
    {
    $result = $this->commentService->delete($comment, Auth::user());
    $flash = $result['success'] ? 'success' : 'error';
    return redirect()->back()->with($flash, $result['message']);
    }
}
