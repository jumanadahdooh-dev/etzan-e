<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminArticleCategoryController extends Controller
{
    public function index()
    {
        $categories = ArticleCategory::query()
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.article-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم التصنيف مطلوب.',
        ]);

        $slug = Str::slug($validated['name']);

        if (blank($slug)) {
            $slug = 'category-' . Str::random(6);
        }

        $originalSlug = $slug;
        $counter = 1;

        while (ArticleCategory::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        ArticleCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon' => $validated['icon'] ?? 'fa-regular fa-folder',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'تم إضافة التصنيف بنجاح.');
    }

    public function edit(ArticleCategory $articleCategory)
    {
        return view('admin.article-categories.edit', compact('articleCategory'));
    }

    public function update(Request $request, ArticleCategory $articleCategory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم التصنيف مطلوب.',
        ]);

        $slug = Str::slug($validated['name']);

        if (blank($slug)) {
            $slug = 'category-' . $articleCategory->id;
        }

        $originalSlug = $slug;
        $counter = 1;

        while (
            ArticleCategory::where('slug', $slug)
                ->where('id', '!=', $articleCategory->id)
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $articleCategory->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon' => $validated['icon'] ?? 'fa-regular fa-folder',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.article-categories.index')
            ->with('success', 'تم تعديل التصنيف بنجاح.');
    }

    public function destroy(ArticleCategory $articleCategory)
    {
        if ($articleCategory->articles()->exists()) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف هذا التصنيف لأنه يحتوي على مقالات.',
            ]);
        }

        $articleCategory->delete();

        return back()->with('success', 'تم حذف التصنيف بنجاح.');
    }
}
