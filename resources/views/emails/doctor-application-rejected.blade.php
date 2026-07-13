<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>رفض الطلب</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f7f9fc; padding:30px;">
    <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:16px; padding:30px; border:1px solid #e7edf3;">
        <h2 style="margin-top:0; color:#18344c;">مرحبًا {{ $doctorApplication->full_name }}</h2>

        <p style="color:#4b6072; line-height:1.9;">
            نشكرك على اهتمامك بالانضمام إلى منصة اتزان.
        </p>

        <p style="color:#4b6072; line-height:1.9;">
            بعد مراجعة الطلب، نعتذر عن عدم قبوله في الوقت الحالي.
        </p>

        @if($doctorApplication->rejection_reason)
            <div style="margin-top:20px; padding:16px; background:#fff7f7; border:1px solid #f3d5d5; border-radius:12px;">
                <strong style="display:block; margin-bottom:8px; color:#a94442;">سبب الرفض:</strong>
                <span style="color:#6d4b4b; line-height:1.8;">
                    {{ $doctorApplication->rejection_reason }}
                </span>
            </div>
        @endif
    </div>
</body>
</html>
