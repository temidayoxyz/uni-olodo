<?php

namespace App\Http\Controllers;

use App\Models\CampusEvent;
use App\Models\NewsArticle;
use Illuminate\View\View;

class PublicNewsController extends Controller
{
    public function index(): View
    {
        return view('public.news', [
            'articles' => NewsArticle::query()->published()->latest('published_at')->paginate(8),
            'events' => CampusEvent::query()->public()->upcoming()->orderBy('starts_at')->get(),
        ]);
    }

    public function show(NewsArticle $article): View
    {
        abort_unless($article->published_at !== null && $article->published_at->isPast(), 404);

        return view('public.news-article', [
            'article' => $article->load('author'),
            'more' => NewsArticle::query()->published()->latest('published_at')->whereKeyNot($article->id)->take(3)->get(),
        ]);
    }
}
