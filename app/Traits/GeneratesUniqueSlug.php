<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    /**
     * يولّد slug فريد لموديل معيّن، وبيضيف رقم بالآخر (-1, -2...) لو الاسم مكرر.
     */
    protected function makeUniqueSlug(
        string $modelClass,
        string $source,
        ?int $ignoreId = null,
        string $fallbackPrefix = 'item',
        string $column = 'slug'
    ): string {
        $slug = Str::slug($source);

        if (blank($slug)) {
            $slug = $fallbackPrefix . '-' . Str::random(8);
        }

        $originalSlug = $slug;
        $counter = 1;

        while (
            $modelClass::where($column, $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
