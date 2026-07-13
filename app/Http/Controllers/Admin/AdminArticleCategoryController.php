<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Traits\GeneratesUniqueSlug;
use Illuminate\Http\Request;

class AdminArticleCategoryController extends Controller
{
    use GeneratesUniqueSlug;

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

        $slug = $this->makeUniqueSlug(ArticleCategory::class, $validated['name'], fallbackPrefix: 'category');

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

        $slug = $this->makeUniqueSlug(ArticleCategory::class, $validated['name'], $articleCategory->id, 'category');

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
