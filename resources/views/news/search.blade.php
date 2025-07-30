@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Hasil Pencarian</h1>

    @if (isset($error))
        <div class="alert alert-warning">{{ $error }}</div>
    @endif

    @if ($results->isEmpty())
        <div class="text-center text-muted">
            <i class="bi bi-search display-1"></i>
            <p class="mt-3">Tidak ada hasil yang ditemukan untuk "<strong>{{ $query }}</strong>".</p>
        </div>
    @else
        <p class="text-muted">Menampilkan {{ $results->count() }} hasil untuk "<strong>{{ $query }}</strong>".</p>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            @foreach ($results as $news)
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">{{ $news['judul'] }}</h5>
                            <p class="card-text text-muted mb-2">Kategori: {{ $news['kategori'] }}</p>
                            <p class="card-text text-muted">Penulis: {{ $news['penulis'] }}</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-end">
                            <a href="{{ route('news.show', $news['id']) }}" class="btn btn-primary">Baca Selengkapnya</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
