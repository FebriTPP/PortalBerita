@extends('layouts.app')
@section('title', 'Manajemen Komentar')

@section('content')
    <div class="bg-body min-vh-100 py-4">
        <div class="container">
            {{-- Header & Search --}}
            <div class="row align-items-center mb-4 gy-2">
                <div class="col-lg-6 col-sm-12">
                    <h1 class="fw-bold text-body">Manajemen Komentar</h1>
                </div>
                <div class="col-lg-6 col-sm-12 d-flex justify-content-lg-end flex-wrap gap-2">
                    <form method="GET" class="d-flex gap-2" action="">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari user/komentar..." value="{{ request('search') }}">
                        <button class="btn btn-outline-info btn-sm" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    <span class="badge bg-info text-dark align-self-center">
                        Total: {{ $comments->total() }} komentar
                    </span>
                </div>
            </div>

            {{-- Table --}}
            <div class="card bg-body text-body border-0">
                <div class="card-body px-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-body">
                            <thead class="border-bottom border-secondary">
                                <tr>
                                    <th width="18%">User</th>
                                    <th width="25%">Judul Berita</th>
                                    <th width="32%">Komentar</th>
                                    <th width="12%">Tanggal</th>
                                    <th width="13%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($comments as $komen)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($komen->user && $komen->user->avatar_url)
                                                    <img src="{{ $komen->user->avatar_url }}" alt="avatar" class="rounded-circle border object-fit-cover" width="32" height="32">
                                                @else
                                                    <div class="avatar-circle" title="{{ $komen->user->name ?? 'User Tidak Diketahui' }}">
                                                        {{ substr($komen->user->name ?? 'U', 0, 1) }}
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold">{{ $komen->user->name ?? 'User Tidak Diketahui' }}</div>
                                                    <div class="small text-muted">{{ $komen->user->email ?? '-' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if(isset($newsData[$komen->post_id]))
                                                <div class="news-title" title="{{ $newsData[$komen->post_id]['judul'] }}">
                                                    <a href="{{ route('news.show', $komen->post_id) }}" class="text-decoration-none text-primary fw-semibold" target="_blank">
                                                        {{ Str::limit($newsData[$komen->post_id]['judul'], 60) }}
                                                    </a>
                                                    <br>
                                                    <small class="text-muted">{{ $newsData[$komen->post_id]['kategori'] ?? '' }}</small>
                                                </div>
                                            @else
                                                <div class="text-danger fw-semibold" title="Berita tidak ditemukan di API">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Berita tidak ditemukan
                                                </div>
                                            @endif
                                        </td>
                                        <td title="{{ $komen->content }}">
                                            {{ Str::limit($komen->content, 80) }}
                                        </td>
                                        <td class="small text-muted">
                                            {{ $komen->created_at->format('d M Y') }}<br>
                                            {{ $komen->created_at->format('H:i') }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $komen->id }}" title="Hapus komentar">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            <!-- Modal -->
                                            <div class="modal fade" id="deleteModal{{ $komen->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $komen->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $komen->id }}">Konfirmasi Hapus</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Yakin ingin menghapus komentar dari <strong>{{ $komen->user->name ?? 'User Tidak Diketahui' }}</strong>?<br>
                                                            <span class="text-muted small">"{{ Str::limit($komen->content, 80) }}"</span>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <form action="{{ route('admin.komentar.destroy', $komen->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Hapus</button>
                                                            </form>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">Belum ada komentar yang tersedia.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-end mt-3 px-3">
                        {{ $comments->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
