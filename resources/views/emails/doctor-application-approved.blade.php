<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قبول الطلب</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f7f9fc; padding:30px;">
    <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:16px; padding:30px; border:1px solid #e7edf3;">
        <h2 style="margin-top:0; color:#18344c;">مرحبًا {{ $doctorApplication->full_name }}</h2>

        <p style="color:#4b6072; line-height:1.9;">
            تم قبول طلب انضمامك كطبيب في منصة اتزان بنجاح.
        </p>

        <p style="color:#4b6072; line-height:1.9;">
            تم تجهيز حسابك على المنصة باستخدام هذا البريد الإلكتروني:
            <strong>{{ $user->email }}</strong>
        </p>

        <p style="color:#4b6072; line-height:1.9;">
            لإكمال تفعيل الحساب، اضغط على الزر التالي لإنشاء كلمة المرور الخاصة بك:
        </p>

        <div style="margin-top:24px; text-align:center;">
            <a href="{{ $setupUrl }}"
               style="display:inline-block; background:#2db7a3; color:#ffffff; text-decoration:none; padding:14px 22px; border-radius:12px; font-weight:bold;">
                إنشاء كلمة المرور
            </a>
        </div>

        <p style="color:#7a8b99; line-height:1.8; margin-top:24px; font-size:13px;">
            هذا الرابط صالح لمدة 24 ساعة.
        </p>
    </div>
</body>
</html>
