@extends('layouts.app')

@section('title', $news['judul'] ?? 'Detail Berita')

@section('content')
    <div class="container my-5">
        <div class="row g-5">
            <!-- ======================================= -->
            <!--        KOLOM KONTEN UTAMA (KIRI)        -->
            <!-- ======================================= -->
            <div class="col-lg-8">
                <article>
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('news.kategori', \Illuminate\Support\Str::slug($news['kategori'] ?? 'umum')) }}">{{ $news['kategori'] ?? 'Kategori' }}</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                {{ \Illuminate\Support\Str::limit($news['judul'] ?? '...', 30) }}</li>
                        </ol>
                    </nav>

                    <!-- Kategori Berita -->
                    <a href="{{ route('news.kategori', \Illuminate\Support\Str::slug($news['kategori'] ?? 'umum')) }}"
                        class="badge text-bg-primary text-decoration-none mb-3 fs-6">{{ $news['kategori'] ?? '-' }}</a>

                    <!-- Judul Artikel -->
                    <h1 class="display-5 fw-bolder mb-3">{{ $news['judul'] ?? 'Judul tidak tersedia' }}</h1>

                    <!-- Info Penulis, Tanggal & Tombol Share -->
                    <div
                        class="d-flex flex-wrap align-items-center justify-content-between border-top border-bottom py-3 mb-4">
                        @php
                            $penulis = $news['penulis'] ?? 'Anonim';
                            $initial = strtoupper(substr($penulis, 0, 1));
                        @endphp

                        <div class="d-flex align-items-center mb-2 mb-md-0">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
                                style="width: 40px; height: 40px; font-weight: bold; font-size: 1rem;">
                                {{ $initial }}
                            </div>
                            <div>
                                <span class="fw-semibold">{{ $penulis }}</span>
                                <div class="text-body-secondary small">
                                    Dipublikasikan pada
                                    {{ \Carbon\Carbon::parse($news['created_at'] ?? now())->isoFormat('D MMMM YYYY, HH:mm') }}
                                    WIB
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="small me-2">Bagikan:</span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Bagikan ke Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($news['judul'] ?? 'Berita Menarik') }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Bagikan ke Twitter">
                                <i class="bi bi-twitter"></i>
                            </a>
                            <a href="https://api.whatsapp.com/send?text={{ urlencode($news['judul'] ?? 'Berita Menarik') }}%20{{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Bagikan ke WhatsApp">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                            <button class="btn btn-outline-secondary btn-sm" title="Salin Tautan" onclick="navigator.clipboard.writeText('{{ url()->current() }}'); alert('Tautan disalin ke clipboard!');">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Gambar Utama Berita -->
                    <figure class="mb-4">
                        <img src="https://lh3.googleusercontent.com/d/{{ $news['gambar'] ?? '' }}"
                            alt="{{ $news['judul'] ?? 'Gambar Berita' }}" class="img-fluid rounded-3 shadow"
                            onerror="this.onerror=null;this.src='https://placehold.co/800x450/343a40/dee2e6?text=Gambar+Tidak+Tersedia';">
                    </figure>

                    <!-- Isi Konten Artikel -->
                    <section class="fs-5 lh-lg article-content">
                        {!! $news['deskripsi'] ?? '<p>Konten berita tidak tersedia.</p>' !!}
                    </section>
                </article>

                <hr class="my-5">

                <!-- Bagian Komentar -->
                <section id="komentar">
                    <h3 class="mb-4 fw-bold">Komentar</h3>

                    <!-- Form untuk Menulis Komentar -->
                    <div class="card mb-4 bg-body-tertiary border-0">
                        <div class="card-body">
                            @auth
                                <form action="{{ route('comments.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="post_id" value="{{ $news['id'] }}">
                                    <div class="mb-3">
                                        <textarea name="content" class="form-control @error('content') is-invalid @enderror" rows="3"
                                            placeholder="Tulis komentar Anda..." required>{{ old('content') }}</textarea>
                                        @error('content')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Kirim Komentar
                                    </button>
                                </form>
                            @else
                                <p class="text-muted">
                                    Silakan <a href="{{ route('login') }}" class="text-decoration-none">login</a>
                                    untuk menulis komentar.
                                </p>
                            @endauth
                        </div>
                        <!-- Daftar Komentar yang Sudah Ada -->
                        <div class="d-flex flex-column gap-4">
                            {{-- Tampilkan komentar dari database --}}
                            @forelse ($comments ?? [] as $comment)
                                <div class="d-flex gap-3">
                                    @if ($comment->user && $comment->user->avatar_url)
                                        <img src="{{ $comment->user->avatar_url }}" class="rounded-circle" width="50"
                                            height="50" alt="{{ $comment->user->name ?? 'Anonymous' }}"
                                            style="object-fit: cover;">
                                    @else
                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold"
                                            style="width: 50px; height: 50px;">
                                            {{ strtoupper(substr($comment->user->name ?? 'A', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-grow-1 bg-body-tertiary p-3 rounded-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="fw-bold mb-1">{{ $comment->user->name ?? 'Anonymous' }}</h6>
                                            <small
                                                class="text-body-secondary">{{ $comment->created_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-2">{{ $comment->content }}</p>
                                        @auth
                                            @if (auth()->user()->id === $comment->user_id || (auth()->user()->role ?? null) === 'admin')
                                                <form action="{{ route('comments.destroy', $comment->id) }}" method="POST"
                                                    class="mt-2"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus komentar ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-body-secondary py-4 bg-body-tertiary rounded-3">
                                    <i class="bi bi-chat-dots fs-1 mb-3 d-block"></i>
                                    <p class="mb-0">Belum ada komentar untuk berita ini. Jadilah yang pertama!</p>
                                </div>
                            @endforelse
                        </div>
                </section>
            </div>

            <!-- ======================================= -->
            <!--       KOLOM SIDEBAR (KANAN)             -->
            <!-- ======================================= -->
            <div class="col-lg-4">
                <div class="position-sticky" style="top: 6rem;">

                    <!-- Berita Terkait -->
                    <div class="p-4 mb-4 rounded-3 bg-body-tertiary">
                        <h4 class="fw-bold">Berita Lain di Kategori Ini</h4>
                        <ul class="list-unstyled mb-0">
                            @forelse ($otherNews as $item)
                                <li>
                                    <a href="{{ route('news.show', $item['id']) }}"
                                        class="d-flex align-items-center gap-3 py-3 link-body-emphasis text-decoration-none border-top">
                                        <img src="https://lh3.googleusercontent.com/d/{{ $item['gambar'] ?? '' }}"
                                            width="80" height="60" class="object-fit-cover rounded-2"
                                            alt="{{ $item['judul'] }}"
                                            onerror="this.onerror=null;this.src='https://placehold.co/100x70/343a40/dee2e6?text=...';">
                                        <small
                                            class="fw-semibold">{{ \Illuminate\Support\Str::limit($item['judul'], 55) }}</small>
                                    </a>
                                </li>
                            @empty
                                <li class="border-top pt-3 text-body-secondary">Tidak ada berita terkait lainnya.</li>
                            @endforelse
                        </ul>
                    </div>
                    <!-- Scroll to Top Button -->
                    <button onclick="scrollToTop()" id="scrollTopBtn"
                        class="btn btn-primary shadow-lg rounded-circle position-fixed bottom-0 end-0 m-4"
                        style="display: none; width: 50px; height: 50px; z-index: 1050; opacity: 0.8;">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
