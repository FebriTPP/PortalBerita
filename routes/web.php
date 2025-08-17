<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Controllers
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =========================
// Public Routes
// =========================

// Homepage
Route::get('/', [NewsController::class, 'index'])->name('news.index');

// News Routes
Route::get('/berita/{id}', [NewsController::class, 'show'])->name('news.show');
Route::get('/kategori/{kategori}', [NewsController::class, 'kategori'])->name('news.kategori');

// Search Route
Route::get('/search', [NewsController::class, 'search'])->name('news.search');

// =========================
// Authentication Routes
// =========================

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

// Register Routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

// Logout Route
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

// =========================
// Comment Routes (Auth Required)
// =========================

Route::middleware('auth')->group(function () {
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

// =========================
// Admin Routes (Auth + Role Required)
// =========================

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // User Management
    Route::get('/create-user', [AdminController::class, 'createUser'])->name('admin.profile.create');
    Route::post('/store-user', [AdminController::class, 'storeUser'])->name('admin.store-user');
    Route::delete('/delete-user/{user}', [AdminController::class, 'deleteUser'])->name('admin.delete-user');

    // Profile Management
    Route::get('/profile/{id}/edit', [AdminController::class, 'editProfile'])->name('admin.profile.edit');
    Route::put('/profile/{id}', [AdminController::class, 'updateProfile'])->name('admin.profile.update');

    // Comment Management
    Route::get('/komentar', [AdminController::class, 'komentarManajemen'])->name('admin.komentar.index');
    Route::delete('/komentar/{id}', [AdminController::class, 'destroyComment'])->name('admin.komentar.destroy');

    // Admin API Routes for Quick Actions
    Route::post('/api/test-connection', [AdminController::class, 'testApiConnection'])->name('admin.api.test');
    Route::post('/api/refresh-key', [AdminController::class, 'refreshApiKey'])->name('admin.api.refresh');
    Route::post('/api/cache-status', [AdminController::class, 'getCacheStatus'])->name('admin.api.cache');

});

// =========================
// User Routes (Auth + Role Required)
// =========================

Route::middleware(['auth', 'role:user'])->prefix('user')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('user.profile.show');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [UserController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/delete', [UserController::class, 'destroy'])->name('user.destroy');
});

// =========================
// Dashboard Redirect Route
// =========================

Route::get('/dashboard', function () {
    if (Auth::check()) {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('user.profile.show');
        }
    }
    return redirect()->route('login');
})->name('dashboard')->middleware('auth');
