@extends('layouts.admin')

@section('title', 'تعديل تصنيف | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/admin-specialties.css') }}">
@endpush

@section('content')
<section class="admin-specialties-page specialties-v2-page">

    <section class="specialties-hero-v2 specialties-hero-v2--compact">
        <div>
            <span class="specialties-kicker-v2">
                <i class="fa-regular fa-folder"></i>
                تصنيفات المقالات
            </span>

            <h1>تعديل: {{ $articleCategory->name }}</h1>
        </div>

        <a href="{{ route('admin.article-categories.index') }}" class="specialties-primary-btn-v2">
            <i class="fa-solid fa-arrow-right"></i>
            <span>رجوع للقائمة</span>
        </a>
    </section>

    <section class="specialties-board-v2">
        <form action="{{ route('admin.article-categories.update', $articleCategory) }}"
              method="POST"
              class="admin-specialty-form">
            @csrf
            @method('PUT')

            @include('admin.article-categories.partials.fields', [
                'values' => $errors->any() ? null : $articleCategory,
                'showErrors' => true,
            ])

            <div class="admin-specialty-modal__actions">
                <a href="{{ route('admin.article-categories.index') }}" class="admin-specialty-cancel">
                    إلغاء
                </a>

                <button type="submit" class="admin-specialty-save">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>حفظ التعديل</span>
                </button>
            </div>
        </form>
    </section>

</section>
@endsection
