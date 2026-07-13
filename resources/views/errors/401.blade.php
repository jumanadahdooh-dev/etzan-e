@include('errors.layout', [
    'code' => '401',
    'tone' => 'green',
    'visual' => 'lock',
    'badge' => 'غير مصرح',
    'title' => 'تحتاجين إلى تسجيل الدخول',
    'message' => 'لا يمكن الوصول إلى هذه الصفحة قبل تسجيل الدخول أو امتلاك صلاحية الدخول المناسبة.',
    'primaryText' => 'تسجيل الدخول',
    'primaryUrl' => url('/login'),
    'primaryIcon' => 'login',
])
