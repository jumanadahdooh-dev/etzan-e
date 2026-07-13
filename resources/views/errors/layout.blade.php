@php
    $code = $code ?? '500';
    $title = $title ?? 'حدث خطأ';
    $message = $message ?? 'حدث خطأ غير متوقع.';
    $badge = $badge ?? 'خطأ';
    $visual = $visual ?? 'alert';
    $tone = $tone ?? 'green';
    $primaryText = $primaryText ?? 'العودة للرئيسية';
    $primaryUrl = $primaryUrl ?? url('/');
    $primaryIcon = $primaryIcon ?? 'home';
    $secondaryText = $secondaryText ?? 'الرجوع للخلف';
    $secondaryUrl = $secondaryUrl ?? 'javascript:history.back()';
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} | {{ $title }} | اتزان</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900;1000&display=swap" rel="stylesheet">

    <style>
        :root {
            --green: #1D9E75;
            --green-dark: #16795a;
            --blue: #4DA8DA;
            --ink: #17333b;
            --muted: #64808a;
            --white: #ffffff;
            --bg: #f8fcfb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Cairo', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top right, rgba(29, 158, 117, 0.13), transparent 24%),
                radial-gradient(circle at bottom left, rgba(77, 168, 218, 0.12), transparent 28%),
                linear-gradient(135deg, #fbfefd 0%, #eef8f5 55%, #f4fbff 100%);
            overflow: hidden;
        }

        .error-page {
            --tone: var(--green);
            --tone-dark: var(--green-dark);
            --tone-soft: rgba(29, 158, 117, 0.12);
            --tone-soft-2: rgba(29, 158, 117, 0.07);
            --tone-border: rgba(29, 158, 117, 0.18);

            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px 20px;
            text-align: center;
            isolation: isolate;
        }

        .error-page.tone-blue {
            --tone: #4DA8DA;
            --tone-dark: #2d7fa8;
            --tone-soft: rgba(77, 168, 218, 0.13);
            --tone-soft-2: rgba(77, 168, 218, 0.07);
            --tone-border: rgba(77, 168, 218, 0.18);
        }

        .error-page.tone-red {
            --tone: #c96b61;
            --tone-dark: #99433b;
            --tone-soft: rgba(201, 107, 97, 0.10);
            --tone-soft-2: rgba(201, 107, 97, 0.06);
            --tone-border: rgba(201, 107, 97, 0.16);
        }

        .error-page.tone-teal {
            --tone: #2b9c93;
            --tone-dark: #18756f;
            --tone-soft: rgba(43, 156, 147, 0.11);
            --tone-soft-2: rgba(43, 156, 147, 0.06);
            --tone-border: rgba(43, 156, 147, 0.16);
        }

        .brand {
            position: absolute;
            top: 26px;
            right: 28px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            z-index: 3;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: #fff;
            background:
                radial-gradient(circle at 35% 25%, rgba(255,255,255,0.95), transparent 30%),
                linear-gradient(135deg, var(--green), var(--blue));
            box-shadow: 0 14px 28px rgba(29, 158, 117, 0.18);
        }

        .brand-mark svg {
            width: 24px;
            height: 24px;
        }

        .brand-text strong {
            display: block;
            font-size: 22px;
            line-height: 1;
            font-weight: 1000;
            color: var(--ink);
        }

        .brand-text span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 4px;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(18px);
            pointer-events: none;
            z-index: 0;
        }

        .orb-1 {
            width: 260px;
            height: 260px;
            top: -60px;
            right: -70px;
            background: var(--tone-soft);
        }

        .orb-2 {
            width: 220px;
            height: 220px;
            bottom: -60px;
            left: -70px;
            background: rgba(77, 168, 218, 0.10);
        }

        .orb-3 {
            width: 110px;
            height: 110px;
            top: 22%;
            left: 12%;
            background: var(--tone-soft-2);
        }

        .error-wrap {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 980px;
            margin: 0 auto;
        }

        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            color: var(--tone-dark);
            background: rgba(255,255,255,0.72);
            border: 1px solid var(--tone-border);
            backdrop-filter: blur(14px);
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 18px;
        }

        .error-badge svg {
            width: 18px;
            height: 18px;
        }

        .error-visual {
            position: relative;
            width: min(520px, 100%);
            margin: 0 auto 18px;
            aspect-ratio: 1 / 1;
            display: grid;
            place-items: center;
        }

        .error-circle {
            position: absolute;
            inset: 10%;
            border-radius: 50%;
            background: radial-gradient(circle, var(--tone-soft), transparent 68%);
            z-index: 0;
        }

        .error-ring {
            position: absolute;
            inset: 14%;
            border-radius: 50%;
            border: 1px dashed rgba(23, 51, 59, 0.10);
        }

        .error-ring::before {
            content: "";
            position: absolute;
            inset: 18%;
            border-radius: 50%;
            border: 1px solid rgba(23, 51, 59, 0.06);
        }

        .error-number {
            position: relative;
            z-index: 2;
            margin: 0;
            font-size: clamp(130px, 24vw, 240px);
            line-height: 0.9;
            font-weight: 1000;
            letter-spacing: -10px;
            background: linear-gradient(180deg, var(--tone-dark), var(--tone), var(--blue));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 18px 40px rgba(23, 51, 59, 0.06);
        }

        .error-icon {
            position: absolute;
            top: 18%;
            left: 18%;
            z-index: 3;
            width: 82px;
            height: 82px;
            display: grid;
            place-items: center;
            border-radius: 24px;
            color: #fff;
            background:
                radial-gradient(circle at 35% 25%, rgba(255,255,255,0.95), transparent 30%),
                linear-gradient(135deg, var(--tone), var(--blue));
            box-shadow: 0 18px 36px rgba(23, 51, 59, 0.12);
        }

        .error-icon svg {
            width: 40px;
            height: 40px;
        }

        .error-mini-label {
            position: absolute;
            bottom: 16%;
            right: 16%;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.76);
            border: 1px solid var(--tone-border);
            backdrop-filter: blur(14px);
            color: var(--tone-dark);
            font-size: 12px;
            font-weight: 950;
            letter-spacing: 0.5px;
        }

        .error-title {
            margin: 0;
            font-size: clamp(32px, 4vw, 50px);
            line-height: 1.35;
            font-weight: 1000;
            color: var(--ink);
        }

        .error-message {
            max-width: 700px;
            margin: 14px auto 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 2;
            font-weight: 700;
        }

        .error-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .error-btn {
            min-height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 24px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 950;
            transition: 0.25s ease;
        }

        .error-btn:hover {
            transform: translateY(-3px);
        }

        .error-btn svg {
            width: 20px;
            height: 20px;
        }

        .error-btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--tone), var(--blue));
            box-shadow: 0 16px 30px rgba(23, 51, 59, 0.14);
        }

        .error-btn-secondary {
            color: var(--tone-dark);
            background: rgba(255,255,255,0.78);
            border: 1px solid var(--tone-border);
            backdrop-filter: blur(14px);
        }

        .error-note {
            margin-top: 26px;
            color: #7a9198;
            font-size: 12px;
            font-weight: 800;
        }

        @media (max-width: 768px) {
            body {
                overflow-y: auto;
            }

            .brand {
                position: static;
                justify-content: center;
                margin-bottom: 28px;
            }

            .error-page {
                padding-top: 24px;
            }

            .error-icon {
                width: 62px;
                height: 62px;
                border-radius: 20px;
            }

            .error-icon svg {
                width: 30px;
                height: 30px;
            }

            .error-message {
                font-size: 14px;
            }

            .error-actions {
                flex-direction: column;
            }

            .error-btn {
                width: 100%;
                max-width: 320px;
            }

            .error-mini-label {
                font-size: 10px;
                padding: 8px 12px;
            }
        }

        /* =========================================================
   FIX ERROR NUMBER + ICONS CUTTING
   يحل مشكلة قص رقم الخطأ والأيقونات
========================================================= */

