<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class AdminUserService
{
    public const USERS_PER_PAGE = 10;
    public const MAX_AVATAR_SIZE = 2048; // KB
    public const ALLOWED_AVATAR_TYPES = ['jpeg','png','jpg','gif'];
    public const DEFAULT_AVATAR = '/avatar/default-avatar.png';
    public const AVATAR_STORAGE_PATH = 'avatars';

    public function getUsersWithSearch(?string $search)
    {
        $q = User::query();
        if($search){
            $q->where(function($qq) use ($search){
                $qq->where('name','like',"%{$search}%")
                   ->orWhere('email','like',"%{$search}%")
                   ->orWhere('role','like',"%{$search}%")
                   ->orWhere('id',$search);
            });
        }
        return $q->orderByDesc('created_at')->paginate(self::USERS_PER_PAGE);
    }

    public function validateUserData(Request $request): array
    {
        return $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','string','lowercase','email','max:255','unique:users'],
            'password' => ['required','confirmed',Rules\Password::defaults()],
            'role' => ['required','in:admin,user'],
            'avatar' => ['nullable','image','mimes:'.implode(',',self::ALLOWED_AVATAR_TYPES),'max:'.self::MAX_AVATAR_SIZE],
        ]);
    }

    public function storeUser(array $validated, Request $request): array
    {
        try {
            DB::beginTransaction();
            $avatarPath = $this->handleAvatarUpload($request);
            $tipe = $validated['role']==='admin' ? 'Admin' : 'User';
            User::create([
                'name'=>$validated['name'],
                'email'=>$validated['email'],
                'password'=>Hash::make($validated['password']),
                'role'=>$validated['role'],
                'joined_at'=>now(),
                'avatar_url'=>$avatarPath,
                'email_verified_at'=>now(),
            ]);
            DB::commit();
            Log::info('New user created',['email'=>$validated['email'],'role'=>$validated['role']]);
            return ['success'=>true,'message'=>"$tipe berhasil ditambahkan!"];
        } catch(\Throwable $e){
            DB::rollBack();
            Log::error('Failed to create user',['error'=>$e->getMessage()]);
            $tipe = $validated['role']==='admin' ? 'Admin' : 'User';
            return ['success'=>false,'message'=>"Gagal menambahkan $tipe. Silakan coba lagi."];
        }
    }

    public function deleteUser(User $user): array
    {
        if($this->isProtectedUser($user)){
            return ['success'=>false,'message'=>'Tidak dapat menghapus admin atau user yang dilindungi!'];
        }
        try {
            DB::beginTransaction();
            $this->deleteUserAvatar($user);
            $user->delete();
            DB::commit();
            Log::info('User deleted',['user_id'=>$user->id,'email'=>$user->email]);
            return ['success'=>true,'message'=>'User berhasil dihapus!'];
        } catch(\Throwable $e){
            DB::rollBack();
            Log::error('Failed to delete user',['user_id'=>$user->id,'error'=>$e->getMessage()]);
            return ['success'=>false,'message'=>'Gagal menghapus user. Silakan coba lagi.'];
        }
    }

    public function findUserOrFail(int $id): User
    {
        return User::findOrFail($id);
    }

    public function updateProfile(Request $request, int $id): array
    {
        $admin = $this->findUserOrFail($id);
        $validated = $this->validateProfileData($request);
        try {
            DB::beginTransaction();
            $admin->name = $validated['name'];
            $this->handleProfileAvatarUpdate($request,$admin);
            $this->handlePasswordUpdate($request,$admin,$validated);
            $admin->save();
            DB::commit();
            Log::info('Profile updated',['user_id'=>$admin->id]);
            return ['success'=>true,'user'=>$admin,'message'=>'Profil berhasil diperbarui!'];
        } catch(\Throwable $e){
            DB::rollBack();
            Log::error('Failed to update profile',['user_id'=>$admin->id,'error'=>$e->getMessage()]);
            return ['success'=>false,'message'=>'Gagal memperbarui profil. Silakan coba lagi.'];
        }
    }

    private function validateProfileData(Request $request): array
    {
        $rules=[
            'name'=>['required','string','max:255'],
            'avatar'=>['nullable','image','mimes:'.implode(',',self::ALLOWED_AVATAR_TYPES),'max:'.self::MAX_AVATAR_SIZE],
            'password'=>['nullable','confirmed','min:8',Rules\Password::defaults()],
            'password_confirmation'=>['nullable'],
            'remove_avatar'=>['nullable','in:0,1'],
        ];
        if($request->filled('password')){ $rules['current_password']=['required','string']; }
        return $request->validate($rules,[
            'password.confirmed'=>'Konfirmasi password tidak cocok.',
            'password.min'=>'Password minimal 8 karakter.',
            'current_password.required'=>'Password saat ini harus diisi untuk mengubah password.',
        ]);
    }

    private function handleAvatarUpload(Request $request): ?string
    {
        if(!$request->hasFile('avatar')){ return null; }
        $avatar=$request->file('avatar');
        $avatarName=time().'_'.uniqid().'.'.$avatar->getClientOriginalExtension();
        $avatarPath=$avatar->storeAs(self::AVATAR_STORAGE_PATH,$avatarName,'public');
        return '/storage/'.$avatarPath;
    }

    private function handleProfileAvatarUpdate(Request $request, User $user): void
    {
        if($request->remove_avatar=='1'){ $this->removeUserAvatar($user); }
        elseif ($request->hasFile('avatar')) { $this->deleteUserAvatar($user); $user->avatar_url=$this->handleAvatarUpload($request); }
    }

    private function removeUserAvatar(User $user): void
    {
        if($this->hasCustomAvatar($user)){ $this->deleteAvatarFile($user->avatar_url); }
        $user->avatar_url=null;
    }

    private function deleteUserAvatar(User $user): void
    {
        if($this->hasCustomAvatar($user)){ $this->deleteAvatarFile($user->avatar_url); }
    }

    private function deleteAvatarFile(string $avatarUrl): void
    {
        $path=str_replace('/storage/','',$avatarUrl); if(Storage::disk('public')->exists($path)){ Storage::disk('public')->delete($path);} }

    private function hasCustomAvatar(User $user): bool
    { return $user->avatar_url && str_starts_with($user->avatar_url,'/storage/'.self::AVATAR_STORAGE_PATH.'/') && $user->avatar_url!==self::DEFAULT_AVATAR; }

    private function handlePasswordUpdate(Request $request, User $user, array $validated): void
    {
        if(!empty($validated['password'])){
            if($request->filled('current_password') && !Hash::check($request->current_password,$user->password)){
                throw new \Exception('Password saat ini tidak benar.');
            }
            $user->password = Hash::make($validated['password']);
        }
    }

    private function isProtectedUser(User $user): bool
    { return $user->role==='admin' || $user->id===Auth::id(); }
}
