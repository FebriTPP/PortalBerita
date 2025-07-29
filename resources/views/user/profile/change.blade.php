<div class="card card-bg-dark border-0">
    <div class="card-body">
        <h5 class="fw-bold mb-4">Ubah Password</h5>

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Password Saat Ini</label>
                <input type="password" name="current_password"
                    class="form-control @error('current_password') is-invalid @enderror">
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="new_password"
                    class="form-control @error('new_password') is-invalid @enderror" id="new_password">
                @error('new_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Minimal 8 karakter</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="new_password_confirmation"
                    class="form-control @error('new_password_confirmation') is-invalid @enderror"
                    id="new_password_confirmation">
                @error('new_password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key"></i> Simpan Password
                </button>
            </div>
        </form>
    </div>
</div>
