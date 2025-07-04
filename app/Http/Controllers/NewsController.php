<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Comment;

class NewsController extends Controller
{
    private function getApiKey()
    {
        return Cache::remember('winnicode_api_key', 3600, function () {
            $response = Http::post('https://winnicode.com/api/login', [
                'email' => 'dummy@dummy.com',
                'password' => 'dummy'
            ]);

            if ($response->successful()) {
                return $response->json()['api_key'] ?? null;
            }

            Log::error('Login API gagal', ['response' => $response->body()]);
            return null;
        });
    }

    public function index()
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            return response()->json(['error' => 'Gagal mendapatkan API Key'], 500);
        }

        $newsResponse = Http::withToken($apiKey)->get('https://winnicode.com/api/publikasi-berita');

        if (!$newsResponse->successful()) {
            Log::error('Gagal mengambil data berita', ['response' => $newsResponse->body()]);
            return view('news.index', [
                'headline' => null,
                'groupedNews' => collect(),
                'availableCategories' => collect()
            ]);
        }

        $newsCollection = collect($newsResponse->json());
        $headline = $newsCollection->first();
        $groupedNews = $newsCollection->slice(1)->groupBy('kategori');

        // Ambil semua kategori yang tersedia dari API
        $availableCategories = $newsCollection
            ->pluck('kategori')
            ->unique()
            ->filter()
            ->values();

        return view('news.index', [
            'headline' => $headline,
            'groupedNews' => $groupedNews,
            'availableCategories' => $availableCategories
        ]);
    }

    public function show($id)
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            return abort(500, 'API Key tidak ditemukan');
        }

        $response = Http::withToken($apiKey)->get('https://winnicode.com/api/publikasi-berita');
        $newsList = $response->successful() ? $response->json() : [];

        $selectedNews = collect($newsList)->firstWhere('id', $id);

        if (!$selectedNews) {
            return abort(404, 'Berita tidak ditemukan');
        }

        $otherNews = collect($newsList)
            ->where('kategori', $selectedNews['kategori'] ?? null)
            ->where('id', '!=', $id)
            ->take(4)
            ->values()
            ->all();

        // Ambil komentar untuk berita ini
        $comments = Comment::where('post_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('news.show', [
            'news' => $selectedNews,
            'otherNews' => $otherNews,
            'comments' => $comments
        ]);
    }

    public function kategori($kategori)
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            return abort(500, 'API Key tidak ditemukan');
        }

        $response = Http::withToken($apiKey)->get('https://winnicode.com/api/publikasi-berita');
        $newsList = $response->successful() ? $response->json() : [];

        if (empty($newsList)) {
            return view('news.kategori', [
                'kategori' => $kategori,
                'filteredNews' => collect(),
                'availableCategories' => collect(),
                'error' => 'Tidak ada berita yang tersedia dari API.'
            ]);
        }

        // Filter berita sesuai kategori
        // Kategori dari URL sudah dalam bentuk slug, jadi kita bandingkan dengan slug dari kategori berita
        $filteredNews = collect($newsList)->filter(function ($news) use ($kategori) {
            if (!isset($news['kategori'])) {
                return false;
            }

            // Ubah kategori berita menjadi slug dan bandingkan dengan parameter kategori
            $newsKategoriSlug = \Illuminate\Support\Str::slug($news['kategori']);
            return $newsKategoriSlug === $kategori;
        });

        // Ambil kategori asli untuk ditampilkan (bukan slug)
        $originalCategory = null;
        if ($filteredNews->isNotEmpty()) {
            $originalCategory = $filteredNews->first()['kategori'];
        } else {
            // Jika tidak ada berita yang cocok, cari kategori asli dari semua berita
            foreach (collect($newsList) as $news) {
                if (isset($news['kategori']) && \Illuminate\Support\Str::slug($news['kategori']) === $kategori) {
                    $originalCategory = $news['kategori'];
                    break;
                }
            }
        }

        // Ambil semua kategori yang tersedia dari API
        $availableCategories = collect($newsList)
            ->pluck('kategori')
            ->unique()
            ->filter()
            ->values();

        return view('news.kategori', [
            'kategori' => $originalCategory ?? ucfirst(str_replace('-', ' ', $kategori)), // Tampilkan kategori asli atau format yang bagus
            'filteredNews' => $filteredNews,
            'availableCategories' => $availableCategories
        ]);
    }
}
