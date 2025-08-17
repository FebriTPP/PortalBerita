<?php

namespace App\Services\Admin;

use App\Models\Comment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminCommentService
{
    public const COMMENTS_PER_PAGE = 10;

    public function getCommentsWithSearch(?string $search)
    {
        return Comment::with(['user'])
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) { $q->where('name','like',"%{$search}%"); })
                      ->orWhere('content','like',"%{$search}%");
            })
            ->latest()
            ->paginate(self::COMMENTS_PER_PAGE);
    }

    public function getNewsDataForComments($comments): array
    {
        $newsData=[]; $postIds=$comments->pluck('post_id')->unique()->toArray(); if(empty($postIds)){return $newsData;}
        try {
            $apiKey = Cache::remember('winnicode_api_key', 3600, function(){ $r=Http::timeout(10)->post('https://winnicode.com/api/login',['email'=>'dummy@dummy.com','password'=>'dummy']); return $r->successful()?($r->json()['api_key']??null):null;});
            if(!$apiKey){return $newsData;}
            $resp = Http::withToken($apiKey)->timeout(10)->get('https://winnicode.com/api/publikasi-berita');
            if($resp->successful()){
                foreach($resp->json() as $news){ if(in_array($news['id'],$postIds)){ $newsData[$news['id']]=$news; } }
            }
        } catch(\Throwable $e){ Log::error('Failed to fetch news data for comments',['error'=>$e->getMessage()]); }
        return $newsData;
    }
}
