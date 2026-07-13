<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>رمز استعادة كلمة المرور</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:30px;">
    <div style="max-width:600px; margin:auto; background:#ffffff; padding:30px; border-radius:16px; border:1px solid #e5e7eb;">
        <h2 style="margin-top:0; color:#17342f;">استعادة كلمة المرور</h2>
        <p style="color:#4b5563; line-height:1.8;">
            استخدم الرمز التالي لإعادة تعيين كلمة المرور الخاصة بك:
        </p>

        <div style="text-align:center; margin:24px 0;">
            <span style="display:inline-block; font-size:32px; font-weight:700; letter-spacing:8px; color:#216b62; background:#eef8f4; padding:16px 24px; border-radius:12px;">
                {{ $code }}
            </span>
        </div>

        <p style="color:#6b7280; line-height:1.8;">
            صلاحية هذا الرمز 10 دقائق فقط.
        </p>

        <p style="color:#9ca3af; font-size:14px;">
            إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذه الرسالة.
        </p>
    </div>
</body>
</html>
