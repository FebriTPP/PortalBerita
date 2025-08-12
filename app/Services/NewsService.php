<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class NewsService
{
    private const API_BASE_URL = 'https://winnicode.com/api';
    private const API_KEY_CACHE_DURATION = 3600; // 1 hour
    private const HTTP_TIMEOUT = 10; // seconds
    private const RELATED_NEWS_LIMIT = 4;
    private const NEWS_CACHE_DURATION = 60; // seconds cache list berita
    private const LOGIN_EMAIL = 'dummy@dummy.com';
    private const LOGIN_PASSWORD = 'dummy';

    /**
     * Get cached API key for external news service
     */
    public function getApiKey(): ?string
    {
        return Cache::remember('winnicode_api_key', self::API_KEY_CACHE_DURATION, function () {
            try {
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->post(self::API_BASE_URL . '/login', [
                        'email' => self::LOGIN_EMAIL,
                        'password' => self::LOGIN_PASSWORD
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['api_key'] ?? null;
                }

                Log::error('API login failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('API login exception', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Fetch news collection from external API
     */
    public function getNewsCollection(?string $apiKey): Collection
    {
        if (!$apiKey) {
            return collect();
        }

        return Cache::remember('winnicode_news_collection', self::NEWS_CACHE_DURATION, function () use ($apiKey) {
            try {
                $response = Http::withToken($apiKey)
                    ->timeout(self::HTTP_TIMEOUT)
                    ->get(self::API_BASE_URL . '/publikasi-berita');

                if (!$response->successful()) {
                    Log::error('Failed to fetch news', [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return collect();
                }

                $data = $response->json();
                if (!is_array($data)) {
                    Log::warning('Unexpected news payload structure', ['payload_type' => gettype($data)]);
                    return collect();
                }
                return collect($data);
            } catch (\Exception $e) {
                Log::error('News fetch exception', ['error' => $e->getMessage()]);
                return collect();
            }
        });
    }

    /**
     * Get available categories from news collection
     */
    public function getAvailableCategories(Collection $newsCollection): Collection
    {
        return $newsCollection->pluck('kategori')->filter()->unique()->values();
    }

    /** Filter news by category slug */
    public function filterNewsByCategory(Collection $newsCollection, string $kategoriSlug): Collection
    {
        $slug = Str::slug($kategoriSlug); // normalisasi tambahan (kalau user kirim slug mentah)
        return $newsCollection->filter(function ($news) use ($slug) {
            return isset($news['kategori']) && Str::slug($news['kategori']) === $slug;
        });
    }

    /** Get original category name from slug */
    public function getOriginalCategoryName(Collection $newsCollection, string $kategoriSlug): ?string
    {
        $filtered = $this->filterNewsByCategory($newsCollection, $kategoriSlug);
        return $filtered->isNotEmpty() ? ($filtered->first()['kategori'] ?? null) : null;
    }

    /** Format category name from slug */
    public function formatCategoryName(string $kategoriSlug): string
    {
        return ucfirst(str_replace('-', ' ', $kategoriSlug));
    }

    /** Get related news */
    public function getRelatedNews(Collection $newsCollection, array $selectedNews, string $excludeId): Collection
    {
        return $newsCollection
            ->where('kategori', $selectedNews['kategori'] ?? null)
            ->where('id', '!=', $excludeId)
            ->take(self::RELATED_NEWS_LIMIT)
            ->values();
    }

    /** Search news in collection */
    public function searchNews(Collection $newsCollection, string $query): Collection
    {
        $q = trim(strtolower($query));
        if ($q === '') {
            return collect();
        }
        return $newsCollection->filter(function ($news) use ($q) {
            $judul = strtolower($news['judul'] ?? '');
            $kategori = strtolower($news['kategori'] ?? '');
            $penulis = strtolower($news['penulis'] ?? '');
            return Str::contains($judul, $q) || Str::contains($kategori, $q) || Str::contains($penulis, $q);
        })->values();
    }
}
