<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\Comment;

class NewsController extends Controller
{
    private const API_BASE_URL = 'https://winnicode.com/api';
    private const API_KEY_CACHE_DURATION = 3600; // 1 hour
    private const HTTP_TIMEOUT = 10; // seconds
    private const RELATED_NEWS_LIMIT = 4;
    private const LOGIN_EMAIL = 'dummy@dummy.com';
    private const LOGIN_PASSWORD = 'dummy';

    /**
     * Get cached API key for external news service
     */
    private function getApiKey(): ?string
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
    private function getNewsCollection(?string $apiKey): Collection
    {
        if (!$apiKey) {
            return collect();
        }

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
            return is_array($data) ? collect($data) : collect();

        } catch (\Exception $e) {
            Log::error('News fetch exception', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Display a listing of news articles
     */
    public function index(): JsonResponse|View
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            return $this->handleApiKeyError();
        }

        $newsCollection = $this->getNewsCollection($apiKey);

        if ($newsCollection->isEmpty()) {
            Log::error('Failed to fetch news data');
            return $this->renderEmptyNewsView();
        }

        return view('news.index', [
            'headline' => $newsCollection->first(),
            'groupedNews' => $newsCollection->slice(1)->groupBy('kategori'),
            'availableCategories' => $this->getAvailableCategories($newsCollection)
        ]);
    }

    /**
     * Display the specified news article
     */
    public function show(string $id): View
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            abort(500, 'API Key tidak ditemukan');
        }

        $newsCollection = $this->getNewsCollection($apiKey);
        $selectedNews = $newsCollection->firstWhere('id', $id);

        if (!$selectedNews) {
            abort(404, 'Berita tidak ditemukan');
        }

        $otherNews = $this->getRelatedNews($newsCollection, $selectedNews, $id);
        $comments = $this->getNewsComments($id);

        return view('news.show', [
            'news' => $selectedNews,
            'otherNews' => $otherNews,
            'comments' => $comments
        ]);
    }

    /**
     * Display news articles by category
     */
    public function kategori(string $kategori): View
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            abort(500, 'API Key tidak ditemukan');
        }

        $newsCollection = $this->getNewsCollection($apiKey);

        if ($newsCollection->isEmpty()) {
            return $this->renderEmptyCategoryView($kategori);
        }

        $filteredNews = $this->filterNewsByCategory($newsCollection, $kategori);
        $originalCategory = $this->getOriginalCategoryName($newsCollection, $kategori);

        return view('news.kategori', [
            'kategori' => $originalCategory ?? $this->formatCategoryName($kategori),
            'filteredNews' => $filteredNews,
            'availableCategories' => $this->getAvailableCategories($newsCollection)
        ]);
    }

    /**
     * Search news articles based on query
     */
    public function search(Request $request): View
    {
        $query = $request->input('q');

        if (!$query) {
            return view('news.search', [
                'results' => collect(),
                'query' => $query,
                'error' => 'Masukkan kata kunci untuk pencarian.'
            ]);
        }

        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            abort(500, 'API Key tidak ditemukan');
        }

        $newsCollection = $this->getNewsCollection($apiKey);

        $results = $newsCollection->filter(function ($news) use ($query) {
            return Str::contains(strtolower($news['judul'] ?? ''), strtolower($query)) ||
                   Str::contains(strtolower($news['kategori'] ?? ''), strtolower($query)) ||
                   Str::contains(strtolower($news['penulis'] ?? ''), strtolower($query));
        });

        return view('news.search', [
            'results' => $results,
            'query' => $query
        ]);
    }

    /**
     * Get available categories from news collection
     */
    private function getAvailableCategories(Collection $newsCollection): Collection
    {
        return $newsCollection
            ->pluck('kategori')
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Handle API key validation error
     */
    private function handleApiKeyError(): JsonResponse
    {
        return response()->json(['error' => 'Failed to get API Key'], 500);
    }

    /**
     * Render empty news view for index page
     */
    private function renderEmptyNewsView(): View
    {
        return view('news.index', [
            'headline' => null,
            'groupedNews' => collect(),
            'availableCategories' => collect()
        ]);
    }

    /**
     * Render empty category view
     */
    private function renderEmptyCategoryView(string $kategori): View
    {
        return view('news.kategori', [
            'kategori' => $this->formatCategoryName($kategori),
            'filteredNews' => collect(),
            'availableCategories' => collect(),
            'error' => 'Tidak ada berita yang tersedia dari API.'
        ]);
    }

    /**
     * Get related news for a specific article
     */
    private function getRelatedNews(Collection $newsCollection, array $selectedNews, string $excludeId): Collection
    {
        return $newsCollection
            ->where('kategori', $selectedNews['kategori'] ?? null)
            ->where('id', '!=', $excludeId)
            ->take(self::RELATED_NEWS_LIMIT)
            ->values();
    }

    /**
     * Get comments for a specific news article
     */
    private function getNewsComments(string $postId): Collection
    {
        return Comment::where('post_id', $postId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Filter news by category slug
     */
    private function filterNewsByCategory(Collection $newsCollection, string $kategoriSlug): Collection
    {
        return $newsCollection->filter(function ($news) use ($kategoriSlug) {
            return isset($news['kategori']) && Str::slug($news['kategori']) === $kategoriSlug;
        });
    }

    /**
     * Get original category name from slug
     */
    private function getOriginalCategoryName(Collection $newsCollection, string $kategoriSlug): ?string
    {
        $filteredNews = $this->filterNewsByCategory($newsCollection, $kategoriSlug);

        return $filteredNews->isNotEmpty()
            ? $filteredNews->first()['kategori']
            : null;
    }

    /**
     * Format category name from slug
     */
    private function formatCategoryName(string $kategoriSlug): string
    {
        return ucfirst(str_replace('-', ' ', $kategoriSlug));
    }
}
