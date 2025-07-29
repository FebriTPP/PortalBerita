<div class="card card-bg-dark border-0">
    <div class="card-body">
        <h5 class="fw-bold mb-4">Edit Profil</h5>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Nama</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                    value="{{ old('name', $user->name) }}">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="{{ $user->email }}" readonly disabled>
                <small class="form-text text-muted">Email tidak dapat diubah</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Foto Profil</label>
                @php
                    $currentUser = App\Models\User::find($user->id);
                    $hasRealAvatar =
                        $currentUser &&
                        $currentUser->getAttributes()['avatar_url'] &&
                        $currentUser->getAttributes()['avatar_url'] !== '/avatar/default-avatar.png' &&
                        strpos($currentUser->getAttributes()['avatar_url'], '/storage/avatars/') === 0;
                @endphp
                @if ($hasRealAvatar)
                    <div class="mb-3">
                        <img src="{{ $currentUser->getAttributes()['avatar_url'] }}" alt="Avatar saat ini"
                            class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;"
                            id="current-avatar">
                        <small class="d-block text-muted">Avatar saat ini</small>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAvatar()">
                                <i class="fas fa-trash"></i> Hapus Avatar
                            </button>
                        </div>
                    </div>
                @endif
                <input type="file" class="form-control @error('avatar') is-invalid @enderror" name="avatar"
                    accept="image/*" id="avatar-input">
                <input type="hidden" name="remove_avatar" id="remove-avatar" value="0">
                @error('avatar')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>

                <!-- Preview untuk avatar baru -->
                <div id="avatar-preview" class="mt-3" style="display: none;">
                    <img id="preview-img" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                    <small class="d-block text-muted">Preview avatar baru</small>
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