body {
    overflow-x: hidden;
    overflow-y: auto;
}

.error-page {
    overflow: visible;
}

.error-wrap {
    overflow: visible;
}

.error-visual {
    width: min(560px, 100%);
    min-height: 430px;
    margin: 0 auto 22px;
    overflow: visible;
    padding: 28px 20px;
}

.error-circle,
.error-ring {
    pointer-events: none;
}

.error-number {
    display: block;
    overflow: visible;
    padding: 18px 22px 24px;
    font-size: clamp(120px, 20vw, 215px);
    line-height: 1.05;
    letter-spacing: -3px;
    text-align: center;
    max-width: 100%;
}

.error-icon {
    width: 76px;
    height: 76px;
    border-radius: 24px;
    top: 14%;
    left: 15%;
    overflow: visible;
}

.error-icon svg,
.error-badge svg,
.error-btn svg,
.brand-mark svg {
    display: block;
    overflow: visible;
    flex-shrink: 0;
}

.error-icon svg {
    width: 38px;
    height: 38px;
}

.error-badge svg {
    width: 19px;
    height: 19px;
}

.error-btn svg {
    width: 20px;
    height: 20px;
}

.error-mini-label {
    bottom: 14%;
    right: 15%;
    white-space: nowrap;
}

/* رقم 400 و 500 أوسع، فنعطيهم مساحة أكثر */
.error-number {
    min-width: max-content;
}

