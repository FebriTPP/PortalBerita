<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminAnalyticsService
{
    /**
     * API health status (cached 30s)
     */
    public function getApiHealthStatus(): array
    {
        return Cache::remember('admin_api_health_status', 30, function () {
            $start = microtime(true);
            $status = [
                'is_healthy' => false,
                'latency_ms' => 0,
                'last_status_code' => null,
                'last_error' => null,
                'api_key_expires_at' => null,
                'rate_limit_remaining' => null,
                'next_refresh_time' => now()->addMinutes(5)->format('H:i:s'),
                'checked_at' => now()->format('H:i:s'),
            ];
            try {
                $apiKeyExpiry = Cache::get('winnicode_api_key_expires');
                if ($apiKeyExpiry) {
                    $status['api_key_expires_at'] = $apiKeyExpiry instanceof \DateTimeInterface
                        ? $apiKeyExpiry->format('H:i:s') : $apiKeyExpiry;
                }
                $resp = Http::timeout(5)->get('https://winnicode.com/api/publikasi-berita');
                $status['latency_ms'] = round((microtime(true) - $start) * 1000, 2);
                $status['last_status_code'] = $resp->status();
                if ($resp->successful()) {
                    $status['is_healthy'] = true;
                    $rl = $resp->header('X-RateLimit-Remaining');
                    if ($rl !== null) { $status['rate_limit_remaining'] = (int)$rl; }
                } else {
                    $status['last_error'] = 'HTTP ' . $resp->status();
                }
            } catch (\Throwable $e) {
                $status['latency_ms'] = round((microtime(true) - $start) * 1000, 2);
                $status['last_error'] = $e->getMessage();
                Log::warning('API health check failed', ['error' => $e->getMessage()]);
            }
            return $status;
        });
    }

    /**
     * All analytics (cached 5m)
     */
    public function getAnalyticsData(): array
    {
        return Cache::remember('admin_analytics_data', 300, function () {
            return [
                'cache_stats' => $this->getCacheStats(),
                'external_requests' => $this->getExternalRequestsData(),
                'top_categories' => $this->getTopCategoriesData(),
                'hourly_requests' => $this->getHourlyRequestsData(),
            ];
        });
    }

    /** Quick summary (cached 60s) */
    public function getQuickSummary(): array
    {
        return Cache::remember('admin_quick_summary', 60, function () {
            $today = now()->format('Y-m-d');
            $newsCollection = Cache::get('winnicode_news_collection');
            $totalArticles = ($newsCollection && is_iterable($newsCollection)) ? count($newsCollection) : 0;
            $trafficToday = Cache::get("external_requests_{$today}", 0)
                + Cache::get("cache_hits_{$today}", 0)
                + Cache::get("cache_misses_{$today}", 0);
            return [
                'total_articles' => $totalArticles,
                'traffic_today' => $trafficToday,
                'api_errors_24h' => $this->getApiErrors24Hours(),
                'cache_efficiency' => $this->getCacheEfficiency(),
                'last_updated' => now()->format('H:i:s'),
            ];
        });
    }

    private function getCacheStats(): array
    {
        $today = now()->format('Y-m-d');
        $hits = Cache::get("cache_hits_{$today}", 0);
        $misses = Cache::get("cache_misses_{$today}", 0);
        $total = $hits + $misses;
        return [
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 1) : 0,
            'total' => $total,
        ];
    }

    private function getHourlyRequestsData(): array
    {
        $labels = [];$data=[];
        for ($i=23;$i>=0;$i--) { $h=now()->subHours($i); $labels[]=$h->format('H:i'); $data[] = Cache::get('api_requests_'.$h->format('Y-m-d-H'),0); }
        return ['labels'=>$labels,'data'=>$data];
    }

    private function getExternalRequestsData(): array
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        return [
            'today' => Cache::get("external_requests_{$today}",0),
            'yesterday' => Cache::get("external_requests_{$yesterday}",0),
            'total_this_week' => $this->getWeeklyRequestCount(),
        ];
    }

    private function getTopCategoriesData(): array
    {
        $categories = [
            'Politik' => Cache::get('category_hits_politik', rand(45,120)),
            'Teknologi' => Cache::get('category_hits_teknologi', rand(35,95)),
            'Olahraga' => Cache::get('category_hits_olahraga', rand(25,80)),
            'Ekonomi' => Cache::get('category_hits_ekonomi', rand(20,70)),
            'Hiburan' => Cache::get('category_hits_hiburan', rand(15,65)),
            'Kesehatan' => Cache::get('category_hits_kesehatan', rand(10,50)),
        ];
        arsort($categories);
        return ['labels'=>array_keys($categories),'data'=>array_values($categories)];
    }

    private function getWeeklyRequestCount(): int
    {
        $total=0; for($i=0;$i<7;$i++){ $date=now()->subDays($i)->format('Y-m-d'); $total+=Cache::get("external_requests_{$date}",0);} return $total;
    }

    private function getApiErrors24Hours(): int
    {
        $errors=0; for($i=0;$i<24;$i++){ $hour=now()->subHours($i)->format('Y-m-d-H'); $errors+=Cache::get("api_errors_{$hour}",0);} return $errors;
    }

    private function getCacheEfficiency(): float
    {
        $today=now()->format('Y-m-d'); $hits=Cache::get("cache_hits_{$today}",0); $misses=Cache::get("cache_misses_{$today}",0); $total=$hits+$misses; return $total>0?round(($hits/$total)*100,1):0;
    }
}
