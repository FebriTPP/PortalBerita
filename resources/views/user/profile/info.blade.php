<div class="card card-bg-dark border-0">
    <div class="card-body">
        <h5 class="fw-bold border-bottom border-secondary pb-2 mb-4">Informasi Pengguna</h5>
        <div class="row mb-3">
            <div class="col-sm-4 text-muted">Nama Lengkap</div>
            <div class="col-sm-8">{{ $user->name }}</div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-4 text-muted">Email</div>
            <div class="col-sm-8">{{ $user->email }}</div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-4 text-muted">Peran</div>
            <div class="col-sm-8"><span class="badge bg-secondary">Pengguna</span></div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-4 text-muted">Tanggal Bergabung</div>
            <div class="col-sm-8">{{ $user->created_at->translatedFormat('d F Y') }}</div>
        </div>
    </div>
</div>

@if(isset($comments) && $comments->total() > 0)
<div class="card card-bg-dark border mt-4">
    <div class="card-body">
        <h5 class="fw-bold border-bottom border-secondary pb-2 mb-3">
            Riwayat Komentar
            <span class="badge bg-info text-dark ms-2">{{ $comments->total() }}</span>
        </h5>

        @if($comments->count() > 0)
            <div class="comment-list">
                @foreach($comments as $c)
                    <div class="comment-item p-3 mb-3 border border-secondary rounded">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    {{ $c->created_at->diffForHumans() }}
                                </small>
                                <small class="text-muted">
                                    ({{ $c->created_at->format('d M Y, H:i') }})
                                </small>
                            </div>
                            <a href="{{ route('news.show', $c->post_id) }}"
                               class="btn btn-outline-primary btn-sm text-decoration-none"
                               target="_blank">
                                <i class="fas fa-external-link-alt me-1"></i>Lihat Berita
                            </a>
                        </div>
                        <div class="comment-content">
                            <div class="text-body">{{ $c->content }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-secondary">
                <small class="text-muted">
                    Menampilkan {{ $comments->firstItem() ?? 0 }}-{{ $comments->lastItem() ?? 0 }}
                    dari {{ $comments->total() }} komentar
                </small>
                <div>
                    {{ $comments->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-comments fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">Tidak ada komentar di halaman ini.</p>
            </div>
        @endif
    </div>
</div>
@elseif(isset($comments))
    {{-- Debug: Comments variable exists but empty --}}
    <div class="card card-bg-dark border mt-4">
        <div class="card-body text-center py-4">
            <i class="fas fa-comment-slash fa-2x text-muted mb-3"></i>
            <h6 class="text-muted mb-1">Belum Ada Komentar</h6>
            <p class="text-muted small mb-2">Anda belum pernah memberikan komentar pada berita apapun.</p>
            <small class="text-muted">Debug: Total komentar = {{ $comments->total() }}</small>
        </div>
    </div>
@else
    {{-- Debug: Comments variable not set --}}
    <div class="card card-bg-dark border mt-4">
        <div class="card-body text-center py-4">
            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
            <h6 class="text-warning mb-1">Debug: Data Komentar Tidak Ditemukan</h6>
            <p class="text-muted small mb-0">Variable $comments tidak tersedia dari controller.</p>
        </div>
    </div>
@endif
