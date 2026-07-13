@extends('layouts.admin')

@section('title', $title . ' | اتزان')

@section('content')
<section class="admin-file-viewer-page">

    <section class="admin-file-viewer-hero">
        <div class="admin-file-viewer-hero__content">
            <span class="admin-file-viewer-kicker">
                <i class="fa-regular fa-file-lines"></i>
                عرض ملف الطبيب
            </span>

            <h1>{{ $title }}</h1>

            <p>
                ملف خاص بطلب الطبيب
                <strong>{{ $doctorApplication->full_name }}</strong>
            </p>
        </div>

        <div class="admin-file-viewer-actions">
            <a href="{{ route('admin.doctor-applications-show', $doctorApplication->id) }}" class="admin-file-viewer-btn">
                <i class="fa-solid fa-arrow-right"></i>
                <span>رجوع</span>
            </a>

            <a href="{{ $dataUri }}" download="{{ $fileName }}" class="admin-file-viewer-btn admin-file-viewer-btn--primary">
                <i class="fa-solid fa-download"></i>
                <span>تحميل الملف</span>
            </a>
        </div>
    </section>

    <section class="admin-file-viewer-card">

        <div class="admin-file-viewer-card__head">
            <div>
                <span>المعاينة</span>
                <h2>{{ $fileName }}</h2>
            </div>

            <div class="admin-file-viewer-type">
                <i class="fa-regular fa-file"></i>
                <span>{{ strtoupper($extension) }}</span>
            </div>
        </div>

        @if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
            <div class="admin-file-viewer-container admin-file-viewer-container--image">
                <img src="{{ $dataUri }}" alt="{{ $title }}">
            </div>
        @elseif ($extension === 'pdf')
            <div class="admin-file-viewer-container admin-file-viewer-container--pdf">
                <iframe src="{{ $dataUri }}"></iframe>
            </div>
        @else
            <div class="admin-file-viewer-unsupported">
                <div class="admin-file-viewer-unsupported__icon">
                    <i class="fa-regular fa-file-lines"></i>
                </div>

                <strong>هذا النوع من الملفات لا يُعرض داخل المتصفح مباشرة.</strong>
                <p>اضغطي زر التحميل بالأعلى لفتح الملف على جهازك.</p>

                <a href="{{ $dataUri }}" download="{{ $fileName }}" class="admin-file-viewer-btn admin-file-viewer-btn--primary">
                    <i class="fa-solid fa-download"></i>
                    تحميل الملف
                </a>
            </div>
        @endif

    </section>

</section>
@endsection

@push('styles')
<style>
/* =========================
   ADMIN FILE VIEWER
   نفس روح الداشبورد وباقي صفحات الأدمن
========================= */

.admin-file-viewer-page {
    display: grid;
    gap: 22px;
}

/* fallback variables */
:root {
    --surface: #ffffff;
    --surface2: #f5fbf8;
    --surface-2: #f5fbf8;

    --text: #173530;
    --muted: #6b8a84;
    --text-2: #6b8a84;

    --border: rgba(29, 158, 117, .15);

    --green: #1D9E75;
    --green-s: rgba(29, 158, 117, .12);
    --green-glow: rgba(29, 158, 117, .25);

    --teal: #1D9E75;

    --blue: #38b8f2;
    --blue-s: rgba(56, 184, 242, .12);

    --red: #ef4444;
    --red-s: rgba(239, 68, 68, .13);

    --shadow: 0 20px 50px rgba(15, 55, 45, .09);
    --shadow-md: 0 20px 50px rgba(15, 55, 45, .09);
    --shadow-sm: 0 8px 24px rgba(15, 55, 45, .07);

    --r-xl: 28px;
    --r-lg: 20px;
}

html[data-theme="dark"] {
    --surface: #132522;
    --surface2: #192e2b;
    --surface-2: #192e2b;

    --text: #e2f5ee;
    --muted: #7fa89f;
    --text-2: #7fa89f;

    --border: rgba(255, 255, 255, .08);

    --green-s: rgba(29, 158, 117, .20);
    --blue-s: rgba(56, 184, 242, .16);
    --red-s: rgba(239, 68, 68, .15);

    --shadow: 0 20px 55px rgba(0, 0, 0, .35);
    --shadow-md: 0 20px 55px rgba(0, 0, 0, .35);
    --shadow-sm: 0 8px 24px rgba(0, 0, 0, .25);
}

/* =========================
   HERO
========================= */

.admin-file-viewer-hero {
    position: relative;
    overflow: hidden;
    padding: 30px;
    border-radius: var(--r-xl);
    background:
        radial-gradient(circle at top right, rgba(29, 158, 117, .12), transparent 36%),
        radial-gradient(circle at bottom left, rgba(56, 184, 242, .10), transparent 38%),
        var(--surface);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
}

.admin-file-viewer-hero::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    left: -70px;
    top: -70px;
    border-radius: 50%;
    background: var(--green-s);
    filter: blur(12px);
    opacity: .75;
}