/* يمنع قص الرقم داخل الدائرة */
.error-visual > * {
    overflow: visible;
}

/* تحسين شكل الرقم على الشاشات الصغيرة */
@media (max-width: 768px) {
    .error-visual {
        width: min(420px, 100%);
        min-height: 330px;
        padding: 18px 10px;
    }

    .error-number {
        font-size: clamp(96px, 27vw, 150px);
        line-height: 1.08;
        letter-spacing: -1px;
        padding: 14px 12px 18px;
    }

    .error-icon {
        width: 58px;
        height: 58px;
        border-radius: 19px;
        top: 13%;
        left: 12%;
    }

    .error-icon svg {
        width: 29px;
        height: 29px;
    }

    .error-mini-label {
        bottom: 13%;
        right: 12%;
        font-size: 10px;
    }
}

@media (max-width: 420px) {
    .error-number {
        font-size: clamp(82px, 25vw, 120px);
        letter-spacing: 0;
    }

    .error-visual {
        min-height: 280px;
    }
}


/* =========================================================
   FIT ERROR PAGE IN ONE SCREEN
   يخلي صفحة الخطأ كاملة ظاهرة بدون سكرول على اللابتوب
========================================================= */

body {
    min-height: 100vh;
    overflow: hidden !important;
}

.error-page {
    min-height: 100vh;
    padding: 16px 18px !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand {
    top: 18px !important;
    right: 22px !important;
}

.brand-mark {
    width: 36px !important;
    height: 36px !important;
    border-radius: 13px !important;
}

.brand-mark svg {
    width: 21px !important;
    height: 21px !important;
}

.brand-text strong {
    font-size: 18px !important;
}

.brand-text span {
    font-size: 8px !important;
    letter-spacing: 3px !important;
}

.error-wrap {
    max-width: 880px !important;
    transform: scale(0.92);
    transform-origin: center;
}

.error-badge {
    margin-bottom: 8px !important;
    padding: 8px 13px !important;
    font-size: 12px !important;
}

.error-visual {
    width: min(410px, 100%) !important;
    min-height: 310px !important;
    aspect-ratio: auto !important;
    margin: 0 auto 6px !important;
    padding: 0 !important;
}

.error-circle {
    inset: 8% !important;
}

.error-ring {
    inset: 13% !important;
}

.error-number {
    font-size: clamp(92px, 18vw, 165px) !important;
    line-height: 1 !important;
    letter-spacing: -2px !important;
    padding: 8px 12px !important;
    min-width: auto !important;
}

.error-icon {
    width: 58px !important;
    height: 58px !important;
    border-radius: 19px !important;
    top: 16% !important;
    left: 16% !important;
}

.error-icon svg {
    width: 29px !important;
    height: 29px !important;
}

.error-mini-label {
    bottom: 16% !important;
    right: 16% !important;
    padding: 7px 11px !important;
    font-size: 10px !important;
}

.error-title {
    font-size: clamp(25px, 3vw, 34px) !important;
    line-height: 1.35 !important;
    margin-top: 0 !important;
}

.error-message {
    max-width: 620px !important;
    margin-top: 8px !important;
    font-size: 14px !important;
    line-height: 1.75 !important;
}

.error-actions {
    margin-top: 16px !important;
    gap: 10px !important;
}

.error-btn {
    min-height: 44px !important;
    padding: 0 18px !important;
    font-size: 13px !important;
}

.error-btn svg {
    width: 18px !important;
    height: 18px !important;
}

.error-note {
    margin-top: 13px !important;
    font-size: 11.5px !important;
}

.orb-1 {
    width: 210px !important;
    height: 210px !important;
}

.orb-2 {
    width: 180px !important;
    height: 180px !important;
}

.orb-3 {
    width: 85px !important;
    height: 85px !important;
}

/* للشاشات القصيرة جدًا */
@media (max-height: 740px) {
    .error-wrap {
        transform: scale(0.84);
    }

    .error-visual {
        min-height: 285px !important;
    }

    .error-number {
        font-size: clamp(82px, 17vw, 145px) !important;
    }

    .error-title {
        font-size: clamp(23px, 2.7vw, 30px) !important;
    }

    .error-message {
        font-size: 13px !important;
        line-height: 1.65 !important;
    }
}

/* على الموبايل نسمح بسكرول بسيط لأن الشاشة صغيرة */
@media (max-width: 768px) {
    body {
        overflow-y: auto !important;
    }

    .error-page {
        padding: 22px 14px !important;
    }

    .brand {
        position: static !important;
        margin-bottom: 16px !important;
    }

    .error-wrap {
        transform: none;
        max-width: 100% !important;
    }

    .error-visual {
        min-height: 260px !important;
    }

    .error-number {
        font-size: clamp(82px, 25vw, 125px) !important;
    }
}

/* =========================================================
   BIG CENTER ERROR NUMBER - ETZAN STYLE
   الرقم كبير ويملي الصفحة مع نص متوازن تحته
========================================================= */

body {
    overflow: hidden !important;
}

.error-page {
    min-height: 100vh;
    padding: 18px 20px !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand {
    top: 22px !important;
    right: 28px !important;
}

.error-wrap {
    width: min(1100px, 100%) !important;
    max-width: 1100px !important;
    transform: none !important;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.error-badge {
    margin-bottom: 4px !important;
    padding: 8px 14px !important;
    font-size: 12.5px !important;
}

.error-visual {
    width: min(760px, 100%) !important;
    min-height: 430px !important;
    aspect-ratio: auto !important;
    margin: -10px auto 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}

.error-circle {
    inset: 1% !important;
    background: radial-gradient(circle, var(--tone-soft), transparent 70%) !important;
}

.error-ring {
    inset: 7% !important;
}

.error-ring::before {
    inset: 18% !important;
}

.error-number {
    font-size: clamp(190px, 34vw, 360px) !important;
    line-height: 0.88 !important;
    letter-spacing: -10px !important;
    padding: 0 22px 18px !important;
    min-width: auto !important;
    max-width: 100% !important;
    text-align: center !important;
}

.error-icon {
    width: 78px !important;
    height: 78px !important;
    border-radius: 25px !important;
    top: 18% !important;
    left: 15% !important;
}

.error-icon svg {
    width: 39px !important;
    height: 39px !important;
}

.error-mini-label {
    bottom: 15% !important;
    right: 14% !important;
    padding: 9px 14px !important;
    font-size: 11px !important;
}

.error-title {
    margin-top: -8px !important;
    font-size: clamp(30px, 3.4vw, 46px) !important;
    line-height: 1.35 !important;
    max-width: 760px !important;
}

.error-message {
    max-width: 760px !important;
    margin-top: 8px !important;
    font-size: 15.5px !important;
    line-height: 1.85 !important;
}

.error-actions {
    margin-top: 20px !important;
    gap: 11px !important;
}

.error-btn {
    min-height: 48px !important;
    padding: 0 22px !important;
    font-size: 13.5px !important;
}

.error-note {
    margin-top: 14px !important;
    font-size: 12px !important;
}

.orb-1 {
    width: 280px !important;
    height: 280px !important;
}

.orb-2 {
    width: 240px !important;
    height: 240px !important;
}

.orb-3 {
    width: 120px !important;
    height: 120px !important;
}

/* للشاشات القصيرة */
@media (max-height: 760px) {
    .error-visual {
        width: min(660px, 100%) !important;
        min-height: 350px !important;
        margin-top: -18px !important;
    }

    .error-number {
        font-size: clamp(160px, 30vw, 300px) !important;
        line-height: 0.86 !important;
    }

    .error-title {
        font-size: clamp(26px, 3vw, 38px) !important;
        margin-top: -12px !important;
    }

    .error-message {
        font-size: 14px !important;
        line-height: 1.7 !important;
    }

    .error-actions {
        margin-top: 14px !important;
    }

    .error-note {
        margin-top: 10px !important;
    }
}

/* للشاشات القصيرة جدًا */
@media (max-height: 650px) {
    .brand {
        transform: scale(0.86);
        transform-origin: top right;
    }

    .error-badge {
        display: none !important;
    }

    .error-visual {
        width: min(600px, 100%) !important;
        min-height: 300px !important;
    }

    .error-number {
        font-size: clamp(140px, 28vw, 250px) !important;
    }

    .error-icon {
        width: 58px !important;
        height: 58px !important;
        border-radius: 19px !important;
    }

    .error-icon svg {
        width: 29px !important;
        height: 29px !important;
    }

    .error-mini-label {
        display: none !important;
    }

    .error-title {
        font-size: clamp(23px, 2.6vw, 32px) !important;
    }

    .error-message {
        max-width: 650px !important;
        font-size: 13px !important;
        line-height: 1.6 !important;
    }

    .error-btn {
        min-height: 42px !important;
        padding: 0 18px !important;
        font-size: 12.5px !important;
    }
}

/* الموبايل */
@media (max-width: 768px) {
    body {
        overflow-y: auto !important;
    }

    .error-page {
        padding: 22px 14px !important;
    }

    .brand {
        position: static !important;
        transform: none !important;
        margin-bottom: 18px !important;
    }

    .error-visual {
        width: min(420px, 100%) !important;
        min-height: 290px !important;
        margin-top: 0 !important;
    }

    .error-number {
        font-size: clamp(115px, 35vw, 175px) !important;
        letter-spacing: -4px !important;
    }

    .error-icon {
        width: 56px !important;
        height: 56px !important;
        border-radius: 18px !important;
        top: 15% !important;
        left: 10% !important;
    }

    .error-icon svg {
        width: 28px !important;
        height: 28px !important;
    }

    .error-mini-label {
        bottom: 12% !important;
        right: 10% !important;
        font-size: 9.5px !important;
        padding: 7px 10px !important;
    }

    .error-title {
        font-size: 26px !important;
    }

    .error-message {
        font-size: 14px !important;
        line-height: 1.75 !important;
    }
}
    </style>
</head>
<body>
    <main class="error-page tone-{{ $tone }}">
        <div class="brand">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 21C12 21 4 16.8 4 9.8C4 5.9 7.1 3 10.8 3C12.9 3 14.4 4 15.2 5.1C16 4 17.5 3 19.6 3C20.1 3 20.6 3.1 21 3.2C20.7 9.8 17.1 16.7 12 21Z" fill="currentColor" opacity=".92"/>
                    <path d="M12 21C12.2 14.6 13.9 9.6 18.5 5.7" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M11.8 14.2C9.2 13.8 7 12.6 5.4 10.5" stroke="white" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </div>

            <div class="brand-text">
                <strong>اتزان</strong>
                <span>ETZAN</span>
            </div>
        </div>

        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <section class="error-wrap">
            <span class="error-badge">
                @include('errors.icons', ['name' => $visual])
                {{ $badge }}
            </span>

            <div class="error-visual">
                <div class="error-circle"></div>
                <div class="error-ring"></div>

                <div class="error-icon">
                    @include('errors.icons', ['name' => $visual])
                </div>

                <div class="error-mini-label">
                    ETZAN ERROR {{ $code }}
                </div>

                <h1 class="error-number">{{ $code }}</h1>
            </div>

            <h2 class="error-title">{{ $title }}</h2>

            <p class="error-message">
                {{ $message }}
            </p>

            <div class="error-actions">
                <a href="{{ $primaryUrl }}" class="error-btn error-btn-primary">
                    @include('errors.icons', ['name' => $primaryIcon])
                    {{ $primaryText }}
                </a>

                <a href="{{ $secondaryUrl }}" class="error-btn error-btn-secondary">
                    @include('errors.icons', ['name' => 'back'])
                    {{ $secondaryText }}
                </a>
            </div>

            <div class="error-note">
                اتزان • تجربة هادئة وواضحة حتى عند حدوث خطأ
            </div>
        </section>
    </main>
</body>
</html>
