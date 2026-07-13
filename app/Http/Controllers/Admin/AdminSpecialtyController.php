<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Traits\GeneratesUniqueSlug;
use Illuminate\Http\Request;

class AdminSpecialtyController extends Controller
{
    use GeneratesUniqueSlug;

    public function index()
    {
        $specialties = Specialty::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.specialties', compact('specialties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            '_form' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم التخصص مطلوب.',
            'description.required' => 'وصف التخصص مطلوب.',
            'sort_order.required' => 'ترتيب الظهور مطلوب.',
            'sort_order.integer' => 'ترتيب الظهور يجب أن يكون رقمًا.',
            'sort_order.min' => 'ترتيب الظهور لا يمكن أن يكون أقل من صفر.',
        ]);

        $slug = $this->makeUniqueSlug(Specialty::class, $validated['name'], fallbackPrefix: 'specialty');

        Specialty::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon' => $this->resolveIconFromName($validated['name']),
            'description' => $validated['description'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'تم إضافة التخصص بنجاح.');
    }

    public function edit(Specialty $specialty)
    {
        return redirect()->route('admin.specialties.index');
    }

    public function update(Request $request, Specialty $specialty)
    {
        $validated = $request->validate([
            '_form' => ['nullable', 'string'],
            'specialty_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم التخصص مطلوب.',
            'description.required' => 'وصف التخصص مطلوب.',
            'sort_order.required' => 'ترتيب الظهور مطلوب.',
            'sort_order.integer' => 'ترتيب الظهور يجب أن يكون رقمًا.',
            'sort_order.min' => 'ترتيب الظهور لا يمكن أن يكون أقل من صفر.',
        ]);

        $slug = $this->makeUniqueSlug(Specialty::class, $validated['name'], $specialty->id, 'specialty');

        $specialty->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon' => $this->resolveIconFromName($validated['name']),
            'description' => $validated['description'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'تم تعديل التخصص بنجاح.');
    }

    public function destroy(Specialty $specialty)
    {
        if ($specialty->doctorProfiles()->exists()) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف هذا التخصص لأنه مرتبط بأطباء.',
            ]);
        }

        if ($specialty->conditions()->exists()) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف هذا التخصص لأنه مرتبط بحالات صحية.',
            ]);
        }

        $specialty->delete();

        return back()->with('success', 'تم حذف التخصص بنجاح.');
    }

    private function resolveIconFromName(string $name): string
    {
        $normalized = mb_strtolower($name);

        if (str_contains($normalized, 'قلب') || str_contains($normalized, 'شرايين')) {
            return 'fa-solid fa-heart-pulse';
        }

        if (str_contains($normalized, 'سكري') || str_contains($normalized, 'غدد')) {
            return 'fa-solid fa-droplet';
        }

        if (str_contains($normalized, 'تغذية') || str_contains($normalized, 'غذاء')) {
            return 'fa-solid fa-bowl-food';
        }

        if (
            str_contains($normalized, 'سمنة') ||
            str_contains($normalized, 'نحافة') ||
            str_contains($normalized, 'وزن')
        ) {
            return 'fa-solid fa-weight-scale';
        }

        if (str_contains($normalized, 'ضغط')) {
            return 'fa-solid fa-gauge-high';
        }

        if (str_contains($normalized, 'نفسي') || str_contains($normalized, 'نفسية')) {
            return 'fa-solid fa-brain';
        }

        if (str_contains($normalized, 'أطفال') || str_contains($normalized, 'اطفال')) {
            return 'fa-solid fa-child-reaching';
        }

        if (str_contains($normalized, 'جلد') || str_contains($normalized, 'جلدية')) {
            return 'fa-solid fa-hand-sparkles';
        }

        if (str_contains($normalized, 'عظام')) {
            return 'fa-solid fa-bone';
        }

        if (str_contains($normalized, 'نساء') || str_contains($normalized, 'ولادة')) {
            return 'fa-solid fa-person-pregnant';
        }

        if (str_contains($normalized, 'عيون') || str_contains($normalized, 'نظر')) {
            return 'fa-regular fa-eye';
        }

        if (str_contains($normalized, 'أسنان') || str_contains($normalized, 'اسنان')) {
            return 'fa-solid fa-tooth';
        }

        return 'fa-solid fa-stethoscope';
    }
}