.admin-file-viewer-hero__content {
    position: relative;
    z-index: 2;
}

.admin-file-viewer-kicker {
    color: var(--green);
    font-size: 12px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.admin-file-viewer-hero h1 {
    margin: 10px 0 8px;
    color: var(--text);
    font-size: 34px;
    font-weight: 900;
    line-height: 1.25;
}

.admin-file-viewer-hero p {
    margin: 0;
    color: var(--muted);
    line-height: 1.8;
    font-weight: 700;
}

.admin-file-viewer-hero p strong {
    color: var(--text);
}

/* =========================
   ACTIONS
========================= */

.admin-file-viewer-actions {
    position: relative;
    z-index: 2;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.admin-file-viewer-btn {
    min-height: 46px;
    padding: 0 18px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: var(--surface2);
    border: 1px solid var(--border);
    color: var(--text);
    font-weight: 900;
    text-decoration: none;
    transition: .22s ease;
    white-space: nowrap;
}

.admin-file-viewer-btn:hover {
    transform: translateY(-2px);
    border-color: var(--green);
    color: var(--green);
    box-shadow: var(--shadow-sm);
}

.admin-file-viewer-btn--primary {
    background: linear-gradient(135deg, var(--green), var(--blue));
    color: #fff;
    border-color: transparent;
    box-shadow: 0 12px 28px var(--green-glow);
}

.admin-file-viewer-btn--primary:hover {
    color: #fff;
    box-shadow: 0 18px 36px var(--green-glow);
}

/* =========================
   CARD
========================= */

.admin-file-viewer-card {
    padding: 18px;
    min-height: 72vh;
    border-radius: var(--r-xl);
    background: var(--surface);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.admin-file-viewer-card__head {
    margin-bottom: 16px;
    padding: 4px 4px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.admin-file-viewer-card__head span {
    color: var(--green);
    font-size: 12px;
    font-weight: 900;
}

.admin-file-viewer-card__head h2 {
    margin: 6px 0 0;
    color: var(--text);
    font-size: 20px;
    font-weight: 900;
    word-break: break-word;
}

.admin-file-viewer-type {
    min-height: 40px;
    padding: 0 14px;
    border-radius: 999px;
    background: var(--green-s);
    border: 1px solid rgba(29, 158, 117, .22);
    color: var(--green);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 900;
}

/* =========================
   VIEWER
========================= */

.admin-file-viewer-container {
    border-radius: 22px;
    background:
        linear-gradient(135deg, rgba(29, 158, 117, .06), rgba(56, 184, 242, .05)),
        var(--surface2);
    border: 1px solid var(--border);
    overflow: hidden;
}

.admin-file-viewer-container--pdf {
    padding: 10px;
}

.admin-file-viewer-container iframe {
    width: 100%;
    height: 74vh;
    border: 0;
    border-radius: 18px;
    background: #fff;
    display: block;
}

.admin-file-viewer-container--image {
    min-height: 68vh;
    display: grid;
    place-items: center;
    padding: 20px;
}

.admin-file-viewer-container img {
    max-width: 100%;
    max-height: 72vh;
    display: block;
    margin: auto;
    border-radius: 18px;
    object-fit: contain;
    box-shadow: var(--shadow-sm);
}

/* =========================
   UNSUPPORTED
========================= */

.admin-file-viewer-unsupported {
    min-height: 60vh;
    padding: 40px 20px;
    display: grid;
    place-items: center;
    text-align: center;
    color: var(--muted);
    line-height: 2;
    border-radius: 22px;
    background: var(--surface2);
    border: 1px dashed var(--border);
}

.admin-file-viewer-unsupported__icon {
    width: 78px;
    height: 78px;
    border-radius: 26px;
    margin: 0 auto 14px;
    background: var(--green-s);
    color: var(--green);
    display: grid;
    place-items: center;
    font-size: 36px;
}

.admin-file-viewer-unsupported strong {
    display: block;
    color: var(--text);
    font-size: 18px;
    font-weight: 900;
}

.admin-file-viewer-unsupported p {
    margin: 8px 0 18px;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 768px) {
    .admin-file-viewer-hero,
    .admin-file-viewer-card__head {
        flex-direction: column;
        align-items: flex-start;
    }

    .admin-file-viewer-actions {
        width: 100%;
    }

    .admin-file-viewer-btn {
        flex: 1;
    }

    .admin-file-viewer-hero h1 {
        font-size: 28px;
    }

    .admin-file-viewer-container iframe {
        height: 68vh;
    }
}

@media (max-width: 520px) {
    .admin-file-viewer-hero,
    .admin-file-viewer-card {
        padding: 18px;
    }

    .admin-file-viewer-actions {
        flex-direction: column;
    }

    .admin-file-viewer-btn {
        width: 100%;
    }

    .admin-file-viewer-type {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush
