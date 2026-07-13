<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
    {
        $doctors = $this->getHomepageDoctors();
        $articles = $this->getHomepageArticles();

        return view('pages.index', compact('doctors', 'articles'));
    }

    private function getHomepageDoctors()
    {
        $limit = (int) setting('home_doctors_limit', 4);
        $order = setting('home_doctors_order', 'latest');
        $onlyActive = setting('home_doctors_only_active', '1') === '1';

        $query = User::query()
            ->where('role', 'doctor');

        if (method_exists(new User, 'doctorProfile')) {
            $query->with('doctorProfile');
        }

        if (method_exists(new User, 'profile')) {
            $query->with('profile');
        }

        if ($onlyActive) {
            if (Schema::hasColumn('users', 'is_active')) {
                $query->where('is_active', 1);
            } elseif (Schema::hasColumn('users', 'status')) {
                $query->whereIn('status', ['active', 'approved']);
            }
        }

        if ($order === 'random') {
            $query->inRandomOrder();
        } elseif ($order === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        return $query->take($limit)->get();
    }

    private function getHomepageArticles()
    {
        $limit = (int) setting('home_articles_limit', 4);
        $order = setting('home_articles_order', 'latest');
        $specialty = setting('home_articles_category');
        $onlyPublished = setting('home_articles_only_published', '1') === '1';

        $query = Article::query();

        if (method_exists(new Article, 'specialty')) {
            $query->with('specialty');
        }

        if (method_exists(new Article, 'author')) {
            $query->with('author');
        }

        if ($onlyPublished) {
            if (Schema::hasColumn('articles', 'status')) {
                $query->where('status', 'published');
            }

            if (Schema::hasColumn('articles', 'published_at')) {
                $query->where(function ($dateQuery) {
                    $dateQuery
                        ->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                });
            }
        }

        if (!empty($specialty) && Schema::hasColumn('articles', 'specialty_id')) {
            if (method_exists(new Article, 'specialty')) {
                $query->whereHas('specialty', function ($specialtyQuery) use ($specialty) {
                    $specialtyQuery
                        ->where('id', $specialty)
                        ->orWhere('slug', $specialty)
                        ->orWhere('name', $specialty);
                });
            } else {
                $query->where('specialty_id', $specialty);
            }
        }

        if ($order === 'random') {
            $query->inRandomOrder();
        } elseif ($order === 'oldest') {
            if (Schema::hasColumn('articles', 'published_at')) {
                $query->oldest('published_at')->oldest('created_at');
            } else {
                $query->oldest();
            }
        } else {
            if (Schema::hasColumn('articles', 'published_at')) {
                $query->latest('published_at')->latest('created_at');
            } else {
                $query->latest();
            }
        }

        return $query->take($limit)->get();
    }
}
