<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class UserService
{
    public const MAX_AVATAR_SIZE = 2048; // KB
    public const ALLOWED_AVATAR_TYPES = ['jpeg','png','jpg','gif'];
    public const DEFAULT_AVATAR = '/avatar/default-avatar.png';
    public const AVATAR_STORAGE_PATH = 'avatars';

    /** Validate profile update */
    public function validateProfile(Request $request): array
    {
        return $request->validate([
            'name' => ['required','string','max:255'],
            'avatar' => ['nullable','image','mimes:'.implode(',',self::ALLOWED_AVATAR_TYPES),'max:'.self::MAX_AVATAR_SIZE],
            'remove_avatar' => ['nullable','in:0,1'],
        ]);
    }

    /** Validate password update */
    public function validatePassword(Request $request): array
    {
        return $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required','confirmed',Rules\Password::defaults()],
        ]);
    }

    /** Update profile (name + avatar) */
    public function updateProfile(Request $request): array
    {
        $validated = $this->validateProfile($request);
        $user = Auth::user();
        if(!$user){ return ['success'=>false,'message'=>'User tidak ditemukan']; }
        try {
            DB::beginTransaction();
            $updates = ['name' => $validated['name']];
            $this->applyAvatarChange($request, $user, $updates);
            User::where('id',$user->id)->update($updates);
            DB::commit();
            Log::info('Profile updated',['user_id'=>$user->id]);
            return ['success'=>true,'message'=>'Profil berhasil diperbarui.'];
        } catch(\Throwable $e){
            DB::rollBack();
            Log::error('Failed to update profile',['user_id'=>$user->id??null,'error'=>$e->getMessage()]);
            return ['success'=>false,'message'=>'Gagal memperbarui profil. Silakan coba lagi.'];
        }
    }

    /** Update password */
    public function updatePassword(Request $request): array
    {
        $validated = $this->validatePassword($request);
        $user = Auth::user(); if(!$user){ return ['success'=>false,'message'=>'User tidak ditemukan']; }
        if(!Hash::check($validated['current_password'],$user->password)){
            return ['success'=>false,'errors'=>['current_password'=>'Password saat ini tidak sesuai']];
        }
        try {
            User::where('id',$user->id)->update(['password'=>Hash::make($validated['new_password'])]);
            Log::info('Password updated',['user_id'=>$user->id]);
            return ['success'=>true,'message'=>'Password berhasil diubah!'];
        } catch(\Throwable $e){
            Log::error('Failed to update password',['user_id'=>$user->id,'error'=>$e->getMessage()]);
            return ['success'=>false,'message'=>'Gagal mengubah password. Silakan coba lagi.'];
        }
    }

    /** Destroy authenticated user account */
    public function destroyAccount(Request $request): array
    {
        $user = Auth::user(); if(!$user){ return ['success'=>false,'message'=>'User tidak ditemukan']; }
        try {
            DB::transaction(function() use ($user){
                Comment::where('user_id',$user->id)->delete();
                $this->removeUserAvatar($user); // delete avatar file if any
                User::destroy($user->id);
            });
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Log::info('User account deleted successfully',['user_id'=>$user->id]);
            return ['success'=>true,'message'=>'Akun Anda berhasil dihapus.'];
        } catch(\Throwable $e){
            Log::error('Failed to delete user account',['user_id'=>$user->id,'error'=>$e->getMessage()]);
            return ['success'=>false,'message'=>'Gagal menghapus akun.'];
        }
    }

    /* ===== Avatar Helpers ===== */
    private function applyAvatarChange(Request $request, User $user, array &$updates): void
    {
        if($request->remove_avatar == '1'){
            $this->removeUserAvatar($user);
            $updates['avatar_url'] = null;
        } elseif ($request->hasFile('avatar')) {
            $this->deleteUserAvatar($user);
            $updates['avatar_url'] = $this->uploadNewAvatar($request->file('avatar'));
        }
    }

    private function removeUserAvatar(User $user): void
    { if($this->hasCustomAvatar($user)){ $this->deleteAvatarFile($user->avatar_url); } }

    private function deleteUserAvatar(User $user): void
    { if($this->hasCustomAvatar($user)){ $this->deleteAvatarFile($user->avatar_url); } }

    private function uploadNewAvatar($avatarFile): string
    {
        $name = time().'_'.uniqid().'.'.$avatarFile->getClientOriginalExtension();
        $path = $avatarFile->storeAs(self::AVATAR_STORAGE_PATH,$name,'public');
        return '/storage/'.$path;
    }

    private function deleteAvatarFile(string $avatarUrl): void
    { $path=str_replace('/storage/','',$avatarUrl); if(Storage::disk('public')->exists($path)){ Storage::disk('public')->delete($path);} }

    private function hasCustomAvatar(User $user): bool
    { return $user->avatar_url && str_starts_with($user->avatar_url,'/storage/'.self::AVATAR_STORAGE_PATH.'/') && $user->avatar_url !== self::DEFAULT_AVATAR; }
}
