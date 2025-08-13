@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
    <style>
        /* Fix chart stretching */
        .chart-wrapper { position: relative; width: 100%; height: 260px; }
        @media (min-width: 992px) { .chart-wrapper.chart-tall { height: 320px; } }
    </style>
    <div class="bg-body min-vh-100 py-4">
        <div class="container">
            {{-- Header --}}
            <div class="row align-items-center mb-4">
                <div class="col-12">
                    <h1 class="fw-bold text-body mb-1">Dashboard Admin</h1>
                    <p class="text-muted">Kelola pengguna dan pantau integrasi API</p>
                </div>
            </div>

            {{-- API Status Cards --}}
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 h-100 @if($apiStatus['is_healthy']) bg-success-subtle @else bg-danger-subtle @endif">
                        <div class="card-body text-center">
                            <div class="display-6 mb-2">
                                @if($apiStatus['is_healthy'])
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                @endif
                            </div>
                            <h6 class="card-title fw-bold">Status API</h6>
                            <p class="card-text small mb-0">
                                @if($apiStatus['is_healthy'])
                                    <span class="text-success fw-semibold">Aktif</span>
                                @else
                                    <span class="text-danger fw-semibold">Error</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 h-100 bg-info-subtle">
                        <div class="card-body text-center">
                            <div class="display-6 mb-2">
                                <i class="bi bi-speedometer2 text-info"></i>
                            </div>
                            <h6 class="card-title fw-bold">Latensi</h6>
                            <p class="card-text small mb-0">
                                <span class="fw-bold">{{ $apiStatus['latency_ms'] }}ms</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 h-100 @if(isset($apiStatus['rate_limit_remaining']) && $apiStatus['rate_limit_remaining'] < 50) bg-danger-subtle @elseif(isset($apiStatus['rate_limit_remaining']) && $apiStatus['rate_limit_remaining'] < 200) bg-warning-subtle @else bg-info-subtle @endif">
                        <div class="card-body text-center">
                            <div class="display-6 mb-2">
                                @if(isset($apiStatus['rate_limit_remaining']) && $apiStatus['rate_limit_remaining'] < 50)
                                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                @elseif(isset($apiStatus['rate_limit_remaining']) && $apiStatus['rate_limit_remaining'] < 200)
                                    <i class="bi bi-hourglass-split text-warning"></i>
                                @else
                                    <i class="bi bi-speedometer text-info"></i>
                                @endif
                            </div>
                            <h6 class="card-title fw-bold">Rate Limit</h6>
                            <p class="card-text small mb-0">
                                @if($apiStatus['rate_limit_remaining'])
                                    <span class="fw-bold @if($apiStatus['rate_limit_remaining'] < 50) text-danger @elseif($apiStatus['rate_limit_remaining'] < 200) text-warning @else text-info @endif">
                                        {{ $apiStatus['rate_limit_remaining'] }}
                                    </span>
                                    @if($apiStatus['rate_limit_total'])
                                        <span class="text-muted">/ {{ $apiStatus['rate_limit_total'] }}</span>
                                    @endif
                                    <span class="text-muted d-block">tersisa</span>
                                    @if($apiStatus['rate_limit_reset_time'])
                                        <small class="text-muted">Reset: {{ $apiStatus['rate_limit_reset_time'] }}</small>
                                    @endif
                                @else
                                    <span class="fw-bold">{{ $apiStatus['calls_today'] ?? 0 }}</span>
                                    {{-- <span class="text-muted d-block">panggilan hari ini</span> --}}
                                    {{-- <small class="text-muted">No limit detected</small> --}}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 h-100 bg-primary-subtle">
                        <div class="card-body text-center">
                            <div class="display-6 mb-2">
                                <i class="bi bi-arrow-clockwise text-primary"></i>
                            </div>
                            <h6 class="card-title fw-bold">Refresh Berikutnya</h6>
                            <p class="card-text small mb-0">
                                <span class="fw-bold">{{ $apiStatus['next_refresh_time'] }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions for Admin --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header bg-body border-0">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="bi bi-lightning-charge me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-6">
                                    <button type="button" class="btn btn-outline-primary w-100 p-3" id="testApiBtn">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="bi bi-wifi fs-4 me-3"></i>
                                            <div class="text-start">
                                                <div class="fw-bold">Tes API</div>
                                                <small class="text-muted">Cek koneksi API real-time</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <button type="button" class="btn btn-outline-warning w-100 p-3" id="refreshApiKeyBtn">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="bi bi-key fs-4 me-3"></i>
                                            <div class="text-start">
                                                <div class="fw-bold">Perbarui API Key</div>
                                                <small class="text-muted">Refresh token autentikasi</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <button type="button" class="btn btn-outline-success w-100 p-3" id="checkCacheBtn">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="bi bi-hdd-stack fs-4 me-3"></i>
                                            <div class="text-start">
                                                <div class="fw-bold">Cek Status Cache</div>
                                                <small class="text-muted">Analisis performa cache</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            {{-- Action Results Display --}}
                            <div id="actionResults" class="mt-3" style="display: none;">
                                <div class="alert alert-info" id="actionAlert">
                                    <div class="d-flex align-items-center">
                                        <div class="spinner-border spinner-border-sm me-2" id="actionSpinner"></div>
                                        <span id="actionMessage">Processing...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- API Status Detail --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header bg-body border-0">
                            <h5 class="card-title mb-0 fw-bold">Status Integrasi API</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Status Code Terakhir</small>
                                    @if($apiStatus['last_status_code'])
                                        <span class="badge @if($apiStatus['last_status_code'] == 200) bg-success @else bg-danger @endif">
                                            {{ $apiStatus['last_status_code'] }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">API Key Kedaluwarsa</small>
                                    @if($apiStatus['api_key_expires_at'])
                                        <span class="fw-semibold">{{ $apiStatus['api_key_expires_at'] }}</span>
                                    @else
                                        <span class="text-muted">Auto-refresh</span>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Rate Limit Status</small>
                                    @if($apiStatus['rate_limit_remaining'])
                                        <span class="fw-semibold @if($apiStatus['rate_limit_remaining'] < 50) text-danger @elseif($apiStatus['rate_limit_remaining'] < 200) text-warning @else text-success @endif">
                                            {{ $apiStatus['rate_limit_remaining'] }}@if($apiStatus['rate_limit_total'])/{{ $apiStatus['rate_limit_total'] }}@endif
                                        </span>
                                        @if($apiStatus['rate_limit_reset_time'])
                                            <small class="text-muted d-block">Reset: {{ $apiStatus['rate_limit_reset_time'] }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">{{ $apiStatus['calls_today'] ?? 0 }} calls today</span>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Terakhir Dicek</small>
                                    <span class="fw-semibold">{{ $apiStatus['checked_at'] }}</span>
                                </div>
                                @if($apiStatus['last_error'])
                                <div class="col-12">
                                    <small class="text-muted d-block">Error Terakhir</small>
                                    <span class="text-danger small">{{ $apiStatus['last_error'] }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Summary Cards --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header bg-body border-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold">Ringkasan Cepat</h5>
                            <small class="text-muted">Update: {{ $summary['last_updated'] }}</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex align-items-center p-3 bg-primary-subtle rounded-3">
                                        <div class="me-3">
                                            <i class="bi bi-newspaper fs-2 text-primary"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-bold mb-0 text-primary">{{ number_format($summary['total_articles']) }}</h4>
                                            <small class="text-muted">Total Artikel (Cache)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex align-items-center p-3 bg-success-subtle rounded-3">
                                        <div class="me-3">
                                            <i class="bi bi-graph-up fs-2 text-success"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-bold mb-0 text-success">{{ number_format($summary['traffic_today']) }}</h4>
                                            <small class="text-muted">Trafik Hari Ini</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex align-items-center p-3 @if($summary['api_errors_24h'] > 0) bg-warning-subtle @else bg-info-subtle @endif rounded-3">
                                        <div class="me-3">
                                            <i class="bi bi-exclamation-triangle fs-2 @if($summary['api_errors_24h'] > 0) text-warning @else text-info @endif"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-bold mb-0 @if($summary['api_errors_24h'] > 0) text-warning @else text-info @endif">{{ $summary['api_errors_24h'] }}</h4>
                                            <small class="text-muted">Error API (24h)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex align-items-center p-3 bg-secondary-subtle rounded-3">
                                        <div class="me-3">
                                            <i class="bi bi-lightning fs-2 text-secondary"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-bold mb-0 text-secondary">{{ $summary['cache_efficiency'] }}%</h4>
                                            <small class="text-muted">Efisiensi Cache</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Analytics Charts Section --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header bg-body border-0">
                            <h5 class="card-title mb-0 fw-bold">Analytics & Monitoring</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                {{-- Cache Performance --}}
                                <div class="col-lg-4">
                                    <h6 class="fw-semibold mb-3">Cache Performance</h6>
                                    <div class="text-center">
                                        <div class="chart-wrapper">
                                            <canvas id="cacheChart"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Hit Rate:</small>
                                                <strong class="text-success">{{ $analytics['cache_stats']['hit_rate'] }}%</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Total Requests:</small>
                                                <strong>{{ $analytics['cache_stats']['total'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- External API Requests --}}
                                <div class="col-lg-8">
                                    <h6 class="fw-semibold mb-3">Request Eksternal (24 Jam Terakhir)</h6>
                                    <div class="chart-wrapper chart-tall">
                                        <canvas id="requestsChart"></canvas>
                                    </div>
                                    <div class="row mt-3 text-center">
                                        <div class="col-4">
                                            <div class="border-end">
                                                <small class="text-muted d-block">Hari Ini</small>
                                                <strong class="text-primary">{{ $analytics['external_requests']['today'] }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border-end">
                                                <small class="text-muted d-block">Kemarin</small>
                                                <strong>{{ $analytics['external_requests']['yesterday'] }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">Minggu Ini</small>
                                            <strong class="text-success">{{ $analytics['external_requests']['total_this_week'] }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Top Categories --}}
                            <div class="row">
                                <div class="col-lg-6">
                                    <h6 class="fw-semibold mb-3">Top Kategori Berita</h6>
                                    <div class="chart-wrapper">
                                        <canvas id="categoriesChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <h6 class="fw-semibold mb-3">Kategori Terpopuler</h6>
                                    <div class="list-group list-group-flush">
                                        @foreach(array_slice(array_combine($analytics['top_categories']['labels'], $analytics['top_categories']['data']), 0, 5) as $category => $hits)
                                        <div class="list-group-item border-0 px-0 d-flex justify-content-between align-items-center">
                                            <span>{{ $category }}</span>
                                            <span class="badge bg-primary rounded-pill">{{ $hits }} hits</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- User Management Section --}}
            <div class="row align-items-center mb-3">
                <div class="col-lg-8">
                    <h3 class="fw-bold text-body mb-0">Manajemen Pengguna</h3>
                </div>
                <div class="col-lg-4 d-flex justify-content-lg-end mt-2 mt-lg-0">
                    {{-- Search --}}
                    <form class="d-flex flex-grow-1 flex-md-grow-0" method="GET" action="{{ route('admin.dashboard') }}">
                        <div class="input-group">
                            <span class="input-group-text bg-body border-secondary text-body">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control bg-body text-body border-secondary"
                                placeholder="Cari pengguna..." value="{{ request('search') }}">
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="card bg-body text-body border-0">
                <div class="card-body px-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-body">
                            <thead>
                                <tr>
                                    <th class="fw-semibold">Pengguna</th>
                                    <th class="fw-semibold">Tanggal Bergabung</th>
                                    <th class="fw-semibold">Peran</th>
                                    <th class="fw-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                @if ($user->avatar_url)
                                                    <img src="{{ $user->avatar_url }}"
                                                        alt="avatar" class="rounded-circle object-fit-cover" width="44"
                                                        height="44">
                                                @else
                                                    <div class="avatar-circle">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold text-body">{{ $user->name }}</div>
                                                    <div class="text-muted small">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small">
                                            {{ $user->joined_at ? \Carbon\Carbon::parse($user->joined_at)->translatedFormat('d F Y') : '-' }}
                                        </td>
                                        <td>
                                            @if ($user->role === 'admin')
                                                <span class="badge bg-primary">Admin</span>
                                            @else
                                                <span class="badge bg-secondary text-white">Pengguna</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.profile.edit', $user->id) }}"
                                                    class="btn btn-outline-warning btn-sm" title="Edit Profile">
                                                    <i class="fas fa-user-edit"></i>
                                                </a>

                                                @if ($user->role !== 'admin')
                                                    <form action="{{ route('admin.delete-user', $user) }}" method="POST"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                                            title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button class="btn btn-outline-secondary btn-sm" disabled title="Admin tidak dapat dihapus">
                                                        <i class="fas fa-shield-alt"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-end mt-3 px-3">
                        {{ $users->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart.js Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Set Analytics Data for Admin Dashboard --}}
    <script>
        // Make analytics data available globally for app.js
        window.adminAnalyticsData = {
            cache_stats: {
                hits: {{ $analytics['cache_stats']['hits'] }},
                misses: {{ $analytics['cache_stats']['misses'] }}
            },
            hourly_requests: {
                labels: {!! json_encode($analytics['hourly_requests']['labels']) !!},
                data: {!! json_encode($analytics['hourly_requests']['data']) !!}
            },
            top_categories: {
                labels: {!! json_encode($analytics['top_categories']['labels']) !!},
                data: {!! json_encode($analytics['top_categories']['data']) !!}
            }
        };
    </script>

    {{-- Admin Dashboard will be initialized by app.js automatically --}}
@endsection
