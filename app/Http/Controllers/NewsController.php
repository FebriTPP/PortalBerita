<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Collection;
use App\Models\Comment;
use App\Services\NewsService;
use Illuminate\Support\Facades\Log;

class NewsController extends Controller
{
    public function __construct(private NewsService $newsService) {}

    // Logic API key & fetch news dipindah ke NewsService

    /**
     * Display a listing of news articles
     */
    public function index(): JsonResponse|View
    {
    $apiKey = $this->newsService->getApiKey();
        if (!$apiKey) {
            return $this->handleApiKeyError();
        }

    $newsCollection = $this->newsService->getNewsCollection($apiKey);

        if ($newsCollection->isEmpty()) {
            Log::error('Failed to fetch news data');
            return $this->renderEmptyNewsView();
        }

        return view('news.index', [
            'headline' => $newsCollection->first(),
            'groupedNews' => $newsCollection->slice(1)->groupBy('kategori'),
            'availableCategories' => $this->newsService->getAvailableCategories($newsCollection)
        ]);
    }

    /**
     * Display the specified news article
     */
    public function show(string $id): View
    {
    $apiKey = $this->newsService->getApiKey();
        if (!$apiKey) {
            abort(500, 'API Key tidak ditemukan');
        }
    $newsCollection = $this->newsService->getNewsCollection($apiKey);
        $selectedNews = $newsCollection->firstWhere('id', $id);

        if (!$selectedNews) {
            abort(404, 'Berita tidak ditemukan');
        }

    $otherNews = $this->newsService->getRelatedNews($newsCollection, $selectedNews, $id);
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
    $apiKey = $this->newsService->getApiKey();
        if (!$apiKey) {
            abort(500, 'API Key tidak ditemukan');
        }
    $newsCollection = $this->newsService->getNewsCollection($apiKey);

        if ($newsCollection->isEmpty()) {
            return $this->renderEmptyCategoryView($kategori);
        }

    $filteredNews = $this->newsService->filterNewsByCategory($newsCollection, $kategori);
    $originalCategory = $this->newsService->getOriginalCategoryName($newsCollection, $kategori);

        return view('news.kategori', [
            'kategori' => $originalCategory ?? $this->newsService->formatCategoryName($kategori),
            'filteredNews' => $filteredNews,
            'availableCategories' => $this->newsService->getAvailableCategories($newsCollection)
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

    $apiKey = $this->newsService->getApiKey();
        if (!$apiKey) {
            abort(500, 'API Key tidak ditemukan');
        }
    $newsCollection = $this->newsService->getNewsCollection($apiKey);
    $results = $this->newsService->searchNews($newsCollection, $query);

        return view('news.search', [
            'results' => $results,
            'query' => $query
        ]);
    }

    /**
     * Get available categories from news collection
     */
    // getAvailableCategories now in service

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
            'kategori' => $this->newsService->formatCategoryName($kategori),
            'filteredNews' => collect(),
            'availableCategories' => collect(),
            'error' => 'Tidak ada berita yang tersedia dari API.'
        ]);
    }

    /**
     * Get related news for a specific article
     */
    // getRelatedNews now in service

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
    // filterNewsByCategory now in service

    /**
     * Get original category name from slug
     */
    // getOriginalCategoryName now in service

    /**
     * Format category name from slug
     */
    // formatCategoryName now in service
}
