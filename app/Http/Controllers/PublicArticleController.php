<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicArticleController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $selectedCategory = $request->query('category', 'all');

        $categoriesQuery = Specialty::query();

        if (Schema::hasColumn('specialties', 'is_active')) {
            $categoriesQuery->where('is_active', true);
        }

        if (Schema::hasColumn('specialties', 'status')) {
            $categoriesQuery->whereIn('status', ['active', 'published', 'approved']);
        }

        if (Schema::hasColumn('specialties', 'sort_order')) {
            $categoriesQuery->orderBy('sort_order');
        }

        $categories = $categoriesQuery
            ->orderBy('name')
            ->get();

        $articlesQuery = Article::query()
            ->published()
            ->with(['specialty', 'author'])
            ->latest('published_at')
            ->latest('created_at');

        if ($selectedCategory && $selectedCategory !== 'all') {
            $articlesQuery->where('specialty_id', $selectedCategory);
        }

        if ($search !== '') {
            $articlesQuery->where(function ($query) use ($search) {
                $query
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('excerpt', 'like', '%' . $search . '%')
                    ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        $articles = $articlesQuery
            ->paginate(8)
            ->withQueryString();

        $featuredArticle = Article::query()
            ->published()
            ->featured()
            ->with(['specialty', 'author'])
            ->latest('published_at')
            ->latest('created_at')
            ->first();

        if (!$featuredArticle) {
            $featuredArticle = Article::query()
                ->published()
                ->with(['specialty', 'author'])
                ->latest('published_at')
                ->latest('created_at')
                ->first();
        }

        return view('pages.articles', compact(
            'articles',
            'categories',
            'featuredArticle',
            'search',
            'selectedCategory'
        ));
    }

    public function show(string $slug)
    {
        $article = Article::query()
            ->published()
            ->with(['specialty', 'author'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedArticles = Article::query()
            ->published()
            ->with('specialty')
            ->where('id', '!=', $article->id)
            ->when($article->specialty_id, function ($query) use ($article) {
                $query->where('specialty_id', $article->specialty_id);
            })
            ->latest('published_at')
            ->latest('created_at')
            ->take(3)
            ->get();

        if ($relatedArticles->isEmpty()) {
            $relatedArticles = Article::query()
                ->published()
                ->with('specialty')
                ->where('id', '!=', $article->id)
                ->latest('published_at')
                ->latest('created_at')
                ->take(3)
                ->get();
        }

        return view('pages.article-details', compact('article', 'relatedArticles'));
    }
}
