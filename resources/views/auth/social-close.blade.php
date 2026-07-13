<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>جارٍ تسجيل الدخول...</title>
</head>
<body>
    <script>
        const redirectTo = @json($redirectTo);

        if (window.opener) {
            window.opener.location.href = redirectTo;
            window.close();
        } else {
            window.location.href = redirectTo;
        }
    </script>
</body>
</html>
